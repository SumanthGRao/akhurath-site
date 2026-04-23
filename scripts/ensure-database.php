#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Idempotent MySQL fixes + optional import of legacy file-based customers into `users`.
 *
 * Requires config/database.local.php (same as the web app).
 *
 *   php scripts/ensure-database.php
 *   php scripts/ensure-database.php --migrate-customers-from-files
 *
 * Flags:
 *   --migrate-customers-from-files  Upsert data/customers.php (+ customer-emails.json) into users (role=customer).
 *   --patches-only                  Skip customer file import (default if no migrate flag).
 */

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';

$dbLocal = AKH_ROOT . '/config/database.local.php';
if (!is_file($dbLocal)) {
    fwrite(STDERR, "Missing config/database.local.php — copy config/database.local.example.php and set DSN/user/pass.\n");
    exit(1);
}

require_once $dbLocal;
require_once AKH_ROOT . '/includes/db.php';

$args = array_slice($argv, 1);
$migrateFiles = in_array('--migrate-customers-from-files', $args, true);
$patchesOnly = in_array('--patches-only', $args, true);
if ($patchesOnly) {
    $migrateFiles = false;
}

/**
 * @return non-empty-string|null
 */
function akh_ensure_db_current_schema(PDO $pdo): ?string
{
    $name = $pdo->query('SELECT DATABASE()');
    if ($name === false) {
        return null;
    }
    $db = $name->fetchColumn();
    if (!is_string($db) || $db === '') {
        return null;
    }

    return $db;
}

function akh_ensure_column_exists(PDO $pdo, string $schema, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$schema, $table, $column]);
    $n = $st->fetchColumn();

    return (int) $n >= 1;
}

function akh_ensure_index_exists(PDO $pdo, string $schema, string $table, string $indexName): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute([$schema, $table, $indexName]);
    $n = $st->fetchColumn();

    return (int) $n >= 1;
}

function akh_ensure_table_exists(PDO $pdo, string $schema, string $table): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $st->execute([$schema, $table]);
    $n = $st->fetchColumn();

    return (int) $n >= 1;
}

$pdo = akh_db();
$schema = akh_ensure_db_current_schema($pdo);
if ($schema === null) {
    fwrite(STDERR, "Could not read current database from the connection. Check AKH_DB_DSN includes dbname=...\n");
    exit(1);
}

echo "Using database: {$schema}\n";

if (!akh_ensure_table_exists($pdo, $schema, 'users')) {
    fwrite(STDERR, "Table `users` is missing. Import sql/schema.sql first (e.g. scripts/mysql-import-schema.sh).\n");
    exit(1);
}

if (!akh_ensure_column_exists($pdo, $schema, 'users', 'email')) {
    echo "Adding column users.email ...\n";
    $pdo->exec(
        'ALTER TABLE users ADD COLUMN email VARCHAR(120) NULL DEFAULT NULL AFTER password_hash'
    );
}

if (!akh_ensure_index_exists($pdo, $schema, 'users', 'ix_users_customer_email')) {
    echo "Adding index ix_users_customer_email ...\n";
    $pdo->exec('ALTER TABLE users ADD KEY ix_users_customer_email (role, email)');
}

if (akh_ensure_table_exists($pdo, $schema, 'contact_enquiries')
    && !akh_ensure_column_exists($pdo, $schema, 'contact_enquiries', 'email')) {
    echo "Adding column contact_enquiries.email ...\n";
    $pdo->exec(
        "ALTER TABLE contact_enquiries ADD COLUMN email VARCHAR(120) NOT NULL DEFAULT '' AFTER phone"
    );
}

echo "Schema patches are up to date.\n";

if (!$migrateFiles) {
    echo "Skipped file → DB customer import (pass --migrate-customers-from-files to run it).\n";
    exit(0);
}

$customersPath = AKH_ROOT . '/data/customers.php';
if (!is_file($customersPath)) {
    echo "No data/customers.php — nothing to import.\n";
    exit(0);
}

/** @var mixed $accounts */
$accounts = require $customersPath;
if (!is_array($accounts) || $accounts === []) {
    echo "data/customers.php is empty — nothing to import.\n";
    exit(0);
}

$emailPath = AKH_ROOT . '/data/customer-emails.json';
$emailMap = [];
if (is_file($emailPath)) {
    $raw = file_get_contents($emailPath);
    if ($raw !== false && $raw !== '') {
        $j = json_decode($raw, true);
        if (is_array($j)) {
            foreach ($j as $k => $v) {
                if (!is_string($k) || !is_string($v)) {
                    continue;
                }
                $ku = strtolower(trim($k));
                if ($ku !== '') {
                    $emailMap[$ku] = strtolower(trim($v));
                }
            }
        }
    }
}

$ins = $pdo->prepare(
    'INSERT INTO users (role, username, password_hash, email) VALUES (\'customer\', ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       password_hash = VALUES(password_hash),
       email = IFNULL(NULLIF(VALUES(email), \'\'), users.email)'
);

$imported = 0;
foreach ($accounts as $username => $hash) {
    if (!is_string($username) || !is_string($hash)) {
        continue;
    }
    $u = strtolower(trim($username));
    if ($u === '' || $hash === '') {
        continue;
    }
    $em = $emailMap[$u] ?? '';
    if ($em !== '' && !filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $em = '';
    }
    if (mb_strlen($em) > 120) {
        $em = '';
    }
    $ins->execute([$u, $hash, $em !== '' ? $em : null]);
    ++$imported;
}

echo "Imported/merged {$imported} customer row(s) from data/customers.php into users (role=customer).\n";
echo "You can archive or remove data/customers.php and data/customer-emails.json after verifying logins.\n";

#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Wipe application data in MySQL (users, tasks KV, notifications, contact form rows).
 * Restores app_kv to the same defaults as sql/schema.sql after import.
 *
 *   php scripts/reset-database.php --confirm
 *
 * Requires config/database.local.php. Use on local XAMPP or Hostinger only when you intend a full reset.
 */

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';

$dbLocal = AKH_ROOT . '/config/database.local.php';
if (!is_file($dbLocal)) {
    fwrite(STDERR, "Missing config/database.local.php\n");
    exit(1);
}

$args = array_slice($argv, 1);
if (!in_array('--confirm', $args, true)) {
    fwrite(STDERR, "Refusing to run without --confirm (this deletes all rows in users, app_kv, task_notification_events, contact_enquiries).\n");
    exit(1);
}

require_once $dbLocal;
require_once AKH_ROOT . '/includes/db.php';

function akh_reset_table_exists(PDO $pdo, string $schema, string $table): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $st->execute([$schema, $table]);

    return (int) $st->fetchColumn() >= 1;
}

$pdo = akh_db();
$dbRow = $pdo->query('SELECT DATABASE()');
if ($dbRow === false) {
    fwrite(STDERR, "Could not read current database.\n");
    exit(1);
}
$schema = $dbRow->fetchColumn();
if (!is_string($schema) || $schema === '') {
    fwrite(STDERR, "No database selected; check AKH_DB_DSN includes dbname=...\n");
    exit(1);
}

echo "Resetting database: {$schema}\n";

$truncateOrder = ['task_notification_events', 'contact_enquiries', 'users', 'app_kv'];
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($truncateOrder as $table) {
    if (!akh_reset_table_exists($pdo, $schema, $table)) {
        echo "Skip (missing table): {$table}\n";
        continue;
    }
    $pdo->exec('TRUNCATE TABLE `' . str_replace('`', '``', $table) . '`');
    echo "Truncated: {$table}\n";
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$rows = [
    ['tasks', '[]'],
    ['task_seq', '{"next":1}'],
    ['editor_seen_tasks', '{}'],
    ['editor_attendance', '{"events":[]}'],
    ['editor_leave', '{"requests":[]}'],
    ['admin_meta', '{"email":"","email_verified":false,"verify_token":null,"verify_expires_at":null}'],
];
$ins = $pdo->prepare('INSERT INTO app_kv (k, v) VALUES (?, ?)');
foreach ($rows as [$k, $v]) {
    $ins->execute([$k, $v]);
}

echo "Inserted default app_kv keys (" . count($rows) . ").\n";
echo "Done. Recreate admin/editor/customers (setup UI, phpMyAdmin, or seed scripts).\n";

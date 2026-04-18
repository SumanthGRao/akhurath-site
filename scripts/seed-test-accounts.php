#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Writes data/customers.php and data/editors.php with user "test" / password "test" (bcrypt).
 * Run from project root: php scripts/seed-test-accounts.php
 * Then set AKH_DEV_TEST_LOGIN to false in includes/config.php for production.
 */
$root = dirname(__DIR__);
$data = $root . '/data';
if (!is_dir($data)) {
    mkdir($data, 0755, true);
}

$hash = password_hash('test', PASSWORD_DEFAULT);
$export = static function (string $hash): string {
    return "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'test' => " . var_export($hash, true) . ",\n];\n";
};

file_put_contents($data . '/customers.php', $export($hash));
file_put_contents($data . '/editors.php', $export($hash));

echo "Wrote data/customers.php and data/editors.php (username: test, password: test).\n";
echo "Set AKH_DEV_TEST_LOGIN to false in includes/config.php when you rely on these files.\n";

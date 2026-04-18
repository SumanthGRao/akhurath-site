#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Usage: php scripts/hash-password.php 'YourPassword'
 * Paste the output into data/customers.php as the value for a username key.
 */
$pw = $argv[1] ?? '';
if ($pw === '') {
    fwrite(STDERR, "Usage: php scripts/hash-password.php 'YourPassword'\n");
    exit(1);
}

echo password_hash($pw, PASSWORD_DEFAULT), "\n";

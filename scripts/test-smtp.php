<?php

declare(strict_types=1);

/**
 * Send one test message via the same SMTP stack as the site (includes/smtp-mail.php).
 *
 * Usage (from project root, e.g. /Users/sumanth/Documents/cursor or XAMPP htdocs):
 *   php scripts/test-smtp.php your@email.com
 *
 * Or:
 *   TEST_SMTP_TO=your@email.com php scripts/test-smtp.php
 *
 * Prereqs in includes/config.php:
 *   AKH_SMTP_ENABLED = true
 *   AKH_SMTP_USER / AKH_SMTP_PASS (Zoho: often full mailbox + app-specific password)
 *   AKH_SMTP_FROM_EMAIL must be allowed for that mailbox
 */

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';
require_once $root . '/includes/smtp-mail.php';

$to = $argv[1] ?? (string) getenv('TEST_SMTP_TO');
$to = trim($to);
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/test-smtp.php you@example.com\n");
    fwrite(STDERR, "   or: TEST_SMTP_TO=you@example.com php scripts/test-smtp.php\n");
    exit(1);
}

if (!AKH_SMTP_ENABLED) {
    fwrite(STDERR, "AKH_SMTP_ENABLED is false. Set it true in includes/config.php and fill Zoho credentials.\n");
    exit(1);
}

$subject = 'Localhost SMTP test — ' . SITE_NAME;
$body = "If you receive this, SMTP from your PHP install works.\n\nTime (UTC): " . gmdate('c') . "\n";

$r = akh_smtp_send($to, $subject, $body);
if ($r['ok']) {
    echo "OK — sent test message to {$to}\n";
    exit(0);
}

echo "FAIL — {$r['error']}\n";
exit(2);

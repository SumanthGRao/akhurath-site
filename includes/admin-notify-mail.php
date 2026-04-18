<?php

declare(strict_types=1);

require_once __DIR__ . '/smtp-mail.php';

/**
 * @return array{ok: bool, error: string}
 */
function akh_admin_mail_verify(string $absoluteVerifyUrl): array
{
    $m = akh_admin_meta();
    $to = trim((string) ($m['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'No valid email on file.'];
    }
    $subject = 'Verify your email — ' . SITE_NAME;
    $body = "Open this link to verify your admin console email:\n\n{$absoluteVerifyUrl}\n\nIf you did not request this, ignore this message.\n";

    return akh_smtp_send($to, $subject, $body);
}

/**
 * @return array{ok: bool, error: string}
 */
function akh_admin_mail_password_changed(string $username): array
{
    $m = akh_admin_meta();
    $to = trim((string) ($m['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'No email on file.'];
    }
    $subject = 'Admin password changed — ' . SITE_NAME;
    $body = "The password for admin user \"{$username}\" was just changed on " . SITE_NAME . ".\n\nIf this was not you, restore access from a backup or your hosting panel.\n";

    return akh_smtp_send($to, $subject, $body);
}

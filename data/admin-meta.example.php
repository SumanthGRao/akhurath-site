<?php

declare(strict_types=1);

/**
 * Live file: data/admin-meta.php (created by first-time admin setup or when you
 * save notification email in Admin → Account). Gitignored except this example.
 *
 * @return array{email: string, email_verified: bool, verify_token: ?string, verify_expires_at: ?int}
 */
return [
    'email' => 'admin@example.com',
    'email_verified' => false,
    'verify_token' => null,
    'verify_expires_at' => null,
];

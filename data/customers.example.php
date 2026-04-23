<?php

declare(strict_types=1);

/**
 * CUSTOMER ACCOUNTS (manual)
 * --------------------------
 * 1. Copy this file to:  data/customers.php
 * 2. Generate a password hash on your machine:
 *        php scripts/hash-password.php 'YourSecurePassword'
 * 3. Add one line per customer login (slug you choose — give this to the client):
 *
 *    return [
 *        'client_anya' => '$2y$10$........................................',
 *    ];
 *
 * After login, clients use the dashboard to create tasks and see status.
 *
 * Self-registration: when AKH_ALLOW_CLIENT_REGISTRATION is true in includes/config.php,
 * new rows are appended here by /customer/register.php (lowercase usernames).
 * Registration also stores each client’s contact email in data/customer-emails.json
 * (not committed) for task and account notifications when SMTP is enabled.
 *
 * The website login checks ONLY this file. Your NAS (drive.akhurathstudio.com)
 * must have a matching user if you want the same username/password everywhere —
 * otherwise clients use this site to reach the portal, then sign in again on
 * the drive with the credentials you create on the NAS.
 *
 * Do not commit data/customers.php to git.
 */
return [];

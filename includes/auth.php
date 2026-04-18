<?php

declare(strict_types=1);

/**
 * @return array<string, string> username => password_hash
 */
function akh_customer_accounts_path(): string
{
    return AKH_ROOT . '/data/customers.php';
}

function akh_customer_accounts(): array
{
    $path = akh_customer_accounts_path();
    if (!is_file($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

/**
 * Persist the full customer map (username => bcrypt hash). Usernames should be normalized (e.g. lowercase).
 *
 * @param array<string, string> $accounts
 */
function akh_customer_write_accounts_file(array $accounts): bool
{
    $path = akh_customer_accounts_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $body = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($accounts, true) . ";\n";

    return @file_put_contents($path, $body, LOCK_EX) !== false;
}

/**
 * Register a new client account. Returns null on success, or an error message.
 */
function akh_customer_register(string $username, string $password, string $passwordConfirm): ?string
{
    if (!AKH_ALLOW_CLIENT_REGISTRATION) {
        return 'Registration is closed. Please contact the studio.';
    }

    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z][a-z0-9_]{2,31}$/', $username)) {
        return 'Username must be 3–32 characters: start with a letter, then letters, numbers, or underscores.';
    }

    $reserved = ['admin', 'root', 'system', 'editor', 'staff', 'akhurath', 'support', 'postmaster', 'webmaster', 'mailer-daemon'];
    if (in_array($username, $reserved, true)) {
        return 'That username is reserved. Please choose another.';
    }

    if (mb_strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (mb_strlen($password) > 128) {
        return 'Password is too long.';
    }
    if ($password !== $passwordConfirm) {
        return 'Passwords do not match.';
    }

    $lockPath = AKH_ROOT . '/data/.customers-register.lock';
    $lockFp = fopen($lockPath, 'c');
    if ($lockFp === false) {
        return 'Could not start registration. Try again shortly.';
    }
    if (!flock($lockFp, LOCK_EX)) {
        fclose($lockFp);

        return 'Could not start registration. Try again shortly.';
    }

    try {
        $accounts = akh_customer_accounts();
        if (isset($accounts[$username])) {
            return 'That username is already taken.';
        }
        $accounts[$username] = password_hash($password, PASSWORD_DEFAULT);
        if (!akh_customer_write_accounts_file($accounts)) {
            return 'Could not save your account. Check server permissions on the data/ folder.';
        }
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }

    return null;
}

/**
 * Create a client account (admin console). Same rules as registration, but ignores AKH_ALLOW_CLIENT_REGISTRATION.
 *
 * @return string|null error or null on success
 */
function akh_customer_admin_add(string $username, string $password, string $passwordConfirm): ?string
{
    $username = strtolower(trim($username));
    if (!preg_match('/^[a-z][a-z0-9_]{2,31}$/', $username)) {
        return 'Username must be 3–32 characters: start with a letter, then letters, numbers, or underscores.';
    }

    $reserved = ['admin', 'root', 'system', 'editor', 'staff', 'akhurath', 'support', 'postmaster', 'webmaster', 'mailer-daemon'];
    if (in_array($username, $reserved, true)) {
        return 'That username is reserved. Please choose another.';
    }

    if (mb_strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (mb_strlen($password) > 128) {
        return 'Password is too long.';
    }
    if ($password !== $passwordConfirm) {
        return 'Passwords do not match.';
    }

    $lockPath = AKH_ROOT . '/data/.customers-register.lock';
    $lockFp = fopen($lockPath, 'c');
    if ($lockFp === false) {
        return 'Could not start. Try again shortly.';
    }
    if (!flock($lockFp, LOCK_EX)) {
        fclose($lockFp);

        return 'Could not start. Try again shortly.';
    }

    try {
        $accounts = akh_customer_accounts();
        if (isset($accounts[$username])) {
            return 'That username already exists.';
        }
        $accounts[$username] = password_hash($password, PASSWORD_DEFAULT);
        if (!akh_customer_write_accounts_file($accounts)) {
            return 'Could not save accounts. Check data/ permissions.';
        }
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }

    return null;
}

function akh_customer_delete(string $username): bool
{
    $key = strtolower(trim($username));
    if ($key === '') {
        return false;
    }

    $lockPath = AKH_ROOT . '/data/.customers-register.lock';
    $lockFp = fopen($lockPath, 'c');
    if ($lockFp === false || !flock($lockFp, LOCK_EX)) {
        if ($lockFp !== false) {
            fclose($lockFp);
        }

        return false;
    }
    try {
        $accounts = akh_customer_accounts();
        unset($accounts[$key]);
        if (!akh_customer_write_accounts_file($accounts)) {
            return false;
        }
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }

    return true;
}

function akh_customer_current(): ?string
{
    $u = $_SESSION['akh_customer'] ?? null;

    return is_string($u) && $u !== '' ? $u : null;
}

function akh_customer_login(string $username, string $password): bool
{
    if (AKH_DEV_TEST_LOGIN && $username === 'test' && $password === 'test') {
        session_regenerate_id(true);
        $_SESSION['akh_customer'] = 'test';

        return true;
    }

    $accounts = akh_customer_accounts();
    $key = strtolower(trim($username));
    if (!isset($accounts[$key])) {
        return false;
    }

    $hash = $accounts[$key];
    if (!is_string($hash) || !password_verify($password, $hash)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['akh_customer'] = $key;

    return true;
}

function akh_customer_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function akh_require_customer(): void
{
    if (akh_customer_current() === null) {
        header('Location: ' . base_path('customer/login.php'));
        exit;
    }
}

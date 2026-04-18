<?php

declare(strict_types=1);

/**
 * @return array<string, string> lowercase username => password_hash
 */
function akh_admin_accounts(): array
{
    $path = AKH_ROOT . '/data/admins.php';
    if (!is_file($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

function akh_admin_current(): ?string
{
    $u = $_SESSION['akh_admin_user'] ?? null;

    return is_string($u) && $u !== '' ? $u : null;
}

/** True when the HTTP client appears to be the same machine (PHP built-in server, local Apache, etc.). */
function akh_admin_request_is_loopback(): bool
{
    $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return $addr === '127.0.0.1' || $addr === '::1';
}

/**
 * When true, admin accepts username/password `test`/`test` without data/admins.php (UI / local dev).
 * Enabled if: AKH_ADMIN_DEV_TEST_LOGIN, or AKH_DEV_TEST_LOGIN, or loopback with no admin accounts yet.
 */
function akh_admin_dev_test_login_allowed(): bool
{
    if (AKH_ADMIN_DEV_TEST_LOGIN || AKH_DEV_TEST_LOGIN) {
        return true;
    }

    return akh_admin_request_is_loopback() && akh_admin_accounts() === [];
}

function akh_admin_login(string $username, string $password): bool
{
    $key = strtolower(trim($username));
    if ($key === '') {
        return false;
    }

    if (akh_admin_dev_test_login_allowed() && $key === 'test' && $password === 'test') {
        session_regenerate_id(true);
        $_SESSION['akh_admin_user'] = 'test';

        return true;
    }

    $accounts = akh_admin_accounts();
    if ($accounts !== []) {
        if (!isset($accounts[$key])) {
            return false;
        }
        $hash = $accounts[$key];
        if (!is_string($hash) || !password_verify($password, $hash)) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['akh_admin_user'] = $key;

        return true;
    }

    return false;
}

function akh_admin_logout(): void
{
    unset($_SESSION['akh_admin_user']);
}

function akh_require_admin(): void
{
    if (akh_admin_current() === null) {
        header('Location: ' . base_path('admin/login.php'));
        exit;
    }
}

/**
 * @param array<string, string> $accounts lowercase username => password_hash
 */
function akh_admin_save_accounts(array $accounts): bool
{
    $target = AKH_ROOT . '/data/admins.php';
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    ksort($accounts);
    $body = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($accounts, true) . ";\n";
    $tmp = $target . '.tmp';
    if (file_put_contents($tmp, $body) === false) {
        return false;
    }
    if (!@rename($tmp, $target)) {
        @unlink($tmp);

        return false;
    }

    return true;
}

function akh_admin_update_password_hash(string $username, string $newHash): bool
{
    $key = strtolower(trim($username));
    if ($key === '' || $newHash === '') {
        return false;
    }
    $accounts = akh_admin_accounts();
    if (!isset($accounts[$key])) {
        return false;
    }
    $accounts[$key] = $newHash;

    return akh_admin_save_accounts($accounts);
}

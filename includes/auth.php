<?php

declare(strict_types=1);

require_once __DIR__ . '/customer-email-store.php';

/**
 * @return array<string, string> username => password_hash
 */
function akh_customer_accounts_path(): string
{
    return AKH_ROOT . '/data/customers.php';
}

function akh_customer_accounts(): array
{
    if (akh_customer_storage_is_database()) {
        $st = akh_db()->query("SELECT username, password_hash FROM users WHERE role = 'customer' ORDER BY username");
        if ($st === false) {
            return [];
        }
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $u = strtolower(trim((string) ($row['username'] ?? '')));
            $h = (string) ($row['password_hash'] ?? '');
            if ($u !== '' && $h !== '') {
                $out[$u] = $h;
            }
        }

        return $out;
    }

    $path = akh_customer_accounts_path();
    if (!is_file($path)) {
        return [];
    }
    $data = require $path;

    return is_array($data) ? $data : [];
}

/**
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
function akh_customer_register(string $username, string $email, string $password, string $passwordConfirm): ?string
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

    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        return 'Please enter a valid email address for confirmations and task updates.';
    }

    if (akh_customer_storage_is_database()) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return 'Could not hash password.';
        }
        try {
            $st = akh_db()->prepare(
                'INSERT INTO users (role, username, password_hash, email) VALUES (?, ?, ?, ?)'
            );
            $st->execute(['customer', $username, $hash, $email]);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate') || str_contains($msg, '1062')) {
                return 'That username is already taken.';
            }

            return 'Could not save your account. Check the database.';
        }

        return null;
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
        if (!akh_customer_email_set($username, $email)) {
            unset($accounts[$username]);
            akh_customer_write_accounts_file($accounts);

            return 'Could not save your contact email. Try again.';
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
function akh_customer_admin_add(string $username, string $password, string $passwordConfirm, string $contactEmail = ''): ?string
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

    $ce = strtolower(trim($contactEmail));
    if ($ce !== '' && (!filter_var($ce, FILTER_VALIDATE_EMAIL) || mb_strlen($ce) > 120)) {
        return 'Invalid contact email.';
    }

    if (akh_customer_storage_is_database()) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return 'Could not hash password.';
        }
        $em = $ce !== '' ? $ce : null;
        try {
            $st = akh_db()->prepare(
                'INSERT INTO users (role, username, password_hash, email) VALUES (?, ?, ?, ?)'
            );
            $st->execute(['customer', $username, $hash, $em]);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate') || str_contains($msg, '1062')) {
                return 'That username already exists.';
            }

            return 'Could not save account. Check the database.';
        }

        return null;
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
        if ($ce !== '') {
            if (!akh_customer_email_set($username, $ce)) {
                unset($accounts[$username]);
                akh_customer_write_accounts_file($accounts);

                return 'Could not save contact email.';
            }
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

    if (akh_customer_storage_is_database()) {
        $st = akh_db()->prepare('DELETE FROM users WHERE role = ? AND username = ?');
        $st->execute(['customer', $key]);

        return $st->rowCount() >= 1;
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
        akh_customer_email_delete($key);
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

    $key = strtolower(trim($username));
    if ($key === '') {
        return false;
    }

    if (akh_customer_storage_is_database()) {
        $st = akh_db()->prepare('SELECT password_hash FROM users WHERE role = ? AND username = ? LIMIT 1');
        $st->execute(['customer', $key]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }
        $hash = (string) ($row['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['akh_customer'] = $key;

        return true;
    }

    $accounts = akh_customer_accounts();
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

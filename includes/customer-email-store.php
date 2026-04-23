<?php

declare(strict_types=1);

/**
 * Client login username → contact email (JSON). Used for task and account notifications.
 *
 * @return array<string, string>
 */
function akh_customer_email_map_load(): array
{
    $path = AKH_ROOT . '/data/customer-emails.json';
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        return [];
    }
    $out = [];
    foreach ($j as $k => $v) {
        if (!is_string($k) || !is_string($v)) {
            continue;
        }
        $k = strtolower(trim($k));
        if ($k === '' || $v === '') {
            continue;
        }
        $out[$k] = strtolower(trim($v));
    }

    return $out;
}

/**
 * @param array<string, string> $map
 */
function akh_customer_email_map_save(array $map): bool
{
    $path = AKH_ROOT . '/data/customer-emails.json';
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    try {
        $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return false;
    }

    return @file_put_contents($path, $json, LOCK_EX) !== false;
}

function akh_customer_email_get(string $username): ?string
{
    $u = strtolower(trim($username));
    if ($u === '') {
        return null;
    }
    $map = akh_customer_email_map_load();
    $e = $map[$u] ?? '';

    return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
}

function akh_customer_email_set(string $username, string $email): bool
{
    $u = strtolower(trim($username));
    $email = strtolower(trim($email));
    if ($u === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        return false;
    }
    $map = akh_customer_email_map_load();
    $map[$u] = $email;

    return akh_customer_email_map_save($map);
}

function akh_customer_email_delete(string $username): bool
{
    $u = strtolower(trim($username));
    if ($u === '') {
        return false;
    }
    $map = akh_customer_email_map_load();
    if (!isset($map[$u])) {
        return true;
    }
    unset($map[$u]);

    return akh_customer_email_map_save($map);
}

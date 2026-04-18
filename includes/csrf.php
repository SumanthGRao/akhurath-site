<?php

declare(strict_types=1);

function akh_csrf_token(): string
{
    if (empty($_SESSION['akh_csrf']) || !is_string($_SESSION['akh_csrf'])) {
        $_SESSION['akh_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['akh_csrf'];
}

function akh_csrf_verify(?string $token): bool
{
    $t = (string) ($token ?? '');
    $s = (string) ($_SESSION['akh_csrf'] ?? '');

    return $t !== '' && $s !== '' && hash_equals($s, $t);
}

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/admin-auth.php';

akh_admin_logout();

header('Location: ' . base_path('admin/login.php'));
exit;

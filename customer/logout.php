<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/auth.php';

akh_customer_logout();

header('Location: ' . base_path('customer/login.php'));
exit;

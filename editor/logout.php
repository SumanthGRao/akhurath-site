<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/editor-auth.php';

akh_editor_logout();

header('Location: ' . base_path('editor/login.php'));
exit;

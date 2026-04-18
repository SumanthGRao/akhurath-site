<?php

declare(strict_types=1);

/** @var string $adminNavActive */

$items = [
    'index.php' => 'Overview',
    'tasks.php' => 'Tasks',
];
$base = base_path('admin/');
?>
<nav class="admin-nav" aria-label="Admin console">
  <?php foreach ($items as $file => $label): ?>
    <?php
    $href = $base . $file;
    $isActive = ($adminNavActive ?? '') === $file;
    ?>
    <a class="admin-nav__link<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo h($href); ?>"><?php echo h($label); ?></a>
  <?php endforeach; ?>
</nav>

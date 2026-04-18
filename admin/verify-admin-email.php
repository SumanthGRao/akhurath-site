<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/admin-meta.php';

$pageTitle = 'Verify admin email — ' . SITE_NAME;
$bodyClass = 'page-portal';

$token = trim((string) ($_GET['token'] ?? ''));
$ok = $token !== '' && akh_admin_meta_verify_token($token);

require_once AKH_ROOT . '/includes/header.php';
?>

  <main id="main" class="portal-main">
    <div class="portal-card">
      <h1 class="portal-title">Email verification</h1>
      <?php if ($ok): ?>
        <p class="banner banner--ok" role="status">Your admin email is verified.</p>
        <p class="portal-foot"><a class="text-link" href="<?php echo h(base_path('admin/login.php')); ?>">→ Admin login</a></p>
      <?php else: ?>
        <p class="banner banner--err" role="alert">This link is invalid or has expired. Sign in and use <strong>Account</strong> to resend verification.</p>
        <p class="portal-foot"><a class="text-link" href="<?php echo h(base_path('admin/login.php')); ?>">← Admin login</a></p>
      <?php endif; ?>
    </div>
  </main>

<?php require_once AKH_ROOT . '/includes/footer.php'; ?>

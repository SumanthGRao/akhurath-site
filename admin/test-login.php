<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/admin-auth.php';

if (!akh_admin_dev_test_login_allowed()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Not found</title></head><body><p>Not found.</p></body></html>';
    exit;
}

$pageTitle = 'Admin test login — ' . SITE_NAME;
$metaDescription = 'Temporary UI login for the admin console.';
$bodyClass = 'page-portal';

$error = '';

if (akh_admin_current() !== null) {
    header('Location: ' . base_path('admin/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (akh_admin_login($user, $pass)) {
        header('Location: ' . base_path('admin/index.php'));
        exit;
    }
    $error = 'Use username “test” and password “test” on this page only.';
}

require_once AKH_ROOT . '/includes/header.php';
?>

  <main id="main" class="portal-main">
    <div class="portal-card">
      <h1 class="portal-title">Admin test login</h1>
      <p class="portal-lead">UI-only sign-in — use <strong>test</strong> / <strong>test</strong>. Allowed when <code>AKH_ADMIN_DEV_TEST_LOGIN</code> or <code>AKH_DEV_TEST_LOGIN</code> is on, or when you have <strong>no</strong> <code>data/admins.php</code> accounts and browse from this machine (<code>127.0.0.1</code> / <code>::1</code>).</p>
      <p class="banner banner--info" role="status">If this page 404s, you are not on loopback without admins — set <code>AKH_ADMIN_DEV_TEST_LOGIN</code> to <code>true</code> in <code>includes/config.php</code> (e.g. Docker or LAN IP).</p>

      <?php if ($error !== ''): ?>
        <p class="banner banner--err" role="alert"><?php echo h($error); ?></p>
      <?php endif; ?>

      <form class="portal-form" method="post" action="" autocomplete="off">
        <label class="field">
          <span>Username</span>
          <input type="text" name="username" value="test" required autocomplete="username" maxlength="120" />
        </label>
        <label class="field">
          <span>Password</span>
          <input type="password" name="password" value="test" required autocomplete="current-password" maxlength="200" />
        </label>
        <button type="submit" class="btn btn--primary btn--block">Sign in (test)</button>
      </form>
      <p class="portal-foot">
        <a class="text-link" href="<?php echo h(base_path('admin/login.php')); ?>">Real admin login</a>
        ·
        <a class="text-link" href="<?php echo h(base_path('index.php')); ?>">Website home</a>
      </p>
    </div>
  </main>

<?php require_once AKH_ROOT . '/includes/footer.php'; ?>

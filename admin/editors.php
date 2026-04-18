<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once AKH_ROOT . '/includes/admin-auth.php';
require_once AKH_ROOT . '/includes/editor-auth.php';
require_once AKH_ROOT . '/includes/tasks.php';
require_once AKH_ROOT . '/includes/csrf.php';

akh_require_admin();

$pageTitle = 'Admin — Editors — ' . SITE_NAME;
$bodyClass = 'page-portal admin-page admin-page--board';
$adminNavActive = 'tasks.php';
$adminConsoleActive = 'editors';

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!akh_csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Security check failed. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'delete_editor') {
            $u = strtolower(trim((string) ($_POST['username'] ?? '')));
            if ($u === '') {
                $error = 'Missing username.';
            } elseif (!akh_editor_delete($u)) {
                $error = 'Could not delete that editor.';
            } else {
                $flash = 'Editor account removed.';
            }
        } elseif ($action === 'add_editor') {
            $u = trim((string) ($_POST['new_username'] ?? ''));
            $p = (string) ($_POST['new_password'] ?? '');
            $p2 = (string) ($_POST['new_password_confirm'] ?? '');
            $err = akh_editor_add($u, $p, $p2);
            if ($err !== null) {
                $error = $err;
            } else {
                $flash = 'Editor account created.';
            }
        } else {
            $error = 'Unknown action.';
        }
    }
}

$accounts = akh_editor_accounts();
ksort($accounts, SORT_STRING);

require_once AKH_ROOT . '/includes/header.php';
?>

  <main id="main" class="portal-main portal-main--board">
    <div class="portal-card portal-card--tasks admin-shell admin-console">
      <header class="admin-head">
        <div>
          <h1 class="portal-title">Editors</h1>
          <p class="portal-lead admin-head__meta">Create or remove editor logins.</p>
        </div>
        <div class="admin-head__actions">
          <?php require __DIR__ . '/includes/admin-console-sidebar.php'; ?>
          <a class="btn btn--ghost btn--sm" href="<?php echo h(base_path('admin/logout.php')); ?>">Sign out</a>
        </div>
      </header>

      <?php require AKH_ROOT . '/includes/admin-nav.php'; ?>

      <?php if ($flash !== ''): ?>
        <p class="banner banner--ok" role="status"><?php echo h($flash); ?></p>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <p class="banner banner--err" role="alert"><?php echo h($error); ?></p>
      <?php endif; ?>

      <section class="portal-section" aria-labelledby="add-editor-h">
        <h2 id="add-editor-h" class="portal-section__title">Add editor</h2>
        <form method="post" action="" class="portal-form">
          <input type="hidden" name="csrf_token" value="<?php echo h(akh_csrf_token()); ?>" />
          <input type="hidden" name="action" value="add_editor" />
          <div class="admin-form-row">
            <label class="field">
              <span>Username</span>
              <input type="text" name="new_username" required maxlength="32" pattern="[a-z][a-z0-9_]{2,31}" autocomplete="off" />
            </label>
            <label class="field">
              <span>Password</span>
              <input type="password" name="new_password" required minlength="8" maxlength="128" autocomplete="new-password" />
            </label>
            <label class="field">
              <span>Confirm</span>
              <input type="password" name="new_password_confirm" required minlength="8" maxlength="128" autocomplete="new-password" />
            </label>
            <button type="submit" class="btn btn--primary">Create</button>
          </div>
        </form>
      </section>

      <section class="portal-section" aria-labelledby="list-editors-h">
        <h2 id="list-editors-h" class="portal-section__title">All editors (<?php echo count($accounts); ?>)</h2>
        <?php if ($accounts === []): ?>
          <p class="portal-muted">No editor accounts yet.</p>
        <?php else: ?>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th scope="col">Username</th>
                  <th scope="col">Assigned tasks</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($accounts as $uname => $_hash): ?>
                  <?php $tc = akh_task_count_for_editor((string) $uname); ?>
                  <tr>
                    <td class="admin-table__mono"><?php echo h((string) $uname); ?></td>
                    <td><?php echo (int) $tc; ?></td>
                    <td>
                      <form method="post" action="" onsubmit="return confirm('Delete editor <?php echo h((string) $uname); ?>?');">
                        <input type="hidden" name="csrf_token" value="<?php echo h(akh_csrf_token()); ?>" />
                        <input type="hidden" name="action" value="delete_editor" />
                        <input type="hidden" name="username" value="<?php echo h((string) $uname); ?>" />
                        <button type="submit" class="btn btn--ghost btn--sm">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>

<?php require_once AKH_ROOT . '/includes/footer.php'; ?>

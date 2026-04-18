<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? SITE_NAME;
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="description" content="<?php echo h($metaDescription ?? SITE_TAGLINE); ?>" />
  <title><?php echo h($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <?php
    $akhCssPath = defined('AKH_ROOT') ? AKH_ROOT . '/assets/css/site.css' : '';
    $akhCssVer = ($akhCssPath !== '' && is_file($akhCssPath)) ? (string) filemtime($akhCssPath) : '';
    $akhCssHref = base_path('assets/css/site.css') . ($akhCssVer !== '' ? '?v=' . rawurlencode($akhCssVer) : '');
  ?>
  <link rel="stylesheet" href="<?php echo h($akhCssHref); ?>" />
</head>
<body class="<?php echo h($bodyClass); ?>">
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="site-header__inner">
      <a class="site-logo" href="<?php echo h(base_path('index.php')); ?>"><?php echo h(SITE_NAME); ?></a>
      <nav class="site-nav" id="site-nav" aria-label="Primary">
        <div class="site-menu site-menu--hub" data-site-menu>
          <div class="site-menu__row">
            <nav class="site-menu__slide" aria-label="Site sections">
              <ul class="site-menu__list site-menu__list--slide">
                <li><a href="<?php echo h(base_path('index.php')); ?>#about">About</a></li>
                <li><a href="<?php echo h(base_path('index.php')); ?>#services">Services</a></li>
                <li><a href="<?php echo h(base_path('index.php')); ?>#work">Work</a></li>
                <li><a href="<?php echo h(base_path('index.php')); ?>#clients">Clients</a></li>
                <li><a href="<?php echo h(base_path('contact.php')); ?>">Get in touch</a></li>
              </ul>
            </nav>
            <div class="site-menu__hubcol">
              <div class="site-menu__hubtop">
                <button
                  type="button"
                  class="site-menu__trigger"
                  id="site-menu-trigger"
                  aria-expanded="false"
                  aria-haspopup="true"
                  aria-controls="site-menu-dropdown"
                  aria-label="Open site menu"
                >
                  <span class="visually-hidden">Site menu</span>
                  <span class="site-menu__trigger-label" aria-hidden="true">Menu</span>
                  <span class="site-menu__trigger-bars" aria-hidden="true">
                    <span class="site-menu__trigger-bar"></span>
                    <span class="site-menu__trigger-bar"></span>
                    <span class="site-menu__trigger-bar"></span>
                  </span>
                </button>
              </div>
              <div class="site-menu__panel site-menu__dropdown" id="site-menu-dropdown" role="region" aria-label="Account">
                <ul class="site-menu__list site-menu__list--dropdown site-menu__list--auth">
                  <?php if (defined('AKH_ALLOW_CLIENT_REGISTRATION') && AKH_ALLOW_CLIENT_REGISTRATION): ?>
                    <li class="site-menu__dd-item">
                      <a class="site-menu__dd-link site-menu__link--muted" href="<?php echo h(base_path('customer/register.php')); ?>">Register</a>
                    </li>
                  <?php endif; ?>
                  <li class="site-menu__dd-item site-menu__dd-item--login">
                    <button
                      type="button"
                      class="site-menu__login-trigger"
                      id="site-login-trigger"
                      aria-expanded="false"
                      aria-haspopup="true"
                      aria-controls="site-login-panel"
                    >
                      <span class="site-menu__login-trigger-text">Login</span>
                      <span class="site-menu__login-chevron" aria-hidden="true"></span>
                    </button>
                    <div id="site-login-panel" class="site-login-panel" role="group" aria-label="Sign in as" aria-hidden="true">
                      <div class="site-login-panel__inner">
                        <a class="site-login-panel__link site-login-panel__link--client" href="<?php echo h(base_path('customer/login.php')); ?>">Client</a>
                        <a class="site-login-panel__link site-login-panel__link--editor" href="<?php echo h(base_path('editor/login.php')); ?>">Editor</a>
                        <a class="site-login-panel__link site-login-panel__link--admin" href="<?php echo h(base_path('admin/login.php')); ?>">Admin</a>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>
  </header>

<?php
require __DIR__ . '/_guard.php';

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
if (!isset($admin_title) || trim($admin_title) === '') $admin_title = 'Admin';

/**
 * Logo admin:
 * - admin_logo_path (opsional)
 * - fallback logo_path (publik)
 */
$rawLogo = setting('admin_logo_path', null);
if (empty($rawLogo)) $rawLogo = setting('logo_path', null);

$logoUrl = $rawLogo ? abs_url($rawLogo) : null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($admin_title) ?> — Admin <?= e($siteName) ?></title>

  <!-- Publik dulu, lalu admin override -->
  <link rel="stylesheet" href="<?= e(site_url('css/style.css')) ?>" />
  <link rel="stylesheet" href="<?= e(site_url('css/admin.css')) ?>" />
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(site_url('Assets/logo.png')) ?>">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
</head>
<body>
  <a class="skip-link" href="#main">Lewati ke konten</a>

  <header class="site-header admin-topbar">
    <div class="container header-inner">

      <a class="brand" href="<?= e(admin_url('')) ?>" aria-label="Admin <?= e($siteName) ?>">
        <?php if (!empty($logoUrl)): ?>
          <img class="brand-logo-img" src="<?= e($logoUrl) ?>" alt="Logo <?= e($siteName) ?>" />
        <?php else: ?>
          <span class="brand-logo-fallback" aria-hidden="true">RM</span>
        <?php endif; ?>

        <span class="brand-info">
          <span class="brand-name"><?= e($siteName) ?></span>
          <span class="brand-tagline">Admin Panel</span>
        </span>
      </a>

      <div class="admin-topbar-actions">
        <!-- Toggle Sidebar (mobile) -->
        <button
          class="admin-sidebar-toggle"
          type="button"
          aria-label="Buka navigasi admin"
          aria-controls="adminSidebar"
          aria-expanded="false"
        >
          Navigasi
        </button>

        <!-- Hamburger header menu (mobile) -->
        <button
          class="nav-toggle"
          type="button"
          aria-label="Buka menu"
          aria-controls="adminNav"
          aria-expanded="false"
        >
          <span class="nav-toggle-bars" aria-hidden="true"></span>
        </button>
      </div>

      <!-- Menu header (desktop tampil inline; mobile jadi dropdown via body.nav-open) -->
      <nav class="nav" id="adminNav" aria-label="Menu Admin">
        <a href="<?= e(site_url('')) ?>" target="_blank" rel="noopener">Lihat Website</a>
        <a href="<?= e(admin_url('logout')) ?>">Logout</a>
      </nav>

    </div>
  </header>

  <!-- Overlay untuk drawer sidebar & dropdown nav -->
  <div class="admin-overlay" hidden></div>

  <main id="main" class="container main">
    <?php if (is_editor() && is_frozen()): ?>
      <div class="admin-alert" style="margin-bottom:12px">
        Akun editor sedang dibekukan. Listing Anda tidak tampil di website publik.
      </div>
    <?php endif; ?>

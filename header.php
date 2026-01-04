<?php
// header.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

if (!isset($page_title) || trim($page_title) === '') {
  $page_title = 'RM Properti';
}

$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
$tagline  = 'Listing elegan & kontak sales';
$logoPath = setting('logo_path', null);
$logoUrl = $logoPath ? abs_url($logoPath) : null;

if (!isset($page_description) || trim($page_description) === '') {
  $page_description = $siteName . ' - ' . $tagline;
}
if (!isset($page_robots) || trim($page_robots) === '') {
  $page_robots = 'index, follow';
}
if (!isset($page_canonical) || trim($page_canonical) === '') {
  $page_canonical = current_url();
}

// Deteksi halaman aktif untuk nav
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$currentKey = trim($currentPath, '/');
if ($currentKey === '') {
  $currentKey = 'home';
} else {
  $currentKey = basename($currentKey);
}

function nav_link(string $href, string $label, string $currentKey): string {
  $path = parse_url($href, PHP_URL_PATH) ?? '';
  $key = trim($path, '/');
  $key = ($key === '') ? 'home' : basename($key);
  $isActive = ($key === $currentKey);
  $cls = $isActive ? 'active' : '';
  $aria = $isActive ? ' aria-current="page"' : '';
  return '<a href="'.e($href).'" class="'.e($cls).'"'.$aria.'>'.e($label).'</a>';
}

// Title tab browser
$fullTitle = ($page_title ? ($page_title . ' - ' . $siteName) : $siteName);
$canonicalUrl = abs_url($page_canonical);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($page_description) ?>" />
  <meta name="robots" content="<?= e($page_robots) ?>" />
  <link rel="canonical" href="<?= e($canonicalUrl) ?>" />
  <link rel="stylesheet" href="<?= e(site_url('css/style.css')) ?>" />
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="<?= e(site_url('Assets/logo.png')) ?>">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">

</head>
<body>

  <a class="skip-link" href="#main">Lewati ke konten</a>

  <header class="site-header">
    <div class="container header-inner">

      <a class="brand" href="<?= e(site_url('')) ?>" aria-label="<?= e($siteName) ?>">
        <?php if (!empty($logoUrl)): ?>
          <img class="brand-logo-img" src="<?= e($logoUrl) ?>" alt="Logo <?= e($siteName) ?>" />
        <?php else: ?>
          <div class="brand-logo-fallback" aria-hidden="true">RM</div>
        <?php endif; ?>

        <div class="brand-info">
          <span class="brand-name"><?= e($siteName) ?></span>
          <span class="brand-tagline"><?= e($tagline) ?></span>
        </div>
      </a>

      <!-- Toggle menu mobile -->
      <button class="nav-toggle" type="button"
              aria-label="Buka menu"
              aria-controls="primary-nav"
              aria-expanded="false">
        <span class="nav-toggle-bars" aria-hidden="true"></span>
      </button>

      <nav id="primary-nav" class="nav" aria-label="Navigasi utama">
        <?= nav_link(site_url(''), 'Home', $currentKey) ?>
        <?= nav_link(site_url('about'), 'About', $currentKey) ?>
        <?= nav_link(site_url('contact'), 'Contact', $currentKey) ?>
      </nav>

    </div>
  </header>

  <!-- PENTING: main TANPA container, supaya HERO bisa full width -->
  <main id="main" class="main">

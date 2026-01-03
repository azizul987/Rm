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

// Deteksi halaman aktif untuk nav
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if ($current === '' || $current === '/') $current = 'index.php';

function nav_link($href, $label, $current) {
  $isActive = ($href === $current);
  $cls = $isActive ? 'active' : '';
  $aria = $isActive ? ' aria-current="page"' : '';
  return '<a href="'.e($href).'" class="'.e($cls).'"'.$aria.'>'.e($label).'</a>';
}

// Title tab browser
$fullTitle = ($page_title ? ($page_title . ' - ' . $siteName) : $siteName);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($fullTitle) ?></title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <a class="skip-link" href="#main">Lewati ke konten</a>

  <header class="site-header">
    <div class="container header-inner">

      <a class="brand" href="index.php" aria-label="<?= e($siteName) ?>">
        <?php if (!empty($logoPath)): ?>
          <img class="brand-logo-img" src="<?= e($logoPath) ?>" alt="Logo <?= e($siteName) ?>" />
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
        <?= nav_link('index.php', 'Home', $current) ?>
        <?= nav_link('about.php', 'About', $current) ?>
        <?= nav_link('contact.php', 'Contact', $current) ?>
      </nav>

    </div>
  </header>

  <!-- PENTING: main TANPA container, supaya HERO bisa full width -->
  <main id="main" class="main">

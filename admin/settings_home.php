<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$admin_title = 'Pengaturan Home';
$active = 'settings_home';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $heroBadge = trim($_POST['home_hero_badge'] ?? '');
  $heroTitle = trim($_POST['home_hero_title'] ?? '');
  $heroSubtitle = trim($_POST['home_hero_subtitle'] ?? '');
  $searchPlaceholder = trim($_POST['home_search_placeholder'] ?? '');
  $typeAllLabel = trim($_POST['home_type_all_label'] ?? '');
  $locationAllLabel = trim($_POST['home_location_all_label'] ?? '');
  $ctaLabel = trim($_POST['home_cta_label'] ?? '');
  $bannerInterval = (int)($_POST['home_banner_interval'] ?? 5000);

  if ($heroTitle === '') {
    $error = 'Judul hero wajib diisi.';
  } else {
    set_setting('home_hero_badge', $heroBadge);
    set_setting('home_hero_title', $heroTitle);
    set_setting('home_hero_subtitle', $heroSubtitle);
    set_setting('home_search_placeholder', $searchPlaceholder);
    set_setting('home_type_all_label', $typeAllLabel);
    set_setting('home_location_all_label', $locationAllLabel);
    set_setting('home_cta_label', $ctaLabel);
    if ($bannerInterval < 2000) $bannerInterval = 2000;
    if ($bannerInterval > 20000) $bannerInterval = 20000;
    set_setting('home_banner_interval', (string)$bannerInterval);

    $success = 'Pengaturan Home berhasil disimpan.';
  }
}

$curBadge = setting('home_hero_badge', 'PT. RMI SUCCESS Mandiri') ?? 'PT. RMI SUCCESS Mandiri';
$curTitle = setting('home_hero_title', 'Mitra Terpercaya Hunian & Investasi Anda') ?? 'Mitra Terpercaya Hunian & Investasi Anda';
$curSubtitle = setting('home_hero_subtitle', 'Wujudkan kesuksesan masa depan dengan properti berkualitas, lokasi strategis, dan legalitas yang terjamin aman.') ?? 'Wujudkan kesuksesan masa depan dengan properti berkualitas, lokasi strategis, dan legalitas yang terjamin aman.';
$curSearch = setting('home_search_placeholder', 'Cari wilayah / nama property...') ?? 'Cari wilayah / nama property...';
$curTypeAll = setting('home_type_all_label', 'Semua Tipe') ?? 'Semua Tipe';
$curLocAll = setting('home_location_all_label', 'Semua Lokasi') ?? 'Semua Lokasi';
$curCta = setting('home_cta_label', 'Temukan Properti') ?? 'Temukan Properti';
$curBannerInterval = (int)(setting('home_banner_interval', '5000') ?? 5000);

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Pengaturan Home</h1>
        <p class="muted">Ubah konten hero dan filter di halaman utama.</p>
      </div>

      <div class="admin-quick">
        <a class="action" href="<?= e(admin_url('index')) ?>">← Kembali</a>
      </div>
    </div>

    <div class="panel admin-panel">

      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="admin-notice success"><?= e($success) ?></div>
      <?php endif; ?>

      <form method="post" action="settings_home">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="form-field">
          <label class="form-label">Badge Hero</label>
          <input class="input" name="home_hero_badge" placeholder="Contoh: PT. RMI SUCCESS Mandiri" value="<?= e($curBadge) ?>">
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Judul Hero</label>
          <input class="input" name="home_hero_title" placeholder="Judul hero" value="<?= e($curTitle) ?>" required>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Subjudul Hero</label>
          <textarea class="input admin-textarea" name="home_hero_subtitle" rows="3" placeholder="Deskripsi hero"><?= e($curSubtitle) ?></textarea>
        </div>

        <div class="admin-grid admin-grid-2" style="margin-top:12px">
          <div class="form-field">
            <label class="form-label">Placeholder Pencarian</label>
            <input class="input" name="home_search_placeholder" value="<?= e($curSearch) ?>">
          </div>
          <div class="form-field">
            <label class="form-label">Label Tombol CTA</label>
            <input class="input" name="home_cta_label" value="<?= e($curCta) ?>">
          </div>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Interval Banner (ms)</label>
          <input class="input" type="number" name="home_banner_interval" min="2000" max="20000" step="500" value="<?= (int)$curBannerInterval ?>">
          <div class="form-help muted">Minimal 2000ms, maksimal 20000ms.</div>
        </div>

        <div class="admin-grid admin-grid-2" style="margin-top:12px">
          <div class="form-field">
            <label class="form-label">Label Semua Tipe</label>
            <input class="input" name="home_type_all_label" value="<?= e($curTypeAll) ?>">
          </div>
          <div class="form-field">
            <label class="form-label">Label Semua Lokasi</label>
            <input class="input" name="home_location_all_label" value="<?= e($curLocAll) ?>">
          </div>
        </div>

        <div class="actions" style="margin-top:14px">
          <button class="action accent" type="submit">Simpan Pengaturan</button>
          <a class="action" href="<?= e(admin_url('index')) ?>">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

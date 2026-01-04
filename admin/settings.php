<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$cfg = require __DIR__ . '/../config.php';

$admin_title = 'Pengaturan Brand';
$active = 'settings_brand';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) {
    $error = 'Ukuran upload melebihi batas server (post_max_size).';
  } else {
    csrf_check();
  }

  $siteName = trim($_POST['site_name'] ?? '');
  $tagline  = trim($_POST['site_tagline'] ?? '');
  $footer   = trim($_POST['footer_text'] ?? '');
  $removeLogo = (string)($_POST['remove_logo'] ?? '') === '1';
  if ($error) {
    // keep error from post_too_large or csrf_check
  } elseif ($siteName === '') {
    $error = 'Nama website wajib diisi.';
  } else {
    set_setting('site_name', $siteName);
    set_setting('site_tagline', $tagline);
    set_setting('footer_text', $footer);

    // Hapus logo (opsional)
    if ($removeLogo) {
      set_setting('logo_path', '');
    }

    // Upload logo (opsional)
    if (!$error && !$removeLogo && !empty($_FILES['logo']['name'])) {
      [$ok, $msg, $fname] = upload_image(
        $_FILES['logo'],
        $cfg['upload']['branding_dir'],
        $cfg['upload']['max_bytes']
      );

      if (!$ok) {
        $error = $msg;
      } else {
        $path = $cfg['upload']['branding_url'] . '/' . $fname;
        set_setting('logo_path', $path);
      }
    }

    if (!$error) $success = 'Pengaturan berhasil disimpan.';
  }
}

include __DIR__ . '/_header.php';

$curName = setting('site_name', 'RM Properti') ?? 'RM Properti';
$curTag  = setting('site_tagline', '') ?? '';
$curFoot = setting('footer_text', '© {year} RM Properti. All rights reserved.') ?? '';
$curLogo = setting('logo_path', null);
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Pengaturan Brand</h1>
        <p class="muted">
          Ubah identitas publik website. Footer mendukung token <code>{year}</code>.
        </p>
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

      <form method="post" action="settings" enctype="multipart/form-data" class="settings-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="settings-layout">

          <!-- LEFT: FORM -->
          <div class="settings-main">

            <div class="admin-grid admin-grid-2">
              <div class="form-field">
                <label class="form-label">Nama Website <span class="muted">(wajib)</span></label>
                <input class="input" name="site_name" placeholder="Nama Website" value="<?= e($curName) ?>" required>
              </div>

              <div class="form-field">
                <label class="form-label">Tagline</label>
                <input class="input" name="site_tagline" placeholder="Tagline singkat" value="<?= e($curTag) ?>">
              </div>
            </div>

            <div class="form-field" style="margin-top:12px">
              <label class="form-label">Footer Text</label>
              <textarea class="input admin-textarea" name="footer_text" rows="4" placeholder="Footer text"><?= e($curFoot) ?></textarea>
              <div class="form-help muted">
                Contoh: <code>© {year} RM Properti</code> (nanti {year} diganti otomatis).
              </div>
            </div>

            <div class="actions" style="margin-top:14px">
              <button class="action accent" type="submit">Simpan Pengaturan</button>
              <a class="action" href="<?= e(admin_url('index')) ?>">Batal</a>
            </div>
          </div>

          <!-- RIGHT: LOGO -->
          <aside class="panel settings-side">
            <div class="settings-side-head">
              <div>
                <div class="settings-side-title">Logo Brand</div>
                <div class="muted settings-side-sub">PNG/JPG/WebP.</div>
              </div>
            </div>

            <div class="settings-logo-preview" id="logoPreview">
              <?php if (!empty($curLogo)): ?>
                <img src="<?= e(abs_url($curLogo)) ?>" alt="Logo <?= e($curName) ?>">
              <?php else: ?>
                <div class="settings-logo-fallback" aria-hidden="true">
                  <?= e(mb_strtoupper(mb_substr($curName ?: 'RM', 0, 2))) ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="form-field" style="margin-top:12px">
              <label class="form-label">Upload logo baru</label>
              <input class="input" type="file" name="logo" accept="image/jpeg,image/png,image/webp" id="logoInput">
              <div class="form-help muted">Disarankan tinggi 64–96px, background transparan jika PNG.</div>
            </div>

            <?php if (!empty($curLogo)): ?>
              <label class="settings-remove">
                <input type="checkbox" name="remove_logo" value="1">
                Hapus logo saat ini
              </label>
            <?php endif; ?>
          </aside>

        </div>
      </form>

    </div>
  </section>
</div>

<script>
  (function(){
    const input = document.getElementById('logoInput');
    const preview = document.getElementById('logoPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function(){
      const file = input.files && input.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      preview.innerHTML = '<img src="'+url+'" alt="Preview logo">';
    });
  })();
</script>

<?php include __DIR__ . '/_footer.php'; ?>

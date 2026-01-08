<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$cfg = require __DIR__ . '/../config.php';

$admin_title = 'Pengaturan Banner';
$active = 'settings_banners';

$error = '';
$success = '';

$slots = [1, 2, 3, 4];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) {
    $error = 'Ukuran upload melebihi batas server (post_max_size).';
  } else {
    csrf_check();
  }

  if (!$error) {
    foreach ($slots as $i) {
      $key = 'home_banner_' . $i;
      $remove = (string)($_POST['remove_banner_' . $i] ?? '') === '1';

      if ($remove) {
        set_setting($key, '');
        continue;
      }

      $file = $_FILES['banner_' . $i] ?? null;
      if ($file && !empty($file['name'])) {
        [$ok, $msg, $fname] = upload_image(
          $file,
          $cfg['upload']['banner_dir'],
          $cfg['upload']['max_bytes']
        );

        if (!$ok) {
          $error = $msg;
          break;
        }
        $path = $cfg['upload']['banner_url'] . '/' . $fname;
        set_setting($key, $path);
      }
    }

    if (!$error) $success = 'Banner berhasil disimpan.';
  }
}

$current = [];
foreach ($slots as $i) {
  $current[$i] = setting('home_banner_' . $i, '') ?? '';
}

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Pengaturan Banner</h1>
        <p class="muted">Upload maksimal 4 banner. Jika kosong, tidak ditampilkan di home.</p>
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

      <form method="post" action="settings_banners" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="admin-grid admin-grid-2">
          <?php foreach ($slots as $i): ?>
            <?php $cur = $current[$i] ?? ''; ?>
            <div class="panel" style="padding:14px">
              <div class="form-field">
                <label class="form-label">Banner <?= (int)$i ?></label>
                <div style="border:1px dashed rgba(0,0,0,0.15); border-radius:12px; overflow:hidden; background:#f8fafc; height:150px; display:flex; align-items:center; justify-content:center;">
                  <?php if ($cur): ?>
                    <img src="<?= e(abs_url($cur)) ?>" alt="Banner <?= (int)$i ?>" style="width:100%; height:100%; object-fit:cover;">
                  <?php else: ?>
                    <div class="muted" style="font-weight:700;">Belum ada banner</div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="form-field" style="margin-top:10px">
                <input class="input" type="file" name="banner_<?= (int)$i ?>" accept="image/jpeg,image/png,image/webp">
                <div class="form-help muted">Disarankan rasio 16:7 (mis. 1600×700). Boleh 16:6.</div>
              </div>

              <?php if ($cur): ?>
                <label class="settings-remove" style="margin-top:8px">
                  <input type="checkbox" name="remove_banner_<?= (int)$i ?>" value="1">
                  Hapus banner ini
                </label>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="actions" style="margin-top:14px">
          <button class="action accent" type="submit">Simpan Banner</button>
          <a class="action" href="<?= e(admin_url('index')) ?>">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

$cfg = require __DIR__ . '/../config.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$item = null;

if ($id) {
  $st = $pdo->prepare("SELECT * FROM portfolio_items WHERE id=?");
  $st->execute([$id]);
  $item = $st->fetch();
  if (!$item) { http_response_code(404); exit('Portfolio item not found'); }
}

$admin_title = $id ? 'Edit Portofolio' : 'Tambah Portofolio';
$active = 'portfolio';
$formAction = $id ? ('portfolio_edit?id=' . (int)$id) : 'portfolio_edit';

function portfolio_local_path(?string $path, string $baseDir): ?string {
  if (!$path) return null;
  if (preg_match('~^https?://~i', $path)) return null;

  $local = __DIR__ . '/../' . ltrim($path, '/');
  $real = realpath($local);
  $base = realpath($baseDir);
  if ($real && $base && str_starts_with($real, $base) && is_file($real)) {
    return $real;
  }
  return null;
}

function portfolio_logo_url(?string $path): ?string {
  if (!$path) return null;
  if (str_starts_with($path, 'data:')) return $path;
  if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) return $path;

  $local = __DIR__ . '/../' . ltrim($path, '/');
  if (!file_exists($local)) return null;
  return abs_url($path);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) {
    $error = 'Ukuran upload melebihi batas server (post_max_size).';
  } else {
    csrf_check();
  }

  $title = trim($_POST['title'] ?? '');
  $url = trim($_POST['url'] ?? '');
  $sortOrder = (int)($_POST['sort_order'] ?? 0);
  $isActive = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

  if (!$error && $title === '') {
    $error = 'Nama client/partner wajib diisi.';
  }

  if (!$error && $url !== '') {
    $isValidUrl = filter_var($url, FILTER_VALIDATE_URL);
    if (!$isValidUrl || !preg_match('~^https?://~i', $url)) {
      $error = 'URL harus diawali http:// atau https://';
    }
  }

  $imagePath = $item['image_path'] ?? null;
  $maxBytes = 2 * 1024 * 1024; // 2MB

  if (!$error && !empty($_FILES['logo']['name'])) {
    [$ok, $msg, $fname] = upload_image($_FILES['logo'], $cfg['upload']['portfolio_dir'], $maxBytes);
    if (!$ok) {
      $error = $msg;
    } else {
      $imagePath = $cfg['upload']['portfolio_url'] . '/' . $fname;
      $oldPath = portfolio_local_path($item['image_path'] ?? null, $cfg['upload']['portfolio_dir']);
      if ($oldPath) @unlink($oldPath);
    }
  }

  if (!$error && !$id && !$imagePath) {
    $error = 'Logo wajib diupload untuk item baru.';
  }

  if (!$error) {
    if ($id) {
      $st = $pdo->prepare("UPDATE portfolio_items
        SET title=?, image_path=?, url=?, sort_order=?, is_active=?
        WHERE id=?");
      $st->execute([$title, $imagePath, $url ?: null, $sortOrder, $isActive, $id]);
    } else {
      $st = $pdo->prepare("INSERT INTO portfolio_items (title, image_path, url, sort_order, is_active)
        VALUES (?,?,?,?,?)");
      $st->execute([$title, $imagePath, $url ?: null, $sortOrder, $isActive]);
    }
    header('Location: portfolio');
    exit;
  }
}

$val = fn($k, $d='') => e((string)($item[$k] ?? $d));
$logoUrl = portfolio_logo_url($item['image_path'] ?? null);

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title"><?= $id ? 'Edit Portofolio' : 'Tambah Portofolio' ?></h1>
        <p class="muted">Upload logo client/partner untuk ditampilkan di halaman About.</p>
      </div>

      <div class="admin-quick">
        <a class="action" href="portfolio">← Kembali</a>
      </div>
    </div>

    <div class="panel admin-panel">

      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="portfolio-edit-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="admin-form-layout">
          <div class="admin-side">
            <div class="admin-grid admin-grid-2">
              <div class="form-field">
                <label class="form-label">Nama Client/Partner <span class="muted">(wajib)</span></label>
                <input class="input" name="title" placeholder="Contoh: PT Contoh Jaya" value="<?= $val('title') ?>" required>
              </div>

              <div class="form-field">
                <label class="form-label">URL (opsional)</label>
                <input class="input" name="url" placeholder="https://..." value="<?= $val('url') ?>">
                <div class="form-help muted">Jika diisi, logo akan menjadi link.</div>
              </div>
            </div>

            <div class="admin-grid admin-grid-2" style="margin-top:12px">
              <div class="form-field">
                <label class="form-label">Urutan</label>
                <input class="input" type="number" name="sort_order" value="<?= $val('sort_order','0') ?>">
                <div class="form-help muted">Semakin kecil, semakin atas.</div>
              </div>

              <div class="form-field">
                <label class="form-label">Status</label>
                <select class="select" name="is_active">
                  <option value="1" <?= ((int)($item['is_active'] ?? 1)) === 1 ? 'selected' : '' ?>>Aktif</option>
                  <option value="0" <?= ((int)($item['is_active'] ?? 1)) === 0 ? 'selected' : '' ?>>Nonaktif</option>
                </select>
              </div>
            </div>

            <div class="actions" style="margin-top:14px">
              <button class="action accent" type="submit">Simpan</button>
              <a class="action" href="portfolio">Batal</a>
            </div>
          </div>

          <aside class="panel portfolio-logo-panel">
            <div class="portfolio-logo-head">
              <div>
                <div class="portfolio-logo-title">Logo</div>
                <div class="muted portfolio-logo-sub">PNG/JPG/WEBP, maksimal 2MB.</div>
              </div>
            </div>

            <div class="portfolio-logo-preview" id="logoPreview">
              <?php if (!empty($logoUrl)): ?>
                <img src="<?= e($logoUrl) ?>" alt="Logo <?= e($item['title'] ?? 'Portofolio') ?>">
              <?php else: ?>
                <div class="portfolio-logo-fallback" aria-hidden="true">Logo</div>
              <?php endif; ?>
            </div>

            <div class="form-field" style="margin-top:10px">
              <input class="input" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
            </div>
          </aside>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

<?php
require __DIR__ . '/_guard.php';

$cfg = require __DIR__ . '/../config.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$s = null;

if ($id) {
  $st = $pdo->prepare("SELECT * FROM sales WHERE id=?");
  $st->execute([$id]);
  $s = $st->fetch();
  if (!$s) { http_response_code(404); exit('Sales not found'); }
}

$admin_title = $id ? 'Edit Sales' : 'Tambah Sales';
$active = 'sales';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $name  = trim($_POST['name'] ?? '');
  $title = trim($_POST['title'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $wa    = trim($_POST['whatsapp'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $bio   = trim($_POST['bio'] ?? '');
  $areas = trim($_POST['areas'] ?? '');
  $exp   = (int)($_POST['experience_years'] ?? 0);

  if ($name === '') {
    $error = 'Nama wajib diisi.';
  } else {
    $photoPath = $s['photo_path'] ?? null;

    if (!empty($_FILES['photo']['name'])) {
      [$ok, $msg, $fname] = upload_image($_FILES['photo'], $cfg['upload']['sales_dir'], $cfg['upload']['max_bytes']);
      if (!$ok) {
        $error = $msg;
      } else {
        $photoPath = $cfg['upload']['sales_url'] . '/' . $fname;
      }
    }

    if (!$error) {
      if ($id) {
        $st = $pdo->prepare("UPDATE sales SET name=?, title=?, phone=?, whatsapp=?, email=?, photo_path=?, bio=?, areas=?, experience_years=? WHERE id=?");
        $st->execute([$name,$title,$phone,$wa,$email,$photoPath,$bio,$areas,$exp,$id]);
      } else {
        $st = $pdo->prepare("INSERT INTO sales (name,title,phone,whatsapp,email,photo_path,bio,areas,experience_years) VALUES (?,?,?,?,?,?,?,?,?)");
        $st->execute([$name,$title,$phone,$wa,$email,$photoPath,$bio,$areas,$exp]);
      }
      header('Location: sales.php');
      exit;
    }
  }
}

$val = fn($k, $d='') => e((string)($s[$k] ?? $d));

/* bantu bikin link wa preview (opsional, aman) */
$waDigits = preg_replace('/\D+/', '', (string)($s['whatsapp'] ?? ''));
$waLink = ($waDigits && str_starts_with($waDigits, '62')) ? ('https://wa.me/' . $waDigits) : null;

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title"><?= $id ? 'Edit Sales' : 'Tambah Sales' ?></h1>
        <p class="muted">
          Isi profil sales untuk ditampilkan pada listing dan tombol WhatsApp di detail properti.
        </p>
      </div>

      <div class="admin-quick">
        <a class="action" href="sales.php">← Kembali</a>
      </div>
    </div>

    <div class="panel admin-panel">

      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="sales-edit-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="admin-form-layout">

          <!-- LEFT: FORM -->
          <div class="admin-side">
            <div class="admin-grid admin-grid-2">
              <div class="form-field">
                <label class="form-label">Nama <span class="muted">(wajib)</span></label>
                <input class="input" name="name" placeholder="Nama sales" value="<?= $val('name') ?>" required>
              </div>

              <div class="form-field">
                <label class="form-label">Jabatan / Title</label>
                <input class="input" name="title" placeholder="Contoh: Marketing Executive" value="<?= $val('title') ?>">
              </div>
            </div>

            <div class="admin-grid admin-grid-2" style="margin-top:12px">
              <div class="form-field">
                <label class="form-label">Telepon</label>
                <input class="input" name="phone" inputmode="tel" placeholder="08xxxx / +62..." value="<?= $val('phone') ?>">
              </div>

              <div class="form-field">
                <label class="form-label">WhatsApp <span class="muted">(format 62...)</span></label>
                <input class="input" name="whatsapp" inputmode="tel" placeholder="62xxxxxxxxxxx" value="<?= $val('whatsapp') ?>">
                <div class="form-help muted">Dipakai untuk tombol WA di listing. Disarankan tanpa spasi/tanda baca.</div>
              </div>

              <div class="form-field" style="grid-column: 1 / -1;">
                <label class="form-label">Email</label>
                <input class="input" type="email" name="email" placeholder="email@domain.com" value="<?= $val('email') ?>">
              </div>
            </div>

            <div class="admin-grid admin-grid-2" style="margin-top:12px">
              <div class="form-field" style="grid-column: 1 / -1;">
                <label class="form-label">Area Layanan <span class="muted">(pisahkan koma)</span></label>
                <input class="input" name="areas" placeholder="Contoh: Palembang, OPI, Jakabaring" value="<?= $val('areas') ?>">
              </div>

              <div class="form-field">
                <label class="form-label">Pengalaman (tahun)</label>
                <input class="input" type="number" min="0" name="experience_years" value="<?= $val('experience_years','0') ?>">
              </div>

              <div class="form-field" style="grid-column: 1 / -1;">
                <label class="form-label">Bio singkat</label>
                <textarea class="input admin-textarea" name="bio" rows="5" placeholder="Ringkas, profesional, 2–4 kalimat."><?= e($s['bio'] ?? '') ?></textarea>
              </div>
            </div>

            <div class="actions" style="margin-top:14px">
              <button class="action accent" type="submit">Simpan</button>
              <a class="action" href="sales.php">Batal</a>
            </div>
          </div>

          <!-- RIGHT: PHOTO -->
          <aside class="panel sales-photo-panel">
            <div class="sales-photo-head">
              <div>
                <div class="sales-photo-title">Foto Sales</div>
                <div class="muted sales-photo-sub">PNG/JPG/WEBP, disarankan kotak.</div>
              </div>

              <?php if ($waLink): ?>
                <a class="action" href="<?= e($waLink) ?>" target="_blank" rel="noopener">Cek WA</a>
              <?php endif; ?>
            </div>

            <div class="sales-photo-preview" id="photoPreview">
              <?php if (!empty($s['photo_path'])): ?>
                <img src="<?= e($s['photo_path']) ?>" alt="Foto <?= e($s['name'] ?? 'Sales') ?>">
              <?php else: ?>
                <div class="sales-photo-fallback" aria-hidden="true">
                  <?= e(mb_strtoupper(mb_substr(($s['name'] ?? 'S'), 0, 1))) ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="form-field" style="margin-top:12px">
              <label class="form-label">Upload foto baru</label>
              <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="photoInput">
              <?php if (!empty($s['photo_path'])): ?>
                <div class="muted form-help">Saat ini: <?= e($s['photo_path']) ?></div>
              <?php endif; ?>
            </div>

            <div class="muted form-help" style="margin-top:10px">
              Tip: foto akan tampil di halaman properti pada bagian kontak sales.
            </div>
          </aside>

        </div>
      </form>
    </div>

  </section>
</div>

<script>
  (function(){
    const input = document.getElementById('photoInput');
    const preview = document.getElementById('photoPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function(){
      const file = input.files && input.files[0];
      if (!file) return;

      const url = URL.createObjectURL(file);
      preview.innerHTML = '<img src="'+url+'" alt="Preview foto">';
    });
  })();
</script>

<?php include __DIR__ . '/_footer.php'; ?>

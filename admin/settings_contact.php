<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$admin_title = 'Pengaturan Contact';
$active = 'settings_contact';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $contactSubtitle = trim($_POST['contact_subtitle'] ?? '');
  $contactEmail    = trim($_POST['contact_email'] ?? '');
  $contactPhone    = trim($_POST['contact_phone'] ?? '');
  $contactAddress  = trim($_POST['contact_address'] ?? '');
  $contactHours    = trim($_POST['contact_hours'] ?? '');
  $contactTip      = trim($_POST['contact_tip'] ?? '');

  if ($contactEmail === '' || $contactPhone === '') {
    $error = 'Email dan telepon wajib diisi.';
  } else {
    set_setting('contact_subtitle', $contactSubtitle);
    set_setting('contact_email', $contactEmail);
    set_setting('contact_phone', $contactPhone);
    set_setting('contact_address', $contactAddress);
    set_setting('contact_hours', $contactHours);
    set_setting('contact_tip', $contactTip);

    $success = 'Pengaturan Contact berhasil disimpan.';
  }
}

$defaultContactSubtitle = 'Untuk kerja sama atau pertanyaan umum, gunakan kanal di bawah. Untuk listing tertentu, buka detail properti lalu klik WhatsApp sales.';
$defaultContactEmail = 'info@rmproperti.id';
$defaultContactPhone = '08xxxxxxxxxx';
$defaultContactAddress = 'Alamat kantor / area layanan (isi sendiri)';
$defaultContactHours = "Senin–Sabtu: 09.00–17.00\nMinggu: By appointment";
$defaultContactTip = 'Tip: untuk konsultasi listing tertentu, buka detail properti lalu klik tombol WhatsApp sales.';

$curContactSubtitle = setting('contact_subtitle', $defaultContactSubtitle) ?? $defaultContactSubtitle;
$curContactEmail = setting('contact_email', $defaultContactEmail) ?? $defaultContactEmail;
$curContactPhone = setting('contact_phone', $defaultContactPhone) ?? $defaultContactPhone;
$curContactAddress = setting('contact_address', $defaultContactAddress) ?? $defaultContactAddress;
$curContactHours = setting('contact_hours', $defaultContactHours) ?? $defaultContactHours;
$curContactTip = setting('contact_tip', $defaultContactTip) ?? $defaultContactTip;

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Pengaturan Contact</h1>
        <p class="muted">
          Ubah konten halaman Contact di public site.
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

      <form method="post" action="settings_contact">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="form-field">
          <label class="form-label">Intro</label>
          <textarea class="input admin-textarea" name="contact_subtitle" rows="3" placeholder="Paragraf intro"><?= e($curContactSubtitle) ?></textarea>
        </div>

        <div class="admin-grid admin-grid-2" style="margin-top:12px">
          <div class="form-field">
            <label class="form-label">Email</label>
            <input class="input" name="contact_email" placeholder="email@domain.com" value="<?= e($curContactEmail) ?>">
          </div>
          <div class="form-field">
            <label class="form-label">Telepon</label>
            <input class="input" name="contact_phone" placeholder="08xxxxxxxxxx" value="<?= e($curContactPhone) ?>">
          </div>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Alamat</label>
          <textarea class="input admin-textarea" name="contact_address" rows="3" placeholder="Alamat kantor"><?= e($curContactAddress) ?></textarea>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Jam Layanan</label>
          <textarea class="input admin-textarea" name="contact_hours" rows="3" placeholder="Satu baris per hari"><?= e($curContactHours) ?></textarea>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Tip Kontak</label>
          <input class="input" name="contact_tip" placeholder="Tip singkat" value="<?= e($curContactTip) ?>">
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

<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib.php';

$admin_title = 'Pengaturan About';
$active = 'settings_about';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $aboutIntro = trim($_POST['about_intro'] ?? '');
  $aboutKpi1Label = trim($_POST['about_kpi1_label'] ?? '');
  $aboutKpi1Value = trim($_POST['about_kpi1_value'] ?? '');
  $aboutKpi1Desc  = trim($_POST['about_kpi1_desc'] ?? '');
  $aboutKpi2Label = trim($_POST['about_kpi2_label'] ?? '');
  $aboutKpi2Value = trim($_POST['about_kpi2_value'] ?? '');
  $aboutKpi2Desc  = trim($_POST['about_kpi2_desc'] ?? '');
  $aboutKpi3Label = trim($_POST['about_kpi3_label'] ?? '');
  $aboutKpi3Value = trim($_POST['about_kpi3_value'] ?? '');
  $aboutKpi3Desc  = trim($_POST['about_kpi3_desc'] ?? '');
  $aboutSec1Title = trim($_POST['about_sec1_title'] ?? '');
  $aboutSec1List  = trim($_POST['about_sec1_list'] ?? '');
  $aboutSec2Title = trim($_POST['about_sec2_title'] ?? '');
  $aboutSec2List  = trim($_POST['about_sec2_list'] ?? '');

  if ($aboutIntro === '') {
    $error = 'Intro About wajib diisi.';
  } else {
    set_setting('about_intro', $aboutIntro);
    set_setting('about_kpi1_label', $aboutKpi1Label);
    set_setting('about_kpi1_value', $aboutKpi1Value);
    set_setting('about_kpi1_desc', $aboutKpi1Desc);
    set_setting('about_kpi2_label', $aboutKpi2Label);
    set_setting('about_kpi2_value', $aboutKpi2Value);
    set_setting('about_kpi2_desc', $aboutKpi2Desc);
    set_setting('about_kpi3_label', $aboutKpi3Label);
    set_setting('about_kpi3_value', $aboutKpi3Value);
    set_setting('about_kpi3_desc', $aboutKpi3Desc);
    set_setting('about_sec1_title', $aboutSec1Title);
    set_setting('about_sec1_list', features_to_json($aboutSec1List));
    set_setting('about_sec2_title', $aboutSec2Title);
    set_setting('about_sec2_list', features_to_json($aboutSec2List));

    $success = 'Pengaturan About berhasil disimpan.';
  }
}

$defaultAboutIntro = 'RM Properti adalah katalog listing properti dengan detail jelas dan akses cepat ke sales, agar proses konsultasi sampai transaksi lebih rapi.';
$defaultSec1List = "Harga, lokasi, spesifikasi\nFoto cover & galeri\nKontak sales yang menangani listing";
$defaultSec2List = "Respons cepat\nTransparansi informasi\nPendampingan hingga closing";

$curAboutIntro = setting('about_intro', $defaultAboutIntro) ?? $defaultAboutIntro;
$curAboutKpi1Label = setting('about_kpi1_label', 'Fokus') ?? 'Fokus';
$curAboutKpi1Value = setting('about_kpi1_value', 'Listing Berkualitas') ?? 'Listing Berkualitas';
$curAboutKpi1Desc  = setting('about_kpi1_desc', 'Info ringkas, jelas, dan mudah dibandingkan.') ?? 'Info ringkas, jelas, dan mudah dibandingkan.';
$curAboutKpi2Label = setting('about_kpi2_label', 'Kecepatan') ?? 'Kecepatan';
$curAboutKpi2Value = setting('about_kpi2_value', 'Respons Cepat') ?? 'Respons Cepat';
$curAboutKpi2Desc  = setting('about_kpi2_desc', 'Akses langsung ke sales yang menangani listing.') ?? 'Akses langsung ke sales yang menangani listing.';
$curAboutKpi3Label = setting('about_kpi3_label', 'Proses') ?? 'Proses';
$curAboutKpi3Value = setting('about_kpi3_value', 'Pendampingan') ?? 'Pendampingan';
$curAboutKpi3Desc  = setting('about_kpi3_desc', 'Dari konsultasi sampai closing lebih rapi.') ?? 'Dari konsultasi sampai closing lebih rapi.';
$curAboutSec1Title = setting('about_sec1_title', 'Apa yang kami tampilkan') ?? 'Apa yang kami tampilkan';
$curAboutSec2Title = setting('about_sec2_title', 'Standar layanan') ?? 'Standar layanan';
$curAboutSec1List  = features_from_json(setting('about_sec1_list', null));
$curAboutSec2List  = features_from_json(setting('about_sec2_list', null));
if ($curAboutSec1List === '') $curAboutSec1List = $defaultSec1List;
if ($curAboutSec2List === '') $curAboutSec2List = $defaultSec2List;

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Pengaturan About</h1>
        <p class="muted">
          Ubah konten halaman About di public site.
        </p>
      </div>

      <div class="admin-quick">
        <a class="action" href="index.php">← Kembali</a>
      </div>
    </div>

    <div class="panel admin-panel">

      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="admin-notice success"><?= e($success) ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="form-field">
          <label class="form-label">Intro</label>
          <textarea class="input admin-textarea" name="about_intro" rows="4" placeholder="Paragraf intro"><?= e($curAboutIntro) ?></textarea>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">KPI 1</label>
          <div class="admin-grid admin-grid-2">
            <input class="input" name="about_kpi1_label" placeholder="Label" value="<?= e($curAboutKpi1Label) ?>">
            <input class="input" name="about_kpi1_value" placeholder="Value" value="<?= e($curAboutKpi1Value) ?>">
          </div>
          <input class="input" name="about_kpi1_desc" style="margin-top:10px" placeholder="Deskripsi" value="<?= e($curAboutKpi1Desc) ?>">
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">KPI 2</label>
          <div class="admin-grid admin-grid-2">
            <input class="input" name="about_kpi2_label" placeholder="Label" value="<?= e($curAboutKpi2Label) ?>">
            <input class="input" name="about_kpi2_value" placeholder="Value" value="<?= e($curAboutKpi2Value) ?>">
          </div>
          <input class="input" name="about_kpi2_desc" style="margin-top:10px" placeholder="Deskripsi" value="<?= e($curAboutKpi2Desc) ?>">
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">KPI 3</label>
          <div class="admin-grid admin-grid-2">
            <input class="input" name="about_kpi3_label" placeholder="Label" value="<?= e($curAboutKpi3Label) ?>">
            <input class="input" name="about_kpi3_value" placeholder="Value" value="<?= e($curAboutKpi3Value) ?>">
          </div>
          <input class="input" name="about_kpi3_desc" style="margin-top:10px" placeholder="Deskripsi" value="<?= e($curAboutKpi3Desc) ?>">
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Section 1</label>
          <input class="input" name="about_sec1_title" placeholder="Judul section" value="<?= e($curAboutSec1Title) ?>">
          <textarea class="input admin-textarea" name="about_sec1_list" rows="4" placeholder="Satu item per baris"><?= e($curAboutSec1List) ?></textarea>
        </div>

        <div class="form-field" style="margin-top:12px">
          <label class="form-label">Section 2</label>
          <input class="input" name="about_sec2_title" placeholder="Judul section" value="<?= e($curAboutSec2Title) ?>">
          <textarea class="input admin-textarea" name="about_sec2_list" rows="4" placeholder="Satu item per baris"><?= e($curAboutSec2List) ?></textarea>
        </div>

        <div class="actions" style="margin-top:14px">
          <button class="action accent" type="submit">Simpan Pengaturan</button>
          <a class="action" href="index.php">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

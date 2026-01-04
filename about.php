<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'About';
$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
$aboutIntro = setting('about_intro', 'RM Properti adalah katalog listing properti dengan detail jelas dan akses cepat ke sales, agar proses konsultasi sampai transaksi lebih rapi.') ?? 'RM Properti adalah katalog listing properti dengan detail jelas dan akses cepat ke sales, agar proses konsultasi sampai transaksi lebih rapi.';

$kpi1Label = setting('about_kpi1_label', 'Fokus') ?? 'Fokus';
$kpi1Value = setting('about_kpi1_value', 'Listing Berkualitas') ?? 'Listing Berkualitas';
$kpi1Desc  = setting('about_kpi1_desc', 'Info ringkas, jelas, dan mudah dibandingkan.') ?? 'Info ringkas, jelas, dan mudah dibandingkan.';
$kpi2Label = setting('about_kpi2_label', 'Kecepatan') ?? 'Kecepatan';
$kpi2Value = setting('about_kpi2_value', 'Respons Cepat') ?? 'Respons Cepat';
$kpi2Desc  = setting('about_kpi2_desc', 'Akses langsung ke sales yang menangani listing.') ?? 'Akses langsung ke sales yang menangani listing.';
$kpi3Label = setting('about_kpi3_label', 'Proses') ?? 'Proses';
$kpi3Value = setting('about_kpi3_value', 'Pendampingan') ?? 'Pendampingan';
$kpi3Desc  = setting('about_kpi3_desc', 'Dari konsultasi sampai closing lebih rapi.') ?? 'Dari konsultasi sampai closing lebih rapi.';

$sec1Title = setting('about_sec1_title', 'Apa yang kami tampilkan') ?? 'Apa yang kami tampilkan';
$sec2Title = setting('about_sec2_title', 'Standar layanan') ?? 'Standar layanan';

function about_list(string $key, array $fallback): array {
  $raw = setting($key, null);
  if ($raw) {
    $arr = json_decode($raw, true);
    if (is_array($arr) && $arr) {
      return array_map('strval', $arr);
    }
  }
  return $fallback;
}

$sec1List = about_list('about_sec1_list', [
  'Harga, lokasi, spesifikasi',
  'Foto cover & galeri',
  'Kontak sales yang menangani listing',
]);
$sec2List = about_list('about_sec2_list', [
  'Respons cepat',
  'Transparansi informasi',
  'Pendampingan hingga closing',
]);

$page_description = str_excerpt($aboutIntro, 155);
$page_og_type = 'website';
$page_canonical = site_url('about');

include __DIR__ . '/header.php';
?>

<section class="about-hero">
  <div class="container about-hero-inner">
    <span class="badge-brand">Tentang Kami</span>
    <h1 class="about-title">Tentang <?= e($siteName) ?></h1>
    <p class="about-subtitle">
      <?= e($aboutIntro) ?>
    </p>
  </div>
</section>

<div class="container">
  <!-- KPI / Highlight -->
  <section class="about-kpis">
    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi1Label) ?></div>
      <div class="kpi-value"><?= e($kpi1Value) ?></div>
      <div class="kpi-desc"><?= e($kpi1Desc) ?></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi2Label) ?></div>
      <div class="kpi-value"><?= e($kpi2Value) ?></div>
      <div class="kpi-desc"><?= e($kpi2Desc) ?></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi3Label) ?></div>
      <div class="kpi-value"><?= e($kpi3Value) ?></div>
      <div class="kpi-desc"><?= e($kpi3Desc) ?></div>
    </div>
  </section>

  <!-- Konten utama -->
  <section class="panel panel-lg about-panel">
    <div class="kv">
      <article class="panel about-box">
        <h2 class="section-title"><?= e($sec1Title) ?></h2>
        <ul class="list">
          <?php foreach ($sec1List as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </article>

      <article class="panel about-box about-box-accent">
        <h2 class="section-title"><?= e($sec2Title) ?></h2>
        <ul class="list">
          <?php foreach ($sec2List as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </article>
    </div>
  </section>
</div>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $siteName,
  'url' => site_url(''),
  'logo' => abs_url(setting('logo_path', 'Assets/logo.png') ?? 'Assets/logo.png'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php include __DIR__ . '/footer.php'; ?>

<?php
$page_title = 'About';
include __DIR__ . '/header.php';

$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
?>

<section class="about-hero">
  <div class="container about-hero-inner">
    <span class="badge-brand">Tentang Kami</span>
    <h1 class="about-title">Tentang <?= e($siteName) ?></h1>
    <p class="about-subtitle">
      <?= e($siteName) ?> adalah katalog listing properti dengan detail jelas dan akses cepat ke sales,
      agar proses konsultasi sampai transaksi lebih rapi.
    </p>
  </div>
</section>

<div class="container">
  <!-- KPI / Highlight -->
  <section class="about-kpis">
    <div class="kpi-card">
      <div class="kpi-label">Fokus</div>
      <div class="kpi-value">Listing Berkualitas</div>
      <div class="kpi-desc">Info ringkas, jelas, dan mudah dibandingkan.</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label">Kecepatan</div>
      <div class="kpi-value">Respons Cepat</div>
      <div class="kpi-desc">Akses langsung ke sales yang menangani listing.</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label">Proses</div>
      <div class="kpi-value">Pendampingan</div>
      <div class="kpi-desc">Dari konsultasi sampai closing lebih rapi.</div>
    </div>
  </section>

  <!-- Konten utama -->
  <section class="panel panel-lg about-panel">
    <div class="kv">
      <article class="panel about-box">
        <h2 class="section-title">Apa yang kami tampilkan</h2>
        <ul class="list">
          <li>Harga, lokasi, spesifikasi</li>
          <li>Foto cover & galeri</li>
          <li>Kontak sales yang menangani listing</li>
        </ul>
      </article>

      <article class="panel about-box about-box-accent">
        <h2 class="section-title">Standar layanan</h2>
        <ul class="list">
          <li>Respons cepat</li>
          <li>Transparansi informasi</li>
          <li>Pendampingan hingga closing</li>
        </ul>
      </article>
    </div>
  </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>

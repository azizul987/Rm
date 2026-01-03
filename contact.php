<?php
$page_title = 'Contact';
include __DIR__ . '/header.php';

$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';

// Bisa kamu pindah ke settings nanti
$email   = 'info@rmproperti.id';
$phone   = '08xxxxxxxxxx'; // format Indonesia umum
$address = 'Alamat kantor / area layanan (isi sendiri)';
$hours   = "Senin–Sabtu: 09.00–17.00\nMinggu: By appointment";

/**
 * Convert nomor lokal Indonesia ke format WhatsApp (E.164) tanpa +.
 * Contoh:
 * 08xxxx -> 628xxxx
 * +628xxx -> 628xxx
 * 628xxx -> 628xxx
 */
function wa_phone($phone){
  $p = preg_replace('/[^0-9+]/', '', (string)$phone);
  $p = ltrim($p, '+');

  if (strpos($p, '62') === 0) return $p;
  if (strpos($p, '0') === 0)  return '62' . substr($p, 1);
  return $p; // fallback
}

$waNumber = wa_phone($phone);
$waText   = "Halo {$siteName}, saya ingin bertanya / konsultasi properti.";
$waLink   = "https://wa.me/{$waNumber}?text=" . rawurlencode($waText);
?>

<section class="contact-hero">
  <div class="container contact-hero-inner">
    <span class="badge-brand">Kontak</span>
    <h1 class="contact-title">Hubungi <?= e($siteName) ?></h1>
    <p class="contact-subtitle">
      Untuk kerja sama atau pertanyaan umum, gunakan kanal di bawah. Untuk listing tertentu,
      buka detail properti lalu klik WhatsApp sales.
    </p>
  </div>
</section>

<div class="container">
  <section class="panel panel-lg contact-panel">
    <div class="kv contact-kv">

      <!-- Kolom 1 -->
      <article class="panel contact-box">
        <h2 class="section-title">Kontak Umum</h2>

        <div class="contact-info">
          <div><span class="label">Brand</span><span class="value"><?= e($siteName) ?></span></div>
          <div><span class="label">Email</span><span class="value"><?= e($email) ?></span></div>
          <div><span class="label">Telepon</span><span class="value"><?= e($phone) ?></span></div>
        </div>

        <div class="contact-actions">
          <a class="btn btn-accent" href="<?= e($waLink) ?>" target="_blank" rel="noopener">
            WhatsApp
          </a>
          <a class="btn btn-ghost" href="mailto:<?= e($email) ?>">Kirim Email</a>
          <a class="btn btn-ghost" href="tel:<?= e($phone) ?>">Telepon</a>
        </div>

        <p class="contact-tip">
          Tip: untuk konsultasi listing tertentu, buka detail properti lalu klik tombol WhatsApp sales.
        </p>
      </article>

      <!-- Kolom 2 -->
      <article class="panel contact-box contact-box-accent">
        <h2 class="section-title">Alamat & Jam Layanan</h2>

        <div class="contact-info">
          <div><span class="label">Alamat</span><span class="value"><?= e($address) ?></span></div>
        </div>

        <hr class="line" />

        <div class="contact-hours"><?= nl2br(e($hours)) ?></div>

        <div class="contact-actions">
          <a class="btn btn-accent" href="index.php">Lihat Listing</a>
          <a class="btn btn-ghost" href="about.php">Tentang RM Properti</a>
        </div>
      </article>

    </div>
  </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>

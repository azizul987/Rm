<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'Contact';
$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';

$email   = setting('contact_email', 'info@rmproperti.id') ?? 'info@rmproperti.id';
$phone   = setting('contact_phone', '08xxxxxxxxxx') ?? '08xxxxxxxxxx'; // format Indonesia umum
$address = setting('contact_address', 'Alamat kantor / area layanan (isi sendiri)') ?? 'Alamat kantor / area layanan (isi sendiri)';
$hours   = setting('contact_hours', "Senin–Sabtu: 09.00–17.00\nMinggu: By appointment") ?? "Senin–Sabtu: 09.00–17.00\nMinggu: By appointment";
$subtitle = setting(
  'contact_subtitle',
  'Untuk kerja sama atau pertanyaan umum, gunakan kanal di bawah. Untuk listing tertentu, buka detail properti lalu klik WhatsApp sales.'
) ?? 'Untuk kerja sama atau pertanyaan umum, gunakan kanal di bawah. Untuk listing tertentu, buka detail properti lalu klik WhatsApp sales.';
$tip = setting(
  'contact_tip',
  'Tip: untuk konsultasi listing tertentu, buka detail properti lalu klik tombol WhatsApp sales.'
) ?? 'Tip: untuk konsultasi listing tertentu, buka detail properti lalu klik tombol WhatsApp sales.';

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

$page_description = str_excerpt($subtitle, 155);
$page_og_type = 'website';
$page_canonical = base_url() . '/contact.php';

include __DIR__ . '/header.php';
?>

<section class="contact-hero">
  <div class="container contact-hero-inner">
    <span class="badge-brand">Kontak</span>
    <h1 class="contact-title">Hubungi <?= e($siteName) ?></h1>
    <p class="contact-subtitle">
      <?= e($subtitle) ?>
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
          <?= e($tip) ?>
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

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $siteName,
  'url' => base_url() . '/index.php',
  'logo' => abs_url(setting('logo_path', 'Assets/logo.png') ?? 'Assets/logo.png'),
  'contactPoint' => [
    '@type' => 'ContactPoint',
    'telephone' => $phone,
    'email' => $email,
    'contactType' => 'customer service',
    'areaServed' => 'ID',
    'availableLanguage' => ['id'],
  ],
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => $address,
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php include __DIR__ . '/footer.php'; ?>

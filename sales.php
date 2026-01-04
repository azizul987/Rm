<?php
require __DIR__ . '/data.php';
require __DIR__ . '/lib.php';

$idRaw = get_str_param('id');
$id = safe_id($idRaw);
$s = ($id !== '') ? find_sales($sales, $id) : null;

if (!$s) {
  http_response_code(404);
  $page_title = 'Sales tidak ditemukan';
  include __DIR__ . '/header.php';
  ?>
  <div class="panel">
    <h2>Sales tidak ditemukan</h2>
    <p class="muted">ID tidak valid atau data belum tersedia.</p>
    <div class="actions">
      <a class="action accent" href="<?= e(site_url('')) ?>">Kembali</a>
    </div>
  </div>
  <?php
  include __DIR__ . '/footer.php';
  exit;
}

$page_title = 'Profil Sales - ' . $s['name'];
$handled = array_values(array_filter($properties, fn($p) => $p['sales_id'] === $s['id']));

$salesPhoto = (file_exists(__DIR__ . '/' . $s['photo'])) ? $s['photo'] : svg_placeholder_data_uri($s['name']);
$waText = "Halo {$s['name']}, saya ingin konsultasi properti dari RM Properti.";

$page_description = str_excerpt((string)($s['bio'] ?? ''), 155);
$page_og_type = 'profile';
$page_canonical = site_url('sales.php?id=' . urlencode($s['id']));
if (!str_starts_with($salesPhoto, 'data:')) {
  $page_image = $salesPhoto;
}

include __DIR__ . '/header.php';
?>

<div class="panel">
  <div class="sales">
    <img src="<?= e($salesPhoto) ?>" alt="<?= e($s['name']) ?>" style="width:78px;height:78px;border-radius:18px" />
    <div>
      <div style="font-weight:950; font-size:18px"><?= e($s['name']) ?></div>
      <div class="muted"><?= e($s['title']) ?> • <?= (int)$s['experience_years'] ?> tahun pengalaman</div>
      <div class="muted" style="margin-top:6px">
        Area: <?= e(implode(', ', $s['areas'])) ?>
      </div>
    </div>
  </div>

  <hr class="line" />

  <h2>Tentang</h2>
  <p class="muted"><?= e($s['bio']) ?></p>

  <div class="actions">
    <a class="action accent" target="_blank" rel="noopener"
       href="<?= e(wa_link($s['whatsapp'], $waText)) ?>">WhatsApp</a>
    <a class="action" href="tel:<?= e($s['phone']) ?>">Telepon</a>
    <a class="action" href="mailto:<?= e($s['email']) ?>">Email</a>
  </div>
</div>

<div style="margin-top:16px">
  <span class="badge"><?= count($handled) ?> properti ditangani</span>
</div>

<section class="grid">
  <?php foreach ($handled as $p): ?>
    <?php
      $cover = first_image_or_placeholder($p['images'] ?? [], $p['title']);
      $slug = slugify((string)($p['title'] ?? 'properti'));
      $href = site_url('property/' . (int)$p['id'] . '/' . $slug);
    ?>
    <a class="card" href="<?= e($href) ?>">
      <div class="thumb">
        <img src="<?= e($cover) ?>" alt="<?= e($p['title']) ?>" />
      </div>
      <div class="card-body">
        <div class="card-title"><?= e($p['title']) ?></div>
        <div class="meta">
          <span><?= e($p['type']) ?></span>
          <span>•</span>
          <span><?= e($p['location']) ?></span>
        </div>
        <div class="price"><?= e(rupiah($p['price'])) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</section>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Person',
  'name' => $s['name'],
  'jobTitle' => $s['title'] ?? null,
  'url' => $page_canonical,
  'image' => !str_starts_with($salesPhoto, 'data:') ? abs_url($salesPhoto) : null,
  'telephone' => $s['phone'] ?? null,
  'email' => $s['email'] ?? null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php include __DIR__ . '/footer.php'; ?>

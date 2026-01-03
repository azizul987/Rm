<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  exit('Properti tidak ditemukan.');
}

$pdo = db();

// Ambil properti + sales
$st = $pdo->prepare("
  SELECT p.*,
         s.name AS sales_name,
         s.title AS sales_title,
         s.phone AS sales_phone,
         s.whatsapp AS sales_whatsapp,
         s.email AS sales_email,
         s.photo_path AS sales_photo,
         s.bio AS sales_bio
  FROM properties p
  LEFT JOIN sales s ON s.id = p.sales_id
  WHERE p.id = ?
  LIMIT 1
");
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
  http_response_code(404);
  exit('Properti tidak ditemukan.');
}

// Public hanya tampilkan yang publish
if (($p['status'] ?? '') !== 'active') {
  http_response_code(404);
  exit('Properti tidak tersedia.');
}

// Ambil gambar (cover + gallery)
$st = $pdo->prepare("
  SELECT *
  FROM property_images
  WHERE property_id=?
  ORDER BY sort_order ASC, id ASC
");
$st->execute([$id]);
$imgs = $st->fetchAll();

// Kumpulkan path gambar (cover + gallery)
$images = [];
foreach ($imgs as $row) {
  if (!empty($row['path'])) $images[] = $row['path'];
}
$imgCount = count($images);
$firstImg = $images[0] ?? null;

// Features
$features = [];
if (!empty($p['features_json'])) {
  $tmp = json_decode($p['features_json'], true);
  if (is_array($tmp)) $features = $tmp;
}

// helper WA (lebih “pasti”)
function wa_link(?string $wa, ?string $text = null): ?string {
  if (!$wa) return null;

  $digits = preg_replace('/\D+/', '', $wa);

  // 08xxx -> 628xxx
  if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
  // 8xxx -> 628xxx
  if (str_starts_with($digits, '8')) $digits = '62' . $digits;

  if ($digits === '') return null;

  $url = "https://wa.me/" . $digits;
  if ($text) $url .= "?text=" . rawurlencode($text);
  return $url;
}

$page_title = $p['title'] ?? 'Detail Properti';
include __DIR__ . '/header.php';

// pesan WA default
$waText = "Halo, saya tertarik dengan properti: " . ($p['title'] ?? '') . " (" . ($p['location'] ?? '') . "). Boleh info lebih lanjut?";
?>

<section class="panel property-detail">

  <!-- Header info -->
  <div class="property-head">
    <div class="property-head-left">
      <h1 class="property-title"><?= e($p['title']) ?></h1>

      <div class="property-meta muted">
        <span><?= e($p['type']) ?></span>
        <span class="dot">•</span>
        <span><?= e($p['location']) ?></span>
        <span class="dot">•</span>
        <span><?= (int)$p['beds'] ?> KT / <?= (int)$p['baths'] ?> KM</span>
      </div>
    </div>

    <div class="property-head-right">
      <div class="property-price"><?= e(rupiah((int)$p['price'])) ?></div>
      <div class="property-size muted">
        LT <?= (int)$p['land'] ?> m² <span class="dot">•</span> LB <?= (int)$p['building'] ?> m²
      </div>
    </div>
  </div>

  <hr class="line" />

  <!-- MEDIA CAROUSEL -->
  <?php if ($firstImg): ?>
    <div class="property-media">

      <div class="mc-stage" aria-label="Foto properti">
        <img
          class="mc-main"
          src="<?= e($firstImg) ?>"
          alt="<?= e($p['title'] ?? 'Foto properti') ?>"
          loading="eager"
        />

        <?php if ($imgCount > 1): ?>
          <button class="mc-arrow prev" type="button" aria-label="Foto sebelumnya">‹</button>
          <button class="mc-arrow next" type="button" aria-label="Foto berikutnya">›</button>
        <?php endif; ?>

        <button class="mc-full" type="button" aria-label="Lihat foto ukuran penuh">Lihat penuh</button>

        <div class="mc-count" aria-live="polite">
          <span class="mc-current">1</span> / <span class="mc-total"><?= (int)$imgCount ?></span>
        </div>
      </div>

      <?php if ($imgCount > 1): ?>
        <div class="mc-thumbs" aria-label="Thumbnail foto">
          <?php foreach ($images as $i => $src): ?>
            <button
              type="button"
              class="mc-thumb <?= $i === 0 ? 'is-active' : '' ?>"
              data-index="<?= (int)$i ?>"
              data-src="<?= e($src) ?>"
              aria-label="Lihat foto <?= (int)($i + 1) ?>"
            >
              <img src="<?= e($src) ?>" alt="Thumbnail <?= (int)($i + 1) ?>" loading="lazy" />
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- LIGHTBOX -->
      <div class="lightbox" id="lightbox" hidden>
        <div class="lb-backdrop" data-close></div>

        <div class="lb-dialog" role="dialog" aria-modal="true" aria-label="Pratinjau foto">
          <button class="lb-close" type="button" data-close aria-label="Tutup">×</button>

          <?php if ($imgCount > 1): ?>
            <button class="lb-nav prev" type="button" aria-label="Foto sebelumnya">‹</button>
            <button class="lb-nav next" type="button" aria-label="Foto berikutnya">›</button>
          <?php endif; ?>

          <img class="lb-img" src="" alt="Foto properti ukuran penuh" />
          <a class="lb-open" href="#" target="_blank" rel="noopener">Buka file asli</a>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <div class="property-body">

    <!-- Konten utama -->
    <div class="property-content">
      <?php if (!empty($p['description'])): ?>
        <div class="property-section">
          <h2 class="property-section-title">Deskripsi</h2>
          <div class="muted property-text"><?= e($p['description']) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($features): ?>
        <div class="property-section">
          <h2 class="property-section-title">Fitur</h2>
          <ul class="list property-features">
            <?php foreach ($features as $f): ?>
              <li><?= e((string)$f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar (Sales) -->
    <aside class="property-aside">
      <div class="panel property-sales">
        <h2 class="property-section-title">Hubungi Sales</h2>

        <?php if (!empty($p['sales_name'])): ?>
          <div class="sales-head">
            <?php if (!empty($p['sales_photo'])): ?>
              <img class="sales-photo" src="<?= e($p['sales_photo']) ?>" alt="<?= e($p['sales_name']) ?>" loading="lazy" />
            <?php else: ?>
              <div class="sales-photo sales-photo-fallback" aria-hidden="true">S</div>
            <?php endif; ?>

            <div class="sales-info">
              <div class="sales-name"><?= e($p['sales_name']) ?></div>
              <div class="muted"><?= e($p['sales_title'] ?? 'Sales Consultant') ?></div>
            </div>
          </div>

          <div class="sales-contacts muted">
            <div>Telp: <strong><?= e($p['sales_phone'] ?? '-') ?></strong></div>
            <div>WA: <strong><?= e($p['sales_whatsapp'] ?? '-') ?></strong></div>
            <?php if (!empty($p['sales_email'])): ?>
              <div>Email: <strong><?= e($p['sales_email']) ?></strong></div>
            <?php endif; ?>
          </div>

          <div class="actions sales-actions">
            <?php $wa = wa_link($p['sales_whatsapp'] ?? null, $waText); ?>
            <?php if ($wa): ?>
              <a class="action accent" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>

            <?php if (!empty($p['sales_email'])): ?>
              <a class="action" href="mailto:<?= e($p['sales_email']) ?>">Email</a>
            <?php endif; ?>
          </div>

          <?php if (!empty($p['sales_bio'])): ?>
            <div class="muted property-text sales-bio"><?= e($p['sales_bio']) ?></div>
          <?php endif; ?>

        <?php else: ?>
          <div class="muted">Sales belum ditautkan untuk listing ini.</div>
        <?php endif; ?>
      </div>
    </aside>

  </div>

  <div class="actions property-back">
    <a class="action" href="index.php">← Kembali ke Listing</a>
  </div>

</section>

<script>
(function(){
  const stage = document.querySelector('.mc-stage');
  if (!stage) return;

  const mainImg = stage.querySelector('.mc-main');
  const thumbs = Array.from(document.querySelectorAll('.mc-thumb'));
  const prevBtn = stage.querySelector('.mc-arrow.prev');
  const nextBtn = stage.querySelector('.mc-arrow.next');
  const fullBtn = stage.querySelector('.mc-full');

  const currentEl = stage.querySelector('.mc-current');
  const totalEl = stage.querySelector('.mc-total');

  // Lightbox
  const lb = document.getElementById('lightbox');
  const lbImg = lb ? lb.querySelector('.lb-img') : null;
  const lbOpen = lb ? lb.querySelector('.lb-open') : null;
  const lbPrev = lb ? lb.querySelector('.lb-nav.prev') : null;
  const lbNext = lb ? lb.querySelector('.lb-nav.next') : null;

  const sources = thumbs.length
    ? thumbs.map(t => t.dataset.src)
    : (mainImg?.src ? [mainImg.src] : []);

  let index = 0;

  function setActive(i){
    if (!sources.length || !mainImg) return;

    index = (i + sources.length) % sources.length;
    const src = sources[index];

    mainImg.src = src;

    // update counter
    if (currentEl) currentEl.textContent = String(index + 1);
    if (totalEl) totalEl.textContent = String(sources.length);

    // active thumb
    thumbs.forEach((t, n) => t.classList.toggle('is-active', n === index));

    // update lightbox if open
    if (lb && !lb.hidden && lbImg){
      lbImg.src = src;
      if (lbOpen) lbOpen.href = src;
    }
  }

  function openLightbox(){
    if (!lb || !lbImg) return;
    lb.hidden = false;
    lbImg.src = sources[index] || (mainImg ? mainImg.src : '');
    if (lbOpen) lbOpen.href = lbImg.src;
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox(){
    if (!lb) return;
    lb.hidden = true;
    document.body.style.overflow = '';
  }

  // thumb click
  thumbs.forEach(t => {
    t.addEventListener('click', () => {
      const i = Number(t.dataset.index || 0);
      setActive(i);
    });
  });

  // prev/next
  if (prevBtn) prevBtn.addEventListener('click', () => setActive(index - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => setActive(index + 1));

  // open full
  if (fullBtn) fullBtn.addEventListener('click', openLightbox);
  stage.addEventListener('click', (e) => {
    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
    if (tag === 'button') return;
    openLightbox();
  });

  // close lightbox
  if (lb){
    lb.addEventListener('click', (e) => {
      if (!e.target) return;
      if (e.target.matches('[data-close]')) closeLightbox();
    });

    const closeBtn = lb.querySelector('.lb-close');
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  }

  // lightbox prev/next
  if (lbPrev) lbPrev.addEventListener('click', (e) => { e.stopPropagation(); setActive(index - 1); });
  if (lbNext) lbNext.addEventListener('click', (e) => { e.stopPropagation(); setActive(index + 1); });

  // keyboard (saat lightbox terbuka)
  document.addEventListener('keydown', (e) => {
    if (!lb || lb.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') setActive(index - 1);
    if (e.key === 'ArrowRight') setActive(index + 1);
  });

  // init
  setActive(0);
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

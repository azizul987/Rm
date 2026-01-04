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
         s.bio AS sales_bio,
         u.role AS creator_role,
         u.status AS creator_status
  FROM properties p
  LEFT JOIN sales s ON s.id = p.sales_id
  LEFT JOIN users u ON u.id = p.created_by
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
// Sembunyikan jika dibuat editor yang dibekukan
if (($p['creator_role'] ?? '') === 'editor' && ($p['creator_status'] ?? '') === 'frozen') {
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

$videos = [];
if (!empty($p['videos_json'])) {
  $tmp = json_decode($p['videos_json'], true);
  if (is_array($tmp)) $videos = $tmp;
}

$videoItems = [];
foreach ($videos as $v) {
  $raw = trim((string)$v);
  if ($raw === '') continue;
  $id = youtube_id($raw);
  if (!$id && preg_match('/^[A-Za-z0-9_-]{11}$/', $raw)) $id = $raw;
  if (!$id) continue;

  $videoItems[] = [
    'type' => 'video',
    'id' => $id,
    'thumb' => 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg',
    'embed' => 'https://www.youtube.com/embed/' . $id,
  ];
}

$mediaItems = [];
foreach ($images as $src) {
  if ($src && !str_starts_with($src, 'data:')) {
    $src = abs_url($src);
  }
  $mediaItems[] = [
    'type' => 'image',
    'src' => $src,
  ];
}
foreach ($videoItems as $v) {
  $mediaItems[] = $v;
}
$mediaCount = count($mediaItems);
$firstMedia = $mediaItems[0] ?? null;
$initialImg = $firstImg;
if ($initialImg && !str_starts_with($initialImg, 'data:')) {
  $initialImg = abs_url($initialImg);
}
if (!$initialImg && $firstMedia && $firstMedia['type'] === 'video') {
  $initialImg = $firstMedia['thumb'];
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
$slug = slugify((string)($p['title'] ?? 'properti'));
$descSource = (string)($p['description'] ?? '');
if ($descSource === '' && !empty($features)) {
  $descSource = implode(', ', array_map('strval', $features));
}
if ($descSource === '') {
  $descSource = ($p['title'] ?? 'Properti') . ' di ' . ($p['location'] ?? '');
}
$page_description = str_excerpt($descSource, 155);
$page_og_type = 'product';
$page_canonical = 'property/' . $id . '/' . $slug;
if (!empty($firstImg) && !str_starts_with($firstImg, 'data:')) {
  $page_image = abs_url($firstImg);
}
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
      <div class="property-price">
        <span class="property-price-label">Harga</span>
        <span class="property-price-value"><?= e(rupiah((int)$p['price'])) ?></span>
      </div>
      <div class="property-size">
        <span class="property-size-item">LT <?= (int)$p['land'] ?> m²</span>
        <span class="dot">•</span>
        <span class="property-size-item">LB <?= (int)$p['building'] ?> m²</span>
      </div>
    </div>
  </div>

  <hr class="line" />

  <!-- MEDIA CAROUSEL -->
  <?php if ($mediaCount > 0): ?>
    <div class="property-media">

      <div class="mc-stage" aria-label="Foto properti">
        <img
          class="mc-main"
          src="<?= e($initialImg ?: '') ?>"
          alt="<?= e($p['title'] ?? 'Media properti') ?>"
          loading="eager"
        />

        <div class="mc-video" hidden>
          <iframe
            class="mc-video-frame"
            src=""
            title="Video properti"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>
        </div>

        <?php if ($mediaCount > 1): ?>
          <button class="mc-arrow prev" type="button" aria-label="Foto sebelumnya">‹</button>
          <button class="mc-arrow next" type="button" aria-label="Foto berikutnya">›</button>
        <?php endif; ?>

        <button class="mc-full" type="button" aria-label="Lihat foto ukuran penuh">Lihat penuh</button>

        <div class="mc-count" aria-live="polite">
          <span class="mc-current">1</span> / <span class="mc-total"><?= (int)$mediaCount ?></span>
        </div>
      </div>

      <?php if ($mediaCount > 1): ?>
        <div class="mc-thumbs" aria-label="Thumbnail foto">
          <?php foreach ($mediaItems as $i => $item): ?>
            <button
              type="button"
              class="mc-thumb <?= $i === 0 ? 'is-active' : '' ?> <?= ($item['type'] ?? '') === 'video' ? 'is-video' : '' ?>"
              data-index="<?= (int)$i ?>"
              data-type="<?= e($item['type']) ?>"
              <?php if (($item['type'] ?? '') === 'video'): ?>
                data-video="<?= e($item['id']) ?>"
              <?php else: ?>
                data-src="<?= e($item['src']) ?>"
              <?php endif; ?>
              aria-label="<?= ($item['type'] ?? '') === 'video' ? 'Lihat video' : 'Lihat foto ' . (int)($i + 1) ?>"
            >
              <img
                src="<?= e(($item['type'] ?? '') === 'video' ? $item['thumb'] : $item['src']) ?>"
                alt="<?= ($item['type'] ?? '') === 'video' ? 'Thumbnail video' : 'Thumbnail ' . (int)($i + 1) ?>"
                loading="lazy"
              />
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
    <a class="action" href="<?= e(site_url('')) ?>">← Kembali ke Listing</a>
  </div>

</section>

<?php
$imageUrls = [];
foreach ($images as $src) {
  if (!str_starts_with($src, 'data:')) $imageUrls[] = abs_url($src);
}
$schemaImage = $imageUrls ?: [abs_url($page_image ?? 'Assets/logo.png')];
$schema = [
  '@context' => 'https://schema.org',
  '@type' => 'RealEstateListing',
  'name' => $p['title'] ?? 'Properti',
  'description' => $page_description,
  'url' => $page_canonical,
  'image' => $schemaImage,
  'address' => [
    '@type' => 'PostalAddress',
    'addressLocality' => $p['location'] ?? '',
  ],
  'offers' => [
    '@type' => 'Offer',
    'priceCurrency' => 'IDR',
    'price' => (int)($p['price'] ?? 0),
    'availability' => 'https://schema.org/InStock',
  ],
];
?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
(function(){
  const stage = document.querySelector('.mc-stage');
  if (!stage) return;

  const mainImg = stage.querySelector('.mc-main');
  const thumbs = Array.from(document.querySelectorAll('.mc-thumb'));
  const prevBtn = stage.querySelector('.mc-arrow.prev');
  const nextBtn = stage.querySelector('.mc-arrow.next');
  const fullBtn = stage.querySelector('.mc-full');
  const videoWrap = stage.querySelector('.mc-video');
  const videoFrame = stage.querySelector('.mc-video-frame');

  const currentEl = stage.querySelector('.mc-current');
  const totalEl = stage.querySelector('.mc-total');

  // Lightbox
  const lb = document.getElementById('lightbox');
  const lbImg = lb ? lb.querySelector('.lb-img') : null;
  const lbOpen = lb ? lb.querySelector('.lb-open') : null;
  const lbPrev = lb ? lb.querySelector('.lb-nav.prev') : null;
  const lbNext = lb ? lb.querySelector('.lb-nav.next') : null;

  const items = thumbs.length
    ? thumbs.map(t => ({
        type: t.dataset.type || 'image',
        src: t.dataset.src || '',
        video: t.dataset.video || '',
      }))
    : (mainImg?.src ? [{ type: 'image', src: mainImg.src }] : []);

  let index = 0;
  let currentType = 'image';

  function setActive(i){
    if (!items.length || !mainImg) return;

    index = (i + items.length) % items.length;
    const item = items[index];
    currentType = item.type || 'image';

    if (currentType === 'video') {
      mainImg.style.display = 'none';
      if (videoWrap) videoWrap.hidden = false;
      if (videoFrame) {
        const id = item.video || '';
        videoFrame.src = id ? ('https://www.youtube.com/embed/' + id + '?rel=0') : '';
      }
      if (lb && !lb.hidden) closeLightbox();
      if (fullBtn) {
        fullBtn.setAttribute('aria-disabled', 'true');
        fullBtn.style.pointerEvents = 'none';
        fullBtn.style.opacity = '0.5';
      }
    } else {
      if (videoFrame) videoFrame.src = '';
      if (videoWrap) videoWrap.hidden = true;
      mainImg.style.display = '';
      mainImg.src = item.src || '';
      if (fullBtn) {
        fullBtn.removeAttribute('aria-disabled');
        fullBtn.style.pointerEvents = '';
        fullBtn.style.opacity = '';
      }
    }

    // update counter
    if (currentEl) currentEl.textContent = String(index + 1);
    if (totalEl) totalEl.textContent = String(items.length);

    // active thumb
    thumbs.forEach((t, n) => t.classList.toggle('is-active', n === index));

    // update lightbox if open
    if (lb && !lb.hidden && lbImg && currentType === 'image'){
      lbImg.src = item.src || '';
      if (lbOpen) lbOpen.href = item.src || '';
    }
  }

  function openLightbox(){
    if (!lb || !lbImg) return;
    if (currentType !== 'image') return;
    lb.hidden = false;
    lbImg.src = items[index]?.src || (mainImg ? mainImg.src : '');
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

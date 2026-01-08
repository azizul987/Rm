<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'Properti';

// 1) Ambil Parameter Pencarian
$q        = trim(get_str('q'));
$type     = trim(get_str('type'));
$location = trim(get_str('location'));
$listingType = trim(get_str('listing_type'));
$listingLabels = [
  'primary' => 'Dijual Primary',
  'secondary' => 'Dijual Secondary',
  'kavling' => 'Kavling Tanah',
  'takeover' => 'Take Over Rumah',
  'sewa' => 'Disewakan',
];

$heroBadge = setting('home_hero_badge', 'PT. RMI SUCCESS Mandiri') ?? 'PT. RMI SUCCESS Mandiri';
$heroTitle = setting('home_hero_title', 'Mitra Terpercaya Hunian & Investasi Anda') ?? 'Mitra Terpercaya Hunian & Investasi Anda';
$heroSubtitle = setting(
  'home_hero_subtitle',
  'Wujudkan kesuksesan masa depan dengan properti berkualitas, lokasi strategis, dan legalitas yang terjamin aman.'
) ?? 'Wujudkan kesuksesan masa depan dengan properti berkualitas, lokasi strategis, dan legalitas yang terjamin aman.';
$searchPlaceholder = setting('home_search_placeholder', 'Cari wilayah / nama property...') ?? 'Cari wilayah / nama property...';
$typeAllLabel = setting('home_type_all_label', 'Semua Tipe') ?? 'Semua Tipe';
$locationAllLabel = setting('home_location_all_label', 'Semua Lokasi') ?? 'Semua Lokasi';
$ctaLabel = setting('home_cta_label', 'Temukan Properti') ?? 'Temukan Properti';
$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';

$bannerImages = [];
for ($i = 1; $i <= 4; $i++) {
  $path = trim((string)(setting('home_banner_' . $i, '') ?? ''));
  if ($path !== '') {
    if (!str_starts_with($path, 'data:')) {
      $path = abs_url($path);
    }
    $bannerImages[] = $path;
  }
}
$bannerInterval = (int)(setting('home_banner_interval', '5000') ?? 5000);
if ($bannerInterval < 2000) $bannerInterval = 2000;
if ($bannerInterval > 20000) $bannerInterval = 20000;

$page_description = str_excerpt($heroSubtitle, 155);
$page_og_type = 'website';

$pdo = db();

// Pagination
$page = max(1, get_int('page', 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// 2) Ambil Data untuk Dropdown (Filter)
$publicWhere = "p.status='active' AND (p.created_by IS NULL OR u.role <> 'editor' OR u.status='active')";
$types = $pdo->query("
  SELECT DISTINCT p.type
  FROM properties p
  LEFT JOIN users u ON u.id = p.created_by
  WHERE {$publicWhere}
  ORDER BY p.type
")->fetchAll();
$locs  = $pdo->query("
  SELECT DISTINCT p.location
  FROM properties p
  LEFT JOIN users u ON u.id = p.created_by
  WHERE {$publicWhere}
  ORDER BY p.location
")->fetchAll();

// 3) Query Utama Pencarian Properti
$whereParts = ["p.status='active'", "(p.created_by IS NULL OR u.role <> 'editor' OR u.status='active')"];
$params = [];

// Logika Filter
if ($q !== '') {
  $whereParts[] = "(p.title LIKE ? OR p.location LIKE ? OR p.type LIKE ?)";
  $like = "%{$q}%";
  $params = array_merge($params, [$like, $like, $like]);
}

if ($type !== '') {
  $whereParts[] = "p.type = ?";
  $params[] = $type;
}

if ($location !== '') {
  $whereParts[] = "p.location = ?";
  $params[] = $location;
}

if ($listingType !== '') {
  $whereParts[] = "p.listing_type = ?";
  $params[] = $listingType;
}

$whereSql = implode(' AND ', $whereParts);
$baseFrom = "FROM properties p LEFT JOIN users u ON u.id = p.created_by";

$countSql = "SELECT COUNT(*) {$baseFrom} WHERE {$whereSql}";
$st = $pdo->prepare($countSql);
$st->execute($params);
$total = (int)$st->fetchColumn();

$hasViewTracking = db_table_exists('property_views');
$viewSelect = $hasViewTracking
  ? ", (SELECT COUNT(*) FROM property_views pv WHERE pv.property_id = p.id) AS view_count"
  : ", 0 AS view_count";

$sql = "
  SELECT p.*,
    (
      SELECT path
      FROM property_images pi
      WHERE pi.property_id = p.id
      ORDER BY pi.sort_order ASC, pi.id ASC
      LIMIT 1
    ) AS cover
    {$viewSelect}
  {$baseFrom}
  WHERE {$whereSql}
  ORDER BY p.id DESC
  LIMIT ? OFFSET ?
";
$paramsPage = array_merge($params, [$limit, $offset]);

$st = $pdo->prepare($sql);
$st->execute($paramsPage);
$rows = $st->fetchAll();

$hasMore = ($offset + $limit) < $total;
$baseParams = [];
if ($q !== '') $baseParams['q'] = $q;
if ($type !== '') $baseParams['type'] = $type;
if ($location !== '') $baseParams['location'] = $location;
if ($listingType !== '') $baseParams['listing_type'] = $listingType;

if ($type !== '' || $location !== '' || $q !== '' || $listingType !== '') {
  $parts = [];
  if ($type !== '') $parts[] = 'Tipe ' . $type;
  if ($location !== '') $parts[] = 'Lokasi ' . $location;
  if ($q !== '') $parts[] = 'Pencarian "' . $q . '"';
  if ($listingType !== '') {
    $label = $listingLabels[$listingType] ?? $listingType;
    $parts[] = $label;
  }
  $page_title = 'Listing Properti' . ($parts ? ' - ' . implode(' • ', $parts) : '');
  $page_description = str_excerpt(
    'Temukan properti ' . ($parts ? implode(', ', $parts) : 'terbaru') . ' di ' . $siteName . '.',
    155
  );
}

if ($q !== '' || $type !== '' || $location !== '' || $listingType !== '' || $page > 1) {
  $page_robots = 'noindex, follow';
}
$page_canonical = site_url('');

// fallback image (SVG)
$fallbackSvg = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
     <rect width="100%" height="100%" fill="#E0F2FE"/>
     <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#009ADE"
           text-anchor="middle" dominant-baseline="middle">RM Properti</text>
   </svg>'
);

function render_cards(array $rows, string $fallbackSvg, bool $showViews): string {
  ob_start();
  foreach ($rows as $p) {
    $slug = slugify((string)($p['title'] ?? 'properti'));
    $href = site_url('property/' . (int)$p['id'] . '/' . $slug);
    $cover = $p['cover'] ?: $fallbackSvg;
    if ($cover && !str_starts_with($cover, 'data:')) {
      $cover = abs_url($cover);
    }
    $listingType = (string)($p['listing_type'] ?? '');
    $isKavling = ($listingType === 'kavling');
    ?>
    <a class="card" href="<?= e($href) ?>">
      <div class="thumb">
        <img
          src="<?= e($cover) ?>"
          alt="<?= e($p['title']) ?>"
        />
      </div>

      <div class="card-body">
        <div class="card-title"><?= e($p['title']) ?></div>

        <div class="meta">
          <span><?= e($p['type']) ?></span>
          <span>•</span>
          <span><?= e($p['location']) ?></span>
        </div>

        <div class="meta">
          <?php if ($isKavling): ?>
            <span class="meta-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 7h18M3 17h18"/>
                <path d="M7 7v10M17 7v10"/>
              </svg>
              Luas Tanah <?= (int)($p['land'] ?? 0) ?> m²
            </span>
          <?php else: ?>
            <span class="meta-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="10" width="18" height="6" rx="2"/>
                <rect x="5" y="7" width="7" height="3" rx="1.5"/>
                <path d="M3 16v4M21 16v4"/>
              </svg>
              <?= (int)$p['beds'] ?> Kamar Tidur
            </span>

            <span class="meta-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 6h6a6 6 0 0 1 6 6v1"/>
                <path d="M14 7l2-2M17 9l2-2"/>
                <path d="M7 14c0 1-1 2-1 2s-1-1-1-2 1-2 1-2 1 1 1 2z"/>
                <path d="M11 14c0 1-1 2-1 2s-1-1-1-2 1-2 1-2 1 1 1 2z"/>
                <path d="M15 14c0 1-1 2-1 2s-1-1-1-2 1-2 1-2 1 1 1 2z"/>
              </svg>
              <?= (int)$p['baths'] ?> Kamar Mandi
            </span>
          <?php endif; ?>
        </div>

        <?php if ($showViews): ?>
          <div class="meta">
            <span class="meta-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <?= (int)($p['view_count'] ?? 0) ?>
            </span>
          </div>
        <?php endif; ?>

        <div class="price"><?= e(rupiah((int)$p['price'])) ?></div>
      </div>
    </a>
    <?php
  }
  return (string)ob_get_clean();
}

$isAjax = get_str('ajax') === '1';
if ($isAjax) {
  $nextUrl = $hasMore
    ? (site_url('') . '?' . http_build_query(array_merge($baseParams, ['page' => $page + 1])))
    : '';
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'html' => render_cards($rows, $fallbackSvg, $hasViewTracking),
    'has_more' => $hasMore,
    'next_url' => $nextUrl,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

include __DIR__ . '/header.php';
?>

<?php if (!empty($bannerImages)): ?>
  <section class="home-banner" aria-label="Banner promosi">
    <div class="home-banner-track" data-interval="<?= (int)$bannerInterval ?>">
      <?php foreach ($bannerImages as $i => $src): ?>
        <div class="home-banner-slide <?= $i === 0 ? 'active' : '' ?>">
          <img src="<?= e($src) ?>" alt="Banner <?= (int)($i + 1) ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($bannerImages) > 1): ?>
      <button class="home-banner-nav prev" type="button" aria-label="Banner sebelumnya">‹</button>
      <button class="home-banner-nav next" type="button" aria-label="Banner berikutnya">›</button>
      <div class="home-banner-dots" role="tablist" aria-label="Navigasi banner">
        <?php foreach ($bannerImages as $i => $src): ?>
          <button class="home-banner-dot <?= $i === 0 ? 'active' : '' ?>" type="button" data-index="<?= (int)$i ?>" aria-label="Banner <?= (int)($i + 1) ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div class="banner-modal" aria-hidden="true" role="dialog" aria-label="Banner penuh">
    <div class="banner-modal-inner" role="document">
      <button class="banner-modal-close" type="button" aria-label="Tutup banner">×</button>
      <img class="banner-modal-img" src="" alt="Banner penuh" />
    </div>
  </div>
<?php endif; ?>

<!-- HERO: background full width, tapi konten pakai container -->
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <span class="badge-brand"><?= e($heroBadge) ?></span>
      <h1><?= e($heroTitle) ?></h1>
      <p><?= e($heroSubtitle) ?></p>
    </div>

    <form class="filters" method="get" action="<?= e(site_url('')) ?>">
      <?php if ($listingType !== ''): ?>
        <input type="hidden" name="listing_type" value="<?= e($listingType) ?>">
      <?php endif; ?>
      <input class="input" type="text" name="q" placeholder="<?= e($searchPlaceholder) ?>" value="<?= e($q) ?>" />

      <select class="select" name="type">
        <option value=""><?= e($typeAllLabel) ?></option>
        <?php foreach ($types as $t): ?>
          <?php $val = (string)($t['type'] ?? ''); ?>
          <option value="<?= e($val) ?>" <?= ($type === $val) ? 'selected' : '' ?>>
            <?= e($val) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="select" name="location">
        <option value=""><?= e($locationAllLabel) ?></option>
        <?php foreach ($locs as $l): ?>
          <?php $val = (string)($l['location'] ?? ''); ?>
          <option value="<?= e($val) ?>" <?= ($location === $val) ? 'selected' : '' ?>>
            <?= e($val) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-accent" type="submit"><?= e($ctaLabel) ?></button>
    </form>

    <div class="listing-tabs" role="tablist" aria-label="Filter listing">
      <?php
        $tabs = [
          '' => 'Semua',
          'primary' => $listingLabels['primary'],
          'secondary' => $listingLabels['secondary'],
          'kavling' => $listingLabels['kavling'],
          'takeover' => $listingLabels['takeover'],
          'sewa' => $listingLabels['sewa'],
        ];
        $tabBase = [];
        if ($q !== '') $tabBase['q'] = $q;
        if ($type !== '') $tabBase['type'] = $type;
        if ($location !== '') $tabBase['location'] = $location;
      ?>
      <?php foreach ($tabs as $key => $label): ?>
        <?php
          $paramsTab = $tabBase;
          if ($key !== '') $paramsTab['listing_type'] = $key;
          $href = site_url('') . ($paramsTab ? '?' . http_build_query($paramsTab) : '');
          $active = ($listingType === $key);
        ?>
        <a class="listing-tab <?= $active ? 'active' : '' ?>" href="<?= e($href) ?>" role="tab" aria-selected="<?= $active ? 'true' : 'false' ?>">
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- KONTEN BIASA: wajib dibungkus container -->
<div class="container">
  <div class="results-count">
    Menampilkan <?= count($rows) ?> dari <?= $total ?> properti
  </div>

  <section class="grid">
    <?= render_cards($rows, $fallbackSvg, $hasViewTracking) ?>
  </section>

  <?php if ($hasMore): ?>
    <?php $nextUrl = site_url('') . '?' . http_build_query(array_merge($baseParams, ['page' => $page + 1])); ?>
    <div class="load-more">
      <a class="btn btn-load-more" id="load-more" href="<?= e($nextUrl) ?>" data-next-url="<?= e($nextUrl) ?>">
        Muat lagi
      </a>
    </div>
  <?php endif; ?>
</div>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => $siteName,
  'url' => site_url(''),
  'potentialAction' => [
    '@type' => 'SearchAction',
    'target' => site_url('') . '?q={search_term_string}',
    'query-input' => 'required name=search_term_string',
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
(function () {
  const btn = document.getElementById('load-more');
  const grid = document.querySelector('.grid');
  if (!btn || !grid) return;

  let loading = false;
  btn.addEventListener('click', async function (e) {
    e.preventDefault();
    if (loading) return;

    const nextUrl = btn.dataset.nextUrl || btn.getAttribute('href');
    if (!nextUrl) return;

    loading = true;
    btn.textContent = 'Memuat...';

    try {
      const sep = nextUrl.includes('?') ? '&' : '?';
      const res = await fetch(nextUrl + sep + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) throw new Error('Load failed');
      const data = await res.json();
      if (data && data.html) {
        grid.insertAdjacentHTML('beforeend', data.html);
      }
      if (data && data.has_more && data.next_url) {
        btn.dataset.nextUrl = data.next_url;
        btn.setAttribute('href', data.next_url);
        btn.textContent = 'Muat lagi';
      } else {
        btn.remove();
      }
    } catch (err) {
      btn.textContent = 'Muat lagi';
    } finally {
      loading = false;
    }
  });
})();
</script>

<script>
(function () {
  const track = document.querySelector('.home-banner-track');
  if (!track) return;

  const slides = Array.from(track.querySelectorAll('.home-banner-slide'));
  const dots = Array.from(document.querySelectorAll('.home-banner-dot'));
  const btnPrev = document.querySelector('.home-banner-nav.prev');
  const btnNext = document.querySelector('.home-banner-nav.next');
  const modal = document.querySelector('.banner-modal');
  const modalImg = modal ? modal.querySelector('.banner-modal-img') : null;
  const modalClose = modal ? modal.querySelector('.banner-modal-close') : null;
  if (slides.length === 0) return;

  let index = 0;
  let timer = null;
  let startX = 0;
  let deltaX = 0;
  let dragging = false;
  const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const intervalMs = Number(track.getAttribute('data-interval')) || 5000;

  function setActive(next) {
    slides[index].classList.remove('active');
    if (dots[index]) {
      dots[index].classList.remove('active');
      dots[index].setAttribute('aria-selected', 'false');
    }
    index = next;
    slides[index].classList.add('active');
    if (dots[index]) {
      dots[index].classList.add('active');
      dots[index].setAttribute('aria-selected', 'true');
    }
  }

  function start() {
    if (prefersReduced || slides.length < 2) return;
    timer = setInterval(() => {
      const next = (index + 1) % slides.length;
      setActive(next);
    }, intervalMs);
  }

  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
  }

  function openModal(src) {
    if (!modal || !modalImg) return;
    modalImg.src = src;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    stop();
  }

  function closeModal() {
    if (!modal || !modalImg) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    modalImg.src = '';
    document.body.classList.remove('modal-open');
    start();
  }

  if (dots.length) {
    dots.forEach((dot, i) => {
      dot.addEventListener('click', function () {
        stop();
        setActive(i);
        start();
      });
    });
  }

  if (btnPrev) {
    btnPrev.addEventListener('click', function () {
      stop();
      const prev = (index - 1 + slides.length) % slides.length;
      setActive(prev);
      start();
    });
  }
  if (btnNext) {
    btnNext.addEventListener('click', function () {
      stop();
      const next = (index + 1) % slides.length;
      setActive(next);
      start();
    });
  }

  track.addEventListener('mouseenter', stop);
  track.addEventListener('mouseleave', start);

  function onStart(x) {
    dragging = true;
    startX = x;
    deltaX = 0;
    stop();
  }
  function onMove(x) {
    if (!dragging) return;
    deltaX = x - startX;
  }
  function onEnd() {
    if (!dragging) return;
    const threshold = 40;
    if (deltaX > threshold) {
      const prev = (index - 1 + slides.length) % slides.length;
      setActive(prev);
    } else if (deltaX < -threshold) {
      const next = (index + 1) % slides.length;
      setActive(next);
    }
    dragging = false;
    start();
  }

  track.addEventListener('mousedown', (e) => onStart(e.clientX));
  window.addEventListener('mousemove', (e) => onMove(e.clientX));
  window.addEventListener('mouseup', onEnd);

  track.addEventListener('touchstart', (e) => {
    if (!e.touches || !e.touches[0]) return;
    onStart(e.touches[0].clientX);
  }, { passive: true });
  track.addEventListener('touchmove', (e) => {
    if (!e.touches || !e.touches[0]) return;
    onMove(e.touches[0].clientX);
  }, { passive: true });
  track.addEventListener('touchend', onEnd);

  track.addEventListener('click', (e) => {
    const img = e.target.closest('.home-banner-slide img');
    if (!img) return;
    openModal(img.getAttribute('src') || '');
  });

  if (modal && modalClose) {
    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('open')) {
        closeModal();
      }
    });
  }

  start();
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

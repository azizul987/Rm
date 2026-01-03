<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'Properti';

// 1) Ambil Parameter Pencarian
$q        = trim(get_str('q'));
$type     = trim(get_str('type'));
$location = trim(get_str('location'));

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

$whereSql = implode(' AND ', $whereParts);
$baseFrom = "FROM properties p LEFT JOIN users u ON u.id = p.created_by";

$countSql = "SELECT COUNT(*) {$baseFrom} WHERE {$whereSql}";
$st = $pdo->prepare($countSql);
$st->execute($params);
$total = (int)$st->fetchColumn();

$sql = "
  SELECT p.*,
    (
      SELECT path
      FROM property_images pi
      WHERE pi.property_id = p.id
      ORDER BY pi.sort_order ASC, pi.id ASC
      LIMIT 1
    ) AS cover
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

if ($type !== '' || $location !== '' || $q !== '') {
  $parts = [];
  if ($type !== '') $parts[] = 'Tipe ' . $type;
  if ($location !== '') $parts[] = 'Lokasi ' . $location;
  if ($q !== '') $parts[] = 'Pencarian "' . $q . '"';
  $page_title = 'Listing Properti' . ($parts ? ' - ' . implode(' • ', $parts) : '');
  $page_description = str_excerpt(
    'Temukan properti ' . ($parts ? implode(', ', $parts) : 'terbaru') . ' di ' . $siteName . '.',
    155
  );
}

if ($q !== '' || $type !== '' || $location !== '' || $page > 1) {
  $page_robots = 'noindex, follow';
}
$page_canonical = 'index.php';

// fallback image (SVG)
$fallbackSvg = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
     <rect width="100%" height="100%" fill="#E0F2FE"/>
     <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#009ADE"
           text-anchor="middle" dominant-baseline="middle">RM Properti</text>
   </svg>'
);

function render_cards(array $rows, string $fallbackSvg): string {
  ob_start();
  foreach ($rows as $p) {
    ?>
    <a class="card" href="property.php?id=<?= (int)$p['id'] ?>">
      <div class="thumb">
        <img
          src="<?= e($p['cover'] ?: $fallbackSvg) ?>"
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
          <span class="meta-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M2 12h20M2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6M12 6v6"/>
            </svg>
            <?= (int)$p['beds'] ?>
          </span>

          <span class="meta-icon">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 3L7 21M17 3l-2 18M3 12h18"/>
            </svg>
            <?= (int)$p['baths'] ?>
          </span>
        </div>

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
    ? ('index.php?' . http_build_query(array_merge($baseParams, ['page' => $page + 1])))
    : '';
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'html' => render_cards($rows, $fallbackSvg),
    'has_more' => $hasMore,
    'next_url' => $nextUrl,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

include __DIR__ . '/header.php';
?>

<!-- HERO: background full width, tapi konten pakai container -->
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <span class="badge-brand"><?= e($heroBadge) ?></span>
      <h1><?= e($heroTitle) ?></h1>
      <p><?= e($heroSubtitle) ?></p>
    </div>

    <form class="filters" method="get" action="index.php">
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
  </div>
</section>

<!-- KONTEN BIASA: wajib dibungkus container -->
<div class="container">
  <div class="results-count">
    Menampilkan <?= count($rows) ?> dari <?= $total ?> properti
  </div>

  <section class="grid">
    <?= render_cards($rows, $fallbackSvg) ?>
  </section>

  <?php if ($hasMore): ?>
    <?php $nextUrl = 'index.php?' . http_build_query(array_merge($baseParams, ['page' => $page + 1])); ?>
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
  'url' => base_url() . '/index.php',
  'potentialAction' => [
    '@type' => 'SearchAction',
    'target' => base_url() . '/index.php?q={search_term_string}',
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

<?php include __DIR__ . '/footer.php'; ?>

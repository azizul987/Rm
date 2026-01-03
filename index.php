<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'Properti';

// 1) Ambil Parameter Pencarian
$q        = trim(get_str('q'));
$type     = trim(get_str('type'));
$location = trim(get_str('location'));

$pdo = db();

// 2) Ambil Data untuk Dropdown (Filter)
$types = $pdo->query("SELECT DISTINCT type FROM properties WHERE status='active' ORDER BY type")->fetchAll();
$locs  = $pdo->query("SELECT DISTINCT location FROM properties WHERE status='active' ORDER BY location")->fetchAll();

// 3) Query Utama Pencarian Properti
$sql = "
  SELECT p.*,
    (
      SELECT path
      FROM property_images pi
      WHERE pi.property_id = p.id
      ORDER BY pi.sort_order ASC, pi.id ASC
      LIMIT 1
    ) AS cover
  FROM properties p
  WHERE p.status='active'
";
$params = [];

// Logika Filter
if ($q !== '') {
  $sql .= " AND (p.title LIKE ? OR p.location LIKE ? OR p.type LIKE ?)";
  $like = "%{$q}%";
  $params = array_merge($params, [$like, $like, $like]);
}

if ($type !== '') {
  $sql .= " AND p.type = ?";
  $params[] = $type;
}

if ($location !== '') {
  $sql .= " AND p.location = ?";
  $params[] = $location;
}

$sql .= " ORDER BY p.id DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

include __DIR__ . '/header.php';

// fallback image (SVG)
$fallbackSvg = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">
     <rect width="100%" height="100%" fill="#E0F2FE"/>
     <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#009ADE"
           text-anchor="middle" dominant-baseline="middle">RM Properti</text>
   </svg>'
);
?>

<!-- HERO: background full width, tapi konten pakai container -->
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <span class="badge-brand">PT. RMI SUCCESS Mandiri</span>
      <h1>Mitra Terpercaya Hunian & Investasi Anda</h1>
      <p>Wujudkan kesuksesan masa depan dengan properti berkualitas, lokasi strategis, dan legalitas yang terjamin aman.</p>
    </div>

    <form class="filters" method="get" action="index.php">
      <input class="input" type="text" name="q" placeholder="Cari wilayah / nama property..." value="<?= e($q) ?>" />

      <select class="select" name="type">
        <option value="">Semua Tipe</option>
        <?php foreach ($types as $t): ?>
          <?php $val = (string)($t['type'] ?? ''); ?>
          <option value="<?= e($val) ?>" <?= ($type === $val) ? 'selected' : '' ?>>
            <?= e($val) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="select" name="location">
        <option value="">Semua Lokasi</option>
        <?php foreach ($locs as $l): ?>
          <?php $val = (string)($l['location'] ?? ''); ?>
          <option value="<?= e($val) ?>" <?= ($location === $val) ? 'selected' : '' ?>>
            <?= e($val) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-accent" type="submit">Temukan Properti</button>
    </form>
  </div>
</section>

<!-- KONTEN BIASA: wajib dibungkus container -->
<div class="container">
  <div class="results-count">
    <?= count($rows) ?> properti ditemukan
  </div>

  <section class="grid">
    <?php foreach ($rows as $p): ?>
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
    <?php endforeach; ?>
  </section>
</div>

<?php include __DIR__ . '/footer.php'; ?>

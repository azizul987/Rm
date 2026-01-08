<?php
require __DIR__ . '/_guard.php';

$admin_title = 'Dashboard';
$active = 'dashboard'; // penting untuk highlight menu

include __DIR__ . '/_header.php';

$pdo = db();
$hasSiteVisits = db_table_exists('site_visits');
$hasPropertyViews = db_table_exists('property_views');
$siteVisits = 0;
$propertyViews = 0;
$trafficDays = [];
$siteDaily = [];
$propertyDaily = [];
$siteSeries = [];
$propertySeries = [];
$siteToday = 0;
$siteYesterday = 0;
$siteLast7 = 0;
$propertyToday = 0;
$propertyYesterday = 0;
$propertyLast7 = 0;
$rangeStart = '';
$rangeEnd = '';

if (is_editor()) {
  $editorId = admin_user_id();
  $st = $pdo->prepare("SELECT p.status, COUNT(*) c
                       FROM properties p
                       LEFT JOIN editor_properties ep
                         ON ep.property_id=p.id AND ep.editor_id=?
                       WHERE (p.created_by=? OR ep.property_id IS NOT NULL)
                       GROUP BY p.status");
  $st->execute([$editorId, $editorId]);
  $rowsKpi = $st->fetchAll();
  $props = 0;
  $activeProps = 0;
  foreach ($rowsKpi as $r) {
    $props += (int)$r['c'];
    if (($r['status'] ?? '') === 'active') $activeProps = (int)$r['c'];
  }
  $sales = 0;

  $st = $pdo->prepare("
    SELECT p.id, p.title, p.status, p.updated_at, s.name AS sales_name
    FROM properties p
    LEFT JOIN editor_properties ep ON ep.property_id=p.id AND ep.editor_id=?
    LEFT JOIN sales s ON s.id=p.sales_id
    WHERE (p.created_by=? OR ep.property_id IS NOT NULL)
    ORDER BY p.updated_at DESC
    LIMIT 6
  ");
  $st->execute([$editorId, $editorId]);
  $recent = $st->fetchAll();

  if ($hasPropertyViews) {
    $st = $pdo->prepare("
      SELECT COUNT(*) c
      FROM property_views pv
      JOIN properties p ON p.id = pv.property_id
      LEFT JOIN editor_properties ep ON ep.property_id = p.id AND ep.editor_id = ?
      WHERE (p.created_by = ? OR ep.property_id IS NOT NULL)
    ");
    $st->execute([$editorId, $editorId]);
    $propertyViews = (int)$st->fetchColumn();
  }
} else {
  $props  = (int)$pdo->query("SELECT COUNT(*) c FROM properties")->fetch()['c'];
  $sales  = (int)$pdo->query("SELECT COUNT(*) c FROM sales")->fetch()['c'];
  $activeProps = (int)$pdo->query("SELECT COUNT(*) c FROM properties WHERE status='active'")->fetch()['c'];

  $recent = $pdo->query("
    SELECT p.id, p.title, p.status, p.updated_at, s.name AS sales_name
    FROM properties p
    LEFT JOIN sales s ON s.id=p.sales_id
    ORDER BY p.updated_at DESC
    LIMIT 6
  ")->fetchAll();

  if ($hasPropertyViews) {
    $propertyViews = (int)$pdo->query("SELECT COUNT(*) c FROM property_views")->fetchColumn();
  }
}

if ($hasSiteVisits) {
  $siteVisits = (int)$pdo->query("SELECT COUNT(*) c FROM site_visits")->fetchColumn();
}

// Traffic harian (7 hari terakhir)
for ($i = 6; $i >= 0; $i--) {
  $trafficDays[] = date('Y-m-d', strtotime("-{$i} days"));
}
$rangeStart = $trafficDays[0] ?? '';
$rangeEnd = $trafficDays[6] ?? '';
$siteDaily = array_fill_keys($trafficDays, 0);
$propertyDaily = array_fill_keys($trafficDays, 0);

if ($hasSiteVisits && $rangeStart !== '') {
  $st = $pdo->prepare("
    SELECT DATE(viewed_at) d, COUNT(*) c
    FROM site_visits
    WHERE viewed_at >= ?
    GROUP BY d
  ");
  $st->execute([$rangeStart]);
  foreach ($st->fetchAll() as $row) {
    $d = (string)($row['d'] ?? '');
    if ($d !== '' && array_key_exists($d, $siteDaily)) {
      $siteDaily[$d] = (int)$row['c'];
    }
  }
}

if ($hasPropertyViews && $rangeStart !== '') {
  if (is_editor()) {
    $st = $pdo->prepare("
      SELECT DATE(pv.viewed_at) d, COUNT(*) c
      FROM property_views pv
      JOIN properties p ON p.id = pv.property_id
      LEFT JOIN editor_properties ep ON ep.property_id = p.id AND ep.editor_id = ?
      WHERE pv.viewed_at >= ?
        AND (p.created_by = ? OR ep.property_id IS NOT NULL)
      GROUP BY d
    ");
    $st->execute([$editorId, $rangeStart, $editorId]);
  } else {
    $st = $pdo->prepare("
      SELECT DATE(viewed_at) d, COUNT(*) c
      FROM property_views
      WHERE viewed_at >= ?
      GROUP BY d
    ");
    $st->execute([$rangeStart]);
  }
  foreach ($st->fetchAll() as $row) {
    $d = (string)($row['d'] ?? '');
    if ($d !== '' && array_key_exists($d, $propertyDaily)) {
      $propertyDaily[$d] = (int)$row['c'];
    }
  }
}

$siteSeries = array_values($siteDaily);
$propertySeries = array_values($propertyDaily);
$siteLast7 = array_sum($siteSeries);
$propertyLast7 = array_sum($propertySeries);
$siteToday = (int)($siteSeries[6] ?? 0);
$propertyToday = (int)($propertySeries[6] ?? 0);
$siteYesterday = (int)($siteSeries[5] ?? 0);
$propertyYesterday = (int)($propertySeries[5] ?? 0);

function traffic_svg(array $siteSeries, array $propertySeries): string {
  $width = 720;
  $height = 180;
  $pad = 16;
  $plotW = $width - ($pad * 2);
  $plotH = $height - ($pad * 2);
  $points = max(1, count($siteSeries));
  $maxVal = max([1, max($siteSeries), max($propertySeries)]);
  $step = $points > 1 ? $plotW / ($points - 1) : 0;

  $sitePts = [];
  $propPts = [];
  for ($i = 0; $i < $points; $i++) {
    $x = $pad + ($step * $i);
    $sv = (int)($siteSeries[$i] ?? 0);
    $pv = (int)($propertySeries[$i] ?? 0);
    $sy = $height - $pad - (($sv / $maxVal) * $plotH);
    $py = $height - $pad - (($pv / $maxVal) * $plotH);
    $sitePts[] = $x . ',' . $sy;
    $propPts[] = $x . ',' . $py;
  }

  $siteLine = implode(' ', $sitePts);
  $propLine = implode(' ', $propPts);

  return '<svg class="traffic-svg" viewBox="0 0 '.$width.' '.$height.'" role="img" aria-label="Grafik trafik 7 hari terakhir">'
    .'<line class="traffic-grid" x1="'.$pad.'" y1="'.($pad + ($plotH / 2)).'" x2="'.($width - $pad).'" y2="'.($pad + ($plotH / 2)).'"></line>'
    .'<line class="traffic-grid" x1="'.$pad.'" y1="'.$pad.'" x2="'.($width - $pad).'" y2="'.$pad.'"></line>'
    .'<line class="traffic-grid" x1="'.$pad.'" y1="'.($height - $pad).'" x2="'.($width - $pad).'" y2="'.($height - $pad).'"></line>'
    .'<polyline class="traffic-line site" points="'.$siteLine.'"></polyline>'
    .'<polyline class="traffic-line property" points="'.$propLine.'"></polyline>'
    .'</svg>';
}
$trafficSvg = traffic_svg($siteSeries, $propertySeries);

function fmt_dt($value){
  if (!$value) return '-';
  $ts = strtotime($value);
  if ($ts === false) return (string)$value;
  return date('d M Y, H:i', $ts);
}

function status_badge($status){
  $s = strtolower((string)$status);
  $label = $status ?: '-';
  $cls = 'badge-status';

  if ($s === 'active') $cls .= ' is-active';
  elseif ($s === 'inactive') $cls .= ' is-muted';
  elseif ($s === 'sold') $cls .= ' is-danger';
  elseif ($s === 'draft') $cls .= ' is-warn';
  else $cls .= ' is-muted';

  return '<span class="'.e($cls).'">'.e($label).'</span>';
}
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead">
      <div>
        <h1 class="admin-title">Dashboard</h1>
        <p class="muted">Ringkasan cepat dan aktivitas terakhir pada listing.</p>
      </div>

      <div class="admin-quick">
        <a class="action accent" href="property_edit">+ Tambah Properti</a>
        <?php if (!is_editor()): ?>
          <a class="action" href="sales_edit">+ Tambah Sales</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-cards admin-cards-3">
      <div class="admin-card">
        <h3>Properti</h3>
        <p>Tambah, edit, atur status, dan kelola foto listing.</p>
        <div class="kpi"><?= $props ?></div>
        <div class="muted">Aktif: <strong><?= $activeProps ?></strong></div>

        <div class="actions">
          <a class="action accent" href="properties">Kelola Properti</a>
          <a class="action" href="property_edit">Tambah Baru</a>
        </div>
      </div>

      <div class="admin-card">
        <h3>Traffic</h3>
        <p>Ringkasan kunjungan website dan detail properti.</p>
        <div class="kpi kpi-blue"><?= $hasSiteVisits ? $siteVisits : 0 ?></div>
        <div class="muted">Kunjungan website</div>
        <div class="muted">Total view detail properti: <strong><?= $hasPropertyViews ? $propertyViews : 0 ?></strong></div>
      </div>

      <?php if (!is_editor()): ?>
        <div class="admin-card">
          <h3>Sales</h3>
          <p>Atur profil sales, nomor WhatsApp, foto, dan area layanan.</p>
          <div class="kpi kpi-blue"><?= $sales ?></div>
          <div class="muted">Pastikan WA format <strong>62...</strong></div>

          <div class="actions">
            <a class="action accent" href="sales">Kelola Sales</a>
            <a class="action" href="sales_edit">Tambah Sales</a>
          </div>
        </div>

        <div class="admin-card">
          <h3>Website</h3>
          <p>Ubah nama brand, tagline, footer, dan logo tanpa edit file.</p>
          <div class="kpi kpi-blue">⚙</div>
          <div class="muted">Branding & tampilan</div>

          <div class="actions">
            <a class="action accent" href="settings">Buka Pengaturan</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="admin-card admin-card-wide">
        <h3>Performa 7 Hari Terakhir</h3>
        <p>Grafik kunjungan website dan view detail properti per hari.</p>

        <?php if (!$hasSiteVisits && !$hasPropertyViews): ?>
          <div class="muted">Tracking belum aktif. Buat tabel `site_visits` dan `property_views`.</div>
        <?php else: ?>
          <div class="traffic-chart">
            <?= $trafficSvg ?>
          </div>
          <div class="traffic-legend">
            <span class="traffic-dot site"></span> Kunjungan website
            <span class="traffic-dot property"></span> View detail properti
          </div>
          <div class="traffic-metrics">
            <span>Hari ini: <strong><?= (int)$siteToday ?></strong> / <strong><?= (int)$propertyToday ?></strong></span>
            <span>Kemarin: <strong><?= (int)$siteYesterday ?></strong> / <strong><?= (int)$propertyYesterday ?></strong></span>
            <span>7 hari: <strong><?= (int)$siteLast7 ?></strong> / <strong><?= (int)$propertyLast7 ?></strong></span>
          </div>
          <?php if ($rangeStart && $rangeEnd): ?>
            <div class="muted">Rentang: <?= e(date('d M Y', strtotime($rangeStart))) ?> – <?= e(date('d M Y', strtotime($rangeEnd))) ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2 class="admin-panel-title">Aktivitas Terbaru</h2>
          <p class="muted">Perubahan listing terakhir (berdasarkan waktu update).</p>
        </div>
        <div>
          <a class="action" href="properties">Lihat Semua</a>
        </div>
      </div>

      <hr class="line" />

      <?php if (!$recent): ?>
        <div class="muted">Belum ada data.</div>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Properti</th>
                <th>Status</th>
                <th>Sales</th>
                <th>Update</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
                <tr>
                  <td class="td-title">
                    <div class="row-title"><?= e($r['title']) ?></div>
                    <div class="muted row-sub">ID: <?= (int)$r['id'] ?></div>
                  </td>
                  <td><?= status_badge($r['status']) ?></td>
                  <td><?= e($r['sales_name'] ?? '-') ?></td>
                  <td><?= e(fmt_dt($r['updated_at'])) ?></td>
                  <td class="td-actions">
                    <a class="action accent" href="property_edit?id=<?= (int)$r['id'] ?>">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

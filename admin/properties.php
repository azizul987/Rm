<?php
require_once __DIR__ . '/_guard.php';

$admin_title = 'Kelola Properti';
$active = 'properties';

$pdo = db();
$editorId = admin_user_id();

// Filter
$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? ''); // ''=all, active/draft/sold

// Pagination
$page = max(1, get_int('page', 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// KPI ringkas
if (is_editor()) {
  $st = $pdo->prepare("SELECT p.status, COUNT(*) c
                       FROM properties p
                       LEFT JOIN editor_properties ep
                         ON ep.property_id=p.id AND ep.editor_id=?
                       WHERE (p.created_by=? OR ep.property_id IS NOT NULL)
                       GROUP BY p.status");
  $st->execute([$editorId, $editorId]);
  $rowsKpi = $st->fetchAll();
  $kpi = ['total' => 0, 'active' => 0, 'draft' => 0, 'sold' => 0];
  foreach ($rowsKpi as $r) {
    $kpi['total'] += (int)$r['c'];
    if (isset($kpi[$r['status']])) $kpi[$r['status']] = (int)$r['c'];
  }
} else {
  $kpi = [
    'total'  => (int)$pdo->query("SELECT COUNT(*) c FROM properties")->fetch()['c'],
    'active' => (int)$pdo->query("SELECT COUNT(*) c FROM properties WHERE status='active'")->fetch()['c'],
    'draft'  => (int)$pdo->query("SELECT COUNT(*) c FROM properties WHERE status='draft'")->fetch()['c'],
    'sold'   => (int)$pdo->query("SELECT COUNT(*) c FROM properties WHERE status='sold'")->fetch()['c'],
  ];
}

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
  elseif ($s === 'draft') $cls .= ' is-warn';
  elseif ($s === 'sold') $cls .= ' is-danger';
  else $cls .= ' is-muted';

  return '<span class="'.e($cls).'">'.e($label).'</span>';
}

// Query list
$baseFrom = "FROM properties p
             LEFT JOIN sales s ON s.id = p.sales_id";
$params = [];
$where = [];

if (is_editor()) {
  $baseFrom .= " LEFT JOIN editor_properties ep ON ep.property_id = p.id AND ep.editor_id = ?";
  $params[] = $editorId;
  $where[] = "(p.created_by = ? OR ep.property_id IS NOT NULL)";
  $params[] = $editorId;
} else {
  $where[] = "1=1";
}

if ($q !== '') {
  $where[] = "(p.title LIKE ? OR p.location LIKE ? OR p.type LIKE ?)";
  $like = "%{$q}%";
  $params = array_merge($params, [$like, $like, $like]);
}

$allowedStatus = ['active', 'draft', 'sold'];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatus, true)) {
  $where[] = "p.status = ?";
  $params[] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) {$baseFrom} WHERE {$whereSql}";
$st = $pdo->prepare($countSql);
$st->execute($params);
$total = (int)$st->fetchColumn();
$totalPages = (int)max(1, ceil($total / $limit));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $limit;
}

$sql = "SELECT p.*, s.name AS sales_name
        {$baseFrom}
        WHERE {$whereSql}
        ORDER BY p.updated_at DESC, p.id DESC
        LIMIT ? OFFSET ?";
$paramsPage = array_merge($params, [$limit, $offset]);

$st = $pdo->prepare($sql);
$st->execute($paramsPage);
$rows = $st->fetchAll();
$hasPrev = $page > 1;
$hasNext = $page < $totalPages;
$baseParams = [];
if ($q !== '') $baseParams['q'] = $q;
if ($statusFilter !== '') $baseParams['status'] = $statusFilter;

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <!-- Page Head -->
    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Kelola Properti</h1>
        <p class="muted">Kelola listing, ubah status (publish/draft/sold), dan hapus properti beserta fotonya.</p>
      </div>

      <div class="admin-quick">
        <a class="action accent" href="property_edit.php">+ Tambah Properti</a>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="admin-kpis">
      <div class="admin-kpi total">
        <div class="admin-kpi-label">Total</div>
        <div class="admin-kpi-value"><?= (int)$kpi['total'] ?></div>
        <div class="admin-kpi-sub muted">Semua listing</div>
      </div>

      <div class="admin-kpi active">
        <div class="admin-kpi-label">Publish</div>
        <div class="admin-kpi-value"><?= (int)$kpi['active'] ?></div>
        <div class="admin-kpi-sub muted">Tayang</div>
      </div>

      <div class="admin-kpi draft">
        <div class="admin-kpi-label">Draft</div>
        <div class="admin-kpi-value"><?= (int)$kpi['draft'] ?></div>
        <div class="admin-kpi-sub muted">Belum tayang</div>
      </div>

      <div class="admin-kpi sold">
        <div class="admin-kpi-label">Sold</div>
        <div class="admin-kpi-value"><?= (int)$kpi['sold'] ?></div>
        <div class="admin-kpi-sub muted">Terjual</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="panel admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2 class="admin-panel-title">Filter</h2>
          <p class="muted">Cari berdasarkan judul, lokasi, atau tipe. Filter berdasarkan status.</p>
        </div>
      </div>

      <hr class="line" />

      <form method="get" class="admin-filters">
        <div class="field">
          <label class="label" for="q">Pencarian</label>
          <input id="q" class="input" name="q" placeholder="Cari judul / lokasi / tipe" value="<?= e($q) ?>">
        </div>

        <div class="field">
          <label class="label" for="status">Status</label>
          <select id="status" class="select" name="status">
            <option value="" <?= $statusFilter===''?'selected':'' ?>>Semua status</option>
            <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Publish (active)</option>
            <option value="draft"  <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option>
            <option value="sold"   <?= $statusFilter==='sold'?'selected':'' ?>>Sold</option>
          </select>
        </div>

        <div class="admin-filters-actions">
          <button class="action accent" type="submit">Terapkan</button>
          <a class="action" href="properties.php">Reset</a>
        </div>
      </form>
    </div>

    <!-- List -->
    <div class="panel admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2 class="admin-panel-title">Daftar Properti</h2>
          <p class="muted">
            Menampilkan <strong><?= count($rows) ?></strong> dari <strong><?= (int)$total ?></strong> data
            <?= $statusFilter ? '• status: <strong>'.e($statusFilter).'</strong>' : '' ?>
            <?= $q ? '• kata kunci: <strong>'.e($q).'</strong>' : '' ?>
            • halaman <strong><?= (int)$page ?></strong> / <strong><?= (int)$totalPages ?></strong>
          </p>
        </div>
      </div>

      <hr class="line" />

      <?php if (!$rows): ?>
        <div class="muted">Belum ada data properti.</div>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Properti</th>
                <th>Tipe</th>
                <th>Lokasi</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Sales</th>
                <th>Update</th>
                <th style="text-align:right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $p): ?>
                <tr>
                  <td class="td-title">
                    <div class="row-title"><?= e($p['title']) ?></div>
                    <div class="muted row-sub">ID: <?= (int)$p['id'] ?></div>
                  </td>

                  <td><?= e($p['type']) ?></td>
                  <td><?= e($p['location']) ?></td>
                  <td><strong><?= e(rupiah((int)$p['price'])) ?></strong></td>
                  <td><?= status_badge($p['status']) ?></td>
                  <td><?= e($p['sales_name'] ?? '-') ?></td>
                  <td><?= e(fmt_dt($p['updated_at'])) ?></td>

                  <td class="td-actions">
                    <div class="admin-actions-cell">
                      <a class="action accent" href="property_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
                      <?php if (!is_editor()): ?>
                        <form method="post" action="property_status.php" class="admin-inline">
                          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

                          <select class="select status-select" name="status" onchange="this.form.submit()" title="Ubah status">
                            <option value="active" <?= ($p['status']==='active')?'selected':'' ?>>Publish</option>
                            <option value="draft"  <?= ($p['status']==='draft')?'selected':'' ?>>Draft</option>
                            <option value="sold"   <?= ($p['status']==='sold')?'selected':'' ?>>Sold</option>
                          </select>
                        </form>

                        <form method="post" action="property_delete.php" class="admin-inline">
                          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                          <button
                            class="action danger"
                            type="submit"
                            onclick="return confirm('Hapus properti ini beserta semua fotonya? Aksi ini tidak bisa dibatalkan.')"
                          >
                            Hapus
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <?php
            $prevUrl = $hasPrev ? ('properties.php?' . http_build_query(array_merge($baseParams, ['page' => $page - 1]))) : '#';
            $nextUrl = $hasNext ? ('properties.php?' . http_build_query(array_merge($baseParams, ['page' => $page + 1]))) : '#';
          ?>
          <div class="admin-pagination">
            <a class="action" href="<?= e($prevUrl) ?>" <?= $hasPrev ? '' : 'aria-disabled="true"' ?>>← Sebelumnya</a>
            <div class="muted">Halaman <?= (int)$page ?> / <?= (int)$totalPages ?></div>
            <a class="action" href="<?= e($nextUrl) ?>" <?= $hasNext ? '' : 'aria-disabled="true"' ?>>Berikutnya →</a>
          </div>
        <?php endif; ?>

        <div class="muted" style="margin-top:10px;font-size:12px">
          Catatan: di mobile, tabel bisa digeser (swipe) ke kanan/kiri.
        </div>
      <?php endif; ?>
    </div>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

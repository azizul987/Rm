<?php
require __DIR__ . '/_guard.php';

$admin_title = 'Dashboard';
$active = 'dashboard'; // penting untuk highlight menu

include __DIR__ . '/_header.php';

$pdo = db();

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
        <a class="action accent" href="property_edit.php">+ Tambah Properti</a>
        <a class="action" href="sales_edit.php">+ Tambah Sales</a>
      </div>
    </div>

    <div class="admin-cards admin-cards-3">
      <div class="admin-card">
        <h3>Properti</h3>
        <p>Tambah, edit, atur status, dan kelola foto listing.</p>
        <div class="kpi"><?= $props ?></div>
        <div class="muted">Aktif: <strong><?= $activeProps ?></strong></div>

        <div class="actions">
          <a class="action accent" href="properties.php">Kelola Properti</a>
          <a class="action" href="property_edit.php">Tambah Baru</a>
        </div>
      </div>

      <div class="admin-card">
        <h3>Sales</h3>
        <p>Atur profil sales, nomor WhatsApp, foto, dan area layanan.</p>
        <div class="kpi kpi-blue"><?= $sales ?></div>
        <div class="muted">Pastikan WA format <strong>62...</strong></div>

        <div class="actions">
          <a class="action accent" href="sales.php">Kelola Sales</a>
          <a class="action" href="sales_edit.php">Tambah Sales</a>
        </div>
      </div>

      <div class="admin-card">
        <h3>Website</h3>
        <p>Ubah nama brand, tagline, footer, dan logo tanpa edit file.</p>
        <div class="kpi kpi-blue">⚙</div>
        <div class="muted">Branding & tampilan</div>

        <div class="actions">
          <a class="action accent" href="settings.php">Buka Pengaturan</a>
        </div>
      </div>
    </div>

    <div class="panel admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2 class="admin-panel-title">Aktivitas Terbaru</h2>
          <p class="muted">Perubahan listing terakhir (berdasarkan waktu update).</p>
        </div>
        <div>
          <a class="action" href="properties.php">Lihat Semua</a>
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
                    <a class="action accent" href="property_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
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

<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

$admin_title = 'Kelola Sales';
$active = 'sales';

$q = trim($_GET['q'] ?? '');

$pdo = db();

$sql = "SELECT * FROM sales WHERE 1=1";
$params = [];

if ($q !== '') {
  $sql .= " AND (name LIKE ? OR phone LIKE ? OR whatsapp LIKE ? OR email LIKE ?)";
  $like = "%{$q}%";
  $params = [$like, $like, $like, $like];
}

$sql .= " ORDER BY updated_at DESC, id DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$total = count($rows);

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Kelola Sales</h1>
        <p class="muted">
          Kelola profil sales (nama, WhatsApp, email, foto, area layanan). Menghapus sales tidak menghapus properti—listing akan menjadi tanpa sales.
        </p>
      </div>

      <div class="admin-quick">
        <a class="action accent" href="sales_edit.php">+ Tambah Sales</a>
      </div>
    </div>

    <div class="panel admin-panel">
      <form method="get" class="admin-filters" action="sales.php">
        <div class="field">
          <label class="label">Cari Sales</label>
          <input class="input" name="q" placeholder="Nama / WA / Email / Telepon" value="<?= e($q) ?>">
        </div>

        <div class="admin-filters-actions">
          <button class="action accent" type="submit">Cari</button>
          <a class="action" href="sales.php">Reset</a>
        </div>
      </form>

      <div class="muted" style="margin-top:10px">
        Menampilkan <strong><?= (int)$total ?></strong> sales
      </div>
    </div>

    <?php if (!$rows): ?>
      <div class="panel admin-panel" style="margin-top:12px">
        <div class="muted">Belum ada data sales.</div>
      </div>
    <?php else: ?>

      <div class="sales-list">
        <?php foreach ($rows as $s): ?>
          <article class="sales-item">
            <div class="sales-left">
              <?php if (!empty($s['photo_path'])): ?>
                <img
                  class="sales-avatar"
                  src="<?= e($s['photo_path']) ?>"
                  alt="<?= e($s['name']) ?>"
                  loading="lazy"
                >
              <?php else: ?>
                <div class="sales-avatar sales-avatar-fallback" aria-hidden="true">
                  <?= e(mb_strtoupper(mb_substr($s['name'] ?: 'S', 0, 1))) ?>
                </div>
              <?php endif; ?>

              <div class="sales-ident">
                <div class="sales-name"><?= e($s['name']) ?></div>
                <div class="sales-title muted"><?= e($s['title'] ?? '-') ?></div>

                <div class="sales-updated muted">
                  Update: <?= e($s['updated_at'] ?? '-') ?>
                </div>
              </div>
            </div>

            <div class="sales-meta">
              <div class="sales-meta-row">
                <span class="sales-meta-k">Telepon</span>
                <span class="sales-meta-v"><?= e($s['phone'] ?? '-') ?></span>
              </div>
              <div class="sales-meta-row">
                <span class="sales-meta-k">WhatsApp</span>
                <span class="sales-meta-v"><?= e($s['whatsapp'] ?? '-') ?></span>
              </div>
              <div class="sales-meta-row">
                <span class="sales-meta-k">Email</span>
                <span class="sales-meta-v"><?= e($s['email'] ?? '-') ?></span>
              </div>
            </div>

            <div class="sales-actions">
              <a class="action accent" href="sales_edit.php?id=<?= (int)$s['id'] ?>">Edit</a>

              <form method="post" action="sales_delete.php" class="admin-inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button
                  class="action danger"
                  type="submit"
                  onclick="return confirm('Hapus sales ini? Properti yang memakai sales ini akan otomatis menjadi tanpa sales.')"
                >
                  Hapus
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

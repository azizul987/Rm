<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

$admin_title = 'Portofolio';
$active = 'portfolio';

$pdo = db();

$st = $pdo->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC, id DESC");
$rows = $st->fetchAll();
$total = count($rows);

function portfolio_logo_url(?string $path): ?string {
  if (!$path) return null;
  if (str_starts_with($path, 'data:')) return $path;
  if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) return $path;

  $local = __DIR__ . '/../' . ltrim($path, '/');
  if (!file_exists($local)) return null;
  return abs_url($path);
}

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Portofolio</h1>
        <p class="muted">Kelola logo client/partner yang ditampilkan di halaman About.</p>
      </div>

      <div class="admin-quick">
        <a class="action accent" href="portfolio_edit">+ Tambah Portofolio</a>
      </div>
    </div>

    <div class="panel admin-panel">
      <div class="muted">Total item: <strong><?= (int)$total ?></strong></div>
    </div>

    <?php if (!$rows): ?>
      <div class="panel admin-panel" style="margin-top:12px">
        <div class="muted">Belum ada item portofolio.</div>
      </div>
    <?php else: ?>
      <div class="portfolio-list">
        <?php foreach ($rows as $r): ?>
          <article class="portfolio-item">
            <?php $logo = portfolio_logo_url($r['image_path'] ?? null); ?>
            <div class="portfolio-logo">
              <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="portfolio-logo-fallback" aria-hidden="true">Logo</div>
              <?php endif; ?>
            </div>

            <div class="portfolio-info">
              <div class="portfolio-title"><?= e($r['title'] ?? '') ?></div>
              <div class="portfolio-url muted"><?= e($r['url'] ?? '-') ?></div>
              <div class="portfolio-meta muted">
                Urutan: <strong><?= (int)($r['sort_order'] ?? 0) ?></strong>
                • Status: <?= !empty($r['is_active']) ? 'Aktif' : 'Nonaktif' ?>
              </div>
            </div>

            <div class="portfolio-actions">
              <a class="action accent" href="portfolio_edit?id=<?= (int)$r['id'] ?>">Edit</a>

              <form method="post" action="portfolio_toggle" class="admin-inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="is_active" value="<?= !empty($r['is_active']) ? 0 : 1 ?>">
                <button class="action" type="submit">
                  <?= !empty($r['is_active']) ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
              </form>

              <form method="post" action="portfolio_sort" class="admin-inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input class="input input-sm" type="number" name="sort_order" value="<?= (int)($r['sort_order'] ?? 0) ?>" style="width:80px">
                <button class="action" type="submit">Urutkan</button>
              </form>

              <form method="post" action="portfolio_delete" class="admin-inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button
                  class="action danger"
                  type="submit"
                  onclick="return confirm('Hapus item portofolio ini?')"
                >Hapus</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

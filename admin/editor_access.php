<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

$admin_title = 'Akses Editor';
$active = 'users';

$pdo = db();
$editorId = (int)($_GET['id'] ?? 0);
if ($editorId <= 0) {
  http_response_code(404);
  exit('Editor tidak ditemukan.');
}

$st = $pdo->prepare("SELECT id, email, role FROM users WHERE id=? LIMIT 1");
$st->execute([$editorId]);
$editor = $st->fetch();
if (!$editor || ($editor['role'] ?? '') !== 'editor') {
  http_response_code(404);
  exit('Editor tidak ditemukan.');
}

$formAction = 'editor_access?id=' . $editorId;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $salesIds = array_map('intval', $_POST['sales_ids'] ?? []);
  $propIds = array_map('intval', $_POST['property_ids'] ?? []);

  $pdo->beginTransaction();
  try {
    $pdo->prepare("DELETE FROM editor_sales WHERE editor_id=?")->execute([$editorId]);
    $pdo->prepare("DELETE FROM editor_properties WHERE editor_id=?")->execute([$editorId]);

    if ($salesIds) {
      $ins = $pdo->prepare("INSERT INTO editor_sales (editor_id, sales_id, granted_by) VALUES (?,?,?)");
      foreach ($salesIds as $sid) {
        if ($sid > 0) $ins->execute([$editorId, $sid, admin_user_id()]);
      }
    }

    if ($propIds) {
      $ins = $pdo->prepare("INSERT INTO editor_properties (editor_id, property_id, granted_by) VALUES (?,?,?)");
      foreach ($propIds as $pid) {
        if ($pid > 0) $ins->execute([$editorId, $pid, admin_user_id()]);
      }
    }

    $pdo->commit();
    $success = 'Akses editor berhasil disimpan.';
  } catch (Throwable $e) {
    $pdo->rollBack();
    $error = 'Gagal menyimpan akses editor.';
  }
}

$sales = $pdo->query("SELECT id, name FROM sales ORDER BY name ASC")->fetchAll();
$props = $pdo->query("SELECT id, title FROM properties ORDER BY updated_at DESC, id DESC")->fetchAll();

$curSales = $pdo->prepare("SELECT sales_id FROM editor_sales WHERE editor_id=?");
$curSales->execute([$editorId]);
$curSalesIds = array_map('intval', array_column($curSales->fetchAll(), 'sales_id'));

$curProps = $pdo->prepare("SELECT property_id FROM editor_properties WHERE editor_id=?");
$curProps->execute([$editorId]);
$curPropIds = array_map('intval', array_column($curProps->fetchAll(), 'property_id'));

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">
    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Akses Editor</h1>
        <p class="muted">Atur sales dan properti yang boleh diakses oleh editor.</p>
      </div>
      <div class="admin-quick">
        <a class="action" href="users">← Kembali</a>
      </div>
    </div>

    <div class="panel admin-panel">
      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="admin-notice success"><?= e($success) ?></div>
      <?php endif; ?>

      <div class="muted" style="margin-bottom:10px">
        Editor: <strong><?= e($editor['email']) ?></strong>
      </div>

      <form method="post" action="<?= e($formAction) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="admin-grid admin-grid-2">
          <div class="panel" style="box-shadow:none">
            <div class="form-field">
              <label class="form-label">Sales yang diizinkan</label>
              <div class="muted" style="font-size:12px;margin-bottom:8px">Editor hanya bisa memilih sales ini saat membuat/ubah properti.</div>
              <div style="display:grid;gap:6px;max-height:320px;overflow:auto;padding:4px 2px">
                <?php foreach ($sales as $s): ?>
                  <label style="display:flex;gap:8px;align-items:center">
                    <input type="checkbox" name="sales_ids[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $curSalesIds, true) ? 'checked' : '' ?>>
                    <span><?= e($s['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="panel" style="box-shadow:none">
            <div class="form-field">
              <label class="form-label">Properti yang diizinkan</label>
              <div class="muted" style="font-size:12px;margin-bottom:8px">Editor hanya bisa mengedit properti yang dicentang.</div>
              <div style="display:grid;gap:6px;max-height:320px;overflow:auto;padding:4px 2px">
                <?php foreach ($props as $p): ?>
                  <label style="display:flex;gap:8px;align-items:center">
                    <input type="checkbox" name="property_ids[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $curPropIds, true) ? 'checked' : '' ?>>
                    <span><?= e($p['title']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="actions" style="margin-top:14px">
          <button class="action accent" type="submit">Simpan Akses</button>
          <a class="action" href="users">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

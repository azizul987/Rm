<?php
require __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);

$admin_title = 'Manajemen Admin';
$active = 'users';

$pdo = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'add_user') {
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $allowedRoles = is_superadmin() ? ['admin', 'sales'] : ['sales'];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Email tidak valid.';
    } elseif (!in_array($role, $allowedRoles, true)) {
      $error = 'Role tidak diizinkan.';
    } elseif (strlen($password) < 6) {
      $error = 'Password minimal 6 karakter.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      try {
        $st = $pdo->prepare("INSERT INTO users (email, password_hash, role, status, credit_limit, credit_used) VALUES (?,?,?,?,?,?)");
        $st->execute([$email, $hash, $role, 'active', 5, 0]);
        $success = 'User berhasil dibuat.';
      } catch (Throwable $e) {
        $error = 'Gagal membuat user (email mungkin sudah terdaftar).';
      }
    }
  }

  if ($action === 'delete_user' && is_superadmin()) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      $error = 'User tidak valid.';
    } elseif ($id === admin_user_id()) {
      $error = 'Tidak bisa menghapus akun sendiri.';
    } else {
      $u = $pdo->prepare("SELECT role FROM users WHERE id=?");
      $u->execute([$id]);
      $row = $u->fetch();
      if (!$row) {
        $error = 'User tidak ditemukan.';
      } elseif (($row['role'] ?? '') === 'superadmin') {
        $error = 'Tidak bisa menghapus superadmin.';
      } else {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $success = 'User berhasil dihapus.';
      }
    }
  }

  if ($action === 'reset_password' && is_superadmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $newPass = (string)($_POST['new_password'] ?? '');
    if ($id <= 0) {
      $error = 'User tidak valid.';
    } elseif (strlen($newPass) < 6) {
      $error = 'Password minimal 6 karakter.';
    } else {
      $hash = password_hash($newPass, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
      $success = 'Password berhasil diubah.';
    }
  }

  if ($action === 'update_sales') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    $creditLimit = (int)($_POST['credit_limit'] ?? 0);

    if ($id <= 0) {
      $error = 'User tidak valid.';
    } elseif (!in_array($status, ['active', 'frozen'], true)) {
      $error = 'Status tidak valid.';
    } elseif ($creditLimit < 0) {
      $error = 'Kredit tidak valid.';
    } else {
      $st = $pdo->prepare("SELECT role FROM users WHERE id=?");
      $st->execute([$id]);
      $row = $st->fetch();
      if (!$row || !in_array(($row['role'] ?? ''), ['sales', 'editor'], true)) {
        $error = 'Hanya sales yang bisa diubah status/kredit.';
      } else {
        $pdo->prepare("UPDATE users SET status=?, credit_limit=? WHERE id=?")
            ->execute([$status, $creditLimit, $id]);
        $success = 'Status/kredit sales berhasil disimpan.';
      }
    }
  }
}

$users = $pdo->query("SELECT id, email, role, status, credit_limit, credit_used, created_at FROM users ORDER BY created_at DESC, id DESC")->fetchAll();

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title">Manajemen Admin</h1>
        <p class="muted">Kelola akun admin dan sales.</p>
      </div>
    </div>

    <div class="panel admin-panel">
      <?php if ($error): ?>
        <div class="admin-alert"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="admin-notice success"><?= e($success) ?></div>
      <?php endif; ?>

      <form method="post" action="users" class="admin-filters" style="margin-top:6px">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_user">

        <div class="field">
          <label class="label">Email</label>
          <input class="input" name="email" placeholder="email@domain.com" required>
        </div>
        <div class="field">
          <label class="label">Role</label>
          <select class="select" name="role" required>
            <?php if (is_superadmin()): ?>
              <option value="admin">admin</option>
            <?php endif; ?>
            <option value="sales">sales</option>
          </select>
        </div>
        <div class="field">
          <label class="label">Password</label>
          <input class="input" type="password" name="password" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="admin-filters-actions">
          <button class="action accent" type="submit">Tambah User</button>
        </div>
      </form>
    </div>

    <div class="panel admin-panel" style="margin-top:14px">
      <div class="admin-panel-head">
        <div>
          <h2 class="admin-panel-title">Daftar User</h2>
          <p class="muted">Menampilkan <?= count($users) ?> user.</p>
        </div>
      </div>

      <hr class="line" />

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Kredit</th>
              <th>Terpakai</th>
              <th>Sisa</th>
              <th>Dibuat</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <?php
                $limit = (int)($u['credit_limit'] ?? 0);
                $used = (int)($u['credit_used'] ?? 0);
                $remain = max(0, $limit - $used);
                $isSalesRow = in_array(($u['role'] ?? ''), ['sales', 'editor'], true);
              ?>
              <tr>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td>
                <td><?= e($u['status'] ?? 'active') ?></td>
                <td><?= $isSalesRow ? e($limit) : '-' ?></td>
                <td><?= $isSalesRow ? e($used) : '-' ?></td>
                <td><?= $isSalesRow ? e($remain) : '-' ?></td>
                <td><?= e($u['created_at']) ?></td>
                <td class="td-actions">
                  <div class="admin-actions-cell">
                    <?php if (in_array(($u['role'] ?? ''), ['sales', 'editor'], true)): ?>
                      <a class="action" href="editor_access?id=<?= (int)$u['id'] ?>">Akses Sales</a>

                      <form method="post" action="users" class="admin-inline">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_sales">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <select class="select" name="status" title="Status sales">
                          <option value="active" <?= ($u['status'] ?? 'active')==='active'?'selected':'' ?>>active</option>
                          <option value="frozen" <?= ($u['status'] ?? '')==='frozen'?'selected':'' ?>>frozen</option>
                        </select>
                        <input class="input" style="width:90px" type="number" name="credit_limit" value="<?= e($limit) ?>" min="0" title="Kredit">
                        <button class="action" type="submit">Simpan</button>
                      </form>
                    <?php endif; ?>

                    <?php if (is_superadmin()): ?>
                      <form method="post" action="users" class="admin-inline">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input class="input" style="width:160px" type="password" name="new_password" placeholder="Password baru" required>
                        <button class="action" type="submit">Ubah Password</button>
                      </form>

                      <form method="post" action="users" class="admin-inline">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button
                          class="action danger"
                          type="submit"
                          onclick="return confirm('Hapus user ini?')"
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
    </div>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

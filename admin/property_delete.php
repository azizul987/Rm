<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid id.');
}

$pdo = db();

// cek owner editor untuk pengurangan kredit
$st = $pdo->prepare("SELECT created_by FROM properties WHERE id=?");
$st->execute([$id]);
$ownerId = (int)($st->fetchColumn() ?? 0);

// ambil semua path foto, hapus file fisiknya
$st = $pdo->prepare("SELECT path FROM property_images WHERE property_id=?");
$st->execute([$id]);
$imgs = $st->fetchAll();

foreach ($imgs as $im) {
  $path = $im['path'] ?? '';
  if ($path) {
    $abs = __DIR__ . '/../' . $path;
    if (is_file($abs)) @unlink($abs);
  }
}

// hapus property (images akan ikut terhapus karena FK CASCADE)
$del = $pdo->prepare("DELETE FROM properties WHERE id=?");
$del->execute([$id]);

if ($ownerId > 0) {
  $st = $pdo->prepare("SELECT role FROM users WHERE id=?");
  $st->execute([$ownerId]);
  $role = $st->fetchColumn();
  if ($role === 'editor') {
    $pdo->prepare("UPDATE users SET credit_used = GREATEST(credit_used - 1, 0) WHERE id=?")
        ->execute([$ownerId]);
  }
}

header('Location: properties.php');
exit;

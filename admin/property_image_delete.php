<?php
require __DIR__ . '/_guard.php';

csrf_check();

$imgId = (int)($_POST['id'] ?? 0);
$propertyId = (int)($_POST['property_id'] ?? 0);
if ($imgId <= 0 || $propertyId <= 0) {
  http_response_code(400);
  exit('Invalid request.');
}

$pdo = db();
$owner = $pdo->prepare("SELECT created_by FROM properties WHERE id=?");
$owner->execute([$propertyId]);
$ownerId = (int)($owner->fetchColumn() ?? 0);

// Editor hanya boleh jika owner sendiri atau properti di-whitelist
if (is_editor()) {
  if ($ownerId !== admin_user_id()) {
    $st = $pdo->prepare("SELECT 1 FROM editor_properties WHERE editor_id=? AND property_id=?");
    $st->execute([admin_user_id(), $propertyId]);
    if (!$st->fetchColumn()) {
      http_response_code(403);
      exit('Akses ditolak.');
    }
  }
}
$st = $pdo->prepare("SELECT path FROM property_images WHERE id=? AND property_id=?");
$st->execute([$imgId, $propertyId]);
$row = $st->fetch();
if (!$row) {
  http_response_code(404);
  exit('Gambar tidak ditemukan.');
}

$path = $row['path'] ?? '';
if ($path) {
  $abs = __DIR__ . '/../' . ltrim($path, '/');
  if (is_file($abs)) @unlink($abs);
}

$pdo->prepare("DELETE FROM property_images WHERE id=?")->execute([$imgId]);

header('Location: property_edit?id=' . $propertyId);
exit;

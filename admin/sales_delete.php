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

// ambil foto sales kalau ada
$st = $pdo->prepare("SELECT photo_path FROM sales WHERE id=?");
$st->execute([$id]);
$row = $st->fetch();

if ($row && !empty($row['photo_path'])) {
  $abs = __DIR__ . '/../' . $row['photo_path'];
  if (is_file($abs)) @unlink($abs);
}

// delete sales; properties.sales_id akan jadi NULL karena FK ON DELETE SET NULL
$del = $pdo->prepare("DELETE FROM sales WHERE id=?");
$del->execute([$id]);

header('Location: sales.php');
exit;

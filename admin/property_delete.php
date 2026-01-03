<?php
require_once __DIR__ . '/_guard.php';
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid id.');
}

$pdo = db();

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

header('Location: properties.php');
exit;

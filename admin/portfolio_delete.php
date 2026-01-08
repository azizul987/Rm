<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid id.');
}

$cfg = require __DIR__ . '/../config.php';
$pdo = db();

function portfolio_local_path(?string $path, string $baseDir): ?string {
  if (!$path) return null;
  if (preg_match('~^https?://~i', $path)) return null;

  $local = __DIR__ . '/../' . ltrim($path, '/');
  $real = realpath($local);
  $base = realpath($baseDir);
  if ($real && $base && str_starts_with($real, $base) && is_file($real)) {
    return $real;
  }
  return null;
}

$st = $pdo->prepare("SELECT image_path FROM portfolio_items WHERE id=?");
$st->execute([$id]);
$row = $st->fetch();

if ($row && !empty($row['image_path'])) {
  $abs = portfolio_local_path($row['image_path'], $cfg['upload']['portfolio_dir']);
  if ($abs) @unlink($abs);
}

$del = $pdo->prepare("DELETE FROM portfolio_items WHERE id=?");
$del->execute([$id]);

header('Location: portfolio');
exit;

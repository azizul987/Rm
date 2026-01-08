<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$isActive = (int)($_POST['is_active'] ?? 1);

if ($id <= 0 || !in_array($isActive, [0, 1], true)) {
  http_response_code(400);
  exit('Invalid request.');
}

$st = db()->prepare("UPDATE portfolio_items SET is_active=? WHERE id=?");
$st->execute([$isActive, $id]);

header('Location: portfolio');
exit;

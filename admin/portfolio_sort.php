<?php
require_once __DIR__ . '/_guard.php';
require_role(['superadmin', 'admin']);
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  exit('Invalid request.');
}

$st = db()->prepare("UPDATE portfolio_items SET sort_order=? WHERE id=?");
$st->execute([$sortOrder, $id]);

header('Location: portfolio');
exit;

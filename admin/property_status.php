<?php
require_once __DIR__ . '/_guard.php';
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? 'active');

$allowed = ['active', 'draft', 'sold'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
  http_response_code(400);
  exit('Invalid request.');
}

$st = db()->prepare("UPDATE properties SET status=? WHERE id=?");
$st->execute([$status, $id]);

header('Location: properties.php');
exit;

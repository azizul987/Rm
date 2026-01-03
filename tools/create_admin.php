<?php
require __DIR__ . '/../db.php';

if (php_sapi_name() !== 'cli') exit("CLI only\n");

$email = $argv[1] ?? '';
$pass  = $argv[2] ?? '';

if (!$email || !$pass) {
  echo "Usage: php tools/create_admin.php admin@rm.local PasswordKuat123\n";
  exit(1);
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$pdo = db();

$stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')");
$stmt->execute([$email, $hash]);

echo "Admin created: {$email}\n";

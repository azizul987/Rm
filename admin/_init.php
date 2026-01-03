<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib.php';

session_start();

function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}
function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}
function admin_user_email(): string {
  return $_SESSION['user_email'] ?? '';
}

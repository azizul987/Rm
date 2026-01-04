<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/../lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('rm_admin_sess');
  session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}
function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login');
    exit;
  }
}
function admin_user_email(): string {
  return $_SESSION['user_email'] ?? '';
}
function admin_user_id(): int {
  return (int)($_SESSION['user_id'] ?? 0);
}

function admin_user_role(): string {
  return $_SESSION['user_role'] ?? 'editor';
}
function admin_user_status(): string {
  return $_SESSION['user_status'] ?? 'active';
}

function is_superadmin(): bool {
  return admin_user_role() === 'superadmin';
}

function is_admin(): bool {
  return admin_user_role() === 'admin';
}

function is_editor(): bool {
  return admin_user_role() === 'editor';
}
function is_frozen(): bool {
  return admin_user_status() === 'frozen';
}

function require_role(array $roles): void {
  if (!is_logged_in()) {
    header('Location: login');
    exit;
  }
  $role = admin_user_role();
  if (!in_array($role, $roles, true)) {
    http_response_code(403);
    exit('Akses ditolak.');
  }
}

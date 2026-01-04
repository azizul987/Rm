<?php
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function rupiah(int|float $a): string { return 'Rp ' . number_format((float)$a, 0, ',', '.'); }

function get_int(string $k, int $d=0): int {
  $v = filter_input(INPUT_GET, $k, FILTER_VALIDATE_INT);
  return ($v === false || $v === null) ? $d : $v;
}
function get_str(string $k, string $d=''): string {
  $v = filter_input(INPUT_GET, $k, FILTER_UNSAFE_RAW);
  return ($v === null) ? $d : trim((string)$v);
}

function base_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host;
}

function base_path(): string {
  $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
  $baseDir = __DIR__;

  $docRoot = str_replace('\\', '/', realpath($docRoot) ?: $docRoot);
  $baseDir = str_replace('\\', '/', realpath($baseDir) ?: $baseDir);

  if ($docRoot !== '' && $baseDir !== '' && str_starts_with($baseDir, $docRoot)) {
    $rel = substr($baseDir, strlen($docRoot));
    $rel = str_replace('\\', '/', $rel);
    $rel = rtrim($rel, '/');
    return ($rel === '') ? '' : $rel;
  }

  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  $dir = str_replace('\\', '/', dirname($script));
  $dir = rtrim($dir, '/');
  if ($dir === '' || $dir === '.' || $dir === '/') return '';
  return $dir;
}

function site_url(string $path): string {
  $path = '/' . ltrim($path, '/');
  $base = rtrim(base_url(), '/');
  $dir = base_path();
  return $base . ($dir ? $dir : '') . $path;
}

function admin_url(string $path = ''): string {
  $base = rtrim(site_url('admin/'), '/');
  if ($path === '') return $base;
  return $base . '/' . ltrim($path, '/');
}

function current_url(): string {
  $uri = $_SERVER['REQUEST_URI'] ?? '/';
  return rtrim(base_url(), '/') . $uri;
}

function abs_url(string $path): string {
  if (preg_match('~^https?://~i', $path)) return $path;
  return site_url($path);
}

function slugify(string $text): string {
  $text = strtolower($text);
  $text = preg_replace('/[^a-z0-9]+/', '-', $text);
  $text = trim($text, '-');
  return $text !== '' ? $text : 'properti';
}

function str_excerpt(string $text, int $max = 160): string {
  $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
  if (strlen($clean) <= $max) return $clean;
  return rtrim(substr($clean, 0, $max - 3)) . '...';
}

function csrf_token(): string {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['_csrf'];
}
function csrf_check(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $t = $_POST['_csrf'] ?? '';
  if (!$t || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $t)) {
    http_response_code(400);
    exit('CSRF validation failed.');
  }
}

function upload_image(array $file, string $destDir, int $maxBytes): array {
  if (!isset($file['error']) || is_array($file['error'])) return [false, 'Invalid upload payload', null];
  if ($file['error'] !== UPLOAD_ERR_OK) return [false, 'Upload error code: ' . $file['error'], null];
  if ($file['size'] > $maxBytes) return [false, 'File terlalu besar', null];

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) return [false, 'Format harus JPG/PNG/WebP', null];

  if (!is_dir($destDir)) mkdir($destDir, 0755, true);

  $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  $path = rtrim($destDir, '/') . '/' . $name;

  if (!move_uploaded_file($file['tmp_name'], $path)) return [false, 'Gagal memindahkan file', null];
  return [true, null, $name];
}

function features_to_json(string $text): ?string {
  $lines = array_filter(array_map('trim', preg_split('/\R/', $text)));
  if (!$lines) return null;
  return json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);
}
function features_from_json(?string $json): string {
  if (!$json) return '';
  $arr = json_decode($json, true);
  if (!is_array($arr)) return '';
  return implode("\n", array_map('strval', $arr));
}

function youtube_id(string $url): ?string {
  $url = trim($url);
  if ($url === '') return null;

  $id = null;
  if (preg_match('~youtu\.be/([A-Za-z0-9_-]{11})~', $url, $m)) $id = $m[1];
  if (!$id && preg_match('~v=([A-Za-z0-9_-]{11})~', $url, $m)) $id = $m[1];
  if (!$id && preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{11})~', $url, $m)) $id = $m[1];
  if (!$id && preg_match('~youtube\.com/shorts/([A-Za-z0-9_-]{11})~', $url, $m)) $id = $m[1];

  return $id ?: null;
}

function videos_to_json(string $text): ?string {
  $lines = array_filter(array_map('trim', preg_split('/\R/', $text)));
  if (!$lines) return null;

  $ids = [];
  foreach ($lines as $line) {
    $id = youtube_id($line);
    if ($id) $ids[] = $id;
  }
  $ids = array_values(array_unique($ids));
  if (!$ids) return null;
  return json_encode($ids, JSON_UNESCAPED_UNICODE);
}

function videos_from_json(?string $json): string {
  if (!$json) return '';
  $arr = json_decode($json, true);
  if (!is_array($arr)) return '';
  $urls = [];
  foreach ($arr as $id) {
    $id = (string)$id;
    if ($id !== '') $urls[] = 'https://youtu.be/' . $id;
  }
  return implode("\n", $urls);
}

function setting(string $key, ?string $default = null): ?string {
  static $cache = null;

  // Pastikan db() tersedia
  if (!function_exists('db')) {
    require __DIR__ . '/db.php';
  }

  if ($cache === null) {
    $cache = [];
    $rows = db()->query("SELECT `key`, `value` FROM settings")->fetchAll();
    foreach ($rows as $r) {
      $cache[$r['key']] = $r['value'];
    }
  }

  return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function set_setting(string $key, ?string $value): void {
  if (!function_exists('db')) {
    require __DIR__ . '/db.php';
  }
  $st = db()->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?)
                       ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $st->execute([$key, $value]);
}
// Alias untuk kompatibilitas kode lama
function get_int_param(string $k, int $d = 0): int {
  return function_exists('get_int') ? get_int($k, $d) : $d;
}

function get_str_param(string $k, string $d = ''): string {
  return function_exists('get_str') ? get_str($k, $d) : $d;
}

<?php
if (!defined('RM_CONFIG_LOADED')) {
  define('RM_CONFIG_LOADED', true);

  $envPath = __DIR__ . '/.env';
  if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;
      $parts = explode('=', $line, 2);
      if (count($parts) !== 2) continue;
      $key = trim($parts[0]);
      $val = trim($parts[1]);
      if ($key === '') continue;
      if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
          (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
        $val = substr($val, 1, -1);
      }
      putenv($key . '=' . $val);
      $_ENV[$key] = $val;
      $_SERVER[$key] = $val;
    }
  }
}

if (!function_exists('env_val')) {
  function env_val(string $key, ?string $default = null): ?string {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') return $default;
    return (string)$val;
  }
}

return [
  'db' => [
    'host' => env_val('DB_HOST', '127.0.0.1'),
    'name' => env_val('DB_NAME', 'rm_properti'),
    'user' => env_val('DB_USER', 'root'),
    'pass' => env_val('DB_PASS', ''),
    'charset' => env_val('DB_CHARSET', 'utf8mb4'),
  ],
  'upload' => [
    'max_bytes' => 5 * 1024 * 1024, // 5MB
    'property_dir' => __DIR__ . '/uploads/properties',
    'sales_dir' => __DIR__ . '/uploads/sales',
    'property_url' => 'uploads/properties',
    'sales_url' => 'uploads/sales',
    'portfolio_dir' => __DIR__ . '/uploads/portfolio',
    'portfolio_url' => 'uploads/portfolio',
    'branding_dir' => __DIR__ . '/uploads/branding',
    'branding_url' => 'uploads/branding',
    'banner_dir' => __DIR__ . '/uploads/banners',
    'banner_url' => 'uploads/banners',
  ],
  'app' => [
    'name' => 'RM Properti',
  ],
];

<?php
return [
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'rm_properti',
    'user' => 'rm_user',
    'pass' => 'AF5476891af',
    'charset' => 'utf8mb4',
  ],
  'upload' => [
    'max_bytes' => 5 * 1024 * 1024, // 5MB
    'property_dir' => __DIR__ . '/uploads/properties',
    'sales_dir' => __DIR__ . '/uploads/sales',
    'property_url' => 'uploads/properties',
    'sales_url' => 'uploads/sales',
    'branding_dir' => __DIR__ . '/uploads/branding',
    'branding_url' => 'uploads/branding',
  ],
  'app' => [
    'name' => 'RM Properti',
  ],
];

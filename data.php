<?php
// data.php — sumber data sederhana (hardcoded). Nanti bisa dipindah ke MySQL.

$sales = [
  'rm01' => [
    'id' => 'rm01',
    'name' => 'Rizki Pratama',
    'title' => 'Property Consultant',
    'phone' => '081234567890',
    'whatsapp' => '6281234567890', // format WA: 62...
    'email' => 'rizki@rmproperti.id',
    'photo' => 'assets/sales-rizki.jpg',
    'bio' => 'Spesialis rumah dan ruko. Fokus proses cepat, transparan, dan aman sampai serah terima.',
    'areas' => ['Palembang', 'Ogan Ilir'],
    'experience_years' => 4,
  ],
  'rm02' => [
    'id' => 'rm02',
    'name' => 'Dina Aulia',
    'title' => 'Senior Sales',
    'phone' => '081298765432',
    'whatsapp' => '6281298765432',
    'email' => 'dina@rmproperti.id',
    'photo' => 'assets/sales-dina.jpg',
    'bio' => 'Spesialis apartemen dan tanah. Membantu negosiasi, legalitas, hingga closing.',
    'areas' => ['Jakarta', 'Tangerang'],
    'experience_years' => 7,
  ],
];

$properties = [
  101 => [
    'id' => 101,
    'title' => 'Rumah Minimalis 3KT Dekat Kampus',
    'type' => 'Rumah',
    'price' => 850000000,
    'location' => 'Palembang',
    'beds' => 3,
    'baths' => 2,
    'land' => 120,
    'building' => 90,
    'images' => [
      'assets/p101-1.jpg',
      'assets/p101-2.jpg',
      'assets/p101-3.jpg',
    ],
    'description' => 'Rumah siap huni dengan akses jalan lebar. Dekat minimarket dan kampus. Lingkungan rapi, cocok untuk keluarga.',
    'features' => ['Carport', 'Dapur luas', 'Air PDAM', 'Listrik 2200W'],
    'sales_id' => 'rm01',
  ],
  102 => [
    'id' => 102,
    'title' => 'Ruko 2 Lantai Pinggir Jalan Utama',
    'type' => 'Ruko',
    'price' => 1350000000,
    'location' => 'Palembang',
    'beds' => 2,
    'baths' => 2,
    'land' => 90,
    'building' => 150,
    'images' => [
      'assets/p102-1.jpg',
      'assets/p102-2.jpg',
    ],
    'description' => 'Lokasi strategis dengan traffic tinggi. Cocok untuk usaha retail/office. Parkir memadai.',
    'features' => ['Balkon', 'Parkir luas', 'CCTV area', 'Dekat pusat kuliner'],
    'sales_id' => 'rm01',
  ],
  103 => [
    'id' => 103,
    'title' => 'Tanah Kavling SHM Siap Bangun',
    'type' => 'Tanah',
    'price' => 420000000,
    'location' => 'Tangerang',
    'beds' => 0,
    'baths' => 0,
    'land' => 100,
    'building' => 0,
    'images' => [
      'assets/p103-1.jpg',
    ],
    'description' => 'Kavling matang, SHM, akses mudah ke tol. Lingkungan berkembang dan aman.',
    'features' => ['SHM', 'Akses tol', 'Jalan cor', 'Kawasan berkembang'],
    'sales_id' => 'rm02',
  ],
];

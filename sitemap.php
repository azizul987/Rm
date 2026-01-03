<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

header('Content-Type: application/xml; charset=utf-8');

$base = rtrim(base_url(), '/');
$urls = [
  [
    'loc' => $base . '/index.php',
    'changefreq' => 'daily',
    'priority' => '1.0',
  ],
  [
    'loc' => $base . '/about.php',
    'changefreq' => 'monthly',
    'priority' => '0.6',
  ],
  [
    'loc' => $base . '/contact.php',
    'changefreq' => 'monthly',
    'priority' => '0.6',
  ],
];

$publicWhere = "p.status='active' AND (p.created_by IS NULL OR u.role <> 'editor' OR u.status='active')";
$st = db()->query("
  SELECT p.id, p.title, p.updated_at
  FROM properties p
  LEFT JOIN users u ON u.id = p.created_by
  WHERE {$publicWhere}
  ORDER BY p.id DESC
");
foreach ($st->fetchAll() as $row) {
  $loc = $base . '/property.php?id=' . (int)$row['id'];
  $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : null;
  $urls[] = [
    'loc' => $loc,
    'changefreq' => 'weekly',
    'priority' => '0.8',
    'lastmod' => $lastmod,
  ];
}

if (file_exists(__DIR__ . '/data.php')) {
  require __DIR__ . '/data.php';
  if (isset($sales) && is_array($sales)) {
    foreach ($sales as $s) {
      if (!isset($s['id'])) continue;
      $urls[] = [
        'loc' => $base . '/sales.php?id=' . urlencode((string)$s['id']),
        'changefreq' => 'monthly',
        'priority' => '0.4',
      ];
    }
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= e($u['loc']) ?></loc>
    <?php if (!empty($u['lastmod'])): ?>
      <lastmod><?= e($u['lastmod']) ?></lastmod>
    <?php endif; ?>
    <changefreq><?= e($u['changefreq']) ?></changefreq>
    <priority><?= e($u['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>

<?php
require __DIR__ . '/_guard.php';

$cfg = require __DIR__ . '/../config.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$p = null;

if ($id) {
  $st = $pdo->prepare("SELECT * FROM properties WHERE id=?");
  $st->execute([$id]);
  $p = $st->fetch();
  if (!$p) { http_response_code(404); exit('Property not found'); }
}

$sales = $pdo->query("SELECT id, name FROM sales ORDER BY name ASC")->fetchAll();

function property_images(int $pid): array {
  $st = db()->prepare("SELECT * FROM property_images WHERE property_id=? ORDER BY sort_order ASC, id ASC");
  $st->execute([$pid]);
  return $st->fetchAll();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $title = trim($_POST['title'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $price = (int)($_POST['price'] ?? 0);
  $location = trim($_POST['location'] ?? '');
  $beds = (int)($_POST['beds'] ?? 0);
  $baths = (int)($_POST['baths'] ?? 0);
  $land = (int)($_POST['land'] ?? 0);
  $building = (int)($_POST['building'] ?? 0);
  $desc = trim($_POST['description'] ?? '');
  $featuresJson = features_to_json($_POST['features'] ?? '');
  $status = trim($_POST['status'] ?? 'active');
  $salesId = ($_POST['sales_id'] ?? '') !== '' ? (int)$_POST['sales_id'] : null;

  if ($title === '' || $type === '' || $location === '') {
    $error = 'Judul, tipe, dan lokasi wajib diisi.';
  } else {
    if ($id) {
      $st = $pdo->prepare("UPDATE properties SET title=?, type=?, price=?, location=?, beds=?, baths=?, land=?, building=?, description=?, features_json=?, status=?, sales_id=? WHERE id=?");
      $st->execute([$title,$type,$price,$location,$beds,$baths,$land,$building,$desc,$featuresJson,$status,$salesId,$id]);
    } else {
      $st = $pdo->prepare("INSERT INTO properties (title,type,price,location,beds,baths,land,building,description,features_json,status,sales_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
      $st->execute([$title,$type,$price,$location,$beds,$baths,$land,$building,$desc,$featuresJson,$status,$salesId]);
      $id = (int)$pdo->lastInsertId();
    }

    // Upload COVER (disimpan sort_order = 1)
    if (!empty($_FILES['cover']['name'])) {
      [$ok, $msg, $fname] = upload_image($_FILES['cover'], $cfg['upload']['property_dir'], $cfg['upload']['max_bytes']);
      if (!$ok) {
        $error = $msg;
      } else {
        // hapus cover lama (sort_order=1) agar konsisten
        $pdo->prepare("DELETE FROM property_images WHERE property_id=? AND sort_order=1")->execute([$id]);
        $path = $cfg['upload']['property_url'] . '/' . $fname;
        $pdo->prepare("INSERT INTO property_images (property_id,path,sort_order) VALUES (?,?,1)")->execute([$id,$path]);
      }
    }

    // Upload GALLERY (multiple)
    if (!$error && !empty($_FILES['gallery']['name'][0])) {
      $count = count($_FILES['gallery']['name']);
      for ($i=0; $i<$count; $i++) {
        $f = [
          'name' => $_FILES['gallery']['name'][$i],
          'type' => $_FILES['gallery']['type'][$i],
          'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
          'error' => $_FILES['gallery']['error'][$i],
          'size' => $_FILES['gallery']['size'][$i],
        ];
        [$ok, $msg, $fname] = upload_image($f, $cfg['upload']['property_dir'], $cfg['upload']['max_bytes']);
        if (!$ok) { $error = $msg; break; }
        $path = $cfg['upload']['property_url'] . '/' . $fname;

        // sort order: ambil max lalu +10
        $mx = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 10) m FROM property_images WHERE property_id=?");
        $mx->execute([$id]);
        $next = (int)$mx->fetch()['m'] + 10;

        $pdo->prepare("INSERT INTO property_images (property_id,path,sort_order) VALUES (?,?,?)")->execute([$id,$path,$next]);
      }
    }

    if (!$error) {
      header('Location: properties.php');
      exit;
    }
  }
}

$val = fn($k, $d='') => e((string)($p[$k] ?? $d));
$featuresText = features_from_json($p['features_json'] ?? null);
$imgs = $id ? property_images($id) : [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $id ? 'Edit' : 'Tambah' ?> Properti — Admin</title>
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
<main class="container main">
  <div class="panel">
    <h2><?= $id ? 'Edit' : 'Tambah' ?> Properti</h2>

    <?php if ($error): ?>
      <div class="badge" style="border-color:#fecaca;color:#991b1b;background:#fff;"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="margin-top:12px">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <div style="display:grid;gap:10px">
        <input class="input" name="title" placeholder="Judul" value="<?= $val('title') ?>" required>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <input class="input" name="type" placeholder="Tipe (Rumah/Ruko/Tanah)" value="<?= $val('type') ?>" required>
          <input class="input" name="location" placeholder="Lokasi" value="<?= $val('location') ?>" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <input class="input" type="number" name="price" placeholder="Harga" value="<?= $val('price','0') ?>">
          <select class="select" name="status">
            <option value="active" <?= ($p['status'] ?? 'active')==='active'?'selected':'' ?>>active</option>
            <option value="sold" <?= ($p['status'] ?? '')==='sold'?'selected':'' ?>>sold</option>
            <option value="draft" <?= ($p['status'] ?? '')==='draft'?'selected':'' ?>>draft</option>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
          <input class="input" type="number" name="beds" placeholder="KT" value="<?= $val('beds','0') ?>">
          <input class="input" type="number" name="baths" placeholder="KM" value="<?= $val('baths','0') ?>">
          <input class="input" type="number" name="land" placeholder="Luas Tanah (m²)" value="<?= $val('land','0') ?>">
          <input class="input" type="number" name="building" placeholder="Luas Bangunan (m²)" value="<?= $val('building','0') ?>">
        </div>

        <select class="select" name="sales_id">
          <option value="">(Tanpa Sales)</option>
          <?php foreach ($sales as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)($p['sales_id'] ?? 0) === (int)$s['id'])?'selected':'' ?>>
              <?= e($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <textarea class="input" name="description" rows="4" placeholder="Deskripsi"><?= e($p['description'] ?? '') ?></textarea>

        <textarea class="input" name="features" rows="4" placeholder="Fitur (1 baris 1 fitur)"><?= e($featuresText) ?></textarea>

        <div class="panel" style="box-shadow:none">
          <div style="font-weight:900;margin-bottom:8px">Cover (mengganti cover lama)</div>
          <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="panel" style="box-shadow:none">
          <div style="font-weight:900;margin-bottom:8px">Gallery (bisa pilih banyak)</div>
          <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp">
        </div>

        <?php if ($imgs): ?>
          <div class="panel" style="box-shadow:none">
            <div style="font-weight:900;margin-bottom:8px">Foto Saat Ini</div>
            <?php foreach ($imgs as $im): ?>
              <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:6px">
                <div class="muted"><?= e($im['path']) ?> (sort <?= (int)$im['sort_order'] ?>)</div>
                <form method="post" action="property_image_delete.php" style="margin:0">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$im['id'] ?>">
                  <input type="hidden" name="property_id" value="<?= (int)$id ?>">
                  <button class="btn" type="submit">Hapus</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="actions">
          <button class="btn btn-accent" type="submit">Simpan</button>
          <a class="action" href="properties.php">Batal</a>
        </div>
      </div>
    </form>
  </div>
</main>
</body>
</html>

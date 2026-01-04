<?php
require __DIR__ . '/_guard.php';

$admin_title = 'Properti';
$active = 'properties';

$cfg = require __DIR__ . '/../config.php';
$pdo = db();
$editorId = admin_user_id();
$editorCredit = null;

$id = (int)($_GET['id'] ?? 0);
$p = null;
$formAction = $id ? ('property_edit?id=' . (int)$id) : 'property_edit';

if ($id) {
  $st = $pdo->prepare("SELECT * FROM properties WHERE id=?");
  $st->execute([$id]);
  $p = $st->fetch();
  if (!$p) { http_response_code(404); exit('Property not found'); }

  if (is_editor()) {
    $ownerId = (int)($p['created_by'] ?? 0);
    if ($ownerId !== $editorId) {
      $chk = $pdo->prepare("SELECT 1 FROM editor_properties WHERE editor_id=? AND property_id=?");
      $chk->execute([$editorId, $id]);
      if (!$chk->fetchColumn()) {
        http_response_code(403);
        exit('Akses ditolak.');
      }
    }
  }
} elseif (is_editor()) {
  $st = $pdo->prepare("SELECT credit_limit, credit_used FROM users WHERE id=? AND role='editor' LIMIT 1");
  $st->execute([$editorId]);
  $editorCredit = $st->fetch();
  if (!$editorCredit) {
    http_response_code(403);
    exit('Akses ditolak.');
  }
  if ((int)$editorCredit['credit_used'] >= (int)$editorCredit['credit_limit']) {
    http_response_code(403);
    exit('Kredit editor sudah habis.');
  }
}

$allowedSalesIds = [];
if (is_editor()) {
  $st = $pdo->prepare("SELECT s.id, s.name
                       FROM sales s
                       INNER JOIN editor_sales es ON es.sales_id=s.id
                       WHERE es.editor_id=?
                       ORDER BY s.name ASC");
  $st->execute([$editorId]);
  $sales = $st->fetchAll();
  $allowedSalesIds = array_map('intval', array_column($sales, 'id'));
} else {
  $sales = $pdo->query("SELECT id, name FROM sales ORDER BY name ASC")->fetchAll();
}

function property_images(int $pid): array {
  $st = db()->prepare("SELECT * FROM property_images WHERE property_id=? ORDER BY sort_order ASC, id ASC");
  $st->execute([$pid]);
  return $st->fetchAll();
}

function admin_public_path(string $path): string {
  if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) return $path;
  return '../' . ltrim($path, '/');
}

function fmt_dt($value){
  if (!$value) return '-';
  $ts = strtotime($value);
  if ($ts === false) return (string)$value;
  return date('d M Y, H:i', $ts);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post_too_large()) {
    $error = 'Ukuran upload melebihi batas server (post_max_size).';
  } else {
    csrf_check();
  }

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
  $videosJson = videos_to_json($_POST['videos'] ?? '');
  $status = trim($_POST['status'] ?? 'active');
  $salesId = ($_POST['sales_id'] ?? '') !== '' ? (int)$_POST['sales_id'] : null;

  if (!$error && ($title === '' || $type === '' || $location === '')) {
    $error = 'Judul, tipe, dan lokasi wajib diisi.';
  } elseif (!$error && is_editor() && $salesId !== null && !in_array($salesId, $allowedSalesIds, true)) {
    $error = 'Sales tidak diizinkan untuk editor ini.';
  } else {
    if ($id) {
      $st = $pdo->prepare("UPDATE properties SET title=?, type=?, price=?, location=?, beds=?, baths=?, land=?, building=?, description=?, features_json=?, videos_json=?, status=?, sales_id=? WHERE id=?");
      $st->execute([$title,$type,$price,$location,$beds,$baths,$land,$building,$desc,$featuresJson,$videosJson,$status,$salesId,$id]);
    } else {
      if (is_editor()) {
        if ($editorCredit === null) {
          $st = $pdo->prepare("SELECT credit_limit, credit_used FROM users WHERE id=? AND role='editor' LIMIT 1");
          $st->execute([$editorId]);
          $editorCredit = $st->fetch();
        }
        if (!$editorCredit || (int)$editorCredit['credit_used'] >= (int)$editorCredit['credit_limit']) {
          $error = 'Kredit editor sudah habis.';
        }
      }

      if (!$error) {
        if (is_editor()) {
          $st = $pdo->prepare("INSERT INTO properties (title,type,price,location,beds,baths,land,building,description,features_json,videos_json,status,sales_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
          $st->execute([$title,$type,$price,$location,$beds,$baths,$land,$building,$desc,$featuresJson,$videosJson,$status,$salesId,$editorId]);
          $pdo->prepare("UPDATE users SET credit_used = credit_used + 1 WHERE id=?")->execute([$editorId]);
        } else {
          $st = $pdo->prepare("INSERT INTO properties (title,type,price,location,beds,baths,land,building,description,features_json,videos_json,status,sales_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
          $st->execute([$title,$type,$price,$location,$beds,$baths,$land,$building,$desc,$featuresJson,$videosJson,$status,$salesId]);
        }
        $id = (int)$pdo->lastInsertId();
      }
    }

    // Upload COVER (sort_order=1)
    if (!$error && !empty($_FILES['cover']['name'])) {
      [$ok, $msg, $fname] = upload_image($_FILES['cover'], $cfg['upload']['property_dir'], $cfg['upload']['max_bytes']);
      if (!$ok) {
        $error = $msg;
      } else {
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

        $mx = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 10) m FROM property_images WHERE property_id=?");
        $mx->execute([$id]);
        $next = (int)$mx->fetch()['m'] + 10;

        $pdo->prepare("INSERT INTO property_images (property_id,path,sort_order) VALUES (?,?,?)")->execute([$id,$path,$next]);
      }
    }

    if (!$error) {
      header('Location: properties');
      exit;
    }
  }
}

$val = fn($k, $d='') => e((string)($p[$k] ?? $d));
$featuresText = features_from_json($p['features_json'] ?? null);
$videosText = videos_from_json($p['videos_json'] ?? null);
$imgs = $id ? property_images($id) : [];

include __DIR__ . '/_header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/_sidebar.php'; ?>

  <section class="admin-content">

    <div class="admin-pagehead admin-pagehead-spaced">
      <div>
        <h1 class="admin-title"><?= $id ? 'Edit' : 'Tambah' ?> Properti</h1>
        <p class="muted">
          Isi data utama, pilih sales, atur status, lalu upload cover & galeri.
          <?php if ($id): ?>
            • ID: <strong><?= (int)$id ?></strong>
          <?php endif; ?>
        </p>
      </div>

      <div class="admin-quick">
        <a class="action" href="properties">← Kembali</a>
        <?php if ($id): ?>
          <a class="action" href="../property.php?id=<?= (int)$id ?>" target="_blank" rel="noopener">Preview</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="admin-alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <div class="admin-form-layout">

        <!-- LEFT: Main form -->
        <div class="panel admin-panel">
          <div class="admin-panel-head">
            <div>
              <h2 class="admin-panel-title">Informasi Utama</h2>
              <p class="muted">Judul, tipe, lokasi, harga, spesifikasi, status, dan sales.</p>
            </div>
          </div>

          <hr class="line" />

          <div class="admin-grid">
            <div class="field">
              <label class="label" for="title">Judul</label>
              <input id="title" class="input" name="title" placeholder="Contoh: Rumah Minimalis 2 Lantai" value="<?= $val('title') ?>" required>
            </div>

            <div class="field">
              <label class="label" for="status">Status</label>
              <select id="status" class="select" name="status">
                <option value="active" <?= ($p['status'] ?? 'active')==='active'?'selected':'' ?>>Publish (active)</option>
                <option value="draft"  <?= ($p['status'] ?? '')==='draft'?'selected':'' ?>>Draft</option>
                <option value="sold"   <?= ($p['status'] ?? '')==='sold'?'selected':'' ?>>Sold</option>
              </select>
            </div>

            <div class="field">
              <label class="label" for="type">Tipe</label>
              <input id="type" class="input" name="type" placeholder="Rumah / Ruko / Tanah" value="<?= $val('type') ?>" required>
            </div>

            <div class="field">
              <label class="label" for="location">Lokasi</label>
              <input id="location" class="input" name="location" placeholder="Contoh: Palembang" value="<?= $val('location') ?>" required>
            </div>

            <div class="field">
              <label class="label" for="price">Harga (angka)</label>
              <input id="price" class="input" type="number" name="price" placeholder="Contoh: 750000000" value="<?= $val('price','0') ?>">
              <div class="muted" style="font-size:12px;margin-top:6px">
                Preview: <strong><?= e(rupiah((int)($p['price'] ?? 0))) ?></strong>
              </div>
            </div>

            <div class="field">
              <label class="label" for="sales_id">Sales</label>
              <select id="sales_id" class="select" name="sales_id">
                <option value="">(Tanpa Sales)</option>
                <?php foreach ($sales as $s): ?>
                  <option value="<?= (int)$s['id'] ?>" <?= ((int)($p['sales_id'] ?? 0) === (int)$s['id'])?'selected':'' ?>>
                    <?= e($s['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="admin-grid admin-grid-4" style="margin-top:12px">
            <div class="field">
              <label class="label" for="beds">KT</label>
              <input id="beds" class="input" type="number" name="beds" value="<?= $val('beds','0') ?>">
            </div>

            <div class="field">
              <label class="label" for="baths">KM</label>
              <input id="baths" class="input" type="number" name="baths" value="<?= $val('baths','0') ?>">
            </div>

            <div class="field">
              <label class="label" for="land">Luas Tanah (m²)</label>
              <input id="land" class="input" type="number" name="land" value="<?= $val('land','0') ?>">
            </div>

            <div class="field">
              <label class="label" for="building">Luas Bangunan (m²)</label>
              <input id="building" class="input" type="number" name="building" value="<?= $val('building','0') ?>">
            </div>
          </div>

          <div class="admin-grid admin-grid-2" style="margin-top:12px">
            <div class="field">
              <label class="label" for="description">Deskripsi</label>
              <textarea id="description" class="input admin-textarea" name="description" rows="5" placeholder="Deskripsi singkat dan jelas..."><?= e($p['description'] ?? '') ?></textarea>
            </div>

            <div class="field">
              <label class="label" for="features">Fitur (1 baris = 1 fitur)</label>
              <textarea id="features" class="input admin-textarea" name="features" rows="5" placeholder="Contoh: Carport&#10;Taman depan&#10;Dekat sekolah"><?= e($featuresText) ?></textarea>
            </div>
          </div>

          <div class="admin-grid admin-grid-2" style="margin-top:12px">
            <div class="field">
              <label class="label" for="videos">Video YouTube (opsional)</label>
              <div class="admin-video-inputs">
                <input id="video_url_input" class="input" type="url" placeholder="Tempel URL YouTube lalu klik Tambah">
                <button class="action" type="button" id="add-video-btn">Tambah</button>
              </div>
              <textarea id="videos" class="input admin-textarea" name="videos" rows="4" placeholder="1 URL per baris, contoh: https://youtu.be/xxxxxx"><?= e($videosText) ?></textarea>
              <div id="video-preview" class="img-grid video-preview"></div>
              <div class="muted" style="margin-top:6px">Hanya mendukung link YouTube. Preview akan muncul otomatis.</div>
            </div>
          </div>

          <div class="actions" style="margin-top:14px">
            <button class="action accent" type="submit">Simpan</button>
              <a class="action" href="properties">Batal</a>
          </div>
        </div>

        <!-- RIGHT: Upload + current images -->
        <div class="admin-side">

          <div class="panel admin-panel">
            <div class="admin-panel-head">
              <div>
                <h2 class="admin-panel-title">Upload Foto</h2>
                <p class="muted">Cover mengganti cover lama. Galeri bisa banyak.</p>
              </div>
            </div>

            <hr class="line" />

            <div class="field">
              <label class="label" for="cover">Cover</label>
              <input id="cover" class="input" type="file" name="cover" accept="image/jpeg,image/png,image/webp">
              <div class="muted" style="font-size:12px;margin-top:6px">
                Disimpan sebagai <strong>sort_order = 1</strong>.
              </div>
            </div>

            <div class="field" style="margin-top:12px">
              <label class="label" for="gallery">Gallery</label>
              <input id="gallery" class="input" type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp">
              <div class="muted" style="font-size:12px;margin-top:6px">
                Urutan otomatis: mengambil max sort_order + 10.
              </div>
            </div>

            <div class="actions" style="margin-top:14px">
              <button class="action accent" type="submit">Simpan</button>
              <a class="action" href="properties">Kembali</a>
            </div>
          </div>

          <?php if ($imgs): ?>
            <div class="panel admin-panel" style="margin-top:14px">
              <div class="admin-panel-head">
                <div>
                  <h2 class="admin-panel-title">Foto Saat Ini</h2>
                  <p class="muted">Klik hapus untuk menghapus gambar tertentu.</p>
                </div>
              </div>

              <hr class="line" />

              <div class="img-grid">
                <?php foreach ($imgs as $im): ?>
                  <div class="img-item">
                    <?php $imgPath = admin_public_path((string)$im['path']); ?>
                    <a class="img-thumb" href="<?= e($imgPath) ?>" target="_blank" rel="noopener" title="Buka gambar">
                      <img src="<?= e($imgPath) ?>" alt="Foto properti">
                      <?php if ((int)$im['sort_order'] === 1): ?>
                        <span class="img-badge">Cover</span>
                      <?php else: ?>
                        <span class="img-badge muted">#<?= (int)$im['sort_order'] ?></span>
                      <?php endif; ?>
                    </a>

                    <div class="img-actions">
                      <div class="muted" style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                        ID <?= (int)$im['id'] ?>
                      </div>

                      <form method="post" action="property_image_delete" class="admin-inline" style="margin:0">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$im['id'] ?>">
                        <input type="hidden" name="property_id" value="<?= (int)$id ?>">
                        <button class="action danger" type="submit">Hapus</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

            </div>
          <?php endif; ?>

        </div>
      </div>
    </form>

  </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

<script>
(function () {
  const textarea = document.getElementById('videos');
  const preview = document.getElementById('video-preview');
  const input = document.getElementById('video_url_input');
  const addBtn = document.getElementById('add-video-btn');
  if (!textarea || !preview) return;

  function extractId(url) {
    const u = String(url || '').trim();
    if (!u) return null;
    let m = u.match(/youtu\.be\/([A-Za-z0-9_-]{11})/);
    if (m) return m[1];
    m = u.match(/[?&]v=([A-Za-z0-9_-]{11})/);
    if (m) return m[1];
    m = u.match(/youtube\.com\/embed\/([A-Za-z0-9_-]{11})/);
    if (m) return m[1];
    m = u.match(/youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/);
    if (m) return m[1];
    return null;
  }

  function renderPreview() {
    const lines = textarea.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
    const ids = [];
    lines.forEach(line => {
      const id = extractId(line);
      if (id && !ids.includes(id)) ids.push(id);
    });

    preview.innerHTML = '';
    if (!ids.length) return;

    ids.forEach(id => {
      const item = document.createElement('div');
      item.className = 'img-item';

      const link = document.createElement('a');
      link.className = 'img-thumb video-thumb';
      link.href = 'https://www.youtube.com/watch?v=' + id;
      link.target = '_blank';
      link.rel = 'noopener';
      link.title = 'Buka video';

      const img = document.createElement('img');
      img.alt = 'Preview video YouTube';
      img.src = 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';

      link.appendChild(img);
      item.appendChild(link);
      preview.appendChild(item);
    });
  }

  function addUrl() {
    const val = input ? input.value.trim() : '';
    if (!val) return;
    const current = textarea.value.trim();
    textarea.value = current ? (current + "\n" + val) : val;
    if (input) input.value = '';
    renderPreview();
  }

  textarea.addEventListener('input', renderPreview);
  if (addBtn) addBtn.addEventListener('click', addUrl);
  if (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addUrl();
      }
    });
  }

  renderPreview();
})();
</script>

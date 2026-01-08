<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$page_title = 'About';
$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
$aboutIntro = setting('about_intro', 'RM Properti adalah katalog listing properti dengan detail jelas dan akses cepat ke sales, agar proses konsultasi sampai transaksi lebih rapi.') ?? 'RM Properti adalah katalog listing properti dengan detail jelas dan akses cepat ke sales, agar proses konsultasi sampai transaksi lebih rapi.';

$kpi1Label = setting('about_kpi1_label', 'Fokus') ?? 'Fokus';
$kpi1Value = setting('about_kpi1_value', 'Listing Berkualitas') ?? 'Listing Berkualitas';
$kpi1Desc  = setting('about_kpi1_desc', 'Info ringkas, jelas, dan mudah dibandingkan.') ?? 'Info ringkas, jelas, dan mudah dibandingkan.';
$kpi2Label = setting('about_kpi2_label', 'Kecepatan') ?? 'Kecepatan';
$kpi2Value = setting('about_kpi2_value', 'Respons Cepat') ?? 'Respons Cepat';
$kpi2Desc  = setting('about_kpi2_desc', 'Akses langsung ke sales yang menangani listing.') ?? 'Akses langsung ke sales yang menangani listing.';
$kpi3Label = setting('about_kpi3_label', 'Proses') ?? 'Proses';
$kpi3Value = setting('about_kpi3_value', 'Pendampingan') ?? 'Pendampingan';
$kpi3Desc  = setting('about_kpi3_desc', 'Dari konsultasi sampai closing lebih rapi.') ?? 'Dari konsultasi sampai closing lebih rapi.';

$sec1Title = setting('about_sec1_title', 'Apa yang kami tampilkan') ?? 'Apa yang kami tampilkan';
$sec2Title = setting('about_sec2_title', 'Standar layanan') ?? 'Standar layanan';

function about_list(string $key, array $fallback): array {
  $raw = setting($key, null);
  if ($raw) {
    $arr = json_decode($raw, true);
    if (is_array($arr) && $arr) {
      return array_map('strval', $arr);
    }
  }
  return $fallback;
}

$sec1List = about_list('about_sec1_list', [
  'Harga, lokasi, spesifikasi',
  'Foto cover & galeri',
  'Kontak sales yang menangani listing',
]);
$sec2List = about_list('about_sec2_list', [
  'Respons cepat',
  'Transparansi informasi',
  'Pendampingan hingga closing',
]);

$portfolioTitleDefault = 'PORTOFOLIO ' . $siteName;
$portfolioSubtitleDefault = 'Terima kasih atas kepercayaan Anda';
$portfolioTitle = setting('about_portfolio_title', $portfolioTitleDefault) ?? $portfolioTitleDefault;
$portfolioSubtitle = setting('about_portfolio_subtitle', $portfolioSubtitleDefault) ?? $portfolioSubtitleDefault;

$portfolioItems = [];
$st = db()->query("SELECT id, title, image_path, url
                   FROM portfolio_items
                   WHERE is_active=1
                   ORDER BY sort_order ASC, id DESC");
$portfolioItems = $st->fetchAll();

function portfolio_logo_url_public(?string $path): ?string {
  if (!$path) return null;
  if (str_starts_with($path, 'data:')) return $path;
  if (preg_match('~^https?://~i', $path) || str_starts_with($path, '/')) return $path;

  $local = __DIR__ . '/' . ltrim($path, '/');
  if (!file_exists($local)) return null;
  return abs_url($path);
}

$page_description = str_excerpt($aboutIntro, 155);
$page_og_type = 'website';
$page_canonical = site_url('about');

include __DIR__ . '/header.php';
?>

<section class="about-hero">
  <div class="container about-hero-inner">
    <span class="badge-brand">Tentang Kami</span>
    <h1 class="about-title">Tentang <?= e($siteName) ?></h1>
    <p class="about-subtitle">
      <?= e($aboutIntro) ?>
    </p>
  </div>
</section>

<div class="container">
  <!-- KPI / Highlight -->
  <section class="about-kpis">
    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi1Label) ?></div>
      <div class="kpi-value"><?= e($kpi1Value) ?></div>
      <div class="kpi-desc"><?= e($kpi1Desc) ?></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi2Label) ?></div>
      <div class="kpi-value"><?= e($kpi2Value) ?></div>
      <div class="kpi-desc"><?= e($kpi2Desc) ?></div>
    </div>

    <div class="kpi-card">
      <div class="kpi-label"><?= e($kpi3Label) ?></div>
      <div class="kpi-value"><?= e($kpi3Value) ?></div>
      <div class="kpi-desc"><?= e($kpi3Desc) ?></div>
    </div>
  </section>

  <!-- Konten utama -->
  <section class="panel panel-lg about-panel">
    <div class="kv">
      <article class="panel about-box">
        <h2 class="section-title"><?= e($sec1Title) ?></h2>
        <ul class="list">
          <?php foreach ($sec1List as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </article>

      <article class="panel about-box about-box-accent">
        <h2 class="section-title"><?= e($sec2Title) ?></h2>
        <ul class="list">
          <?php foreach ($sec2List as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </article>
    </div>
  </section>
</div>

<section class="about-portfolio">
  <div class="container">
    <div class="about-portfolio-head">
      <h2 class="about-portfolio-title"><?= e($portfolioTitle) ?></h2>
      <p class="about-portfolio-subtitle"><?= e($portfolioSubtitle) ?></p>
    </div>

    <?php if ($portfolioItems): ?>
      <div class="portfolio-grid">
        <?php foreach ($portfolioItems as $item): ?>
          <?php
            $logoUrl = portfolio_logo_url_public($item['image_path'] ?? null);
            if (!$logoUrl) continue;
            $link = trim((string)($item['url'] ?? ''));
            $isExternal = $link !== '' && preg_match('~^https?://~i', $link);
            $target = $isExternal ? '_blank' : '_self';
          ?>
          <?php if ($link !== ''): ?>
            <a
              class="portfolio-card"
              href="<?= e($link) ?>"
              target="<?= e($target) ?>"
              <?= $isExternal ? 'rel="noopener"' : '' ?>
              data-full="<?= e($logoUrl) ?>"
              data-link="<?= e($link) ?>"
            >
              <img src="<?= e($logoUrl) ?>" alt="<?= e($item['title'] ?? '') ?>" loading="lazy">
            </a>
          <?php else: ?>
            <div class="portfolio-card" data-full="<?= e($logoUrl) ?>" data-link="">
              <img src="<?= e($logoUrl) ?>" alt="<?= e($item['title'] ?? '') ?>" loading="lazy">
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="muted">Belum ada portofolio ditampilkan.</div>
    <?php endif; ?>
  </div>
</section>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'Organization',
  'name' => $siteName,
  'url' => site_url(''),
  'logo' => abs_url(setting('logo_path', 'Assets/logo.png') ?? 'Assets/logo.png'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<div class="lightbox" id="portfolio-lightbox" hidden>
  <div class="lb-backdrop" data-close></div>
  <div class="lb-dialog" role="dialog" aria-modal="true" aria-label="Pratinjau portofolio">
    <button class="lb-close" type="button" data-close aria-label="Tutup">×</button>
    <img class="lb-img" id="portfolio-lb-img" src="" alt="Logo portofolio" />
    <a class="lb-open" id="portfolio-lb-link" href="#" target="_blank" rel="noopener" hidden>Buka link</a>
  </div>
</div>

<script>
  (function () {
    const items = document.querySelectorAll('.portfolio-card[data-full]');
    const lb = document.getElementById('portfolio-lightbox');
    const img = document.getElementById('portfolio-lb-img');
    const link = document.getElementById('portfolio-lb-link');
    if (!items.length || !lb || !img || !link) return;

    function openLb(src, href) {
      img.src = src;
      if (href) {
        link.href = href;
        link.hidden = false;
      } else {
        link.hidden = true;
      }
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeLb() {
      lb.hidden = true;
      img.src = '';
      document.body.style.overflow = '';
    }

    items.forEach((el) => {
      el.addEventListener('click', function (e) {
        const src = el.getAttribute('data-full') || '';
        if (!src) return;
        e.preventDefault();
        const href = el.getAttribute('data-link') || '';
        openLb(src, href);
      });
    });

    lb.addEventListener('click', function (e) {
      if (e.target && e.target.hasAttribute('data-close')) closeLb();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !lb.hidden) closeLb();
    });
  })();
</script>

<?php include __DIR__ . '/footer.php'; ?>

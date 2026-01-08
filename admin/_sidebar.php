<?php
// admin/_sidebar.php
$active = $active ?? '';

function admin_nav_item(string $href, string $label, string $key, string $active): string {
  $isActive = ($key === $active);
  $cls  = $isActive ? 'active' : '';
  $aria = $isActive ? ' aria-current="page"' : '';
  return '<a class="'.e($cls).'" href="'.e($href).'"'.$aria.'>'.e($label).'</a>';
}
?>

<aside id="adminSidebar" class="admin-sidebar" aria-label="Sidebar Admin">
  <div class="admin-sidebar-title">Navigasi</div>

  <nav class="admin-nav">
    <?= admin_nav_item(admin_url('index'), 'Dashboard', 'dashboard', $active) ?>
    <?= admin_nav_item(admin_url('properties'), 'Kelola Properti', 'properties', $active) ?>
    <?php if (is_superadmin() || is_admin()): ?>
      <?= admin_nav_item(admin_url('sales'), 'Kelola Sales', 'sales', $active) ?>
      <?= admin_nav_item(admin_url('portfolio'), 'Portofolio', 'portfolio', $active) ?>
      <?= admin_nav_item(admin_url('settings'), 'Pengaturan Brand', 'settings_brand', $active) ?>
      <?= admin_nav_item(admin_url('settings_banners'), 'Pengaturan Banner', 'settings_banners', $active) ?>
      <?= admin_nav_item(admin_url('settings_home'), 'Pengaturan Home', 'settings_home', $active) ?>
      <?= admin_nav_item(admin_url('settings_about'), 'Pengaturan About', 'settings_about', $active) ?>
      <?= admin_nav_item(admin_url('settings_contact'), 'Pengaturan Contact', 'settings_contact', $active) ?>
      <?= admin_nav_item(admin_url('users'), 'Manajemen Admin', 'users', $active) ?>
    <?php endif; ?>
  </nav>

  <div class="admin-sidebar-foot">
    <a class="action action-block" href="<?= e(site_url('')) ?>" target="_blank" rel="noopener">Lihat Website</a>
    <a class="action action-block" href="<?= e(admin_url('logout')) ?>">Logout</a>
  </div>
</aside>

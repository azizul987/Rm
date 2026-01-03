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
    <?= admin_nav_item('index.php', 'Dashboard', 'dashboard', $active) ?>
    <?= admin_nav_item('properties.php', 'Kelola Properti', 'properties', $active) ?>
    <?php if (is_superadmin() || is_admin()): ?>
      <?= admin_nav_item('sales.php', 'Kelola Sales', 'sales', $active) ?>
      <?= admin_nav_item('settings.php', 'Pengaturan Brand', 'settings_brand', $active) ?>
      <?= admin_nav_item('settings_home.php', 'Pengaturan Home', 'settings_home', $active) ?>
      <?= admin_nav_item('settings_about.php', 'Pengaturan About', 'settings_about', $active) ?>
      <?= admin_nav_item('settings_contact.php', 'Pengaturan Contact', 'settings_contact', $active) ?>
      <?= admin_nav_item('users.php', 'Manajemen Admin', 'users', $active) ?>
    <?php endif; ?>
  </nav>

  <div class="admin-sidebar-foot">
    <a class="action action-block" href="../index.php" target="_blank" rel="noopener">Lihat Website</a>
    <a class="action action-block" href="logout.php">Logout</a>
  </div>
</aside>

<?php
// admin/_footer.php
// $siteName biasanya sudah ada dari _header.php, tapi aman kalau belum:
if (!isset($siteName) || !$siteName) {
  $siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
}
?>

  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>
        <div class="footer-title"><?= e($siteName) ?></div>
        <div class="muted">Admin Panel</div>
      </div>
      <div class="muted">© <?= date('Y') ?> <?= e($siteName) ?></div>
    </div>
  </footer>

  <script>
    (function () {
      const overlay = document.querySelector('.admin-overlay');

      // ===== HEADER NAV (hamburger) =====
      const navBtn = document.querySelector('.nav-toggle');
      const nav = document.getElementById('adminNav');

      function setOverlay(open){
        if (!overlay) return;
        overlay.hidden = !open;
      }

      function closeHeaderNav(){
        document.body.classList.remove('nav-open');
        if (navBtn) {
          navBtn.setAttribute('aria-expanded', 'false');
          navBtn.setAttribute('aria-label', 'Buka menu');
        }
        const keep = document.body.classList.contains('admin-sidebar-open');
        setOverlay(keep);
      }

      if (navBtn && nav) {
        navBtn.addEventListener('click', function () {
          document.body.classList.remove('admin-sidebar-open');
          const sbBtn = document.querySelector('.admin-sidebar-toggle');
          if (sbBtn) sbBtn.setAttribute('aria-expanded', 'false');

          const isOpen = document.body.classList.toggle('nav-open');
          navBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          navBtn.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
          setOverlay(isOpen);
        });

        nav.addEventListener('click', function (e) {
          if (e.target && e.target.tagName === 'A') closeHeaderNav();
        });
      }

      // ===== SIDEBAR DRAWER (mobile) =====
      const sbBtn = document.querySelector('.admin-sidebar-toggle');
      const sidebar = document.getElementById('adminSidebar');

      function closeSidebar(){
        document.body.classList.remove('admin-sidebar-open');
        if (sbBtn) sbBtn.setAttribute('aria-expanded', 'false');
        const keep = document.body.classList.contains('nav-open');
        setOverlay(keep);
      }

      if (sbBtn && sidebar) {
        sbBtn.addEventListener('click', function () {
          closeHeaderNav();
          const isOpen = document.body.classList.toggle('admin-sidebar-open');
          sbBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          setOverlay(isOpen);
        });
      }

      if (overlay) {
        overlay.addEventListener('click', function () {
          closeSidebar();
          closeHeaderNav();
          setOverlay(false);
        });
      }

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeSidebar();
          closeHeaderNav();
          setOverlay(false);
        }
      });

      if (sidebar) {
        sidebar.addEventListener('click', function (e) {
          if (e.target && e.target.tagName === 'A') closeSidebar();
        });
      }
    })();
  </script>

</body>
</html>

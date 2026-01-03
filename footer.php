  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-brand">
        <div class="footer-title">RM Properti</div>
        <div class="footer-subtitle">Katalog properti dan sales (public site).</div>
      </div>

      <div class="footer-meta">
        <div class="footer-copy">© <?= date('Y') ?> RM Properti</div>
      </div>
    </div>
  </footer>

  <script>
    (function () {
      const btn = document.querySelector('.nav-toggle');
      const nav = document.getElementById('primary-nav');
      if (!btn || !nav) return;

      btn.addEventListener('click', function () {
        const isOpen = document.body.classList.toggle('nav-open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        btn.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
      });

      // Tutup menu saat klik link (mobile)
      nav.addEventListener('click', function (e) {
        if (e.target && e.target.tagName === 'A' && document.body.classList.contains('nav-open')) {
          document.body.classList.remove('nav-open');
          btn.setAttribute('aria-expanded', 'false');
          btn.setAttribute('aria-label', 'Buka menu');
        }
      });
    })();
  </script>

</body>
</html>

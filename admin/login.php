<?php
require __DIR__ . '/_init.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $email = trim($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');

  $stmt = db()->prepare("SELECT id, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if ($u && password_verify($pass, $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_role'] = $u['role'] ?? 'editor';
    $_SESSION['user_status'] = $u['status'] ?? 'active';
    header('Location: ' . admin_url('index.php'));
    exit;
  }
  $error = 'Email atau password salah.';
}

/* Brand (opsional) */
$siteName = setting('site_name', 'RM Properti') ?? 'RM Properti';
$rawLogo  = setting('logo_path', null);

/* Fix path untuk folder /admin */
$logoUrl = null;
if (!empty($rawLogo)) {
  if (preg_match('#^https?://#i', $rawLogo) || str_starts_with($rawLogo, '/')) {
    $logoUrl = $rawLogo;
  } else {
    $logoUrl = '../' . ltrim($rawLogo, '/');
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Admin — <?= e($siteName) ?></title>

  <link rel="stylesheet" href="../css/style.css" />
  <style>
    .auth-wrap{
      min-height: 100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 28px 16px;
      background: linear-gradient(180deg, #FFFFFF 0%, #F0F9FF 100%);
    }
    .auth-card{
      width: 100%;
      max-width: 520px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 18px 40px -18px rgba(0, 154, 222, 0.28);
      overflow: hidden;
    }
    .auth-head{
      padding: 18px 18px 14px;
      border-bottom: 1px solid var(--border);
      display:flex;
      align-items:center;
      gap: 12px;
      background: #fff;
    }
    .auth-logo{
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display:grid;
      place-items:center;
      background: rgba(0, 154, 222, 0.10);
      border: 1px solid rgba(0, 154, 222, 0.20);
      overflow:hidden;
      flex: 0 0 auto;
    }
    .auth-logo img{ width: 100%; height: 100%; object-fit: contain; }
    .auth-brand{ line-height: 1.15; min-width: 0; }
    .auth-brand .name{
      font-weight: 950;
      color: var(--brand-blue);
      font-size: 16px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .auth-brand .sub{
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .auth-body{ padding: 18px; }
    .auth-title{
      margin: 0 0 6px;
      font-size: 20px;
      font-weight: 950;
      color: var(--text-main);
      letter-spacing: -0.2px;
    }
    .auth-desc{
      margin: 0 0 14px;
      color: var(--text-muted);
      font-size: 13px;
      line-height: 1.6;
    }

    .auth-alert{
      border: 1px solid rgba(215, 25, 32, 0.35);
      background: rgba(215, 25, 32, 0.06);
      color: #991b1b;
      border-radius: 12px;
      padding: 10px 12px;
      font-weight: 700;
      font-size: 13px;
      margin-bottom: 12px;
    }

    .auth-form{ display: grid; gap: 12px; margin-top: 10px; }
    .field{ display:grid; gap: 6px; }
    .label{
      font-size: 12px;
      font-weight: 800;
      color: var(--text-muted);
    }
    .input{ width: 100%; }
    .btn{ width: 100%; }

    /* Password field with toggle */
    .pw-wrap{ position: relative; }
    .pw-wrap .input{ padding-right: 46px; }
    .pw-toggle{
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: #fff;
      cursor: pointer;
      padding: 0;
    }
    .pw-toggle:hover{
      box-shadow: var(--shadow);
      border-color: rgba(0,0,0,0.10);
    }
    .pw-toggle:focus-visible{
      outline: 3px solid rgba(0, 154, 222, 0.35);
      outline-offset: 2px;
    }
    .pw-toggle svg{ width: 18px; height: 18px; color: var(--text-muted); }

    .auth-foot{
      padding: 14px 18px 18px;
      border-top: 1px solid var(--border);
      display:flex;
      justify-content: space-between;
      align-items:center;
      gap: 12px;
      flex-wrap: wrap;
      background: #fff;
    }
    .auth-foot a{
      color: var(--brand-blue);
      font-weight: 800;
      font-size: 13px;
    }
    .auth-foot a:hover{ text-decoration: underline; }

    @media (max-width: 420px){
      .auth-head{ padding: 16px; }
      .auth-body{ padding: 16px; }
      .auth-foot{ padding: 12px 16px 16px; }
    }
  </style>
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card" aria-label="Login Admin">

      <div class="auth-head">
        <div class="auth-logo" aria-hidden="true">
          <?php if (!empty($logoUrl)): ?>
            <img src="<?= e($logoUrl) ?>" alt="" />
          <?php else: ?>
            <span style="font-weight:950;color:var(--brand-blue)">RM</span>
          <?php endif; ?>
        </div>

        <div class="auth-brand">
          <div class="name"><?= e($siteName) ?></div>
          <div class="sub">Admin Panel</div>
        </div>
      </div>

      <div class="auth-body">
        <h1 class="auth-title">Login Admin</h1>
        <p class="auth-desc">Masuk untuk mengelola properti, sales, dan pengaturan website.</p>

        <?php if ($error): ?>
          <div class="auth-alert" role="alert">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="post" class="auth-form" autocomplete="on">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

          <div class="field">
            <label class="label" for="email">Email</label>
            <input
              id="email"
              class="input"
              type="email"
              name="email"
              placeholder="contoh@domain.com"
              value="<?= e($email) ?>"
              required
              autofocus
            />
          </div>

          <div class="field">
            <label class="label" for="password">Password</label>

            <div class="pw-wrap">
              <input
                id="password"
                class="input"
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
              />

              <button
                class="pw-toggle"
                type="button"
                id="pwToggle"
                aria-label="Tampilkan password"
                aria-pressed="false"
                title="Tampilkan password"
              >
                <!-- Eye icon -->
                <svg id="iconEye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>

                <!-- Eye-off icon (hidden by default) -->
                <svg id="iconEyeOff" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                  <path d="M3 3l18 18"></path>
                  <path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.4"></path>
                  <path d="M9.9 4.2A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-4.3 5.1"></path>
                  <path d="M6.6 6.6C3.6 8.7 2 12 2 12s3.5 7 10 7a9.9 9.9 0 0 0 3.2-.5"></path>
                </svg>
              </button>
            </div>

            <div class="muted" style="font-size:12px;margin-top:2px">
              Klik ikon untuk melihat/menyembunyikan password.
            </div>
          </div>

          <button class="btn btn-accent" type="submit">Masuk</button>
        </form>
      </div>

      <div class="auth-foot">
        <a href="<?= e(site_url('')) ?>">← Kembali ke Website</a>
        <span class="muted" style="font-size:12px">© <?= date('Y') ?> <?= e($siteName) ?></span>
      </div>

    </section>
  </main>

  <script>
    (function(){
      const input = document.getElementById('password');
      const btn = document.getElementById('pwToggle');
      const eye = document.getElementById('iconEye');
      const eyeOff = document.getElementById('iconEyeOff');

      if (!input || !btn || !eye || !eyeOff) return;

      btn.addEventListener('click', function(){
        const isHidden = (input.type === 'password');
        input.type = isHidden ? 'text' : 'password';

        btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        btn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        btn.title = isHidden ? 'Sembunyikan password' : 'Tampilkan password';

        eye.style.display = isHidden ? 'none' : 'block';
        eyeOff.style.display = isHidden ? 'block' : 'none';

        input.focus();
      });
    })();
  </script>
</body>
</html>

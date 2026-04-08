<?php
require_once __DIR__ . '/../lang_init.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . ($role === 'admin' ? '../admin/' : '../metrologue/'));
    exit;
}
$lang_code = $_SESSION['lang'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang['login'] ?> — CM2E</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">

  <!-- LEFT PANEL -->
  <div class="auth-panel">
    <div class="auth-brand">
      <div class="auth-brand-line"></div>
      <span class="auth-brand-text">CM2E · <?= $lang['time_tracking_system'] ?></span>
    </div>

    <div class="auth-card">

      <?php if (isset($_SESSION['login_success'])): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['login_success']) ?></div>
        <?php unset($_SESSION['login_success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">✕ <?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <h1><?= $lang['welcome_back'] ?> 👋</h1>
      <p class="auth-subtitle"><?= $lang['login_workspace'] ?></p>

      <form action="login_process.php" method="POST">

        <div class="form-group">
          <label><?= $lang['email_address'] ?></label>
          <input type="email" name="email" placeholder="votre@cm2e.tn" required autocomplete="email">
        </div>

        <div class="form-group">
          <label><?= $lang['password'] ?></label>
          <div class="pw-wrap">
            <input type="password" name="password" id="pwd" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="toggle-password" onclick="togglePwd()">
              <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary"><?= $lang['login_button'] ?></button>

      </form>

      <div class="auth-links">
        <a href="forgot_password.php" class="auth-link"><?= $lang['forgot_password'] ?></a>
      </div>

    </div>
  </div>

  <!-- RIGHT VISUAL -->
  <div class="auth-visual">
    <div class="auth-visual-shape"></div>
    <div class="auth-visual-inner">
      <div class="auth-logo-box">
        <img src="../public/assets/img/logocm2e.png" alt="CM2E" onerror="this.src='../public/assets/img/logocm2e.jpg'">
      </div>
      <div class="auth-tagline"><?= $lang['company_full'] ?></div>
      <div class="auth-tagline-sub"><?= $lang['tracking_words'] ?></div>
      <div class="auth-stats">
        <div class="auth-stat">
          <div class="auth-stat-num">100%</div>
          <div class="auth-stat-label"><?= $lang['digital'] ?></div>
        </div>
        <div class="auth-stat-div"></div>
        <div class="auth-stat">
          <div class="auth-stat-num">⚡</div>
          <div class="auth-stat-label"><?= $lang['realtime'] ?></div>
        </div>
        <div class="auth-stat-div"></div>
        <div class="auth-stat">
          <div class="auth-stat-num">🔒</div>
          <div class="auth-stat-label"><?= $lang['secure'] ?></div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function togglePwd() {
  const i = document.getElementById('pwd');
  i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>

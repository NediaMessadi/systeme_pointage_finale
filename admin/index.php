<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lang_init.php';
requireAdmin();

$page = $_GET['page'] ?? 'dashboard';
$admin_pages = ['dashboard','metrologues','projets','pointages','planning','scores','securite','systeme'];
if (!in_array($page, $admin_pages)) $page = 'dashboard';

$theme   = $_SESSION['theme']  ?? 'light';
$body_cl = $theme === 'dark' ? 'dark-mode' : '';
$prenom  = $_SESSION['user_prenom'] ?? 'Admin';
$nom     = $_SESSION['user_nom']    ?? '';
$initial = strtoupper(substr($prenom, 0, 1));
$photo   = $_SESSION['user_photo']  ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang['app_name'] ?> — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body class="<?= $body_cl ?>">

<!-- TOAST -->
<div class="toast" id="toast"></div>

<div class="app-shell">

  <!-- SIDEBAR ADMIN -->
  <aside class="sidebar">
    <div class="sb-brand">
      <img src="../public/assets/img/logocm2e.png" alt="CM2E" class="sb-logo" onerror="this.src='../public/assets/img/logocm2e.jpg'">
      <div class="sb-brand-text">
        <div class="sb-brand-name">CM2E</div>
        <div class="sb-brand-sub">Administration</div>
      </div>
    </div>

    <div class="sb-profile">
      <div class="sb-avatar" style="background:var(--red)">
        <?php if ($photo): ?>
          <img src="../uploads/<?= htmlspecialchars($photo) ?>" alt="Photo">
        <?php else: ?>
          <span><?= $initial ?></span>
        <?php endif; ?>
      </div>
      <div class="sb-profile-info">
        <div class="sb-name"><?= htmlspecialchars($prenom . ' ' . $nom) ?></div>
        <div class="sb-role-badge">Administrateur</div>
      </div>
    </div>

    <nav class="sb-nav">
      <div class="sb-section">Vue d'ensemble</div>
      <a href="?page=dashboard" class="sb-link <?= $page==='dashboard'?'active':'' ?>">
        <span class="sb-icon">▦</span>Tableau de bord
      </a>

      <div class="sb-section">Gestion</div>
      <a href="?page=metrologues" class="sb-link <?= $page==='metrologues'?'active':'' ?>">
        <span class="sb-icon">👷</span>Métrologues
      </a>
      <a href="?page=projets" class="sb-link <?= $page==='projets'?'active':'' ?>">
        <span class="sb-icon">📁</span>Projets
      </a>
      <a href="?page=pointages" class="sb-link <?= $page==='pointages'?'active':'' ?>">
        <span class="sb-icon">📋</span>Pointages
      </a>
      <a href="?page=planning" class="sb-link <?= $page==='planning'?'active':'' ?>">
        <span class="sb-icon">📅</span>Planning
      </a>

      <div class="sb-section">Performance</div>
      <a href="?page=scores" class="sb-link <?= $page==='scores'?'active':'' ?>">
        <span class="sb-icon">🏆</span>Scores & Classement
      </a>

      <div class="sb-section">Administration</div>
      <a href="?page=securite" class="sb-link <?= $page==='securite'?'active':'' ?>">
        <span class="sb-icon">🔒</span>Sécurité
      </a>
      <a href="?page=systeme" class="sb-link <?= $page==='systeme'?'active':'' ?>">
        <span class="sb-icon">⚙</span>Système
      </a>
    </nav>

    <div class="sb-footer">
      <a href="?page=systeme" class="sb-footer-link">
        <span class="sb-conn" id="sb-conn">● Connecté</span>
      </a>
      <a href="../auth/logout.php" class="sb-logout">⏻ Déconnexion</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    <?php include __DIR__ . '/pages/' . $page . '.php'; ?>
  </main>

</div>

<script src="../public/assets/js/admin.js?v=<?= @filemtime(__DIR__ . '/../public/assets/js/admin.js') ?: time() ?>"></script>
</body>
</html>

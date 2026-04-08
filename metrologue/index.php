<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lang_init.php';
requireAuth();
if (isAdmin()) { header('Location: ../admin/'); exit; }

$page = $_GET['page'] ?? 'tableau_bord';
$pages_ok = ['tableau_bord','calendrier_performance','projets','projet_courant','parametres','profil_utilisateur','securite_compte','apropos'];
if (!in_array($page, $pages_ok)) $page = 'tableau_bord';

$theme   = $_SESSION['theme']  ?? 'light';
$taille  = $_SESSION['taille'] ?? 'normal';
$body_cl = [];
if ($theme  === 'dark')  $body_cl[] = 'dark-mode';
if ($taille === 'grand') $body_cl[] = 'text-large';
$body_class = implode(' ', $body_cl);

$prenom  = $_SESSION['user_prenom'] ?? 'Utilisateur';
$nom     = $_SESSION['user_nom']    ?? '';
$initial = strtoupper(substr($prenom, 0, 1));
$photo   = $_SESSION['user_photo']  ?? null;
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang['app_name'] ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="<?= $body_class ?>">

<div class="app-layout">

  <!-- SIDEBAR MÉTROLOGUE -->
  <aside class="sidebar">
    <div class="sidebar-accent"></div>

    <div class="sidebar-logo">
      <img src="../public/assets/img/logocm2e.png" alt="CM2E" onerror="this.src='../public/assets/img/logocm2e.jpg'">
    </div>

    <div class="sidebar-profile">
      <div class="sidebar-avatar">
        <?php if ($photo): ?>
          <img src="../uploads/<?= htmlspecialchars($photo) ?>?v=<?= time() ?>" alt="Photo">
        <?php else: ?>
          <span><?= $initial ?></span>
        <?php endif; ?>
      </div>
      <div class="sidebar-profile-info">
        <h4><?= htmlspecialchars($prenom . ' ' . $nom) ?></h4>
        <small><?= $lang['job_title'] ?></small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label"><?= $lang['nav_main'] ?></div>
      <a href="?page=tableau_bord" class="<?= $page==='tableau_bord'?'active':'' ?>">
        <span class="nav-icon">▦</span><?= $lang['dashboard'] ?>
      </a>
      <a href="?page=calendrier_performance" class="<?= $page==='calendrier_performance'?'active':'' ?>">
        <span class="nav-icon">📅</span><?= $lang['performance_calendar'] ?>
      </a>

      <div class="nav-section-label"><?= $lang['nav_projects'] ?></div>
      <a href="?page=projets" class="<?= $page==='projets'?'active':'' ?>">
        <span class="nav-icon">📁</span><?= $lang['projects'] ?>
      </a>
      <a href="?page=projet_courant" class="<?= $page==='projet_courant'?'active':'' ?>">
        <span class="nav-icon">▶</span><?= $lang['current_project'] ?>
      </a>

      <div class="nav-section-label"><?= $lang['nav_account'] ?></div>
      <a href="?page=profil_utilisateur" class="<?= $page==='profil_utilisateur'?'active':'' ?>">
        <span class="nav-icon">👤</span><?= $lang['my_profile'] ?>
      </a>
      <a href="?page=parametres" class="<?= $page==='parametres'?'active':'' ?>">
        <span class="nav-icon">⚙</span><?= $lang['settings'] ?>
      </a>
      <a href="?page=securite_compte" class="<?= $page==='securite_compte'?'active':'' ?>">
        <span class="nav-icon">🔑</span>Sécurité
      </a>
      <a href="?page=apropos" class="<?= $page==='apropos'?'active':'' ?>">
        <span class="nav-icon">ℹ</span><?= $lang['about'] ?>
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="../auth/logout.php">
        <span>⏻</span> <?= $lang['logout'] ?>
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    <?php include __DIR__ . '/pages/' . $page . '.php'; ?>
  </main>

</div>

<script src="../public/assets/js/metrologue.js"></script>
</body>
</html>

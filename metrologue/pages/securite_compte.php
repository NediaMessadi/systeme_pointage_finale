<?php
require_once __DIR__ . '/../../config/config.php';
$uid = currentUserId();
$pdo = db();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new1    = $_POST['new_password']     ?? '';
    $new2    = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $err = 'Mot de passe actuel incorrect.';
    } elseif (strlen($new1) < 8) {
        $err = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($new1 !== $new2) {
        $err = 'Les mots de passe ne correspondent pas.';
    } else {
        $new_hash = password_hash($new1, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$new_hash, $uid]);
        appLog('🔑', 'Mot de passe modifié', 'ok', $uid);
        $msg = 'Mot de passe modifié avec succès.';
    }
}
?>

<div class="page-header animate-in">
  <div><h1><?= $lang['account_security'] ?></h1><div class="page-subtitle"><?= $lang['manage_password'] ?></div></div>
</div>

<?php if ($msg): ?><div class="alert alert-success animate-in">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error animate-in">✕ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="settings-card animate-in">
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">🔑</span><?= $lang['change_password'] ?></div>
    <form method="POST">
      <div class="fg" style="margin-bottom:14px">
        <label class="fl"><?= $lang['current_password'] ?></label>
        <input class="fi" type="password" name="current_password" placeholder="••••••••" required>
      </div>
      <div class="fg" style="margin-bottom:14px">
        <label class="fl"><?= $lang['new_password'] ?></label>
        <input class="fi" type="password" name="new_password" placeholder="••••••••" minlength="8" required>
      </div>
      <div class="fg" style="margin-bottom:14px">
        <label class="fl"><?= $lang['confirm_new_password'] ?></label>
        <input class="fi" type="password" name="confirm_password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-primary" style="width:auto;padding:10px 28px">🔒 <?= $lang['update'] ?></button>
    </form>
  </div>
</div>

<?php
require_once __DIR__ . '/../../config/config.php';
$uid = currentUserId();
$pdo = db();

$msg = '';

/* Mise à jour profil */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = clean($_POST['prenom'] ?? '');
    $nom    = clean($_POST['nom']    ?? '');
    $tel    = clean($_POST['telephone'] ?? '');
    if ($prenom && $nom) {
        $pdo->prepare("UPDATE users SET prenom=?,nom=?,telephone=? WHERE id=?")->execute([$prenom,$nom,$tel,$uid]);
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom']    = $nom;
        $msg = 'Profil mis à jour avec succès.';
    }
}

/* Upload photo */
if (!empty($_FILES['photo']['tmp_name'])) {
    $ext  = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $ok   = ['jpg','jpeg','png','webp'];
    if (in_array($ext, $ok) && $_FILES['photo']['size'] < 2000000) {
        $filename = "user_{$uid}." . $ext;
        $dest     = __DIR__ . '/../../uploads/' . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $pdo->prepare("UPDATE users SET photo=? WHERE id=?")->execute([$filename, $uid]);
            $_SESSION['user_photo'] = $filename;
            $msg = 'Photo mise à jour.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$initiale = strtoupper(substr($user['prenom'],0,1));
?>

<div class="page-header animate-in">
  <div><h1><?= $lang['my_profile'] ?></h1><div class="page-subtitle"><?= $lang['profile_subtitle'] ?></div></div>
</div>

<?php if ($msg): ?><div class="alert alert-success animate-in">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="settings-card animate-in">

  <!-- Photo -->
  <div class="settings-section" style="align-items:center;text-align:center;padding-bottom:24px">
    <div id="avatarPreview" class="profile-avatar-lg" style="background:<?= htmlspecialchars($user['color'] ?? '#E31E24') ?>">
      <?php if ($user['photo']): ?>
        <img src="../../uploads/<?= htmlspecialchars($user['photo']) ?>?v=<?= time() ?>" alt="Photo">
      <?php else: ?>
        <span><?= $initiale ?></span>
      <?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" style="margin-top:12px">
      <label class="btn-secondary" style="cursor:pointer;display:inline-block">
        📷 <?= $lang['change_photo'] ?>
        <input type="file" name="photo" accept="image/*" style="display:none" onchange="previewPhoto(this);this.form.submit()">
      </label>
    </form>
  </div>

  <!-- Infos personnelles -->
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">👤</span><?= $lang['personal_information'] ?></div>
    <form method="POST">
      <div class="fr2" style="margin-bottom:14px">
        <div class="fg"><label class="fl"><?= $lang['first_name'] ?></label><input class="fi" type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required></div>
        <div class="fg"><label class="fl"><?= $lang['last_name'] ?></label><input class="fi" type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required></div>
      </div>
      <div class="fg" style="margin-bottom:14px"><label class="fl"><?= $lang['phone'] ?></label><input class="fi" type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"></div>
      <button type="submit" name="update_profile" value="1" class="btn-primary" style="width:auto;padding:10px 28px">💾 <?= $lang['save'] ?></button>
    </form>
  </div>

  <!-- Infos compte (lecture seule) -->
  <div class="settings-section">
    <div class="settings-section-title"><span class="s-icon">🔑</span><?= $lang['account_information'] ?></div>
    <div class="info-row"><span class="info-label"><?= $lang['email'] ?></span><span class="info-value"><?= htmlspecialchars($user['email']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['role'] ?></span><span class="info-value"><?= ucfirst($user['role']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['level'] ?></span><span class="info-value"><?= htmlspecialchars($user['niveau']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['position'] ?></span><span class="info-value"><?= htmlspecialchars($user['poste'] ?? '—') ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['binder'] ?></span><span class="info-value"><?= $user['classeur'] ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['hired_on'] ?></span><span class="info-value"><?= $user['hire_date'] ? date('d/m/Y',strtotime($user['hire_date'])) : '—' ?></span></div>
    <div class="info-row"><span class="info-label"><?= $lang['current_score'] ?></span><span class="info-value"><strong><?= $user['score'] >= 0 ? '+'.$user['score'] : $user['score'] ?></strong></span></div>
  </div>

</div>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const av = document.getElementById('avatarPreview');
      av.innerHTML = '<img src="'+e.target.result+'" alt="Photo">';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

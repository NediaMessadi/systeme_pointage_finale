<?php
require_once __DIR__ . '/../lang_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name('cm2e_sess');
    session_start();
}
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . ($role === 'admin' ? '../admin/' : '../metrologue/')); exit;
}
$step    = $_SESSION['reset_step'] ?? 1;
$email   = $_SESSION['reset_email'] ?? '';
$error   = $_SESSION['reset_error'] ?? '';
$success = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mot de passe oublié — CM2E</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/style.css">
<style>
.reset-wrapper{display:flex;min-height:100vh;align-items:center;justify-content:center;background:var(--bg);}
.reset-card{background:var(--surface);border-radius:var(--radius,12px);border:1.5px solid var(--border);padding:40px;width:100%;max-width:420px;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.reset-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
.reset-logo img{height:36px;}
.reset-logo-title{font-size:18px;font-weight:800;color:#E31E24;}
.reset-logo-sub{font-size:10px;color:#94A3B8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.reset-card h2{font-size:20px;font-weight:800;color:#0F172A;margin-bottom:6px;}
.reset-card p.sub{font-size:13px;color:#64748B;margin-bottom:24px;}
.step-indicator{display:flex;gap:6px;margin-bottom:24px;}
.step-dot{flex:1;height:4px;border-radius:2px;background:#E2E8F0;}
.step-dot.done{background:#E31E24;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;}
.form-group input{width:100%;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:14px;font-family:inherit;transition:border-color .15s;}
.form-group input:focus{border-color:#E31E24;outline:none;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:42px;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94A3B8;padding:4px;}
.btn-red{width:100%;padding:11px;background:#E31E24;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s;margin-top:4px;}
.btn-red:hover{background:#b91c1c;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-err{background:#fee2e2;color:#991b1b;}
.alert-ok{background:#d1fae5;color:#065f46;}
.back-link{display:block;text-align:center;margin-top:16px;font-size:13px;color:#64748B;}
.back-link a{color:#E31E24;font-weight:600;}
.pw-strength{height:4px;border-radius:2px;margin-top:6px;transition:all .3s;background:#E2E8F0;}
.pw-strength.weak{background:#ef4444;width:30%;}
.pw-strength.medium{background:#f59e0b;width:65%;}
.pw-strength.strong{background:#10b981;width:100%;}
.pw-hint{font-size:11px;color:#94A3B8;margin-top:4px;}
</style>
</head>
<body>
<div class="reset-wrapper">
<div class="reset-card">

  <div class="reset-logo">
    <img src="../public/assets/img/logocm2e.png" alt="CM2E" onerror="this.style.display='none'">
    <div>
      <div class="reset-logo-title">CM2E</div>
      <div class="reset-logo-sub">Réinitialisation</div>
    </div>
  </div>

  <!-- Indicateur d'étapes -->
  <div class="step-indicator">
    <div class="step-dot <?= $step >= 1 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 2 ? 'done' : '' ?>"></div>
    <div class="step-dot <?= $step >= 3 ? 'done' : '' ?>"></div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-err">✕ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-ok">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <!-- ÉTAPE 1 : Saisir l'email -->
    <h2>Mot de passe oublié</h2>
    <p class="sub">Entrez votre adresse email professionnelle pour vérifier votre identité.</p>
    <form action="forgot_process.php" method="POST">
      <input type="hidden" name="step" value="1">
      <div class="form-group">
        <label>Adresse email</label>
        <input type="email" name="email" placeholder="votre@cm2e.tn" value="<?= htmlspecialchars($email) ?>" required autofocus>
      </div>
      <button type="submit" class="btn-red">Vérifier →</button>
    </form>

  <?php elseif ($step === 2): ?>
    <!-- ÉTAPE 2 : Nouveau mot de passe -->
    <h2>Nouveau mot de passe</h2>
    <p class="sub">Compte trouvé pour <strong><?= htmlspecialchars($email) ?></strong>. Choisissez un nouveau mot de passe.</p>
    <form action="forgot_process.php" method="POST" id="resetForm">
      <input type="hidden" name="step" value="2">
      <div class="form-group">
        <label>Nouveau mot de passe</label>
        <div class="pw-wrap">
          <input type="password" name="new_password" id="pw1" placeholder="••••••••" required minlength="6" oninput="checkStrength(this.value)">
          <button type="button" class="toggle-pw" onclick="togglePw('pw1')">👁</button>
        </div>
        <div class="pw-strength" id="strength-bar"></div>
        <div class="pw-hint" id="strength-hint">Minimum 6 caractères</div>
      </div>
      <div class="form-group">
        <label>Confirmer le mot de passe</label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="pw2" placeholder="••••••••" required minlength="6">
          <button type="button" class="toggle-pw" onclick="togglePw('pw2')">👁</button>
        </div>
      </div>
      <button type="submit" class="btn-red">Réinitialiser le mot de passe</button>
    </form>

  <?php elseif ($step === 3): ?>
    <!-- ÉTAPE 3 : Succès -->
    <h2>Mot de passe réinitialisé ✓</h2>
    <p class="sub">Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.</p>
    <a href="login.php" class="btn-red" style="display:block;text-align:center;text-decoration:none;padding:11px;border-radius:8px;background:#E31E24;color:#fff;font-weight:700;">Se connecter →</a>
  <?php endif; ?>

  <?php if ($step < 3): ?>
    <div class="back-link">← <a href="login.php">Retour à la connexion</a></div>
  <?php endif; ?>

</div>
</div>

<script>
function togglePw(id) {
  const i = document.getElementById(id);
  i.type = i.type === 'password' ? 'text' : 'password';
}
function checkStrength(v) {
  const bar  = document.getElementById('strength-bar');
  const hint = document.getElementById('strength-hint');
  if (!bar) return;
  if (v.length < 6) {
    bar.className = 'pw-strength'; hint.textContent = 'Trop court (min. 6 caractères)';
  } else if (v.length < 8 || !/[0-9]/.test(v)) {
    bar.className = 'pw-strength weak'; hint.textContent = 'Faible — ajoutez des chiffres';
  } else if (v.length < 12 || !/[A-Z]/.test(v)) {
    bar.className = 'pw-strength medium'; hint.textContent = 'Moyen — ajoutez des majuscules';
  } else {
    bar.className = 'pw-strength strong'; hint.textContent = 'Fort ✓';
  }
}
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  if (p1 !== p2) { e.preventDefault(); alert('Les mots de passe ne correspondent pas.'); }
});
</script>
</body>
</html>

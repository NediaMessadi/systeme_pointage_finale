<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lang_init.php';
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php'); exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = $lang['fill_fields'];
    header('Location: login.php'); exit;
}

/* Protection brute-force */
$attempts_key = 'login_attempts_' . md5($email);
$lockout_key  = 'login_lockout_'  . md5($email);

if (!empty($_SESSION[$lockout_key]) && time() < $_SESSION[$lockout_key]) {
    $wait = ceil(($_SESSION[$lockout_key] - time()) / 60);
    $_SESSION['error'] = "Compte temporairement bloqué. Réessayez dans {$wait} min.";
    header('Location: login.php'); exit;
}

$stmt = db()->prepare("SELECT id,prenom,nom,email,password_hash,role,active,photo FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $user['active'] && password_verify($password, $user['password_hash'])) {
    /* Succès */
    unset($_SESSION[$attempts_key], $_SESSION[$lockout_key]);

    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['user_nom']   = $user['nom'];
    $_SESSION['user_prenom']= $user['prenom'];
    $_SESSION['user_photo'] = $user['photo'];

    db()->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    appLog('🔑', "Connexion réussie — {$user['prenom']} {$user['nom']}", 'ok', $user['id']);

    header('Location: ' . ($user['role'] === 'admin' ? '../admin/' : '../metrologue/'));
    exit;
}

/* Échec */
$_SESSION[$attempts_key] = ($_SESSION[$attempts_key] ?? 0) + 1;
if ($_SESSION[$attempts_key] >= 5) {
    $_SESSION[$lockout_key] = time() + 600; /* 10 min */
    appLog('⚠️', "Brute-force détecté — {$email}", 'warn');
}

$_SESSION['error'] = $lang['login_error'];
header('Location: login.php'); exit;
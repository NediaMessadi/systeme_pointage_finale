<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lang_init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php'); exit;
}

$step = (int)($_POST['step'] ?? 1);

/* ── ÉTAPE 1 : Vérifier l'email ─────────────────── */
if ($step === 1) {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_error'] = 'Veuillez entrer une adresse email valide.';
        $_SESSION['reset_step']  = 1;
        header('Location: forgot_password.php'); exit;
    }

    try {
        $stmt = db()->prepare("SELECT id, prenom, nom FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        /* Essai avec colonne 'active' (ancienne version du schéma) */
        try {
            $stmt = db()->prepare("SELECT id, prenom, nom FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (PDOException $e2) {
            $user = false;
        }
    }

    if (!$user) {
        /* Message neutre pour éviter l'énumération d'emails */
        $_SESSION['reset_error'] = 'Aucun compte actif trouvé pour cette adresse.';
        $_SESSION['reset_step']  = 1;
        $_SESSION['reset_email'] = $email;
        header('Location: forgot_password.php'); exit;
    }

    $_SESSION['reset_email']   = $email;
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_step']    = 2;
    header('Location: forgot_password.php'); exit;
}

/* ── ÉTAPE 2 : Réinitialiser le mot de passe ─────── */
if ($step === 2) {
    $userId = $_SESSION['reset_user_id'] ?? null;

    if (!$userId) {
        $_SESSION['reset_step']  = 1;
        $_SESSION['reset_error'] = 'Session expirée. Recommencez.';
        header('Location: forgot_password.php'); exit;
    }

    $newPw  = $_POST['new_password']     ?? '';
    $confPw = $_POST['confirm_password'] ?? '';

    if (strlen($newPw) < 6) {
        $_SESSION['reset_error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        $_SESSION['reset_step']  = 2;
        header('Location: forgot_password.php'); exit;
    }

    if ($newPw !== $confPw) {
        $_SESSION['reset_error'] = 'Les mots de passe ne correspondent pas.';
        $_SESSION['reset_step']  = 2;
        header('Location: forgot_password.php'); exit;
    }

    $hash = password_hash($newPw, PASSWORD_DEFAULT);

    try {
        /* Essai avec colonne password_hash */
        $stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
    } catch (PDOException $e) {
        try {
            /* Essai avec colonne password */
            $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
        } catch (PDOException $e2) {
            $_SESSION['reset_error'] = 'Erreur lors de la mise à jour. Contactez l\'administrateur.';
            $_SESSION['reset_step']  = 2;
            header('Location: forgot_password.php'); exit;
        }
    }

    try {
        sysLog('🔑', 'Réinitialisation mot de passe — user #' . $userId, 'warn', $userId);
    } catch (Exception $e) {}

    /* Nettoyer les données de reset */
    unset($_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_step']);
    $_SESSION['reset_step'] = 3;

    header('Location: forgot_password.php'); exit;
}

header('Location: forgot_password.php'); exit;

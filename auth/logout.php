<?php
require_once __DIR__ . '/../config/config.php';

/* Démarrer la session avec le bon nom */
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}

/* Logger avant de vider */
if (!empty($_SESSION['user_id'])) {
    try {
        appLog('🚪', 'Déconnexion — ' . ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
    } catch (Throwable $e) {}
}

/* Vider + détruire la session */
session_unset();
$_SESSION = [];

/* Supprimer le cookie de session */
$cookieName = session_name();
setcookie($cookieName, '', [
    'expires'  => time() - 86400,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (isset($_COOKIE[$cookieName])) {
    unset($_COOKIE[$cookieName]);
}

session_destroy();

/* Anti-cache + redirection absolue vers login */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

/* Construire le chemin absolu depuis SCRIPT_NAME */
$base = rtrim(str_replace('auth/logout.php', '', $_SERVER['SCRIPT_NAME']), '/');
header('Location: ' . $base . '/auth/login.php');
exit;
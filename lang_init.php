<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('cm2e_sess');
    session_start();
}

/* Changer langue/thème/taille via POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lang']))   $_SESSION['lang']   = $_POST['lang'];
    if (isset($_POST['theme']))  $_SESSION['theme']  = $_POST['theme'];
    if (isset($_POST['taille'])) $_SESSION['taille'] = $_POST['taille'];
    if (isset($_POST['_redirect'])) {
        header('Location: ' . $_POST['_redirect']); exit;
    }
}

$lang_code = $_SESSION['lang'] ?? 'fr';
$path = __DIR__ . "/lang/{$lang_code}.php";
$lang = file_exists($path) ? require $path : require __DIR__ . '/lang/fr.php';

if (!function_exists('__')) {
    function __(string $key): string {
        global $lang;
        return $lang[$key] ?? $key;
    }
}

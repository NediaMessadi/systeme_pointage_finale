<?php
/**
 * CM2E — Point d'entrée unique
 * Redirige selon le rôle après authentification
 */
require_once __DIR__ . '/config/config.php';
startSession();

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php'); exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: admin/'); exit;
}

header('Location: metrologue/'); exit;

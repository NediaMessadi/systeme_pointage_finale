<?php
/**
 * CM2E — API Bouton physique (ESP32) pour absence / présence
 * GET /api/button_absence.php?uid=XXXX&action=absent|present
 *
 * Appelé directement par l'ESP32 (pas de session requise).
 * Insère ou met à jour le pointage du jour pour le métrologue
 * identifié par son badge_uid.
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

/* ── Lecture des paramètres ───────────────────── */
$uid    = strtoupper(trim($_GET['uid']    ?? $_POST['uid']    ?? ''));
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));

if (empty($uid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UID manquant']);
    exit;
}

if (!in_array($action, ['absent', 'present'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'action invalide — utiliser absent ou present']);
    exit;
}

$pdo   = db();
$today = date('Y-m-d');
$now   = date('H:i:s');

/* ── Retrouver l'utilisateur par badge_uid ──── */
$stmt = $pdo->prepare("SELECT id, prenom, nom FROM users WHERE badge_uid = ? AND active = 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Badge inconnu', 'uid' => $uid]);
    exit;
}

$status = ($action === 'absent') ? 'abs' : 'ok';

/* ── Vérifier pointage existant du jour ─────── */
$stmt = $pdo->prepare("SELECT id, arrivee, status FROM pointages WHERE user_id = ? AND date = ?");
$stmt->execute([$user['id'], $today]);
$pt = $stmt->fetch();

if ($pt) {
    /* Mise à jour du statut uniquement */
    if ($action === 'present' && empty($pt['arrivee'])) {
        /* Enregistre l'heure d'arrivée si pas encore saisie */
        $pdo->prepare("UPDATE pointages SET status = ?, arrivee = ?, rfid_scan = 0 WHERE id = ?")
            ->execute([$status, $now, $pt['id']]);
    } else {
        $pdo->prepare("UPDATE pointages SET status = ? WHERE id = ?")
            ->execute([$status, $pt['id']]);
    }
} else {
    /* Création du pointage */
    $arrivee = ($action === 'present') ? $now : null;
    $pdo->prepare(
        "INSERT INTO pointages (user_id, date, arrivee, status, rfid_scan) VALUES (?, ?, ?, ?, 0)"
    )->execute([$user['id'], $today, $arrivee, $status]);
}

$label = ($action === 'absent') ? 'Absent (bouton)' : 'Présent (bouton relâché)';
appLog('🔘', "$label — {$user['prenom']} {$user['nom']}", $status === 'abs' ? 'warn' : 'ok', $user['id']);

echo json_encode([
    'success' => true,
    'action'  => $action,
    'status'  => $status,
    'user'    => $user['prenom'] . ' ' . $user['nom'],
    'time'    => $now,
    'message' => "{$user['prenom']} marqué {$label} à {$now}",
]);

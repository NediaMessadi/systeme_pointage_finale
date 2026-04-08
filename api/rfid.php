<?php
/**
 * CM2E — API RFID pour ESP32
 * POST /api/rfid.php?uid=XXXX
 * Enregistre automatiquement l'arrivée ou le départ par badge RFID
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

/* Méthode POST uniquement */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'POST uniquement']);
    exit;
}

$uid = strtoupper(trim($_GET['uid'] ?? $_POST['uid'] ?? ''));
if (empty($uid)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'UID manquant']);
    exit;
}

$pdo  = db();
$today = date('Y-m-d');
$now   = date('H:i:s');
$nowDt = date('Y-m-d H:i:s');

/* Retrouver l'utilisateur par badge_uid */
$stmt = $pdo->prepare("SELECT id, prenom, nom FROM users WHERE badge_uid=? AND active=1");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Badge inconnu','uid'=>$uid]);
    exit;
}

/* Vérifier le pointage existant du jour */
$stmt = $pdo->prepare("SELECT id, arrivee, depart FROM pointages WHERE user_id=? AND date=?");
$stmt->execute([$user['id'], $today]);
$pt = $stmt->fetch();

/* Paramètres de l'entreprise */
$late_limit = $pdo->query("SELECT value FROM user_settings WHERE setting_key='late_limit'")->fetchColumn() ?: '08:15';

if (!$pt) {
    /* PREMIÈRE SCAN = ARRIVÉE */
    $status = ($now <= $late_limit.':00') ? 'ok' : 'late';
    $pdo->prepare(
        "INSERT INTO pointages (user_id,date,arrivee,status,rfid_scan) VALUES (?,?,?,?,1)"
    )->execute([$user['id'], $today, $now, $status]);

    appLog('📡', "Arrivée RFID — {$user['prenom']} {$user['nom']} à $now", 'ok', $user['id']);

    echo json_encode([
        'success'  => true,
        'action'   => 'arrivee',
        'user'     => $user['prenom'] . ' ' . $user['nom'],
        'time'     => $now,
        'status'   => $status,
        'message'  => "Bonjour {$user['prenom']} ! Arrivée enregistrée.",
    ]);

} elseif ($pt['arrivee'] && !$pt['depart']) {
    /* DEUXIÈME SCAN = DÉPART */
    $arr   = strtotime($today . ' ' . $pt['arrivee']);
    $dep   = strtotime($today . ' ' . $now);
    $mins  = round(($dep - $arr) / 60);
    $duree = floor($mins/60) . 'h' . sprintf('%02d', $mins % 60);

    $pdo->prepare(
        "UPDATE pointages SET depart=?, duree=? WHERE id=?"
    )->execute([$now, $duree, $pt['id']]);

    appLog('📡', "Départ RFID — {$user['prenom']} {$user['nom']} à $now (durée: $duree)", 'ok', $user['id']);

    echo json_encode([
        'success' => true,
        'action'  => 'depart',
        'user'    => $user['prenom'] . ' ' . $user['nom'],
        'time'    => $now,
        'duree'   => $duree,
        'message' => "Au revoir {$user['prenom']} ! Durée: $duree",
    ]);

} else {
    /* Déjà complet */
    echo json_encode([
        'success' => true,
        'action'  => 'already_complete',
        'user'    => $user['prenom'] . ' ' . $user['nom'],
        'message' => 'Pointage du jour déjà complet.',
    ]);
}

<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pdo = db();
$type = $_GET['type'] ?? '';
$limit = min((int)($_GET['limit'] ?? 100), 500);

$where = "WHERE 1=1";
$params = [];
if ($type && in_array($type, ['ok','warn','err'])) {
    $where .= " AND l.log_type=?"; $params[] = $type;
}

$stmt = $pdo->prepare(
    "SELECT l.*, u.prenom, u.nom FROM system_logs l
     LEFT JOIN users u ON u.id=l.user_id
     $where ORDER BY l.created_at DESC LIMIT $limit"
);
$stmt->execute($params);
jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);

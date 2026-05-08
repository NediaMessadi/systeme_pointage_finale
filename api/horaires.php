<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

/* Vérifie que la colonne assigned_time existe dans pointages */
$assignedTimeExists = false;
if ($driver === 'mysql') {
    $colStmt = $pdo->prepare("SHOW COLUMNS FROM pointages LIKE 'assigned_time'");
    $colStmt->execute();
    $assignedTimeExists = (bool)$colStmt->fetch();
} else {
    $cols = $pdo->query("PRAGMA table_info(pointages)")->fetchAll();
    foreach ($cols as $c) {
        if (($c['name'] ?? '') === 'assigned_time') {
            $assignedTimeExists = true;
            break;
        }
    }
}

if (!$assignedTimeExists) {
    jsonResponse(['success' => false, 'error' => "La colonne pointages.assigned_time est introuvable"], 500);
}

switch ($method) {
    case 'GET':
        $date = $_GET['date'] ?? date('Y-m-d');
        $userId = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;

        $sql = "SELECT p.user_id, p.date as work_date, p.assigned_time, p.note, u.prenom, u.nom, u.color
                FROM pointages p
                JOIN users u ON u.id = p.user_id
                WHERE p.date = ? AND p.assigned_time IS NOT NULL";
        $params = [$date];

        if ($userId) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY u.prenom ASC, u.nom ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        jsonResponse(['success' => true, 'data' => $rows]);

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $workDate = $d['date'] ?? null;
        $assignedTime = $d['assigned_time'] ?? ($d['start_time'] ?? null);
        $targetUserId = !empty($d['user_id']) ? (int)$d['user_id'] : null;

        if (!$workDate || !$assignedTime) {
            jsonResponse(['success' => false, 'error' => 'date et assigned_time sont requis'], 422);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            jsonResponse(['success' => false, 'error' => 'Format date invalide'], 422);
        }

        if (!preg_match('/^\d{2}:\d{2}/', (string)$assignedTime)) {
            jsonResponse(['success' => false, 'error' => 'Format horaire invalide'], 422);
        }
        $assignedTime = substr((string)$assignedTime, 0, 5);

        $activeCol = 'active';
        if ($driver === 'sqlite') {
            try {
                $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
                $names = array_map(static fn($c) => $c['name'] ?? '', $cols);
                $activeCol = in_array('active', $names, true) ? 'active' : (in_array('is_active', $names, true) ? 'is_active' : '');
            } catch (Throwable $e) {
                $activeCol = '';
            }
        }

        $baseUserSql = "SELECT id FROM users WHERE role='metrologue'";
        if ($activeCol !== '') {
            $baseUserSql .= " AND {$activeCol}=1";
        }

        if ($targetUserId) {
            $uStmt = $pdo->prepare($baseUserSql . " AND id=?");
            $uStmt->execute([$targetUserId]);
            $users = $uStmt->fetchAll();
        } else {
            $uStmt = $pdo->query($baseUserSql . " ORDER BY prenom ASC");
            $users = $uStmt->fetchAll();
        }

        if (empty($users)) {
            jsonResponse(['success' => false, 'error' => 'Aucun métrologue actif trouvé'], 404);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO pointages (user_id, date, assigned_time)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE assigned_time = VALUES(assigned_time)"
        );

        $sqliteStmt = null;
        if ($driver === 'sqlite') {
            $sqliteStmt = $pdo->prepare(
                "INSERT INTO pointages (user_id, date, assigned_time)
                 VALUES (?, ?, ?)
                 ON CONFLICT(user_id, date) DO UPDATE SET assigned_time=excluded.assigned_time"
            );
        }

        $count = 0;
        foreach ($users as $u) {
            $params = [(int)$u['id'], $workDate, $assignedTime];
            if ($sqliteStmt) {
                $sqliteStmt->execute($params);
            } else {
                $stmt->execute($params);
            }
            $count++;
        }

        appLog('🕒', "Affectation horaire {$workDate} {$assignedTime} ({$count} métrologue(s))", 'ok', currentUserId());

        jsonResponse(['success' => true, 'assigned_count' => $count], 201);

    default:
        jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

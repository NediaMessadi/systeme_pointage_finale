<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    case 'GET':
        if (isAdmin() && (($_GET['action'] ?? '') === 'assigned_times')) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $params = [$date];
            $sql = "SELECT p.user_id, p.date as work_date, p.assigned_time, p.note, u.prenom, u.nom, u.color
                    FROM pointages p
                    JOIN users u ON u.id = p.user_id
                    WHERE p.date = ?";
            if (!empty($_GET['user_id'])) {
                $sql .= " AND p.user_id = ?";
                $params[] = (int)$_GET['user_id'];
            }
            $sql .= " ORDER BY u.prenom ASC, u.nom ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
        }

        $where  = "WHERE 1=1";
        $params = [];

        if (!isAdmin()) {
            $where .= " AND p.user_id=?"; $params[] = currentUserId();
        } else {
            if (!empty($_GET['user_id'])) { $where .= " AND p.user_id=?"; $params[] = (int)$_GET['user_id']; }
            if (!empty($_GET['status']))  { $where .= " AND p.status=?";  $params[] = $_GET['status'];  }
        }
        if (!empty($_GET['date'])) { $where .= " AND p.date=?"; $params[] = $_GET['date']; }
        if ($id) { $where = "WHERE p.id=?"; $params = [$id]; }

        $stmt = $pdo->prepare(
            "SELECT p.*, u.prenom, u.nom, u.color
             FROM pointages p JOIN users u ON u.id=p.user_id
             $where ORDER BY p.date DESC, u.prenom ASC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        /* KPI pour la date demandée */
        $kpi = null;
        if (!empty($_GET['date']) || !$id) {
            $day = $_GET['date'] ?? date('Y-m-d');
            $kstmt = $pdo->prepare(
                "SELECT
                 SUM(status='ok') as ok_count,
                 SUM(status='late') as late_count,
                 SUM(status='abs') as abs_count,
                 SUM(rfid_scan=1) as rfid_count
                 FROM pointages WHERE date=?"
            );
            $kstmt->execute([$day]);
            $kpi = $kstmt->fetch();
        }

        jsonResponse(['success'=>true,'data'=>$id?($rows[0]??null):$rows,'kpi'=>$kpi]);

    case 'POST':
        requireAdmin();
        $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (($d['action'] ?? '') === 'assign_time') {
            $date = $d['date'] ?? null;
            $assignedTime = $d['assigned_time'] ?? null;
            $targetUserId = !empty($d['user_id']) ? (int)$d['user_id'] : null;

            if (!$date || !$assignedTime) {
                jsonResponse(['success'=>false,'error'=>'date et assigned_time requis'], 422);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                jsonResponse(['success'=>false,'error'=>'Format date invalide'], 422);
            }
            if (!preg_match('/^\d{2}:\d{2}/', (string)$assignedTime)) {
                jsonResponse(['success'=>false,'error'=>'Format horaire invalide'], 422);
            }
            $assignedTime = substr((string)$assignedTime, 0, 5);

            $usersSql = "SELECT id FROM users WHERE role='metrologue' AND active=1";
            $usersParams = [];
            if ($targetUserId) {
                $usersSql .= " AND id=?";
                $usersParams[] = $targetUserId;
            }
            $uStmt = $pdo->prepare($usersSql . " ORDER BY prenom ASC");
            $uStmt->execute($usersParams);
            $users = $uStmt->fetchAll();
            if (empty($users)) {
                jsonResponse(['success'=>false,'error'=>'Aucun métrologue actif trouvé'], 404);
            }

            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare(
                    "INSERT INTO pointages (user_id,date,assigned_time)
                     VALUES (?,?,?)
                     ON CONFLICT(user_id,date) DO UPDATE SET assigned_time=excluded.assigned_time"
                );
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO pointages (user_id,date,assigned_time)
                     VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE assigned_time=VALUES(assigned_time)"
                );
            }

            $count = 0;
            foreach ($users as $u) {
                $stmt->execute([(int)$u['id'], $date, $assignedTime]);
                $count++;
            }

            appLog('🕒', "Horaire assigné {$date} {$assignedTime} ({$count} métrologue(s))", 'ok', currentUserId());
            jsonResponse(['success'=>true,'assigned_count'=>$count], 201);
        }

        if (empty($d['user_id']) || empty($d['date'])) {
            jsonResponse(['success'=>false,'error'=>'user_id et date requis'], 422);
        }
        $status = in_array($d['status']??'ok',['ok','late','abs']) ? $d['status'] : 'ok';
        $duree  = null;
        if (!empty($d['arrivee']) && !empty($d['depart'])) {
            $arr  = strtotime($d['arrivee']);
            $dep  = strtotime($d['depart']);
            $mins = round(($dep - $arr) / 60);
            $duree = floor($mins/60) . 'h' . sprintf('%02d', $mins % 60);
        }
        $stmt = $pdo->prepare(
            "INSERT INTO pointages (user_id,date,arrivee,depart,duree,status,note)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE arrivee=VALUES(arrivee),depart=VALUES(depart),duree=VALUES(duree),status=VALUES(status),note=VALUES(note)"
        );
        $stmt->execute([
            (int)$d['user_id'], $d['date'],
            $d['arrivee']  ?? null, $d['depart'] ?? null, $duree,
            $status, clean($d['note'] ?? ''),
        ]);
        jsonResponse(['success'=>true,'id'=>(int)$pdo->lastInsertId()], 201);

    case 'PUT':
    case 'PATCH':
        requireAdmin();
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = []; $params = [];
        $allowed = ['arrivee','depart','status','note'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) { $fields[] = "$f=?"; $params[] = $d[$f] ?: null; }
        }
        if (!empty($d['arrivee']) && !empty($d['depart'])) {
            $mins  = round((strtotime($d['depart']) - strtotime($d['arrivee'])) / 60);
            $duree = floor($mins/60).'h'.sprintf('%02d',$mins%60);
            $fields[] = "duree=?"; $params[] = $duree;
        }
        if (empty($fields)) jsonResponse(['success'=>false,'error'=>'Rien à mettre à jour'], 422);
        $params[] = $id;
        $pdo->prepare("UPDATE pointages SET ".implode(',',$fields)." WHERE id=?")->execute($params);
        jsonResponse(['success'=>true]);

    case 'DELETE':
        requireAdmin();
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        $pdo->prepare("DELETE FROM pointages WHERE id=?")->execute([$id]);
        jsonResponse(['success'=>true]);

    default:
        jsonResponse(['success'=>false,'error'=>'Méthode non autorisée'], 405);
}

<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    case 'GET':
        $where  = "WHERE 1=1";
        $params = [];

        if (!isAdmin()) {
            $where .= " AND p.user_id=?"; $params[] = currentUserId();
        } else {
            if (!empty($_GET['user_id'])) { $where .= " AND p.user_id=?"; $params[] = (int)$_GET['user_id']; }
        }
        if (!empty($_GET['status'])) { $where .= " AND p.status=?"; $params[] = $_GET['status']; }
        if ($id) { $where = "WHERE p.id=?"; $params = [$id]; }

        $stmt = $pdo->prepare(
            "SELECT p.*,
             u.prenom, u.nom, u.color,
             COUNT(t.id) as total_tasks, SUM(t.completed) as done_tasks,
             COALESCE(
               CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id',t.id,'label',t.label,'type',t.type,'sort_order',t.sort_order,'completed',t.completed,'completed_at',t.completed_at) ORDER BY t.sort_order), ']'),
               '[]'
             ) as tasks_json
             FROM projects p
             LEFT JOIN users u ON u.id=p.user_id
             LEFT JOIN tasks t ON t.project_id=p.id
             $where GROUP BY p.id ORDER BY p.created_at DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['tasks']      = json_decode($r['tasks_json'] ?? '[]', true) ?: [];
            $r['done_tasks'] = (int)($r['done_tasks'] ?? 0);
            $r['total_tasks']= (int)($r['total_tasks'] ?? 0);
            unset($r['tasks_json']);
        }

        jsonResponse(['success'=>true,'data'=> $id ? ($rows[0] ?? null) : $rows]);

    case 'POST':
        requireAdmin();
        $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($d['user_id']) || empty($d['due_date'])) {
            jsonResponse(['success'=>false,'error'=>'Métrologue et échéance requis'], 422);
        }
               if (!empty($d['name'])) {
            $code = strtoupper(trim(clean($d['name'])));
            if ((int)$pdo->query("SELECT COUNT(*) FROM projects WHERE code='" . addslashes($code) . "'")->fetchColumn() > 0) {
                jsonResponse(['success'=>false,'error'=>'Ce nom/code de projet existe déjà'], 409);
            }
        } else {
            $code = generateProjectCode();
            while ((int)$pdo->query("SELECT COUNT(*) FROM projects WHERE code='$code'")->fetchColumn() > 0) {
                $code = generateProjectCode();
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO projects (code,user_id,priority,start_date,due_date,description) VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([
            $code,
            (int)$d['user_id'],
            in_array($d['priority']??'Normale',['Normale','Haute','Urgente']) ? $d['priority'] : 'Normale',
            !empty($d['start_date']) ? $d['start_date'] : date('Y-m-d'),
            $d['due_date'],
            clean($d['description'] ?? ''),
        ]);
        $projId = (int)$pdo->lastInsertId();

        /* Créer les 4 tâches par défaut */
        $default_tasks = [
            ['Finaliser le rapport',    'finaliser', 0],
            ['Vérification terrain',    'verifier',  1],
            ['Commande matériel',       'commande',  2],
            ['Réception & validation',  'reception', 3],
        ];
        $tstmt = $pdo->prepare("INSERT INTO tasks (project_id,label,type,sort_order) VALUES (?,?,?,?)");
        foreach ($default_tasks as $t) {
            $tstmt->execute([$projId, $t[0], $t[1], $t[2]]);
        }

try { appLog('📁', "Nouveau projet créé : $code"); } catch (Throwable $e) {}        jsonResponse(['success'=>true,'id'=>$projId,'code'=>$code], 201);

    case 'PUT':
    case 'PATCH':
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];

        /* Toggle tâche — réservé au métrologue assigné */
        if (isset($d['task_id'])) {
            $tid = (int)$d['task_id'];
            /* Vérifier que la tâche appartient au projet du user */
            if (!isAdmin()) {
                $check = $pdo->prepare("SELECT t.id FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.id=? AND p.user_id=?");
                $check->execute([$tid, currentUserId()]);
                if (!$check->fetch()) jsonResponse(['success'=>false,'error'=>'Accès refusé'], 403);
            }
            $done = (bool)($d['done'] ?? false);
            $pdo->prepare("UPDATE tasks SET completed=?,completed_at=? WHERE id=?")->execute([
                (int)$done, $done ? date('Y-m-d H:i:s') : null, $tid
            ]);
            /* Mettre à jour le statut du projet */
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=?"); $cnt->execute([$id]);
            $all = (int)$cnt->fetchColumn();
            $dn  = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=? AND completed=1"); $dn->execute([$id]);
            $doneCount = (int)$dn->fetchColumn();
            $proj = $pdo->prepare("SELECT status,user_id FROM projects WHERE id=?"); $proj->execute([$id]);
            $p    = $proj->fetch();
            if ($p && $p['status'] === 'pending' && $doneCount > 0) {
                $pdo->prepare("UPDATE projects SET status='progress' WHERE id=?")->execute([$id]);
            }
            jsonResponse(['success'=>true,'done'=>$doneCount,'total'=>$all]);
        }

        /* Terminer projet */
        if (isset($d['complete']) && $d['complete'] && isAdmin() === false) {
            $proj = $pdo->prepare("SELECT due_date,user_id FROM projects WHERE id=?");
            $proj->execute([$id]);
            $p = $proj->fetch();
            if (!$p || $p['user_id'] != currentUserId()) jsonResponse(['success'=>false,'error'=>'Accès refusé'], 403);
            $score  = computeProjectScore($p['due_date'], date('Y-m-d H:i:s'));
            $pdo->prepare("UPDATE projects SET status='done',completed_at=NOW(),score_awarded=? WHERE id=?")->execute([$score,$id]);
            $pdo->prepare("UPDATE users SET score=score+? WHERE id=?")->execute([$score,$p['user_id']]);
            $pdo->prepare("UPDATE tasks SET completed=1,completed_at=NOW() WHERE project_id=? AND completed=0")->execute([$id]);
            try { appLog('✅', "Projet ID $id terminé, score: $score"); } catch (Throwable $e) {}

            jsonResponse(['success'=>true,'score'=>$score]);
        }

        /* Mise à jour admin */
        requireAdmin();
        $fields = []; $params = [];
        $allowed = ['user_id','status','priority','start_date','due_date','description'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) { $fields[] = "$f=?"; $params[] = $d[$f]; }
        }
        if (empty($fields)) jsonResponse(['success'=>false,'error'=>'Rien à mettre à jour'], 422);
        $params[] = $id;
        $pdo->prepare("UPDATE projects SET ".implode(',',$fields)." WHERE id=?")->execute($params);
        jsonResponse(['success'=>true]);

    case 'DELETE':
        requireAdmin();
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        $p = $pdo->prepare("SELECT code FROM projects WHERE id=?"); $p->execute([$id]);
        $proj = $p->fetch();
        $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
        if ($proj) { try { appLog('🗑️', "Projet supprimé : {$proj['code']}"); } catch (Throwable $e) {} }
        jsonResponse(['success'=>true]);

    default:
        jsonResponse(['success'=>false,'error'=>'Méthode non autorisée'], 405);
}

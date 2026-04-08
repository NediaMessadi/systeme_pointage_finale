<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    case 'GET':
        $role = $_GET['role'] ?? '';
        $where = "WHERE active=1";
        $params = [];
        if ($role) { $where .= " AND role=?"; $params[] = $role; }
        if ($id)   { $where = "WHERE id=?";   $params = [$id]; }

        /* Admin: all users; Métrologue: only own data */
        if (!isAdmin() && !$id) {
            $where = "WHERE id=?"; $params = [currentUserId()];
        }

        $stmt = $pdo->prepare(
            "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.role, u.niveau,
             u.poste, u.classeur, u.color, u.badge_uid, u.photo, u.score,
             u.hire_date, u.active, u.last_login, u.created_at,
             (SELECT COUNT(*) FROM projects WHERE user_id=u.id AND status='done') as projets_termines,
             (SELECT COUNT(*) FROM pointages WHERE user_id=u.id AND status='abs') as absences_count
             FROM users u $where ORDER BY u.prenom ASC"
        );
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        jsonResponse(['success'=>true,'data'=> $id ? ($users[0] ?? null) : $users]);

    case 'POST':
        requireAdmin();
        $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($d['prenom']) || empty($d['nom']) || empty($d['email']) || empty($d['password'])) {
            jsonResponse(['success'=>false,'error'=>'Champs requis manquants'], 422);
        }
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (prenom,nom,email,telephone,password_hash,role,niveau,poste,classeur,color,badge_uid,hire_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            clean($d['prenom']), clean($d['nom']), clean($d['email']),
            clean($d['telephone'] ?? ''), $hash,
            in_array($d['role']??'metrologue',['admin','metrologue']) ? $d['role'] : 'metrologue',
            in_array($d['niveau']??'Junior',['Junior','Intermédiaire','Senior','Expert']) ? $d['niveau'] : 'Junior',
            clean($d['poste'] ?? ''), (int)($d['classeur'] ?? 1),
            clean($d['color'] ?? '#E31E24'),
            !empty($d['badge_uid']) ? strtoupper(clean($d['badge_uid'])) : null,
            !empty($d['hire_date']) ? $d['hire_date'] : null,
        ]);
        $newId = (int)$pdo->lastInsertId();
        sysLog('➕', "Nouveau métrologue : {$d['prenom']} {$d['nom']}", 'ok');
        jsonResponse(['success'=>true,'id'=>$newId], 201);

    case 'PUT':
    case 'PATCH':
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        if (!isAdmin() && $id !== currentUserId()) jsonResponse(['success'=>false,'error'=>'Accès refusé'], 403);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];

        $fields = []; $params = [];
        $allowed = isAdmin()
            ? ['prenom','nom','email','telephone','niveau','poste','classeur','color','badge_uid','hire_date','active']
            : ['prenom','nom','telephone','photo'];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) {
                if ($f === 'badge_uid') {
                    $fields[]  = "$f=?";
                    $params[]  = !empty($d[$f]) ? strtoupper(clean($d[$f])) : null;
                } elseif ($f === 'active') {
                    $fields[]  = "$f=?";
                    $params[]  = (int)(bool)$d[$f];
                } else {
                    $fields[]  = "$f=?";
                    $params[]  = clean((string)$d[$f]);
                }
            }
        }
        if (!empty($d['password']) && isAdmin()) {
            $fields[]  = "password_hash=?";
            $params[]  = password_hash($d['password'], PASSWORD_BCRYPT);
        }
        if (empty($fields)) jsonResponse(['success'=>false,'error'=>'Aucun champ à mettre à jour'], 422);
        $params[] = $id;
        $pdo->prepare("UPDATE users SET ".implode(',',$fields)." WHERE id=?")->execute($params);
        sysLog('✏️', "Métrologue modifié — ID $id", 'ok');
        jsonResponse(['success'=>true]);

    case 'DELETE':
        requireAdmin();
        if (!$id) jsonResponse(['success'=>false,'error'=>'ID requis'], 400);
        $u = $pdo->prepare("SELECT prenom,nom FROM users WHERE id=?");
        $u->execute([$id]);
        $user = $u->fetch();
        $pdo->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$id]);
        if ($user) sysLog('🗑️', "Métrologue supprimé : {$user['prenom']} {$user['nom']}", 'warn');
        jsonResponse(['success'=>true]);

    default:
        jsonResponse(['success'=>false,'error'=>'Méthode non autorisée'], 405);
}

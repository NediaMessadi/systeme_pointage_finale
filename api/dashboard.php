<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo = db();
$q   = currentQuarter();

/* KPIs */
$active_proj = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('progress','pending')")->fetchColumn();
$active_users= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='metrologue' AND active=1")->fetchColumn();
$done_tasks  = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE completed=1")->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pointages WHERE date=CURDATE() AND status='abs'");
$stmt->execute();
$abs_today = (int)$stmt->fetchColumn();

/* Leaderboard */
$stmt = $pdo->prepare(
    "SELECT u.id, u.prenom, u.nom, u.color,
     COALESCE(SUM(p.score_awarded),0) as score
     FROM users u
     LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
     WHERE u.role='metrologue' AND u.active=1
     GROUP BY u.id ORDER BY score DESC LIMIT 10"
);
$stmt->execute([$q['start'], $q['end'].' 23:59:59']);
$leaderboard = $stmt->fetchAll();

/* Planning semaine */
$ref = new DateTime('now');
$dayNum = (int)$ref->format('N');
$ref->modify('-' . ($dayNum - 1) . ' days');
$monday = $ref->format('Y-m-d');
$fridayDt = clone $ref;
$fridayDt->modify('+4 days');
$friday = $fridayDt->format('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT u.id, u.prenom, u.nom, u.color,
     COALESCE(SUM(p.score_awarded),0) as score
     FROM users u
     LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
     WHERE u.role='metrologue' AND u.active=1
     GROUP BY u.id ORDER BY u.prenom ASC"
);
$stmt->execute([$q['start'], $q['end'].' 23:59:59']);
$metro_list = $stmt->fetchAll();

$planning = [];
foreach ($metro_list as $u) {
    $days = [];
    $day_names = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
    for ($i = 0; $i < 5; $i++) {
        $day_date = date('Y-m-d', strtotime($monday . " +$i days"));
        $day_name = $day_names[$i];

        $pstmt = $pdo->prepare("SELECT status, arrivee, depart FROM pointages WHERE user_id=? AND date=?");
        $pstmt->execute([$u['id'], $day_date]);
        $pt = $pstmt->fetch();

        $tstmt = $pdo->prepare(
            "SELECT p.code, p.status, t.type, t.label, t.completed,
                    (SELECT COUNT(*) FROM tasks t3 WHERE t3.project_id=p.id) as total_tasks,
                    (SELECT COUNT(*) FROM tasks t4 WHERE t4.project_id=p.id AND t4.completed=1) as done_tasks,
                    p.checklist_done
             FROM projects p
             LEFT JOIN tasks t ON t.project_id=p.id AND t.sort_order=(
               SELECT MIN(t2.sort_order) FROM tasks t2 WHERE t2.project_id=p.id AND t2.completed=0
             )
             WHERE p.user_id=? AND DATE(p.due_date) = ?
             LIMIT 1"
        );
        $tstmt->execute([$u['id'], $day_date]);
        $task = $tstmt->fetch();

        $allTasksDone = ($task && (int)$task['total_tasks'] > 0 && (int)$task['done_tasks'] === (int)$task['total_tasks']);

        $days[] = [
            'date'           => $day_date,
            'day_name'       => $day_name,
            'pointage'       => $pt  ?: null,
            'proj_code'      => $task['code']           ?? null,
            'task_type'      => $task['type']           ?? null,
            'task_label'     => $task['label']          ?? null,
            'task_done'      => (bool)($task['completed']     ?? false),
            'all_tasks_done' => $allTasksDone,
            'checklist_done' => (bool)($task['checklist_done'] ?? false),
        ];
    }
    $planning[] = ['user' => $u, 'days' => $days];
}

/* DB version */
$db_ver = $pdo->query("SELECT VERSION()")->fetchColumn();

jsonResponse([
    'success'     => true,
    'kpi'         => [
        'active_proj'  => $active_proj,
        'active_users' => $active_users,
        'done_tasks'   => $done_tasks,
        'abs_today'    => $abs_today,
    ],
    'leaderboard' => $leaderboard,
    'planning'    => $planning,
    'quarter'     => $q,
    'db_version'  => $db_ver,
    'week_start'  => $monday,
    'week_end'    => $friday,
]);
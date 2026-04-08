<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo = db();
$q   = currentQuarter();

/* KPIs */
$active_proj = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('progress','pending')")->fetchColumn();
$active_users= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='metrologue' AND active=1")->fetchColumn();
$done_tasks  = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE completed=1")->fetchColumn();
$abs_today   = (int)$pdo->prepare("SELECT COUNT(*) FROM pointages WHERE date=CURDATE() AND status='abs'")->execute([]) ? 0 : 0;
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
$monday = date('Y-m-d', strtotime('monday this week'));
$friday = date('Y-m-d', strtotime('friday this week'));

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

/* Tâches actives par métrologue et par jour de la semaine */
$planning = [];
foreach ($metro_list as $u) {
    $days = [];
    for ($i = 0; $i < 5; $i++) {
        $day_date = date('Y-m-d', strtotime("monday this week +$i days"));

        /* Pointage du jour */
        $pstmt = $pdo->prepare("SELECT status FROM pointages WHERE user_id=? AND date=?");
        $pstmt->execute([$u['id'], $day_date]);
        $pt = $pstmt->fetchColumn();

        /* Tâche du projet actif ce jour */
        $tstmt = $pdo->prepare(
            "SELECT t.type, t.completed FROM projects p
             JOIN tasks t ON t.project_id=p.id
             WHERE p.user_id=? AND p.status IN ('progress','pending')
             ORDER BY t.sort_order ASC LIMIT 1"
        );
        $tstmt->execute([$u['id']]);
        $task = $tstmt->fetch();

        $days[] = [
            'date'      => $day_date,
            'pointage'  => $pt ?: null,
            'task_type' => $task['type']   ?? null,
            'task_done' => (bool)($task['completed'] ?? false),
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

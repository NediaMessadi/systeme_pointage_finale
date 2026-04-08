<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo = db();
$q   = currentQuarter();

/* Date de la semaine demandée (offset en semaines) */
$offset  = (int)($_GET['offset'] ?? 0);
$ref     = new DateTime('now');
$ref->modify('+' . ($offset * 7) . ' days');
$dayNum  = (int)$ref->format('N');
$ref->modify('-' . ($dayNum - 1) . ' days');
$monday  = $ref->format('Y-m-d');
$fridayDt = clone $ref;
$fridayDt->modify('+4 days');
$friday  = $fridayDt->format('Y-m-d');

/* Tous les métrologues actifs */
$stmt = $pdo->prepare(
    "SELECT u.id, u.prenom, u.nom, u.color,
     COALESCE(SUM(p.score_awarded),0) as score
     FROM users u
     LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
     WHERE u.role='metrologue' AND u.active=1
     GROUP BY u.id ORDER BY u.prenom ASC"
);
$stmt->execute([$q['start'], $q['end'].' 23:59:59']);
$metrologues = $stmt->fetchAll();

$planning = [];
foreach ($metrologues as $u) {
    $days = [];
    for ($i = 0; $i < 5; $i++) {
        $day_date = date('Y-m-d', strtotime($monday . " +$i days"));
        $day_name = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'][$i];

        /* Pointage */
        $pstmt = $pdo->prepare("SELECT status, arrivee, depart FROM pointages WHERE user_id=? AND date=?");
        $pstmt->execute([$u['id'], $day_date]);
        $pt = $pstmt->fetch();

        /* Projet dont l'échéance = exactement ce jour */
        $tstmt = $pdo->prepare(
            "SELECT p.id, p.code, p.due_date, p.status, p.completed_at, p.score_awarded,
                    t.type, t.label, t.completed,
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
            'proj_code'      => $task['code']         ?? null,
            'task_type'      => $task['type']         ?? null,
            'task_label'     => $task['label']        ?? null,
            'task_done'      => (bool)($task['completed'] ?? false),
            'all_tasks_done' => $allTasksDone,
            'checklist_done' => (bool)($task['checklist_done'] ?? false),
        ];
    }
    $planning[] = ['user' => $u, 'days' => $days];
}

jsonResponse([
    'success'    => true,
    'planning'   => $planning,
    'week_start' => $monday,
    'week_end'   => $friday,
    'offset'     => $offset,
]);
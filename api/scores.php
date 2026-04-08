<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$q      = currentQuarter();

switch ($method) {

    case 'GET':
        /* Classement trimestriel */
        $stmt = $pdo->prepare(
            "SELECT u.id, u.prenom, u.nom, u.color, u.niveau,
             COALESCE(SUM(p.score_awarded),0) as score,
             COUNT(CASE WHEN p.status='done' THEN 1 END) as projets_termines,
             (SELECT COUNT(*) FROM pointages pt WHERE pt.user_id=u.id AND pt.status='abs' AND pt.date BETWEEN ? AND ?) as absences
             FROM users u
             LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
             WHERE u.role='metrologue' AND u.active=1
             GROUP BY u.id ORDER BY score DESC, projets_termines DESC"
        );
        $stmt->execute([$q['start'], $q['end'], $q['start'], $q['end'].' 23:59:59']);
        $leaderboard = $stmt->fetchAll();

        /* Historique champions */
        $hist = $pdo->query(
            "SELECT qs.*, u.color FROM quarter_scores qs LEFT JOIN users u ON u.id=qs.champion_id ORDER BY qs.year DESC, qs.quarter DESC LIMIT 20"
        )->fetchAll();

        jsonResponse([
            'success'     => true,
            'leaderboard' => $leaderboard,
            'history'     => $hist,
            'quarter'     => $q,
        ]);

    case 'POST':
        requireAdmin();
        $d = json_decode(file_get_contents('php://input'), true) ?? [];

        /* Publier champion */
        if (isset($d['action']) && $d['action'] === 'publish_champion') {
            $stmt = $pdo->prepare(
                "SELECT u.id, u.prenom, u.nom, COALESCE(SUM(p.score_awarded),0) as score
                 FROM users u LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
                 WHERE u.role='metrologue' AND u.active=1
                 GROUP BY u.id ORDER BY score DESC LIMIT 1"
            );
            $stmt->execute([$q['start'], $q['end'].' 23:59:59']);
            $champ = $stmt->fetch();

            if (!$champ) jsonResponse(['success'=>false,'error'=>'Aucun métrologue actif'], 422);

            $pdo->prepare(
                "INSERT INTO quarter_scores (quarter,year,champion_id,champion_nom,champion_score)
                 VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE champion_id=VALUES(champion_id),champion_nom=VALUES(champion_nom),champion_score=VALUES(champion_score)"
            )->execute([
                $q['quarter'], $q['year'],
                $champ['id'], $champ['prenom'].' '.$champ['nom'], $champ['score']
            ]);
            appLog('🏆', "Champion {$q['quarter']}{$q['year']} publié : {$champ['prenom']} {$champ['nom']}", 'ok');
            jsonResponse(['success'=>true,'champion'=>$champ]);
        }

        /* Réinitialiser scores */
        if (isset($d['action']) && $d['action'] === 'reset') {
            $pdo->exec("UPDATE users SET score=0 WHERE role='metrologue'");
            appLog('🔄', "Scores réinitialisés — début {$q['quarter']}{$q['year']}", 'warn');
            jsonResponse(['success'=>true]);
        }

        jsonResponse(['success'=>false,'error'=>'Action inconnue'], 422);

    default:
        jsonResponse(['success'=>false,'error'=>'Méthode non autorisée'], 405);
}

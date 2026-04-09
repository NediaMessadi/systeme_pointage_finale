<?php
// FICHIER DE DIAGNOSTIC - à supprimer après test
require_once __DIR__ . '/config/config.php';
$pdo = db();

// 1. Calcul des dates
$ref = new DateTime('now');
$dayNum = (int)$ref->format('N');
$ref->modify('-' . ($dayNum - 1) . ' days');
$monday = $ref->format('Y-m-d');
$fridayDt = clone $ref;
$fridayDt->modify('+4 days');
$friday = $fridayDt->format('Y-m-d');

echo "<h2>1. Dates calculées</h2>";
echo "Aujourd'hui : " . date('Y-m-d') . " (" . date('l') . ")<br>";
echo "Lundi : $monday<br>";
echo "Vendredi : $friday<br>";

// 2. Jours de la semaine
echo "<h2>2. Jours de la semaine</h2>";
for ($i = 0; $i < 5; $i++) {
    $d = date('Y-m-d', strtotime($monday . " +$i days"));
    echo "Jour $i : $d<br>";
}

// 3. Projets cette semaine
echo "<h2>3. Projets avec due_date cette semaine</h2>";
$stmt = $pdo->prepare("SELECT p.code, p.due_date, p.status, p.user_id, u.prenom, u.nom FROM projects p JOIN users u ON u.id=p.user_id WHERE DATE(p.due_date) BETWEEN ? AND ? ORDER BY p.due_date");
$stmt->execute([$monday, $friday]);
$rows = $stmt->fetchAll();
if ($rows) {
    foreach ($rows as $r) {
        echo "{$r['code']} — {$r['due_date']} — {$r['status']} — {$r['prenom']} {$r['nom']} (user_id={$r['user_id']})<br>";
    }
} else {
    echo "<strong style='color:red'>AUCUN projet trouvé pour cette semaine !</strong><br>";
}

// 4. Test query planning pour chaque métrologue
echo "<h2>4. Test requête planning par métrologue</h2>";
$users = $pdo->query("SELECT id, prenom, nom FROM users WHERE role='metrologue' AND active=1")->fetchAll();
foreach ($users as $u) {
    for ($i = 0; $i < 5; $i++) {
        $day = date('Y-m-d', strtotime($monday . " +$i days"));
        $st = $pdo->prepare("SELECT p.code FROM projects p WHERE p.user_id=? AND DATE(p.due_date)=? LIMIT 1");
        $st->execute([$u['id'], $day]);
        $code = $st->fetchColumn();
        if ($code) {
            echo "<strong style='color:green'>{$u['prenom']} {$u['nom']} — $day — $code</strong><br>";
        }
    }
}
echo "<br><em>Si rien en vert ci-dessus = aucun projet trouvé par la requête</em>";
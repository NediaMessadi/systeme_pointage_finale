<?php
require_once __DIR__ . '/../../config/config.php';
$uid  = currentUserId();
$pdo  = db();

/* Score trimestre */
$q   = currentQuarter();
$stmt = $pdo->prepare(
    "SELECT SUM(score_awarded) FROM projects WHERE user_id=? AND status='done' AND completed_at BETWEEN ? AND ?"
);
$stmt->execute([$uid, $q['start'], $q['end'].' 23:59:59']);
$score_trimestre = (int)($stmt->fetchColumn() ?? 0);

/* Projets stats */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id=?");
$stmt->execute([$uid]);
$total_projets = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id=? AND status='done'");
$stmt->execute([$uid]);
$projets_termines = (int)$stmt->fetchColumn();

/* Projet actuel */
$stmt = $pdo->prepare(
    "SELECT p.*, GROUP_CONCAT(t.completed ORDER BY t.sort_order) as tasks_status,
     COUNT(t.id) as total_tasks, SUM(t.completed) as done_tasks
     FROM projects p LEFT JOIN tasks t ON t.project_id=p.id
     WHERE p.user_id=? AND p.status IN ('progress','pending')
     GROUP BY p.id ORDER BY CASE WHEN p.status='progress' THEN 0 ELSE 1 END, p.created_at ASC
     LIMIT 1"
);
$stmt->execute([$uid]);
$projet_actuel = $stmt->fetch();

/* Classement */
$stmt = $pdo->prepare(
    "SELECT u.id, u.prenom, u.nom, u.color,
     COALESCE(SUM(p.score_awarded),0) as score
     FROM users u
     LEFT JOIN projects p ON p.user_id=u.id AND p.status='done' AND p.completed_at BETWEEN ? AND ?
     WHERE u.role='metrologue' AND u.active=1
     GROUP BY u.id ORDER BY score DESC"
);
$stmt->execute([$q['start'], $q['end'].' 23:59:59']);
$leaderboard = $stmt->fetchAll();

$my_rank = 1;
foreach ($leaderboard as $i => $row) {
    if ($row['id'] == $uid) { $my_rank = $i + 1; break; }
}

function dateFr(): string {
    $j = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $m = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    return $j[date('w')] . ' ' . date('j') . ' ' . $m[(int)date('n')] . ' ' . date('Y');
}
?>

<!-- Bannière de bienvenue -->
<div class="me-hero animate-in">
  <div class="me-hero-left">
    <div class="me-hero-date"><?= dateFr() ?></div>
    <h1 class="me-hero-title">Bonjour, <?= htmlspecialchars($prenom ?? '') ?> 👋</h1>
    <p class="me-hero-sub">Voici votre espace de travail du jour</p>
  </div>
  <div class="me-hero-score">
    <div class="me-score-val"><?= $score_trimestre >= 0 ? '+' . $score_trimestre : $score_trimestre ?></div>
    <div class="me-score-lbl">Score trimestre</div>
  </div>
</div>

<!-- Stats KPI -->
<div class="kpis-row animate-in">
  <div class="kpi-card">
    <div class="kpi-icon">📁</div>
    <div class="kpi-data">
      <div class="kpi-value"><?= $total_projets ?></div>
      <div class="kpi-label">Projets total</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon">✅</div>
    <div class="kpi-data">
      <div class="kpi-value"><?= $projets_termines ?></div>
      <div class="kpi-label">Terminés</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon">🏅</div>
    <div class="kpi-data">
      <div class="kpi-value">#<?= $my_rank ?></div>
      <div class="kpi-label">Classement</div>
    </div>
  </div>
  <div class="kpi-card <?= $score_trimestre >= 0 ? 'kpi-card-pos' : 'kpi-card-neg' ?>">
    <div class="kpi-icon"><?= $score_trimestre >= 0 ? '📈' : '📉' ?></div>
    <div class="kpi-data">
      <div class="kpi-value"><?= $score_trimestre >= 0 ? '+' . $score_trimestre : $score_trimestre ?></div>
      <div class="kpi-label">Score trimestre</div>
    </div>
  </div>
</div>

<div class="dash-two-col">

  <!-- Projet en cours -->
  <div class="card animate-in">
    <div class="card-title">▶ Projet en cours</div>
    <?php if ($projet_actuel): ?>
      <div class="current-proj">
        <div class="cp-code"><?= htmlspecialchars($projet_actuel['code']) ?></div>
        <div class="cp-desc"><?= htmlspecialchars($projet_actuel['description'] ?? '') ?></div>
        <div class="cp-meta">
          <span class="chip <?= $projet_actuel['status']==='progress'?'chip-prog':'chip-wait' ?>">
            <?= $projet_actuel['status']==='progress' ? 'En cours' : 'En attente' ?>
          </span>
          <?php if ($projet_actuel['due_date']): ?>
            <span style="font-size:12px;color:var(--txt3)">
              Échéance : <?= date('d/m/Y', strtotime($projet_actuel['due_date'])) ?>
            </span>
          <?php endif; ?>
        </div>
        <?php
          $done  = (int)($projet_actuel['done_tasks'] ?? 0);
          $total = (int)($projet_actuel['total_tasks'] ?? 4);
          $pct   = $total > 0 ? round($done/$total*100) : 0;
        ?>
        <div class="cp-progress">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
            <span><?= $done ?>/<?= $total ?> tâches</span>
            <span style="font-weight:700"><?= $pct ?>%</span>
          </div>
          <div class="pbar-bg"><div class="pbar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <a href="?page=projet_courant" class="btn-primary" style="display:inline-block;text-align:center;text-decoration:none;margin-top:12px">
          Accéder au projet →
        </a>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-ico">📭</div>
        <p>Aucun projet actif pour l'instant.</p>
        <p style="font-size:12px;color:var(--txt3)">Un projet vous sera assigné par l'administrateur.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Classement -->
  <div class="card animate-in">
    <div class="card-title">🏆 Classement — <?= $q['label'] ?></div>
    <?php foreach (array_slice($leaderboard, 0, 5) as $i => $row): ?>
      <div class="lb-row <?= $row['id']==$uid ? 'lb-me' : '' ?>">
        <div class="lb-rank"><?= $i===0 ? '🥇' : ($i===1 ? '🥈' : ($i===2 ? '🥉' : ($i+1))) ?></div>
        <div class="lb-avatar" style="background:<?= htmlspecialchars($row['color']) ?>">
          <?= strtoupper(substr($row['prenom'],0,1)) ?>
        </div>
        <div class="lb-info">
          <div class="lb-name"><?= htmlspecialchars($row['prenom'].' '.$row['nom']) ?></div>
        </div>
        <div class="lb-score <?= $row['score']>0?'score-pos':($row['score']<0?'score-neg':'') ?>">
          <?= $row['score'] >= 0 ? '+' . $row['score'] : $row['score'] ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($leaderboard)): ?>
      <div class="empty-state"><p>Aucune donnée</p></div>
    <?php endif; ?>
  </div>

</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
  const fill = document.getElementById('perfFill');
  if (fill) setTimeout(() => { fill.style.width = '<?= min($score_trimestre, 100) ?>%'; }, 200);
});
</script>

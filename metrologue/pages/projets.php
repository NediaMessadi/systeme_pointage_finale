<?php
require_once __DIR__ . '/../../config/config.php';
$uid  = currentUserId();
$pdo  = db();

$search = trim($_GET['search'] ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['code','created_at','due_date']) ? $_GET['sort'] : 'created_at';
$order  = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$status_filter = $_GET['status'] ?? '';

$where  = "WHERE p.user_id=?";
$params = [$uid];
if ($search)       { $where .= " AND (p.code LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status_filter){ $where .= " AND p.status=?"; $params[] = $status_filter; }

$stmt = $pdo->prepare(
    "SELECT p.*, COUNT(t.id) as total_tasks, SUM(t.completed) as done_tasks
     FROM projects p LEFT JOIN tasks t ON t.project_id=p.id
     $where GROUP BY p.id ORDER BY p.$sort $order"
);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<div class="page-header animate-in">
  <div>
    <h1><?= $lang['project_history'] ?></h1>
    <div class="page-subtitle"><?= count($projects) ?> <?= $lang['projects_found'] ?></div>
  </div>
</div>

<!-- Filtres -->
<div class="table-toolbar animate-in">
  <form method="GET" style="flex:1">
    <input type="hidden" name="page" value="projets">
    <div class="search-input">
      <span class="search-icon">🔍</span>
      <input type="text" name="search" placeholder="<?= htmlspecialchars($lang['code_or_description'], ENT_QUOTES) ?>" value="<?= htmlspecialchars($search) ?>">
    </div>
  </form>
  <form method="GET">
    <input type="hidden" name="page" value="projets">
    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
    <select name="status" class="filter-select" onchange="this.form.submit()">
      <option value=""><?= $lang['all_statuses'] ?></option>
      <option value="pending"  <?= $status_filter==='pending'  ?'selected':'' ?>><?= $lang['status_pending'] ?></option>
      <option value="progress" <?= $status_filter==='progress' ?'selected':'' ?>><?= $lang['status_in_progress'] ?></option>
      <option value="done"     <?= $status_filter==='done'     ?'selected':'' ?>><?= $lang['status_done'] ?></option>
    </select>
  </form>
</div>

<!-- Tableau -->
<?php if (empty($projects)): ?>
  <div class="empty-state card animate-in">
    <div class="empty-ico">📂</div>
    <p><?= $lang['no_project_found'] ?></p>
  </div>
<?php else: ?>
  <div class="projects-grid animate-in">
    <?php foreach ($projects as $p):
      $total  = (int)($p['total_tasks'] ?? 0);
      $done   = (int)($p['done_tasks']  ?? 0);
      $pct    = $total > 0 ? round($done/$total*100) : 0;
      $isDue  = $p['due_date'] && strtotime($p['due_date']) < time() && $p['status'] !== 'done';
    ?>
      <div class="proj-card">
        <div class="proj-card-top">
          <div class="proj-code"><?= htmlspecialchars($p['code']) ?></div>
          <?php
            if ($p['status']==='done')    echo '<span class="chip chip-done">' . $lang['status_done'] . '</span>';
            elseif ($p['status']==='progress') echo '<span class="chip chip-prog">' . $lang['status_in_progress'] . '</span>';
            else echo '<span class="chip chip-wait">' . $lang['status_pending'] . '</span>';
          ?>
        </div>
        <?php if ($p['description']): ?>
          <p class="proj-card-desc"><?= htmlspecialchars(substr($p['description'], 0, 80)) ?>…</p>
        <?php endif; ?>
        <div class="proj-card-meta">
          <?php if ($p['due_date']): ?>
            <span class="<?= $isDue ? 'text-danger' : 'text-muted' ?>">
              📅 <?= date('d/m/Y', strtotime($p['due_date'])) ?><?= $isDue ? ' ⚠️' : '' ?>
            </span>
          <?php endif; ?>
          <?php if ($p['status']==='done' && $p['score_awarded'] !== null): ?>
            <span class="score-chip <?= $p['score_awarded']>0?'score-pos':($p['score_awarded']<0?'score-neg':'score-zero') ?>">
              <?= $p['score_awarded'] > 0 ? '+1' : ($p['score_awarded'] < 0 ? '-1' : '0') ?>
            </span>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px">
          <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--txt3);margin-bottom:3px">
            <span><?= sprintf($lang['tasks_count'], $done, $total) ?></span><span><?= $pct ?>%</span>
          </div>
          <div class="pbar-bg"><div class="pbar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php if ($p['status'] !== 'done'): ?>
          <a href="?page=projet_courant" class="proj-card-btn"><?= $lang['view_project'] ?> →</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../config/config.php';
$uid = currentUserId();
$pdo = db();
$isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
$nowExpr = $isSqlite ? "datetime('now','localtime')" : "NOW()";

/* Ajouter colonne checklist_done si elle n'existe pas encore */
try { $pdo->exec("ALTER TABLE projects ADD COLUMN checklist_done TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}

/* Projet actuel: en cours → sinon en attente */
$stmt = $pdo->prepare(
    "SELECT p.*, GROUP_CONCAT(t.id ORDER BY t.sort_order) as task_ids
     FROM projects p LEFT JOIN tasks t ON t.project_id=p.id
     WHERE p.user_id=? AND p.status IN ('progress','pending')
     GROUP BY p.id
     ORDER BY CASE WHEN p.status='progress' THEN 0 ELSE 1 END, p.created_at ASC LIMIT 1"
);
$stmt->execute([$uid]);
$projet = $stmt->fetch();

/* Charger les tâches du projet */
$tasks = [];
if ($projet) {
    $tstmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id=? ORDER BY sort_order ASC");
    $tstmt->execute([$projet['id']]);
    $tasks = $tstmt->fetchAll();
}

//* Toggle tâche */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_task']) && $projet) {

    $tid = (int)$_POST['toggle_task'];

    // 🔥 UPDATE TASK
    $pdo->prepare("
    UPDATE tasks
    SET completed = NOT completed,
        completed_at = CASE
        WHEN completed=0 THEN {$nowExpr}
            ELSE NULL
        END
    WHERE id=? AND project_id=?
    ")->execute([$tid, $projet['id']]);

    // 🔥 RÉCUPÉRER TOUTES LES TÂCHES
    $stmtTasks = $pdo->prepare("
    SELECT completed
    FROM tasks
    WHERE project_id=?
    ORDER BY sort_order ASC
    ");

    $stmtTasks->execute([$projet['id']]);

    $allTasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

    $states = [];

    foreach ($allTasks as $task) {

        $states[] = $task['completed'] ? 1 : 0;
    }

    // 🔥 FORMAT MQTT
$mqttMessage = implode('|', $states);

// ✅ Envoi HTTP vers Node.js
$ch = curl_init('http://192.168.0.151:3001/tasks');

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $mqttMessage);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);

$response = curl_exec($ch);

if ($response === false) {
    error_log('CURL ERROR: ' . curl_error($ch));
} else {
    error_log('HTTP RESPONSE: ' . $response);
}

curl_close($ch);

    /* Vérifier si toutes tâches done → marquer projet en cours */
    $cnt  = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=?");
    $cnt->execute([$projet['id']]);
    $all  = (int)$cnt->fetchColumn();
    $done = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=? AND completed=1");
    $done->execute([$projet['id']]);
    $doneCount = (int)$done->fetchColumn();

    if ($projet['status'] === 'pending' && $doneCount > 0) {
        $pdo->prepare("UPDATE projects SET status='progress' WHERE id=?")->execute([$projet['id']]);
    }

    header('Location: ?page=projet_courant'); exit;
}

/* Toggle checklist finale */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_checklist']) && $projet) {
    $stAll  = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=?"); $stAll->execute([$projet['id']]); $cntAll = (int)$stAll->fetchColumn();
    $stDone = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=? AND completed=1"); $stDone->execute([$projet['id']]); $cntDone = (int)$stDone->fetchColumn();
    if ($cntAll > 0 && $cntDone === $cntAll) {
        $newVal = (int)$projet['checklist_done'] ? 0 : 1;
        $pdo->prepare("UPDATE projects SET checklist_done=? WHERE id=?")->execute([$newVal, $projet['id']]);
    }
    header('Location: ?page=projet_courant'); exit;
}

/* Terminer projet */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminer_projet']) && $projet) {
    /* Vérifier checklist cochée + toutes tâches faites */
    $stAll  = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=?"); $stAll->execute([$projet['id']]); $cntAll = (int)$stAll->fetchColumn();
    $stDone = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE project_id=? AND completed=1"); $stDone->execute([$projet['id']]); $cntDone = (int)$stDone->fetchColumn();
    if ($cntAll === 0 || $cntDone < $cntAll || !(int)$projet['checklist_done']) {
        header('Location: ?page=projet_courant'); exit;
    }

    $dueDate     = $projet['due_date'];
    $completedAt = date('Y-m-d H:i:s');
    $score       = computeProjectScore($dueDate, $completedAt);

    $pdo->prepare("UPDATE projects SET status='done', completed_at={$nowExpr}, score_awarded=?, checklist_done=1 WHERE id=?")->execute([$score, $projet['id']]);
    $pdo->prepare("UPDATE users SET score = score + ? WHERE id=?")->execute([$score, $uid]);
    $pdo->prepare("UPDATE tasks SET completed=1, completed_at={$nowExpr} WHERE project_id=? AND completed=0")->execute([$projet['id']]);

    $scoreLabel = $score > 0 ? '+1' : ($score < 0 ? '-1' : '0');
    try { appLog('✅', "Projet {$projet['code']} terminé — Score: {$scoreLabel}", 'ok', $uid); } catch (Exception $e) {}

    header('Location: ?page=projets'); exit;
}

/* Mettre à jour description */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['description']) && $projet) {
    $pdo->prepare("UPDATE projects SET description=? WHERE id=?")->execute([$_POST['description'], $projet['id']]);
    header('Location: ?page=projet_courant'); exit;
}

/* Démarrer projet (pending → progress) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_project']) && $projet) {
    $pdo->prepare("UPDATE projects SET status='progress' WHERE id=? AND status='pending'")->execute([$projet['id']]);
    header('Location: ?page=projet_courant'); exit;
}
?>

<div class="page-header animate-in">
  <div>
    <h1><?= $lang['current_project'] ?></h1>
    <div class="page-subtitle"><?= $lang['current_project_subtitle'] ?></div>
  </div>
</div>

<?php if (!$projet): ?>
  <div class="empty-state card animate-in">
    <div class="empty-ico">📭</div>
    <h3><?= $lang['no_current_project'] ?></h3>
    <p><?= $lang['project_assigned_admin'] ?></p>
    <a href="?page=projets" class="btn-primary" style="text-decoration:none;display:inline-block;margin-top:16px"><?= $lang['view_history'] ?> →</a>
  </div>
<?php else: ?>

<?php
$totalTasks = count($tasks);
$doneTasks  = count(array_filter($tasks, fn($t) => $t['completed']));
$pct        = $totalTasks > 0 ? round($doneTasks/$totalTasks*100) : 0;
$isDue      = $projet['due_date'] && strtotime($projet['due_date']) < time();
$isAllDone  = $doneTasks === $totalTasks && $totalTasks > 0;
?>

<div class="proj-grid animate-in">

  <!-- Info projet -->
  <div class="card proj-info-card">
    <div class="card-title">📋 <?= htmlspecialchars($projet['code']) ?></div>

    <div class="proj-meta-row">
      <span class="chip <?= $projet['status']==='progress'?'chip-prog':($projet['status']==='done'?'chip-done':'chip-wait') ?>">
        <?= $projet['status']==='progress' ? $lang['status_in_progress'] : ($projet['status']==='done' ? $lang['status_done'] : $lang['status_pending']) ?>
      </span>
      <?php if ($projet['due_date']): ?>
        <span class="proj-due <?= $isDue ? 'proj-due-late' : '' ?>">
          📅 <?= $lang['due_date'] ?> : <?= date('d/m/Y', strtotime($projet['due_date'])) ?>
          <?= $isDue ? ' ⚠️ ' . $lang['overdue'] : '' ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($projet['description']): ?>
      <p style="font-size:13px;color:var(--txt2);margin:10px 0"><?= nl2br(htmlspecialchars($projet['description'])) ?></p>
    <?php endif; ?>

    <!-- Progression -->
    <div class="prog-section">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
        <span><?= sprintf($lang['completed_tasks_count'], $doneTasks, $totalTasks) ?></span>
        <span style="font-weight:700;color:var(--red)"><?= $pct ?>%</span>
      </div>
      <div class="pbar-bg"><div class="pbar-fill" id="proj-pbar" style="width:<?= $pct ?>%"></div></div>
    </div>

    <?php
      $checkDone = (int)($projet['checklist_done'] ?? 0);
      if ($projet['status'] === 'pending'): ?>
      <form method="POST" style="margin-top:14px">
        <input type="hidden" name="start_project" value="1">
        <button class="btn-primary" style="width:100%">▶ <?= $lang['start_project'] ?></button>
      </form>
    <?php elseif ($isAllDone && $checkDone): ?>
      <form method="POST" style="margin-top:14px" onsubmit="return confirm('Confirmer la complétion du projet ?')">
        <input type="hidden" name="terminer_projet" value="1">
        <button class="btn-primary" style="width:100%;background:var(--green)">✅ <?= $lang['mark_project_done'] ?></button>
      </form>
    <?php elseif ($isAllDone && !$checkDone): ?>
      <div style="margin-top:14px;padding:10px 14px;background:#fff5f5;border:1.5px solid #fca5a5;border-radius:8px;font-size:12px;color:#ef4444;text-align:center">
        ⚠️ <?= $lang['check_checklist_to_finish'] ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tâches -->
  <div class="card">
    <div class="card-title">✅ <?= $lang['task_list'] ?></div>
    <div style="font-size:12px;color:var(--txt3);margin-bottom:12px">
      <?= $lang['check_tasks_as_you_progress'] ?>
    </div>

    <?php if (empty($tasks)): ?>
      <div class="empty-state"><p><?= $lang['no_tasks_defined'] ?></p></div>
    <?php else: ?>
      <div class="tasks-list">
        <?php foreach ($tasks as $t):
          $label = $lang['task_' . $t['type']] ?? $t['label'];
          $typeClass  = ['finaliser'=>'F','verifier'=>'V','envoyer'=>'E','commander'=>'Cm'][$t['type']] ?? 'F';
          $typeLetter = ['finaliser'=>'F','verifier'=>'V','envoyer'=>'E','commander'=>'Cm'][$t['type']] ?? 'F';
        ?>
          <form method="POST" class="task-item <?= $t['completed'] ? 'task-done' : '' ?>">
            <input type="hidden" name="toggle_task" value="<?= $t['id'] ?>">
            <button type="submit" class="task-toggle <?= $t['completed'] ? 'task-toggle-done' : '' ?>">
              <?= $t['completed'] ? '✓' : '' ?>
            </button>
            <div class="task-body">
              <span class="task-chip chip-<?= $typeClass ?>"><?= $typeLetter ?></span>
              <span class="task-label"><?= htmlspecialchars($label) ?></span>
            </div>
            <?php if ($t['completed'] && $t['completed_at']): ?>
              <span class="task-date"><?= date('d/m H:i', strtotime($t['completed_at'])) ?></span>
            <?php endif; ?>
          </form>
        <?php endforeach; ?>

        <!-- ── Checklist finale ── -->
        <?php
          $checklistDone    = (int)($projet['checklist_done'] ?? 0);
          $checklistEnabled = $isAllDone;
        ?>
        <div class="task-item checklist-row" style="margin-top:10px;border-top:1.5px dashed var(--border);padding-top:10px;
             background:<?= $checklistDone ? '#f0fdf4' : '#fff5f5' ?>;border-radius:8px;border:1.5px solid <?= $checklistDone ? 'var(--green)' : '#fca5a5' ?>">
          <form method="POST" style="display:contents">
            <input type="hidden" name="toggle_checklist" value="1">
            <button type="submit"
              class="task-toggle <?= $checklistDone ? 'task-toggle-done' : '' ?>"
              style="background:<?= $checklistDone ? 'var(--green)' : '#ef4444' ?>;border-color:<?= $checklistDone ? 'var(--green)' : '#ef4444' ?>"
              <?= !$checklistEnabled ? 'disabled title="' . htmlspecialchars($lang['complete_all_tasks_first'], ENT_QUOTES) . '"' : '' ?>>
              <?= $checklistDone ? '✓' : '' ?>
            </button>
            <div class="task-body">
              <span class="task-chip" style="background:<?= $checklistDone ? 'var(--green)' : '#ef4444' ?>;color:#fff;font-size:11px">✔</span>
              <span class="task-label" style="font-weight:700;color:<?= $checklistDone ? 'var(--green)' : '#ef4444' ?>">
                <?= $lang['checklist'] ?>
                <?php if (!$checklistEnabled): ?>
                  <span style="font-weight:400;font-size:11px;color:var(--txt3)"> — <?= sprintf($lang['finish_tasks_first'], $totalTasks) ?></span>
                <?php endif; ?>
              </span>
            </div>
            <?php if ($checklistDone): ?>
              <span class="task-date" style="color:var(--green);font-weight:700">✓ <?= $lang['validated'] ?></span>
            <?php endif; ?>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Description / Remarques -->
  <div class="card col-full">
    <div class="card-title">📝 <?= $lang['notes'] ?></div>
    <form method="POST">
      <textarea name="description" class="desc-textarea"
        placeholder="<?= htmlspecialchars($lang['write_remark'], ENT_QUOTES) ?>" rows="4"><?= htmlspecialchars($projet['description'] ?? '') ?></textarea>
      <button type="submit" class="btn-primary" style="width:auto;padding:10px 28px;margin-top:8px">💾 <?= $lang['save'] ?></button>
    </form>
  </div>

</div>

<?php endif; ?>

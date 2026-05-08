<?php
require_once __DIR__ . '/../../config/config.php';
$uid  = currentUserId();
$pdo  = db();

$q            = currentQuarter();
$mois_courant = isset($_GET['mois'])  ? (int)$_GET['mois']  : (int)date('n');
$annee        = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');
$trimestre    = ceil($mois_courant / 3);
$pm           = ($trimestre - 1) * 3 + 1;
$mois         = [['mois'=>$pm,'annee'=>$annee],['mois'=>$pm+1,'annee'=>$annee],['mois'=>$pm+2,'annee'=>$annee]];

/* Scores par projet terminé */
$stmt = $pdo->prepare(
    "SELECT DATE(completed_at) as d, score_awarded FROM projects WHERE user_id=? AND status='done' AND completed_at IS NOT NULL"
);
$stmt->execute([$uid]);
$scores_par_date = [];
foreach ($stmt->fetchAll() as $s) {
    $scores_par_date[$s['d']] = ($scores_par_date[$s['d']] ?? 0) + (int)$s['score_awarded'];
}

/* Pointages */
$stmt = $pdo->prepare("SELECT date, status FROM pointages WHERE user_id=?");
$stmt->execute([$uid]);
$pointages = [];
foreach ($stmt->fetchAll() as $p) {
    $pointages[$p['date']] = $p['status'];
}

$today = date('Y-m-d');
$mois_noms = ($_SESSION['lang'] ?? 'fr') === 'en'
  ? [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December']
  : [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];

/* Navigation */
$prev_m = $pm - 3; $prev_y = $annee;
$next_m = $pm + 3; $next_y = $annee;
if ($prev_m < 1)  { $prev_m += 12; $prev_y--; }
if ($next_m > 12) { $next_m -= 12; $next_y++; }
?>

<div class="page-header animate-in">
  <div>
    <h1><?= $lang['performance_calendar'] ?></h1>
    <div class="page-subtitle"><?= $lang['daily_scores'] ?></div>
  </div>
</div>

<!-- Navigation -->
<div class="cal-nav animate-in">
  <a class="cal-nav-btn" href="?page=calendrier_performance&mois=<?= $prev_m ?>&annee=<?= $prev_y ?>">←</a>
  <div class="cal-nav-center">
    <span class="cal-nav-title">
      <?= $mois_noms[$mois[0]['mois']] ?> · <?= $mois_noms[$mois[1]['mois']] ?> · <?= $mois_noms[$mois[2]['mois']] ?> <?= $annee ?>
    </span>
  </div>
  <a class="cal-nav-btn" href="?page=calendrier_performance&mois=<?= $next_m ?>&annee=<?= $next_y ?>">→</a>
</div>

<!-- Légende -->
<div class="cal-legend animate-in">
  <span class="legend-item"><span class="legend-dot dot-pos"></span><?= $lang['legend_before_deadline'] ?></span>
  <span class="legend-item"><span class="legend-dot dot-zero"></span><?= $lang['legend_on_time'] ?></span>
  <span class="legend-item"><span class="legend-dot dot-neg"></span><?= $lang['legend_after_deadline'] ?></span>
  <span class="legend-item"><span class="legend-dot dot-abs"></span><?= $lang['absent'] ?></span>
  <span class="legend-item"><span class="legend-dot dot-late"></span><?= $lang['late'] ?></span>
</div>

<!-- Calendriers -->
<div class="cal-grid animate-in">
<?php foreach ($mois as $item):
    $m = $item['mois']; $y = $item['annee'];
    $first_ts = mktime(0,0,0,$m,1,$y);
    $nb_jours = (int)date('t',$first_ts);
    $start_dow = ((int)date('N',$first_ts)); /* 1=Lun, 7=Dim */
?>
  <div class="cal-month-card">
    <div class="cal-month-title"><?= $mois_noms[$m] ?> <?= $y ?></div>
    <div class="cal-day-headers">
      <?php foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $h): ?>
        <div class="cal-dh"><?= $h ?></div>
      <?php endforeach; ?>
    </div>
    <div class="cal-days">
      <?php
        for ($blank = 1; $blank < $start_dow; $blank++) echo '<div class="cal-day cal-day-blank"></div>';
        for ($d = 1; $d <= $nb_jours; $d++):
            $date_str = sprintf('%04d-%02d-%02d', $y, $m, $d);
            $score    = $scores_par_date[$date_str] ?? null;
            $pstatus  = $pointages[$date_str] ?? null;
            $is_today = $date_str === $today;
            $dow      = (int)date('N', mktime(0,0,0,$m,$d,$y));
            $is_we    = $dow >= 6;

            $cls = 'cal-day';
            if ($is_today)  $cls .= ' cal-today';
            if ($is_we)     $cls .= ' cal-we';
            if ($score !== null) {
                if ($score > 0)       $cls .= ' cal-pos';
                elseif ($score < 0)   $cls .= ' cal-neg';
                else                  $cls .= ' cal-zero';
            } elseif ($pstatus === 'abs')  $cls .= ' cal-abs';
            elseif ($pstatus === 'late')   $cls .= ' cal-late';
      ?>
          <div class="<?= $cls ?>" title="<?= $date_str ?>">
            <span class="cal-day-num"><?= $d ?></span>
            <?php if ($score !== null): ?>
              <span class="cal-score"><?= $score > 0 ? '+' . $score : ($score < 0 ? $score : '0') ?></span>
            <?php elseif ($pstatus): ?>
              <span class="cal-score"><?= $pstatus === 'abs' ? '✕' : ($pstatus === 'late' ? '⚠' : '') ?></span>
            <?php endif; ?>
          </div>
      <?php endfor; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

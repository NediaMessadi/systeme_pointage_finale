<?php
/* SQLite override for online demo — replaces MySQL */
if (!defined('CM2E_CONFIG_LOADED')) {
    define('CM2E_CONFIG_LOADED', true);
}

define('DB_HOST',    'localhost');
define('DB_NAME',    'cm2e');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8');
define('APP_NAME',    'CM2E · Système de Pointage');
define('APP_VERSION', '3.0.0');
define('SESSION_NAME','cm2e_sess');
define('SESSION_LIFETIME', 28800);

date_default_timezone_set('Africa/Tunis');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbFile = __DIR__ . '/cm2e.sqlite';
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
    }
    return $pdo;
}

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(['lifetime'=>SESSION_LIFETIME,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}
function requireAuth(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        if (isAjax()) { jsonResponse(['success'=>false,'error'=>'Non authentifié'], 401); }
        header('Location: /auth/login.php'); exit;
    }
}
function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        if (isAjax()) { jsonResponse(['success'=>false,'error'=>'Accès refusé'], 403); }
        header('Location: /metrologue/'); exit;
    }
}
function isAdmin(): bool { startSession(); return ($_SESSION['role'] ?? '') === 'admin'; }
function currentUserId(): int { startSession(); return (int)($_SESSION['user_id'] ?? 0); }
function isAjax(): bool { return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'); }
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function clean(string $v): string { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }
function appUrl(string $path = ''): string { return $path; }
function currentQuarter(): array {
    $month = (int)date('n'); $year = (int)date('Y');
    $q = (int)ceil($month / 3);
    $labels = [
        1 => ['label'=>'T1 — Jan → Mar','start'=>"$year-01-01",'end'=>"$year-03-31"],
        2 => ['label'=>'T2 — Avr → Jun','start'=>"$year-04-01",'end'=>"$year-06-30"],
        3 => ['label'=>'T3 — Jul → Sep','start'=>"$year-07-01",'end'=>"$year-09-30"],
        4 => ['label'=>'T4 — Oct → Déc','start'=>"$year-10-01",'end'=>"$year-12-31"],
    ];
    $info = $labels[$q];
    $start = new DateTime($info['start']);
    $end   = new DateTime($info['end'].' 23:59:59');
    $now   = new DateTime();
    $total = $end->getTimestamp() - $start->getTimestamp();
    $elapsed = max(0, $now->getTimestamp() - $start->getTimestamp());
    return [
        'quarter'=>"T$q",'year'=>$year,
        'label'=>$info['label']." $year",
        'start'=>$info['start'],'end'=>$info['end'],
        'progress'=>min(100, round($elapsed/$total*100)),
        'remaining'=>max(0,(int)ceil(($end->getTimestamp()-$now->getTimestamp())/86400)),
    ];
}
function generateProjectCode(): string {
    $prefixes = ['MICRO','METRO','CALIB','VERIF','AUDIT','MAINT','PRESS','DEBIT','TEMP','ELECTR'];
    return $prefixes[array_rand($prefixes)] . '-' . rand(100,999);
}
function computeProjectScore(string $dueDate, string $completedAt): int {
    $due  = new DateTime($dueDate.' 23:59:59');
    $done = new DateTime($completedAt);
    $diff = $done->getTimestamp() - $due->getTimestamp();
    if ($diff < 0) return 1;
    if ($diff === 0) return 0;
    return -1;
}
function sysLog(string $icon, string $message, string $type = 'ok', ?int $userId = null): void {
    try {
        db()->prepare("INSERT INTO system_logs (icon,message,log_type,user_id) VALUES (?,?,?,?)")
             ->execute([$icon,$message,$type,$userId ?? currentUserId() ?: null]);
    } catch (Exception $e) {}
}

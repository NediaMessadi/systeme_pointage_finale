<?php
if (defined('CM2E_CONFIG_LOADED')) return;
define('CM2E_CONFIG_LOADED', true);

/**
 * CM2E — Configuration unifiée (Admin + Métrologue)
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'cm2e');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    'CM2E · Système de Pointage');
define('APP_VERSION', '3.0.0');
define('SESSION_NAME','cm2e_sess');
define('SESSION_LIFETIME', 28800);

date_default_timezone_set('Africa/Tunis');

/* ── PDO Singleton (MySQL → SQLite fallback) ───── */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            /* MySQL non disponible → démo SQLite */
            $dbFile = __DIR__ . '/cm2e.sqlite';
            $needInit = !file_exists($dbFile);
            $pdo = new PDO('sqlite:' . $dbFile, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
            if ($needInit) {
                require_once __DIR__ . '/setup_sqlite.php';
            }
        }
    }
    return $pdo;
}

/* ── Session ───────────────────────────────────── */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(['lifetime'=>SESSION_LIFETIME,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}

/* ── Helpers Auth ──────────────────────────────── */
function requireAuth(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        if (isAjax()) { jsonResponse(['success'=>false,'error'=>'Non authentifié'], 401); }
        header('Location: ' . appUrl('/auth/login.php')); exit;
    }
}

function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        if (isAjax()) { jsonResponse(['success'=>false,'error'=>'Accès refusé'], 403); }
        header('Location: ' . appUrl('/metrologue/')); exit;
    }
}

function isAdmin(): bool {
    startSession();
    return ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId(): int {
    startSession();
    return (int)($_SESSION['user_id'] ?? 0);
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
           str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

/* ── Réponse JSON ──────────────────────────────── */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Sanitize ──────────────────────────────────── */
function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

/* ── URL de l'application ─────────────────────── */
function appUrl(string $path = ''): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    /* Remonter jusqu'à la racine cm2e */
    $depth = substr_count(ltrim($base, '/'), '/');
    $root  = $base;
    for ($i = 0; $i < $depth; $i++) $root = dirname($root);
    return $root . $path;
}

/* ── Trimestre courant ─────────────────────────── */
function currentQuarter(): array {
    $month = (int)date('n'); $year = (int)date('Y');
    $q = ceil($month / 3);
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
    $total   = $end->getTimestamp() - $start->getTimestamp();
    $elapsed = max(0, $now->getTimestamp() - $start->getTimestamp());
    $remaining = max(0, (int)ceil(($end->getTimestamp()-$now->getTimestamp())/86400));
    return [
        'quarter'  => "T$q", 'year'=>$year,
        'label'    => $info['label']." $year",
        'start'    => $info['start'], 'end'=>$info['end'],
        'progress' => min(100, round($elapsed/$total*100)),
        'remaining'=> $remaining,
    ];
}

/* ── Générer un code projet ────────────────────── */
function generateProjectCode(): string {
    $prefixes = ['MICRO','METRO','CALIB','VERIF','AUDIT','MAINT','PRESS','DEBIT','TEMP','ELECTR'];
    return $prefixes[array_rand($prefixes)] . '-' . rand(100, 999);
}

/* ── Calculer le score d'un projet ─────────────── */
function computeProjectScore(string $dueDate, string $completedAt): int {
    $due = new DateTime($dueDate);
    $done = new DateTime($completedAt);
    $diff = $done->getTimestamp() - $due->getTimestamp();
    if ($diff < 0)  return 1;   // avant deadline
    if ($diff === 0) return 0;  // exactement
    return -1;                  // après deadline
}

if (!function_exists('appLog')) {
    function appLog(string $icon, string $message, string $type = 'ok', ?int $userId = null): void {
        try {
            db()->prepare("INSERT INTO system_logs (icon,message,log_type,user_id) VALUES (?,?,?,?)")
                 ->execute([$icon, $message, $type, $userId ?? currentUserId() ?: null]);
        } catch (Exception $e) {
            // Silently fail on logging errors
        }
    }
}

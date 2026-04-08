<?php
/**
 * Auto-setup SQLite database on first run (demo mode)
 */
require_once __DIR__ . '/config_sqlite.php';

function initSqliteDb(): void {
    $db = db();
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prenom TEXT NOT NULL,
        nom TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'metrologue',
        badge_uid TEXT,
        photo TEXT DEFAULT 'default.png',
        is_active INTEGER DEFAULT 1,
        failed_attempts INTEGER DEFAULT 0,
        locked_until TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        nom TEXT NOT NULL,
        description TEXT,
        statut TEXT DEFAULT 'en_attente',
        priorite TEXT DEFAULT 'normale',
        due_date TEXT,
        completed_at TEXT,
        score_awarded INTEGER DEFAULT 0,
        created_by INTEGER,
        assigned_to INTEGER,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(created_by) REFERENCES users(id),
        FOREIGN KEY(assigned_to) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        titre TEXT NOT NULL,
        description TEXT,
        statut TEXT DEFAULT 'a_faire',
        priorite TEXT DEFAULT 'normale',
        due_date TEXT,
        completed_at TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS pointages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        date_pointage TEXT NOT NULL,
        heure_arrivee TEXT,
        heure_depart TEXT,
        duree_minutes INTEGER DEFAULT 0,
        rfid_scan INTEGER DEFAULT 0,
        notes TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS planning (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        date_debut TEXT NOT NULL,
        date_fin TEXT NOT NULL,
        titre TEXT NOT NULL,
        description TEXT,
        type TEXT DEFAULT 'conge',
        statut TEXT DEFAULT 'en_attente',
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS quarter_scores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        annee INTEGER NOT NULL,
        trimestre TEXT NOT NULL,
        score INTEGER DEFAULT 0,
        nb_projets INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        icon TEXT DEFAULT '📋',
        message TEXT NOT NULL,
        log_type TEXT DEFAULT 'ok',
        user_id INTEGER,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS user_settings (
        user_id INTEGER PRIMARY KEY,
        theme TEXT DEFAULT 'light',
        lang TEXT DEFAULT 'fr',
        notifications INTEGER DEFAULT 1,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    /* Sample data */
    $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ((int)$count === 0) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (prenom,nom,email,password,role,badge_uid) VALUES (?,?,?,?,?,?)");
        $stmt->execute(['Admin','CM2E','admin@cm2e.tn',$hash,'admin','BADGE001']);
        $stmt->execute(['Ahmed','Malouli','ahmed.malouli@cm2e.tn',$hash,'metrologue','BADGE002']);
        $stmt->execute(['Sara','Belhadj','sara.belhadj@cm2e.tn',$hash,'metrologue','BADGE003']);
        $stmt->execute(['Mohamed','Trabelsi','mohamed.trabelsi@cm2e.tn',$hash,'metrologue','BADGE004']);

        $adminId = $db->lastInsertId() - 3;
        $u2 = (int)$db->query("SELECT id FROM users WHERE email='ahmed.malouli@cm2e.tn'")->fetchColumn();
        $u3 = (int)$db->query("SELECT id FROM users WHERE email='sara.belhadj@cm2e.tn'")->fetchColumn();

        /* Sample projects */
        $ps = $db->prepare("INSERT INTO projects (code,nom,description,statut,priorite,due_date,created_by,assigned_to) VALUES (?,?,?,?,?,?,?,?)");
        $today = date('Y-m-d');
        $ps->execute(['CALIB-257','Étalonnage Capteurs Pression','Étalonnage trimestriel des capteurs de pression haute sensibilité','en_cours','haute',date('Y-m-d',strtotime('+15 days')),$adminId,$u2]);
        $ps->execute(['MICRO-102','Micromanipulateurs Labo','Vérification et réglage des micromanipulateurs numériques','en_attente','normale',date('Y-m-d',strtotime('+30 days')),$adminId,$u3]);
        $ps->execute(['METRO-044','Métrologie Thermique','Rapport métrologie thermique Q1','termine','normale',date('Y-m-d',strtotime('-5 days')),$adminId,$u2]);

        $p1 = $db->query("SELECT id FROM projects WHERE code='CALIB-257'")->fetchColumn();
        $p2 = $db->query("SELECT id FROM projects WHERE code='METRO-044'")->fetchColumn();

        /* Sample tasks */
        $ts = $db->prepare("INSERT INTO tasks (project_id,user_id,titre,statut,priorite,due_date) VALUES (?,?,?,?,?,?)");
        $ts->execute([$p1,$u2,'Préparation matériel étalonnage','en_cours','haute',date('Y-m-d',strtotime('+3 days'))]);
        $ts->execute([$p1,$u2,'Procédure de mesure','a_faire','normale',date('Y-m-d',strtotime('+8 days'))]);
        $ts->execute([$p2,$u2,'Rédaction rapport final','termine','normale',date('Y-m-d',strtotime('-3 days'))]);

        /* Sample pointages today */
        $pp = $db->prepare("INSERT INTO pointages (user_id,date_pointage,heure_arrivee,heure_depart,duree_minutes) VALUES (?,?,?,?,?)");
        $pp->execute([$u2,$today,'08:15:00','17:00:00',525]);
        $pp->execute([$u3,$today,'08:30:00',null,0]);

        /* System log */
        $db->exec("INSERT INTO system_logs (icon,message,log_type) VALUES ('🚀','Base de données SQLite initialisée (mode démo)','ok')");
    }
}

initSqliteDb();

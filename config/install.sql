-- ============================================================
-- CM2E — Schéma MySQL Unifié v3.0
-- Import via phpMyAdmin : Importer → ce fichier
-- ============================================================

CREATE DATABASE IF NOT EXISTS `cm2e`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cm2e`;

-- ─────────────────────────────────────────────
-- UTILISATEURS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `prenom`        VARCHAR(80)     NOT NULL,
  `nom`           VARCHAR(80)     NOT NULL,
  `email`         VARCHAR(160)    NOT NULL UNIQUE,
  `telephone`     VARCHAR(30)     DEFAULT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `role`          ENUM('admin','metrologue') NOT NULL DEFAULT 'metrologue',
  `niveau`        ENUM('Junior','Intermédiaire','Senior','Expert') NOT NULL DEFAULT 'Junior',
  `poste`         VARCHAR(100)    DEFAULT NULL,
  `classeur`      TINYINT         NOT NULL DEFAULT 1,
  `color`         VARCHAR(10)     NOT NULL DEFAULT '#E31E24',
  `badge_uid`     VARCHAR(20)     DEFAULT NULL UNIQUE,
  `photo`         VARCHAR(255)    DEFAULT NULL,
  `score`         INT             NOT NULL DEFAULT 0,
  `hire_date`     DATE            DEFAULT NULL,
  `active`        TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login`    DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email`   (`email`),
  KEY `idx_role`    (`role`),
  KEY `idx_badge`   (`badge_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PROJETS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `projects` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(20)     NOT NULL UNIQUE,
  `user_id`       INT UNSIGNED    DEFAULT NULL,
  `status`        ENUM('pending','progress','done') NOT NULL DEFAULT 'pending',
  `priority`      ENUM('Normale','Haute','Urgente') NOT NULL DEFAULT 'Normale',
  `start_date`    DATE            DEFAULT NULL,
  `due_date`      DATE            DEFAULT NULL,
  `description`   TEXT            DEFAULT NULL,
  `score_awarded` TINYINT         DEFAULT NULL COMMENT '+1 avant, 0 deadline, -1 après',
  `completed_at`  DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`   (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_proj_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- TÂCHES (liées aux projets)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tasks` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `project_id`   INT UNSIGNED    NOT NULL,
  `label`        VARCHAR(200)    NOT NULL DEFAULT 'Tâche',
  `type`         ENUM('finaliser','verifier','commande','reception') NOT NULL DEFAULT 'finaliser',
  `sort_order`   TINYINT         NOT NULL DEFAULT 0,
  `completed`    TINYINT(1)      NOT NULL DEFAULT 0,
  `completed_at` DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  CONSTRAINT `fk_task_proj` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- POINTAGES (présences)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pointages` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `date`       DATE            NOT NULL,
  `arrivee`    TIME            DEFAULT NULL,
  `depart`     TIME            DEFAULT NULL,
  `duree`      VARCHAR(10)     DEFAULT NULL,
  `status`     ENUM('ok','late','abs') NOT NULL DEFAULT 'ok',
  `note`       VARCHAR(255)    DEFAULT NULL,
  `rfid_scan`  TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = via badge RFID',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`,`date`),
  CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- HORAIRES AFFECTÉS (planification des heures)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `working_time_assignments` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `work_date`  DATE            NOT NULL,
  `start_time` TIME            NOT NULL,
  `end_time`   TIME            NOT NULL,
  `note`       VARCHAR(255)    DEFAULT NULL,
  `created_by` INT UNSIGNED    DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wta_user_date` (`user_id`,`work_date`),
  KEY `idx_wta_work_date` (`work_date`),
  CONSTRAINT `fk_wta_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wta_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SCORES TRIMESTRIELS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quarter_scores` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quarter`        VARCHAR(10)  NOT NULL,
  `year`           YEAR         NOT NULL,
  `champion_id`    INT UNSIGNED DEFAULT NULL,
  `champion_nom`   VARCHAR(160) DEFAULT NULL,
  `champion_score` INT          DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_champ` FOREIGN KEY (`champion_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- LOGS SYSTÈME
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `icon`       VARCHAR(10)     NOT NULL DEFAULT '•',
  `message`    VARCHAR(500)    NOT NULL,
  `log_type`   ENUM('ok','warn','err') NOT NULL DEFAULT 'ok',
  `user_id`    INT UNSIGNED    DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PARAMÈTRES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(60)  NOT NULL UNIQUE,
  `value`       VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- DONNÉES D'EXEMPLE
-- ─────────────────────────────────────────────

-- Mot de passe par défaut = "password" (bcrypt)
INSERT IGNORE INTO `users`
  (`prenom`,`nom`,`email`,`telephone`,`password_hash`,`role`,`niveau`,`poste`,`classeur`,`color`,`score`,`badge_uid`,`hire_date`)
VALUES
  ('Admin','Système','admin@cm2e.tn',NULL,
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin','Expert','Administrateur',0,'#E31E24',0,NULL,'2018-01-01'),
  ('Karim','Belhaj','k.belhaj@cm2e.tn','+216 71 234 001',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Senior','Métrologie industrielle',3,'#E31E24',2,'A1B2C3D4','2020-03-15'),
  ('Sami','Mansouri','s.mansouri@cm2e.tn','+216 71 234 002',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Intermédiaire','Vérification instruments',1,'#D97706',1,'B2C3D4E5','2021-01-10'),
  ('Nour','Hamdi','n.hamdi@cm2e.tn','+216 71 234 003',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Expert','Audit métrologique',5,'#2563EB',1,'C3D4E5F6','2019-06-20'),
  ('Ali','Ben Salah','a.bensalah@cm2e.tn','+216 71 234 004',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Senior','Maintenance préventive',2,'#059669',0,'D4E5F6A7','2021-08-05'),
  ('Fatma','Riahi','f.riahi@cm2e.tn','+216 71 234 005',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Junior','Calibration capteurs',7,'#7C3AED',-1,'E5F6A7B8','2022-02-14'),
  ('Youssef','Khemiri','y.khemiri@cm2e.tn','+216 71 234 006',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Intermédiaire','Réparation instruments',4,'#DB2777',0,'F6A7B8C9','2021-11-01'),
  ('Rania','Saidi','r.saidi@cm2e.tn','+216 71 234 007',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'metrologue','Junior','Vérification débitmètres',6,'#0D9488',-1,'A7B8C9D0','2023-03-08');

-- Projets avec codes courts
INSERT IGNORE INTO `projects` (`code`,`user_id`,`status`,`priority`,`start_date`,`due_date`,`description`)
VALUES
  ('CALIB-257', 2,'progress','Haute',   CURDATE(), DATE_ADD(CURDATE(),INTERVAL 15 DAY),'Étalonnage capteurs pression zone P-12'),
  ('AUDIT-102', 3,'done',    'Normale', DATE_SUB(CURDATE(),INTERVAL 30 DAY), DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Audit métrologique station S3'),
  ('VERIF-388', 4,'pending', 'Normale', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 25 DAY),'Vérification débitmètres zone A'),
  ('MAINT-519', 5,'progress','Haute',   CURDATE(), DATE_ADD(CURDATE(),INTERVAL 10 DAY),'Maintenance préventive unité 7'),
  ('MICRO-741', 7,'progress','Urgente', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 3 DAY), 'Réparation manomètre M-04');

-- Tâches des projets (4 par projet)
INSERT IGNORE INTO `tasks` (`project_id`,`label`,`type`,`sort_order`,`completed`,`completed_at`)
SELECT p.id,'Finaliser le rapport','finaliser',0,
  CASE WHEN p.status='done' THEN 1 ELSE (p.status='progress') END,
  CASE WHEN p.status='done' THEN p.completed_at ELSE NULL END
FROM projects p WHERE p.code IN ('CALIB-257','AUDIT-102','VERIF-388','MAINT-519','MICRO-741');

INSERT IGNORE INTO `tasks` (`project_id`,`label`,`type`,`sort_order`,`completed`,`completed_at`)
SELECT p.id,'Vérification terrain','verifier',1,
  CASE WHEN p.status='done' THEN 1 ELSE 0 END,
  CASE WHEN p.status='done' THEN p.completed_at ELSE NULL END
FROM projects p WHERE p.code IN ('CALIB-257','AUDIT-102','VERIF-388','MAINT-519','MICRO-741');

INSERT IGNORE INTO `tasks` (`project_id`,`label`,`type`,`sort_order`,`completed`)
SELECT p.id,'Commande matériel','commande',2,0
FROM projects p WHERE p.code IN ('CALIB-257','AUDIT-102','VERIF-388','MAINT-519','MICRO-741');

INSERT IGNORE INTO `tasks` (`project_id`,`label`,`type`,`sort_order`,`completed`)
SELECT p.id,'Réception & validation','reception',3,0
FROM projects p WHERE p.code IN ('CALIB-257','AUDIT-102','VERIF-388','MAINT-519','MICRO-741');

-- Marquer le projet terminé AUDIT-102
UPDATE projects SET status='done', completed_at=DATE_SUB(CURDATE(),INTERVAL 5 DAY),
  score_awarded=1 WHERE code='AUDIT-102';
UPDATE users SET score = score + 1 WHERE id = (SELECT user_id FROM projects WHERE code='AUDIT-102');

-- Pointages d'exemple (cette semaine)
INSERT IGNORE INTO `pointages` (`user_id`,`date`,`arrivee`,`depart`,`duree`,`status`)
VALUES
  (2,CURDATE(),'07:55','17:02','9h07','ok'),
  (3,CURDATE(),'08:20','17:00','8h40','late'),
  (4,CURDATE(),'07:48','17:15','9h27','ok'),
  (5,CURDATE(),NULL,NULL,NULL,'abs'),
  (6,CURDATE(),'08:00','17:00','9h00','ok'),
  (7,CURDATE(),'08:05','17:10','9h05','ok'),
  (8,CURDATE(),'08:00','17:00','9h00','ok');

-- Logs système
INSERT IGNORE INTO `system_logs` (`icon`,`message`,`log_type`) VALUES
  ('🔑','Système initialisé — CM2E v3.0','ok'),
  ('➕','Plateforme unifiée admin + métrologue active','ok'),
  ('📡','Intégration RFID badge activée','ok');

-- Paramètres
INSERT IGNORE INTO `user_settings` (`setting_key`,`value`) VALUES
  ('company_name','CM2E — Centre de Métrologie'),
  ('admin_email','admin@cm2e.tn'),
  ('arrival_time','08:00'),
  ('late_limit','08:15'),
  ('rfid_enabled','1');

CREATE DATABASE IF NOT EXISTS sidequest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sidequest;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS reward_transactions, reports, notifications, user_achievements, achievements, quest_completions, quest_participants, quests, categories, admin_settings, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(30) NOT NULL UNIQUE,
 email VARCHAR(120) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('user','admin') NOT NULL DEFAULT 'user',
 level INT UNSIGNED NOT NULL DEFAULT 1,
 xp INT UNSIGNED NOT NULL DEFAULT 0,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 avatar_path VARCHAR(255) NULL,
 bio VARCHAR(500) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(60) NOT NULL UNIQUE,
 slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE quests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 creator_id INT UNSIGNED NOT NULL,
 title VARCHAR(120) NOT NULL,
 description TEXT NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 location VARCHAR(255) NOT NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 scheduled_at DATETIME NOT NULL,
 required_people INT UNSIGNED NOT NULL DEFAULT 1,
 difficulty ENUM('Easy','Normal','Hard','Epic') NOT NULL DEFAULT 'Normal',
 duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
 reward_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 xp_reward INT UNSIGNED NOT NULL DEFAULT 100,
 requirements TEXT NULL,
 instructions TEXT NULL,
 max_participants INT UNSIGNED NOT NULL DEFAULT 1,
 status ENUM('open','requested','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_quest_status (status),
 INDEX idx_quest_category (category_id),
 INDEX idx_quest_creator (creator_id),
 INDEX idx_quest_scheduled (scheduled_at),
 CONSTRAINT fk_quest_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_quest_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE quest_participants (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quest_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 status ENUM('requested','accepted','in_progress','completed','rejected','cancelled') NOT NULL DEFAULT 'requested',
 requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 accepted_at DATETIME NULL,
 started_at DATETIME NULL,
 completed_at DATETIME NULL,
 UNIQUE KEY uq_quest_user (quest_id,user_id),
 INDEX idx_participant_user_status (user_id,status),
 CONSTRAINT fk_participant_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
 CONSTRAINT fk_participant_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quest_completions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quest_id INT UNSIGNED NOT NULL,
 participant_id INT UNSIGNED NOT NULL,
 participant_confirmed_at DATETIME NULL,
 creator_confirmed_at DATETIME NULL,
 status ENUM('pending','confirmed','disputed') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_completion_quest_participant (quest_id,participant_id),
 CONSTRAINT fk_completion_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
 CONSTRAINT fk_completion_participant FOREIGN KEY (participant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE achievements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 description VARCHAR(255) NOT NULL,
 icon VARCHAR(20) DEFAULT '✦'
) ENGINE=InnoDB;

CREATE TABLE user_achievements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 achievement_id INT UNSIGNED NOT NULL,
 earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_user_achievement (user_id,achievement_id),
 CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ua_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 title VARCHAR(120) NOT NULL,
 message VARCHAR(500) NOT NULL,
 link VARCHAR(255) NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_notification_user (user_id,is_read),
 CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reward_transactions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 quest_id INT UNSIGNED NULL,
 transaction_type ENUM('quest_reward','platform_fee','leaderboard_allocation','development_allocation','champion_prize') NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 note VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_reward_type (transaction_type),
 CONSTRAINT fk_reward_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_reward_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE reports (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reporter_id INT UNSIGNED NOT NULL,
 quest_id INT UNSIGNED NOT NULL,
 reason VARCHAR(100) NOT NULL,
 details TEXT NULL,
 status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
 resolved_by INT UNSIGNED NULL,
 resolved_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_report_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE admin_settings (
 setting_key VARCHAR(80) PRIMARY KEY,
 setting_value VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO categories(name,slug) VALUES
('Adventure','adventure'),('Sports','sports'),('Gaming','gaming'),('Social','social'),
('Community','community'),('Creative','creative'),('Study','study'),('Fitness','fitness'),
('Food','food'),('Travel','travel'),('Events','events'),('Help / Assistance','help'),
('Random','random'),('Other','other');

INSERT INTO achievements(name,description,icon) VALUES
('FIRST QUEST','Completed your first SideQuest.','✦'),
('QUEST HUNTER','Completed 10 quests.','✦'),
('ADVENTURER','Completed 25 quests.','✦'),
('SIDEQUEST LEGEND','Completed 100 quests.','✦'),
('QUEST CREATOR','Created your first SideQuest.','✦'),
('HIGH ROLLER','Completed a paid SideQuest.','✦'),
('LOCAL HERO','Completed community-related quests.','✦'),
('EXPLORER','Completed quests across multiple categories.','✦');


INSERT INTO admin_settings VALUES
('PLATFORM_FEE_PERCENT','10'),('LEADERBOARD_POOL_SHARE','70'),('DEVELOPMENT_SHARE','30'),('CHAMPION_PRIZE_PERCENT','50');



CREATE VIEW leaderboard AS
SELECT u.id,u.username,u.level,u.xp,COUNT(qp.id) quests_completed
FROM users u LEFT JOIN quest_participants qp ON qp.user_id=u.id AND qp.status='completed'
GROUP BY u.id,u.username,u.level,u.xp;

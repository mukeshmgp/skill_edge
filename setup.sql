-- ═══════════════════════════════════════════════════════════
--  SKILL EDGE – TNPSC Portal  |  Database Setup
--  Run in phpMyAdmin → SQL tab
-- ═══════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `skilledge`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skilledge`;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)  NOT NULL,
  `email`        VARCHAR(150)  NOT NULL UNIQUE,
  `password`     VARCHAR(255)  NOT NULL,
  `avatar`       VARCHAR(255)  DEFAULT NULL,
  `role`         ENUM('student','admin') DEFAULT 'student',
  `dark_mode`    TINYINT(1)    DEFAULT 0,
  `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Questions ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `questions` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `exam_group`     ENUM('Group1','Group2','Group2A','Group4') NOT NULL,
  `subject`        VARCHAR(100)  NOT NULL,
  `topic`          VARCHAR(100)  DEFAULT NULL,
  `question`       TEXT          NOT NULL,
  `option_a`       VARCHAR(500)  NOT NULL,
  `option_b`       VARCHAR(500)  NOT NULL,
  `option_c`       VARCHAR(500)  NOT NULL,
  `option_d`       VARCHAR(500)  NOT NULL,
  `correct_option` ENUM('A','B','C','D') NOT NULL,
  `explanation`    TEXT          DEFAULT NULL,
  `difficulty`     ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_group_subject` (`exam_group`, `subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tests (each attempt) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tests` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED  NOT NULL,
  `exam_group`   VARCHAR(20)   NOT NULL,
  `subject`      VARCHAR(100)  NOT NULL,
  `total_q`      INT           NOT NULL DEFAULT 0,
  `correct`      INT           NOT NULL DEFAULT 0,
  `wrong`        INT           NOT NULL DEFAULT 0,
  `skipped`      INT           NOT NULL DEFAULT 0,
  `score_pct`    DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `time_taken`   INT           NOT NULL DEFAULT 0,
  `taken_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Results (per-question) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `results` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_id`         INT UNSIGNED NOT NULL,
  `user_id`         INT UNSIGNED NOT NULL,
  `question_id`     INT UNSIGNED NOT NULL,
  `selected_option` ENUM('A','B','C','D','X') DEFAULT 'X',
  `is_correct`      TINYINT(1)   NOT NULL DEFAULT 0,
  `is_bookmarked`   TINYINT(1)   DEFAULT 0,
  `test_date`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`test_id`)     REFERENCES `tests`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Bookmarks ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `question_id` INT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bookmark` (`user_id`,`question_id`),
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Demo admin ──────────────────────────────────────────────
INSERT IGNORE INTO `users` (`name`,`email`,`password`,`role`)
VALUES ('Admin','admin@skilledge.local',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');
-- password = Admin@1234

-- ── Sample questions ────────────────────────────────────────
INSERT INTO `questions`
  (`exam_group`,`subject`,`topic`,`question`,`option_a`,`option_b`,`option_c`,`option_d`,`correct_option`,`explanation`,`difficulty`)
VALUES
('Group1','General Science','Physics','Which law states that energy cannot be created or destroyed?',
 'Newton\'s First Law','Law of Conservation of Energy','Ohm\'s Law','Faraday\'s Law','B',
 'The Law of Conservation of Energy states that the total energy of an isolated system remains constant.','Easy'),
('Group1','General Science','Chemistry','What is the chemical formula of water?',
 'H2O2','HO','H2O','H3O','C','Water is composed of two hydrogen atoms and one oxygen atom.','Easy'),
('Group1','General Science','Biology','Which organelle is known as the powerhouse of the cell?',
 'Nucleus','Ribosome','Mitochondria','Chloroplast','C',
 'Mitochondria produce ATP through cellular respiration, earning the nickname powerhouse.','Easy'),
('Group1','History & Culture','Ancient India','The Harappan civilisation flourished during which period?',
 '5000–3000 BCE','3300–1300 BCE','1500–500 BCE','800–400 BCE','B',
 'The Indus Valley (Harappan) civilization is dated approximately 3300–1300 BCE.','Medium'),
('Group1','Indian Polity','Constitution','Article 32 of the Indian Constitution deals with',
 'Right to Education','Right to Property','Right to Constitutional Remedies','Freedom of Speech','C',
 'Article 32 provides the right to move the Supreme Court for enforcement of Fundamental Rights.','Medium'),
('Group1','Economy','Macro','GDP stands for',
 'Gross Domestic Product','General Development Plan','Global Demand Projection','Gross Demand Percentage','A',
 'GDP measures the total monetary value of goods and services produced within a country.','Easy'),
('Group1','Geography','Physical','Which is the longest river in India?',
 'Brahmaputra','Krishna','Godavari','Ganga','D',
 'The Ganga is approximately 2,525 km long, making it the longest river entirely within India.','Easy'),
('Group1','Aptitude & Mental Ability','Number Series','Find the next number: 2, 6, 12, 20, 30, ?',
 '40','42','44','46','B',
 'The differences are 4,6,8,10,12 — next difference is 12, so 30+12=42.','Medium'),
('Group2','General Science','Physics','Unit of electric current is',
 'Volt','Ohm','Ampere','Watt','C','The SI unit of electric current is the Ampere (A).','Easy'),
('Group2','History','Medieval','The Battle of Panipat (1526) was fought between',
 'Akbar and Hemu','Babur and Ibrahim Lodi','Humayun and Sher Shah','Ahmad Shah and Marathas','B',
 'Babur defeated Ibrahim Lodi at the First Battle of Panipat, establishing Mughal rule.','Medium'),
('Group2','Polity','Parliament','The Rajya Sabha is a',
 'Temporary House','Permanent House','Joint House','None of these','B',
 'The Rajya Sabha is the upper house and is a permanent body — it cannot be dissolved.','Easy'),
('Group2','Geography','India','Sundarbans is located in which state?',
 'Odisha','Andhra Pradesh','West Bengal','Tamil Nadu','C',
 'The Sundarbans mangrove delta lies in West Bengal and Bangladesh.','Easy'),
('Group2','Economy','Banking','RBI stands for',
 'Reserve Bank of India','Regional Bank of India','Rural Bank of India','Revenue Bank of India','A',
 'RBI is India\'s central bank established in 1935.','Easy'),
('Group2A','General Science','Biology','Photosynthesis takes place in',
 'Mitochondria','Nucleus','Chloroplast','Cell Wall','C',
 'Chloroplasts contain chlorophyll, the pigment that captures light for photosynthesis.','Easy'),
('Group2A','Aptitude','Ratio','If A:B = 2:3 and B:C = 4:5, then A:C is',
 '8:15','2:5','6:15','4:9','A',
 'A/B=2/3, B/C=4/5 → A/C = (2/3)×(4/5)? No — A:C = (2×4):(3×5)=8:15.','Medium'),
('Group4','General Science','Physics','Sound travels fastest through',
 'Vacuum','Air','Water','Steel','D',
 'Sound travels fastest in solids. Speed in steel ≈ 5960 m/s vs air ≈ 343 m/s.','Medium'),
('Group4','Current Affairs','India','India\'s space agency is called',
 'NASA','ISRO','ESA','JAXA','B',
 'The Indian Space Research Organisation (ISRO) is headquartered in Bengaluru.','Easy'),
('Group4','History','Modern','Who is known as the Father of the Indian Nation?',
 'Jawaharlal Nehru','B.R. Ambedkar','Mahatma Gandhi','Subhas Chandra Bose','C',
 'Mahatma Gandhi is widely regarded as the Father of the Nation for leading India\'s independence movement.','Easy'),
('Group4','Polity','Basic','How many Fundamental Rights are currently guaranteed by the Indian Constitution?',
 '5','6','7','8','B',
 'There are 6 Fundamental Rights after the Right to Property was removed in 1978.','Medium'),
('Group4','Aptitude','Time & Work','A can finish work in 18 days and B in 9 days. Working together they finish in',
 '4 days','5 days','6 days','7 days','C',
 '1/18+1/9=1/18+2/18=3/18=1/6 → 6 days.','Medium');

CREATE TABLE IF NOT EXISTS `biography` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bio` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `skill_name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `biography` (`id`, `bio`)
VALUES (1, 'I am an ICT student, specializing in data science, back-end development and DevOps.
I am currently a 3rd year bachelor student at HZ University of Applied Sciences. At the moment,
I am doing my Study Abroad Minor at the Frankfurt University of Applied Sciences in Germany.')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `skills` (`skill_name`)
SELECT 'PHP' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'PHP');

INSERT INTO `skills` (`skill_name`)
SELECT 'Docker' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'Docker');

INSERT INTO `skills` (`skill_name`)
SELECT 'MySQL' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'MySQL');

INSERT INTO `skills` (`skill_name`)
SELECT 'JavaScript' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'JavaScript');

INSERT INTO `skills` (`skill_name`)
SELECT 'DevOps' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'DevOps');

INSERT INTO `skills` (`skill_name`)
SELECT 'Data Science' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM skills WHERE skill_name = 'Data Science');
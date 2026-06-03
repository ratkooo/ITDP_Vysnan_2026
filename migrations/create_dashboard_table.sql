CREATE TABLE IF NOT EXISTS courses (
                                       id INT AUTO_INCREMENT PRIMARY KEY,
                                       course_name VARCHAR(255) NOT NULL,
                                       ec_points INT NOT NULL,
                                       grade VARCHAR(10) DEFAULT '-',
                                       status VARCHAR(50) DEFAULT 'In Progress',
                                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default curriculum milestones cleanly checking for real name matches
INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'German Intense Course A1.1', 5, '8.5', 'Passed' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'German Intense Course A1.1');

INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'Distributed Systems', 5, '-', 'In Progress' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'Distributed Systems');

INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'IT Security', 5, '-', 'In Progress' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'IT Security');

INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'AI', 5, '-', 'In Progress' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'AI');

INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'Practical Computer Networks and Applications', 5, '-', 'In Progress' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'Practical Computer Networks and Applications');

INSERT INTO courses (course_name, ec_points, grade, status)
SELECT 'Real-Time Systems', 5, '-', 'In Progress' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE course_name = 'Real-Time Systems');
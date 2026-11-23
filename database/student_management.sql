CREATE DATABASE IF NOT EXISTS student_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_management;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  roll_number VARCHAR(30) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  course VARCHAR(80) NOT NULL,
  department VARCHAR(80) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_roll_number (roll_number),
  UNIQUE KEY uq_students_email (email),
  KEY idx_students_department (department),
  CONSTRAINT chk_students_semester CHECK (semester BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional fictional sample records for demonstration:
INSERT IGNORE INTO students (name, roll_number, email, phone, course, department, semester) VALUES
('Aditi Sharma','IT2026-014','aditi.demo@example.com','9876543210','B.Tech','Information Technology',6),
('Rahul Verma','CSE2026-021','rahul.demo@example.com','9876501234','B.Tech','Computer Science',4);

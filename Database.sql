-- Create database
CREATE DATABASE IF NOT EXISTS if0_39931409_exam_portal;
USE if0_39931409_exam_portal;

-- Table for exams
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_code VARCHAR(50) NOT NULL UNIQUE,
    exam_name VARCHAR(255) NOT NULL,
    academic_year VARCHAR(20),
    description TEXT,
    duration_minutes INT DEFAULT 60,
    total_questions INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for questions
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT,
    question_text TEXT NOT NULL,
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
	explanation TEXT;
    correct_answer CHAR(1), -- 'A', 'B', 'C', or 'D'
    marks INT DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Table for exam attempts (without the extra columns first)
CREATE TABLE exam_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT,
    student_name VARCHAR(255),
    start_time DATETIME,
    end_time DATETIME,
    submitted BOOLEAN DEFAULT FALSE,
    total_marks INT,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Table for answers
CREATE TABLE answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT,
    question_id INT,
    selected_answer CHAR(1),
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Now add the extra columns to exam_attempts
ALTER TABLE exam_attempts 
ADD COLUMN correct_count INT DEFAULT 0,
ADD COLUMN incorrect_count INT DEFAULT 0,
ADD COLUMN skipped_count INT DEFAULT 0,
ADD COLUMN results_json TEXT;

-- Insert sample data
INSERT INTO exams (exam_code, exam_name, academic_year, description, duration_minutes, total_questions) VALUES
('CU A 24-25', 'CU A 24-25 Exam', '2024-2025', 'Annual Examination 2024-25', 60, 20),
('CU A 23-24', 'CU A 23-24 Exam', '2023-2024', 'Annual Examination 2023-24', 60, 20),
('CU-A 22-23', 'CU A 22-23 Exam', '2022-2023', 'Shift-4 Examination', 60, 20),
('CU A 21-22', 'CU A 21-22 Exam', '2021-2022', 'Annual Examination 2021-22', 60, 20),
('CU A 20-21', 'CU A 20-21 Exam', '2020-2021', 'Annual Examination 2020-21', 60, 20),
('CU A 19-20', 'CU A 19-20 Exam', '2019-2020', 'Set-1 Examination', 60, 20),
('CU A 18-19', 'CU A 18-19 Exam', '2018-2019', 'Annual Examination 2018-19', 60, 20),
('CU A 17-18', 'CU A 17-18 Exam', '2017-2018', 'Annual Examination 2017-18', 60, 20),
('CU A 16-17', 'CU A 16-17 Exam', '2016-2017', 'Annual Examination 2016-17', 60, 20);

-- Insert sample questions for first exam
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 'What is 5 + 3?', '5', '8', '7', '6', 'B'),
(1, 'What is the capital of France?', 'London', 'Berlin', 'Paris', 'Madrid', 'C'),
(1, 'Which programming language is this?', 'Java', 'Python', 'PHP', 'C++', 'C'),
(1, 'What is 10 × 5?', '50', '15', '5', '100', 'A'),
(1, 'Who developed PHP?', 'Rasmus Lerdorf', 'Guido van Rossum', 'Brendan Eich', 'James Gosling', 'A');

-- Insert sample math questions
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 'What is the solution of \(10x^2 - 2x + 1 = 0\)?', 'No real solution', 'x = 0.1', 'x = 0.2', 'x = 0.5', 'A'),
(1, 'What is the value of \(2x^2 + 4x + 1 = 0\) when solved?', 'x = -1 ± √2', 'x = -1 ± 0.5', 'x = -2 ± 1', 'x = -0.5 ± 1', 'A'),
(1, 'What is the angle measure of \(90^\circ\) in radians?', 'π/2', 'π', '2π', 'π/4', 'A'),
(1, 'If \(0^\circ\) angle is given, what is its complementary angle?', '90°', '180°', '45°', '0°', 'A'),
(1, 'Solve the equation: \(x^2 - 5x + 6 = 0\)', 'x = 2, 3', 'x = -2, -3', 'x = 1, 6', 'x = -1, -6', 'A');
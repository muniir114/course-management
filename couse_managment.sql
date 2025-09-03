CREATE DATABASE course_management;
USE course_management;

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    birthday DATE NOT NULL,
    annual_course INT CHECK (annual_course BETWEEN 1 AND 3),
    logo VARCHAR(255) DEFAULT 'default_student.png'
);

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identification_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT 'default_teacher.png'
);

-- Spaces table
CREATE TABLE spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    logo VARCHAR(255) DEFAULT 'default_space.png'
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    teacher_id INT,
    space_id INT,
    logo VARCHAR(255) DEFAULT 'default_course.png',
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (space_id) REFERENCES spaces(id)
);

-- Course registrations table
CREATE TABLE course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logo VARCHAR(255) DEFAULT 'default_registration.png',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Insert sample data
INSERT INTO students (student_number, first_name, last_name, birthday, annual_course, logo) VALUES
('S1001', 'John', 'Doe', '2000-05-15', 2, 'student1.png'),
('S1002', 'Jane', 'Smith', '2001-08-22', 1, 'student2.png'),
('S1003', 'Robert', 'Johnson', '1999-12-03', 3, 'student3.png'),
('S1004', 'Emily', 'Williams', '2000-03-30', 2, 'student4.png');

INSERT INTO teachers (identification_number, first_name, last_name, subject, logo) VALUES
('T2001', 'Michael', 'Brown', 'Mathematics', 'teacher1.png'),
('T2002', 'Sarah', 'Williams', 'Physics', 'teacher2.png'),
('T2003', 'David', 'Miller', 'Computer Science', 'teacher3.png');

INSERT INTO spaces (name, capacity, logo) VALUES
('Room 101', 30, 'room101.png'),
('Lab A', 20, 'laba.png'),
('Auditorium', 100, 'auditorium.png');

INSERT INTO courses (name, description, start_date, end_date, teacher_id, space_id, logo) VALUES
('Calculus I', 'Introduction to differential and integral calculus', '2023-09-01', '2023-12-15', 1, 1, 'calculus.png'),
('Physics Fundamentals', 'Basic principles of mechanics and thermodynamics', '2023-09-05', '2023-12-20', 2, 2, 'physics.png'),
('Web Development', 'Building modern web applications', '2023-08-28', '2023-12-10', 3, 3, 'webdev.png');

INSERT INTO course_registrations (student_id, course_id, logo) VALUES
(1, 1, 'reg1.png'),
(2, 1, 'reg2.png'),
(3, 2, 'reg3.png'),
(1, 3, 'reg4.png'),
(4, 2, 'reg5.png'),
(4, 3, 'reg6.png');
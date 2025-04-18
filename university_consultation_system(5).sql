-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2025 at 08:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `university_consultation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_hours`
--

CREATE TABLE `consultation_hours` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultation_hours`
--

INSERT INTO `consultation_hours` (`id`, `teacher_id`, `day`, `time_from`, `time_to`) VALUES
(17, 20021245, 'Tuesday', '12:00:00', '14:00:00'),
(18, 20021249, 'Sunday', '12:01:00', '14:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `consultation_requests`
--

CREATE TABLE `consultation_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `consultation_hour_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultation_requests`
--

INSERT INTO `consultation_requests` (`id`, `student_id`, `teacher_id`, `consultation_hour_id`, `status`) VALUES
(24, 20021246, 20021245, 17, 'approved'),
(25, 20021246, 20021245, 17, 'approved'),
(26, 20021246, 20021245, 17, 'pending'),
(27, 20021250, 20021245, 17, 'rejected');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `section` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_time` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_code`, `course_name`, `section`, `created_at`, `class_time`) VALUES
('CSE341', 'MICROPROCESSOR', '1', '2024-12-27 15:01:49', '11:00 AM-12:20 AM,SUNDAY-TUESDAY'),
('cse350', 'Electronics and Circuits', '1', '2024-12-27 11:30:22', '9:00 AM-10:50 AM,SUNDAY-TUESDAY'),
('CSE370', 'DATABASE SYSTEM', '1', '2024-12-27 11:25:18', '9:00 AM-10:50 AM,SUNDAY-TUESDAY'),
('CSE422', 'ARTIFICIAL INTELLIGENCE', '1', '2024-12-27 14:48:51', '11:00 AM-12:20 AM,SUNDAY-TUESDAY');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `student_id` char(8) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `role` enum('student','teacher') NOT NULL,
  `class_time` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `course_code`, `section`, `student_id`, `teacher_id`, `role`, `class_time`) VALUES
(56, 'CSE370', '1', '20021246', 7, 'student', '9:00 AM-10:50 AM,SUNDAY-TUESDAY'),
(59, 'CSE341', '1', '20021248', 8, 'student', '11:00 AM-12:20 AM,SUNDAY-TUESDAY'),
(60, 'CSE341', '3', '20021248', 7, 'student', '9:00 AM-10:50 AM,SUNDAY-TUESDAY'),
(61, 'CSE341', '1', '20021250', 7, 'student', '11:00 AM-12:20 AM,SUNDAY-TUESDAY');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `rated_by` int(11) NOT NULL,
  `rated_for` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `role` enum('teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `rated_by`, `rated_for`, `rating`, `comment`, `role`, `created_at`) VALUES
(4, 20021245, 20021246, 5, 'lovely number 1', 'student', '2025-01-01 20:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` char(8) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `name`) VALUES
('20021246', 20021246, 'Dewan Raiyan Uddin'),
('20021248', 20021248, 'Raiyed Ahmed'),
('20021250', 20021250, 'Angel Samiha ');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `name`) VALUES
(7, 20021245, 'Dewan Raiyan '),
(8, 20021247, 'Faiyaz Ahmed'),
(9, 20021249, 'Redwan Morshed');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(20021244, 'Md. Raiyan Uddin', 'raiyan@gmail.com', 'admin1', 'admin', '2024-12-29 09:25:48'),
(20021245, 'Dewan Raiyan ', 'dewan@gmail.com', 'admin', 'teacher', '2024-12-29 09:26:11'),
(20021246, 'Dewan Raiyan Uddin', 'uddin@gmail.com', 'admin', 'student', '2024-12-29 09:26:39'),
(20021247, 'Faiyaz Ahmed', 'faiyaz@gmail.com', 'teacher', 'teacher', '2024-12-29 09:27:57'),
(20021248, 'Raiyed Ahmed', 'raiyed@gmail.com', 'raiyed', 'student', '2024-12-29 09:28:28'),
(20021249, 'Redwan Morshed', 'redwan@gmail.com', 'redwan1', 'teacher', '2024-12-29 09:54:18'),
(20021250, 'Angel Samiha ', 'Samiha@gmail.com', 'samiha', 'student', '2025-01-01 09:08:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `consultation_hours`
--
ALTER TABLE `consultation_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teacher_user` (`teacher_id`);

--
-- Indexes for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultation_hour_id` (`consultation_hour_id`),
  ADD KEY `consultation_requests_ibfk_1` (`student_id`),
  ADD KEY `consultation_requests_ibfk_2` (`teacher_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_code`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rated_by` (`rated_by`),
  ADD KEY `fk_rated_for` (`rated_for`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultation_hours`
--
ALTER TABLE `consultation_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20021251;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `consultation_hours`
--
ALTER TABLE `consultation_hours`
  ADD CONSTRAINT `fk_teacher_user` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD CONSTRAINT `consultation_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_requests_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_requests_ibfk_3` FOREIGN KEY (`consultation_hour_id`) REFERENCES `consultation_hours` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_rated_by` FOREIGN KEY (`rated_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_rated_for` FOREIGN KEY (`rated_for`) REFERENCES `users` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

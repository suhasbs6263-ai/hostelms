-- Hostel Management System Full Setup
-- Import this single file into phpMyAdmin
-- It creates the database, required tables, admin login, sample rooms,
-- sample courses, demo students, demo bookings, and demo log activity.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `hostelms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hostelms`;

DROP TABLE IF EXISTS `userLog`;
DROP TABLE IF EXISTS `registration`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `userregistration`;
DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', '2026-04-02 06:00:00');

CREATE TABLE `userregistration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regNo` varchar(80) NOT NULL,
  `firstName` varchar(120) NOT NULL,
  `middleName` varchar(120) DEFAULT NULL,
  `lastName` varchar(120) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `contactNo` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `userregistration_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `userregistration`
(`id`, `regNo`, `firstName`, `middleName`, `lastName`, `gender`, `contactNo`, `email`, `password`, `postingDate`)
VALUES
(1, 'UTS2026001', 'Suhas', 'B', 'S', 'Male', '8050327765', 'suhas.demo1@gmail.com', 'ad6a280417a0f533d8b670c61667e1a0', '2026-04-01 08:10:00'),
(2, 'UTS2026002', 'Ramesh', 'Y', 'S', 'Male', '9876543210', 'ramesh.demo2@gmail.com', 'ad6a280417a0f533d8b670c61667e1a0', '2026-04-01 08:15:00'),
(3, 'UTS2026003', 'Priya', 'K', 'M', 'Female', '9123456780', 'priya.demo3@gmail.com', 'ad6a280417a0f533d8b670c61667e1a0', '2026-04-01 08:20:00'),
(4, 'UTS2026004', 'Rahul', 'N', 'P', 'Male', '9988776655', 'rahul.demo4@gmail.com', 'ad6a280417a0f533d8b670c61667e1a0', '2026-04-01 08:25:00'),
(5, 'UTS2026005', 'Keerthana', 'A', 'R', 'Female', '9012345678', 'keerthana.demo5@gmail.com', 'ad6a280417a0f533d8b670c61667e1a0', '2026-04-01 08:30:00');

CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_fn` varchar(255) DEFAULT NULL,
  `course_sn` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `courses` (`id`, `course_fn`, `course_sn`, `created_at`) VALUES
(1, 'Bachelor of Computer Applications', 'BCA', '2026-04-01 07:30:00'),
(2, 'Bachelor of Business Administration', 'BBA', '2026-04-01 07:32:00'),
(3, 'Bachelor of Commerce', 'BCOM', '2026-04-01 07:34:00');

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_no` varchar(20) NOT NULL,
  `seater` int(11) NOT NULL DEFAULT 0,
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_room_no_unique` (`room_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rooms` (`id`, `room_no`, `seater`, `fees`, `posting_date`) VALUES
(1, '101', 2, 2500.00, '2026-04-01 07:40:00'),
(2, '102', 3, 2200.00, '2026-04-01 07:42:00'),
(3, '103', 4, 1800.00, '2026-04-01 07:44:00'),
(4, '201', 2, 2700.00, '2026-04-01 07:46:00'),
(5, '202', 1, 3500.00, '2026-04-01 07:48:00');

CREATE TABLE `registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roomno` varchar(20) NOT NULL,
  `seater` varchar(20) NOT NULL,
  `feespm` varchar(20) NOT NULL,
  `foodstatus` varchar(20) NOT NULL,
  `stayfrom` date NOT NULL,
  `duration` varchar(20) NOT NULL,
  `course` varchar(255) NOT NULL,
  `regno` varchar(80) NOT NULL,
  `firstName` varchar(120) NOT NULL,
  `middleName` varchar(120) DEFAULT NULL,
  `lastName` varchar(120) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `contactno` varchar(20) NOT NULL,
  `emailid` varchar(150) NOT NULL,
  `egycontactno` varchar(20) NOT NULL,
  `guardianName` varchar(150) NOT NULL,
  `guardianRelation` varchar(80) NOT NULL,
  `guardianContactno` varchar(20) NOT NULL,
  `corresAddress` text NOT NULL,
  `corresCity` varchar(100) NOT NULL,
  `corresPincode` varchar(20) NOT NULL,
  `pmntAddress` text NOT NULL,
  `pmntCity` varchar(100) NOT NULL,
  `pmntPincode` varchar(20) NOT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `registration`
(`id`, `roomno`, `seater`, `feespm`, `foodstatus`, `stayfrom`, `duration`, `course`, `regno`, `firstName`, `middleName`, `lastName`, `gender`, `contactno`, `emailid`, `egycontactno`, `guardianName`, `guardianRelation`, `guardianContactno`, `corresAddress`, `corresCity`, `corresPincode`, `pmntAddress`, `pmntCity`, `pmntPincode`, `postingDate`)
VALUES
(1, '101', '2', '2500.00', '1', '2026-04-03', '12', 'Bachelor of Computer Applications', 'UTS2026001', 'Suhas', 'B', 'S', 'Male', '8050327765', 'suhas.demo1@gmail.com', '9448011111', 'Rekha', 'Mother', '9448022222', '1st Cross, Sira Road', 'Tumakuru', '572137', '1st Cross, Sira Road', 'Tumakuru', '572137', '2026-04-01 09:00:00'),
(2, '102', '3', '2200.00', '0', '2026-04-04', '10', 'Bachelor of Business Administration', 'UTS2026002', 'Ramesh', 'Y', 'S', 'Male', '9876543210', 'ramesh.demo2@gmail.com', '9448033333', 'Shobha', 'Mother', '9448044444', 'MG Road', 'Bengaluru', '560001', 'MG Road', 'Bengaluru', '560001', '2026-04-01 09:10:00'),
(3, '103', '4', '1800.00', '1', '2026-04-05', '8', 'Bachelor of Commerce', 'UTS2026003', 'Priya', 'K', 'M', 'Female', '9123456780', 'priya.demo3@gmail.com', '9448055555', 'Mahesh', 'Father', '9448066666', 'Vidyanagar', 'Mysuru', '570001', 'Vidyanagar', 'Mysuru', '570001', '2026-04-01 09:20:00'),
(4, '201', '2', '2700.00', '1', '2026-04-06', '6', 'Bachelor of Computer Applications', 'UTS2026004', 'Rahul', 'N', 'P', 'Male', '9988776655', 'rahul.demo4@gmail.com', '9448077777', 'Geetha', 'Sister', '9448088888', 'Jayanagar 4th Block', 'Bengaluru', '560011', 'Jayanagar 4th Block', 'Bengaluru', '560011', '2026-04-01 09:30:00');

CREATE TABLE `userLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `userEmail` varchar(150) DEFAULT NULL,
  `userIp` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `loginTime` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `userLog` (`id`, `userId`, `userEmail`, `userIp`, `city`, `country`, `loginTime`) VALUES
(1, 1, 'suhas.demo1@gmail.com', '127.0.0.1', 'Tumakuru', 'India', '2026-04-01 10:00:00'),
(2, 2, 'ramesh.demo2@gmail.com', '127.0.0.1', 'Bengaluru', 'India', '2026-04-01 10:05:00'),
(3, 3, 'priya.demo3@gmail.com', '127.0.0.1', 'Mysuru', 'India', '2026-04-01 10:10:00'),
(4, 4, 'rahul.demo4@gmail.com', '127.0.0.1', 'Bengaluru', 'India', '2026-04-01 10:15:00'),
(5, 5, 'keerthana.demo5@gmail.com', '127.0.0.1', 'Davanagere', 'India', '2026-04-01 10:20:00'),
(6, 1, 'suhas.demo1@gmail.com', '127.0.0.1', 'Tumakuru', 'India', '2026-04-02 08:30:00'),
(7, 3, 'priya.demo3@gmail.com', '127.0.0.1', 'Mysuru', 'India', '2026-04-02 08:50:00');

ALTER TABLE `admin` AUTO_INCREMENT = 2;
ALTER TABLE `userregistration` AUTO_INCREMENT = 6;
ALTER TABLE `courses` AUTO_INCREMENT = 4;
ALTER TABLE `rooms` AUTO_INCREMENT = 6;
ALTER TABLE `registration` AUTO_INCREMENT = 5;
ALTER TABLE `userLog` AUTO_INCREMENT = 8;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

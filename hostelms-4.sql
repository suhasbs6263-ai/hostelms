-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 06, 2026 at 06:13 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hostelms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', '2026-04-02 06:26:12');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_fn` varchar(255) DEFAULT NULL,
  `course_sn` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_fn`, `course_sn`, `created_at`) VALUES
(1, 'Bachelor of Computer Applications', 'BCA', '2026-04-02 06:37:27'),
(2, 'Bachelor of Commerce', 'BCOM', '2026-04-02 08:42:49'),
(3, 'B.Tech Agricultural Engineering', 'B.tec agri ', '2026-05-11 15:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
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
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `roomno`, `seater`, `feespm`, `foodstatus`, `stayfrom`, `duration`, `course`, `regno`, `firstName`, `middleName`, `lastName`, `gender`, `contactno`, `emailid`, `egycontactno`, `guardianName`, `guardianRelation`, `guardianContactno`, `corresAddress`, `corresCity`, `corresPincode`, `pmntAddress`, `pmntCity`, `pmntPincode`, `postingDate`) VALUES
(20, '121', '3', '10000.00', '1', '2026-04-03', '1', 'BCA', '234', 'Suhas', 'suhas', 'B.S', 'Male', '8050327765', 'suhasbs6263@gmail.com', '8050327765', 'rekha', 'mother', '847355', 'Tumkur,sira-572137', 'sira', '572137', 'Tumkur,sira-572137', 'sira', '572137', '2026-04-02 06:36:11'),
(21, '121', '3', '10000.00', '1', '2026-05-13', '1', 'Bachelor of Computer Applications', 'U11SD23S0002', 'Sanjay', 'n', 'sanju', 'Male', '6366855877', 'sanjusanjay3208@gmail.com', '6366855877', 'manoj', 'brother', '43958579', 'abc,sira', 'sira', '572137', 'abc,sira', 'sira', '572137', '2026-04-11 05:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_no` varchar(20) NOT NULL,
  `room_type` varchar(50) NOT NULL DEFAULT 'Standard',
  `capacity` int(11) NOT NULL DEFAULT 0,
  `occupied_beds` int(11) NOT NULL DEFAULT 0,
  `seater` int(11) NOT NULL DEFAULT 0,
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('available','full','maintenance','inactive') NOT NULL DEFAULT 'available',
  `posting_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_no`, `room_type`, `capacity`, `occupied_beds`, `seater`, `fees`, `status`, `posting_date`, `created_at`, `updated_at`) VALUES
(1, '121', 'Standard', 22, 2, 22, 10000.00, 'available', '2026-04-02 06:33:10', '2026-05-09 21:25:32', '2026-06-06 08:50:41'),
(2, '122', 'Standard', 3, 0, 3, 20000.00, 'available', '2026-04-11 05:45:36', '2026-05-09 21:25:32', '2026-05-10 17:57:51'),
(3, '334', 'Standard', 45, 0, 45, 45445.00, 'available', '2026-04-18 11:00:59', '2026-05-09 21:25:32', '2026-05-09 21:25:32'),
(4, '10', 'Standard', 5, 2, 5, 25000.00, 'available', '2026-05-10 12:36:39', '2026-05-10 18:06:39', '2026-06-06 08:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `userLog`
--

CREATE TABLE `userLog` (
  `id` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `userEmail` varchar(150) DEFAULT NULL,
  `userIp` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `loginTime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userLog`
--

INSERT INTO `userLog` (`id`, `userId`, `userEmail`, `userIp`, `city`, `country`, `loginTime`) VALUES
(1, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-02 06:40:49'),
(2, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-02 07:16:49'),
(3, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-02 08:09:50'),
(4, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-10 17:29:20'),
(5, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-10 17:32:12'),
(6, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-10 17:35:34'),
(7, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-10 17:42:34'),
(8, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-11 03:52:56'),
(9, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-11 04:51:16'),
(10, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-11 04:53:43'),
(11, 2, 'sanjusanjay3208@gmail.com', '::1', '', '', '2026-04-11 04:55:00'),
(12, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-18 10:55:57'),
(13, 1, 'suhasbs6263@gmail.com', '::1', '', '', '2026-04-25 05:29:23');

-- --------------------------------------------------------

--
-- Table structure for table `userregistration`
--

CREATE TABLE `userregistration` (
  `id` int(11) NOT NULL,
  `regNo` varchar(80) NOT NULL,
  `firstName` varchar(120) NOT NULL,
  `middleName` varchar(120) DEFAULT NULL,
  `lastName` varchar(120) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `contactNo` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `postingDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userregistration`
--

INSERT INTO `userregistration` (`id`, `regNo`, `firstName`, `middleName`, `lastName`, `gender`, `contactNo`, `email`, `password`, `postingDate`) VALUES
(1, 'U11SD23S0039', 'SUHAS', 'B S', 'suhas', 'Male', '8050327765', 'suhasbs6263@gmail.com', '$2y$10$BPiV4Ik8OWh4rkMeYVFmAOVC0IIc4U2OYTupJJCNdwONF4jRyTDRy', '2026-04-02 06:28:45'),
(2, 'U11SD23S0002', 'sanjay', 'n', 'sanju', 'Male', '6366855877', 'sanjusanjay3208@gmail.com', '51615156618f529edf79f5218fbc31d8', '2026-04-11 04:49:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_username_unique` (`username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_no_unique` (`room_no`);

--
-- Indexes for table `userLog`
--
ALTER TABLE `userLog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userregistration`
--
ALTER TABLE `userregistration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userregistration_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `userLog`
--
ALTER TABLE `userLog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `userregistration`
--
ALTER TABLE `userregistration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

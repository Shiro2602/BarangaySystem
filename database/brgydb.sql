-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2024 at 01:53 PM
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
-- Database: `brgydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `blotter`
--

CREATE TABLE `blotter` (
  `id` int(11) NOT NULL,
  `complainant_id` int(11) DEFAULT NULL,
  `complainee_id` int(11) DEFAULT NULL,
  `incident_type` varchar(100) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_location` text NOT NULL,
  `incident_details` text NOT NULL,
  `status` enum('Pending','Ongoing','Resolved','Dismissed') DEFAULT 'Pending',
  `resolution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blotter`
--

INSERT INTO `blotter` (`id`, `complainant_id`, `complainee_id`, `incident_type`, `incident_date`, `incident_location`, `incident_details`, `status`, `resolution`, `created_at`, `latitude`, `longitude`) VALUES
(2, 1, 2, 'Others', '2024-01-15', 'Near Labac Elementary School, Brgy. Labac, Naic, Cavite', 'Complainant reported missing personal belongings near the school premises', 'Pending', NULL, '2024-12-03 11:38:29', 14.31570000, 120.76750000),
(4, 2, 3, 'Others', '2024-01-25', 'Labac Basketball Court, Brgy. Labac, Naic, Cavite', 'Disturbance during basketball game', 'Resolved', 'Both parties agreed to maintain peace', '2024-12-03 11:38:29', 14.31550000, 120.76730000),
(5, 4, 1, 'Others', '2024-02-01', 'Purok 2, Brgy. Labac, Naic, Cavite', 'Noise complaint from neighboring residence during late hours', 'Pending', NULL, '2024-12-03 11:38:29', 14.31580000, 120.76760000),
(6, 1, 2, 'Others', '2024-01-15', 'Near Labac Elementary School, Brgy. Labac, Naic, Cavite', 'Complainant reported missing personal belongings near the school premises', 'Pending', NULL, '2024-12-03 11:42:40', 14.31320000, 120.73740000),
(8, 2, 3, 'Others', '2024-01-25', 'Labac Basketball Court, Brgy. Labac, Naic, Cavite', 'Disturbance during basketball game', 'Resolved', 'Both parties agreed to maintain peace', '2024-12-03 11:42:40', 14.31300000, 120.73700000),
(9, 4, 1, 'Others', '2024-02-01', 'Purok 2, Brgy. Labac, Naic, Cavite', 'Noise complaint from neighboring residence during late hours', 'Resolved', NULL, '2024-12-03 11:42:40', 14.31330000, 120.73750000),
(10, 3, 1, 'Assault', '2024-12-04', 'Front of Labac Church', 'test', 'Resolved', NULL, '2024-12-03 11:53:05', 14.31268021, 120.73908443);

-- --------------------------------------------------------

--
-- Table structure for table `clearances`
--

CREATE TABLE `clearances` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `purpose` text NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearances`
--

INSERT INTO `clearances` (`id`, `resident_id`, `purpose`, `issue_date`, `expiry_date`, `or_number`, `amount`, `status`) VALUES
(1, 1, 'test', '2024-11-29', '2025-05-29', '05468406810350', 1.00, 'Approved'),
(3, 5, 'na', '2024-12-04', '2024-12-04', '78', 90.00, 'Approved'),
(11, 8, 'Sunong Dunong', '2024-12-28', '2025-12-28', '912312', 90.00, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(11) NOT NULL,
  `household_head_id` int(11) DEFAULT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `household_head_id`, `address`, `created_at`) VALUES
(5, 3, '65', '2024-12-01 13:52:52'),
(6, 2, '54', '2024-12-01 13:53:02'),
(8, 4, '54 Gitna Street, Labac, Naic, Cavite', '2024-12-05 06:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `indigency`
--

CREATE TABLE `indigency` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `purpose` text NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `indigency`
--

INSERT INTO `indigency` (`id`, `resident_id`, `purpose`, `issue_date`, `expiry_date`, `or_number`, `status`) VALUES
(5, 4, 'req', '2024-12-28', '2025-06-28', '123123123', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `officials`
--

CREATE TABLE `officials` (
  `id` int(11) NOT NULL,
  `position` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `term_start` date NOT NULL,
  `term_end` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officials`
--

INSERT INTO `officials` (`id`, `position`, `first_name`, `last_name`, `contact_number`, `term_start`, `term_end`, `status`) VALUES
(1, 'Barangay Chairman', 'Maria', 'Santos', '09987654321', '2023-01-01', '2025-12-31', 'Active'),
(2, 'Barangay Secretary', 'User', 'Test2', '09876541234', '2024-11-20', '2025-01-24', 'Active'),
(3, 'Barangay Secretary', 'User', 'Test', '09876541234', '2024-11-20', '2025-01-24', 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `birthdate` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `household_id` int(11) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Filipino',
  `age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `first_name`, `middle_name`, `last_name`, `birthdate`, `gender`, `civil_status`, `address`, `contact_number`, `email`, `occupation`, `created_at`, `household_id`, `nationality`, `age`) VALUES
(1, 'Juan', 'Manuel', 'Dela Cruz', '1990-01-15', 'Male', 'Married', '123 Sample St., Barangay Sample', '09123456789', '', 'Employee', '2024-11-28 13:46:43', 5, 'American', NULL),
(2, 'Test 1', 'Lorem', 'Ipsum', '2011-05-25', 'Male', 'Married', '84 Labac, Naic, Cavite', '09166846214', 'test2@gmail.com', 'Student', '2024-11-30 09:50:04', 6, 'Filipino', NULL),
(3, 'rewq', 'qwer', 'alsdkjasldkj', '1991-06-21', 'Male', 'Single', '34', '12312031023', '', 'Rigger', '2024-12-01 13:52:41', 5, 'Filipino', NULL),
(4, 'Juan', NULL, 'Dela Cruz', '1990-05-15', 'Male', 'Single', 'Purok 1, Brgy. Labac, Naic, Cavite', '09123456789', NULL, 'Teacher', '2024-12-03 11:13:37', 8, 'Filipino', NULL),
(5, 'Maria', NULL, 'Santos', '1985-08-22', 'Female', 'Married', 'Purok 2, Brgy. Labac, Naic, Cavite', '09187654321', NULL, 'Nurse', '2024-12-03 11:13:37', 8, 'Filipino', NULL),
(6, 'Pedro', NULL, 'Garcia', '1988-03-10', 'Male', 'Married', 'Purok 3, Brgy. Labac, Naic, Cavite', '09198765432', NULL, 'Driver', '2024-12-03 11:13:37', 8, 'Filipino', NULL),
(7, 'Ana', NULL, 'Reyes', '1992-11-30', 'Female', 'Single', 'Purok 4, Brgy. Labac, Naic, Cavite', '09165432198', NULL, 'Engineer', '2024-12-03 11:13:37', 8, 'Filipino', NULL),
(8, 'Ralph Andrei', 'Alon', 'Encarnacion', '2003-04-26', 'Male', 'Single', 'Nia Road, Labac', '09876547162', 'email@gmail.com', 'Student', '2024-12-05 06:19:08', NULL, 'Filipino', NULL),
(9, 'Jimmy', 'Yam', 'Santos', '1998-08-25', 'Male', 'Divorced', 'Nia Road, Labac', '09871236152', 'jimmy@gmail.com', 'Unemployed', '2024-12-11 11:15:53', NULL, 'Filipino', 26);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','secretary') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `first_name`, `last_name`, `email`, `created_at`) VALUES
(3, 'admin2', '$2y$10$9.r.Qi0MwZxhOAsmEdjYpuc9uAqXulL6APkxUl.7Sqg2gXyamKEjS', 'admin', 'Admin', 'Admin', 'admin2@gmail.com', '2024-11-29 11:55:01'),
(5, 'admin3', '$2y$10$JfxLiRurnJSRaJDWin3jCuorklto7pUhdUJ20J.oat3pWxJm2Scb6', 'admin', 'Admin', 'User', 'admin@example.com', '2024-11-30 08:30:46'),
(8, 'sec', '$2y$10$5wHD6t8i8hak1JfNEPM8puywmg7LfSKAyKS6vs.YOOwAmmmlOSyDu', 'secretary', 'sect', 'ary', 'sect@gmail.com', '2024-12-03 21:22:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blotter`
--
ALTER TABLE `blotter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complainant_id` (`complainant_id`),
  ADD KEY `respondent_id` (`complainee_id`);

--
-- Indexes for table `clearances`
--
ALTER TABLE `clearances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_head_id` (`household_head_id`);

--
-- Indexes for table `indigency`
--
ALTER TABLE `indigency`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `officials`
--
ALTER TABLE `officials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blotter`
--
ALTER TABLE `blotter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `clearances`
--
ALTER TABLE `clearances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `indigency`
--
ALTER TABLE `indigency`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `officials`
--
ALTER TABLE `officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blotter`
--
ALTER TABLE `blotter`
  ADD CONSTRAINT `blotter_ibfk_1` FOREIGN KEY (`complainant_id`) REFERENCES `residents` (`id`),
  ADD CONSTRAINT `blotter_ibfk_2` FOREIGN KEY (`complainee_id`) REFERENCES `residents` (`id`);

--
-- Constraints for table `clearances`
--
ALTER TABLE `clearances`
  ADD CONSTRAINT `clearances_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`);

--
-- Constraints for table `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `households_ibfk_1` FOREIGN KEY (`household_head_id`) REFERENCES `residents` (`id`);

--
-- Constraints for table `indigency`
--
ALTER TABLE `indigency`
  ADD CONSTRAINT `indigency_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`);

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

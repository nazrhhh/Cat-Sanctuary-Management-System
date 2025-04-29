-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Feb 20, 2025 at 02:23 AM
-- Server version: 5.7.44
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cat_sanctuary`
--

-- --------------------------------------------------------

--
-- Table structure for table `Behavior`
--

CREATE TABLE `Behavior` (
  `behaviorID` int(11) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `behaviorType` varchar(50) NOT NULL,
  `Description` text,
  `Date` date NOT NULL,
  `caretakerID` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `Behavior`
--

INSERT INTO `Behavior` (`behaviorID`, `catID`, `behaviorType`, `Description`, `Date`, `caretakerID`, `CreatedAt`) VALUES
(1, 1, 'Social Interaction', 'Playful and active, loves playing with others.', '2025-02-01', 3, '2025-02-01 01:07:53'),
(2, 2, 'Social Interaction', 'A bit shy but want to play. Need more interactions.', '2025-02-01', 3, '2025-02-01 04:20:14'),
(3, 3, 'Eating', 'Eats well and like to play around', '2025-02-07', 3, '2025-02-19 20:51:20'),
(4, 4, 'Aggressive', 'hissing when approached by other cats', '2025-02-08', 3, '2025-02-19 22:07:38'),
(5, 5, 'Aggressive', 'Hissing at caretaker when hungry', '2025-02-20', 4, '2025-02-20 01:29:42');

-- --------------------------------------------------------

--
-- Table structure for table `Cat`
--

CREATE TABLE `Cat` (
  `catID` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Breed` varchar(30) DEFAULT NULL,
  `Color` varchar(30) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `IntakeDate` date NOT NULL,
  `Status` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `Cat`
--

INSERT INTO `Cat` (`catID`, `Name`, `DateOfBirth`, `Breed`, `Color`, `Gender`, `IntakeDate`, `Status`) VALUES
(1, 'Milo', '2023-05-14', 'Siamese', 'Cream & Brown', 'Male', '2024-12-01', 1),
(2, 'Luna', '2024-08-30', 'Maine Coon', 'Gray', 'Female', '2025-01-15', 1),
(3, 'Snowball', '2020-06-22', 'Persian', 'White', 'Female', '2024-10-23', 1),
(4, 'Muffin', '2024-04-30', 'Scottish Fold', 'Grey', 'Male', '2024-01-15', 1),
(5, 'Loki', '2023-01-09', 'Domestic Shorthair', 'Orange Tabby', 'Male', '2025-01-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `DailyCare`
--

CREATE TABLE `DailyCare` (
  `careID` int(11) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `caretakerID` int(11) DEFAULT NULL,
  `Date` date NOT NULL,
  `FeedingTime` time DEFAULT NULL,
  `FoodType` varchar(50) DEFAULT NULL,
  `FoodAmount` decimal(5,2) DEFAULT NULL,
  `Behavior` text,
  `Grooming` tinyint(1) DEFAULT '0',
  `Notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `DailyCare`
--

INSERT INTO `DailyCare` (`careID`, `catID`, `caretakerID`, `Date`, `FeedingTime`, `FoodType`, `FoodAmount`, `Behavior`, `Grooming`, `Notes`) VALUES
(1, 1, 3, '2025-02-01', '08:30:00', 'Dry Food', 50.00, 'Playful and active', 1, 'Loves to climb on shelves'),
(2, 2, 3, '2025-02-01', '12:00:00', 'Wet Food', 70.00, 'A bit shy, but eating well', 0, 'Needs more socialization'),
(3, 3, 4, '2025-02-07', '11:00:00', 'Wet Food', 40.50, 'Tamed and clingy', 1, 'Likes to approach for interaction.'),
(4, 4, 4, '2025-02-20', '09:00:00', 'Dry Food', 70.70, 'Playful', 1, 'Likes to play around');

-- --------------------------------------------------------

--
-- Table structure for table `DailyTasks`
--

CREATE TABLE `DailyTasks` (
  `taskID` int(11) NOT NULL,
  `catArea` varchar(50) NOT NULL,
  `taskName` varchar(100) NOT NULL,
  `taskType` enum('Cleaning','Feeding','Monitoring','Medical') NOT NULL,
  `timePeriod` enum('Morning','Afternoon','Evening') NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `caretakerID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `DailyTasks`
--

INSERT INTO `DailyTasks` (`taskID`, `catArea`, `taskName`, `taskType`, `timePeriod`, `startTime`, `endTime`, `status`, `caretakerID`) VALUES
(1, 'Cat Area 1', 'Care Task #1', 'Cleaning', 'Morning', '07:00:00', '09:00:00', 'Completed', 3),
(2, 'Cat Area 2', 'Care Task #2', 'Cleaning', 'Afternoon', '13:00:00', '15:00:00', 'Pending', 4),
(3, 'Cat Area 3', 'Care Task #3', 'Cleaning', 'Evening', '18:00:00', '20:00:00', 'Pending', 4),
(8, 'Playground', 'Behavior Monitoring', 'Monitoring', 'Afternoon', '12:00:00', '13:00:00', 'Pending', 3),
(9, 'Area 4', 'Feeding Monitoring', 'Feeding', 'Morning', '09:00:00', '10:00:00', 'Completed', 4);

-- --------------------------------------------------------

--
-- Table structure for table `HealthRecord`
--

CREATE TABLE `HealthRecord` (
  `recordID` int(11) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `staffID` int(11) DEFAULT NULL,
  `Date` date NOT NULL,
  `Type` varchar(30) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Treatment` varchar(50) DEFAULT NULL,
  `Medications` varchar(50) DEFAULT NULL,
  `nextCheckup` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `HealthRecord`
--

INSERT INTO `HealthRecord` (`recordID`, `catID`, `staffID`, `Date`, `Type`, `Description`, `Treatment`, `Medications`, `nextCheckup`) VALUES
(1, 1, 2, '2025-01-20', 'Vaccination', 'Routine FVRCP vaccine.', 'Injection administered', 'FVRCP Vaccine', '2025-02-01'),
(2, 2, 3, '2025-01-22', 'Infection', 'Ear infection detected.', 'Ear drops prescribed', 'Antibiotic Ear Drops', '2025-02-10'),
(3, 4, 2, '2025-01-31', 'Surgery', 'Wound cleaning and dressing.', 'Wound Care', 'Antibiotic ointment', '2025-02-07'),
(4, 3, 2, '2025-02-04', 'Checkup', 'Routine deworming for internal parasites.', 'Deworming..', 'Drontal 1/2 tablet', '2025-08-05'),
(5, 2, 2, '2025-02-01', 'Emergency', 'Deep wound on the leg', 'Surgery', 'Painkillers, Antibiotics', '2025-02-04');

-- --------------------------------------------------------

--
-- Table structure for table `MedicalSchedule`
--

CREATE TABLE `MedicalSchedule` (
  `scheduleID` int(11) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `staffID` int(11) DEFAULT NULL,
  `appointmentDate` datetime NOT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `Notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `MedicalSchedule`
--

INSERT INTO `MedicalSchedule` (`scheduleID`, `catID`, `staffID`, `appointmentDate`, `Type`, `Status`, `Notes`) VALUES
(1, 1, 3, '2025-02-10 14:00:00', 'Vaccination', 'Scheduled', 'Follow-up for FVRCP booster shot.'),
(2, 2, 2, '2025-02-15 10:00:00', 'Checkup', 'Scheduled', 'Re-evaluation of ear infection treatment'),
(3, 4, 4, '2025-02-19 09:00:00', 'Checkup', 'Rescheduled', 'Checkup'),
(6, 3, 4, '2025-02-01 15:30:00', 'Dental', 'Scheduled', 'Dental Cleaning'),
(7, 5, 3, '2025-02-28 09:30:00', 'Follow-up', 'Scheduled', 'Follow up');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Role` varchar(20) DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `Status` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `Username`, `Password`, `firstName`, `lastName`, `Email`, `Role`, `ContactNumber`, `LastLogin`, `Status`) VALUES
(1, 'admin', '$2b$12$gEkzwfifDm5fK3BB7WmDIOmLLDdt.AF.oCmXEV230cSCBfCZlbG46', 'Ademin', 'Satu', 'admin1@gmail.com', 'Admin', '0123456789', NULL, 1),
(2, 'medic', '$2b$12$vuzpCwuZGlEhYcV.3JlMF.44wddzGTYOst/t0AB9E8SIl1aG1L5sC', 'DR', 'Abu', 'drabu@gmail.com', 'Medical', '0198765432', NULL, 1),
(3, 'caretaker', '$2b$12$MgrSeU/yAp9HJv4MRnMDk.30tyXs.yLMe58jES6xJkQBPxUJY7.I.', 'Alissa', 'Azrin', 'alissa@gmail.com', 'Caretaker', '0148726395', NULL, 1),
(4, 'caretaker2', '$2y$10$582pbYE0Zy3kmEhb2uegl.k0qTxAtXPldNeZ6ymvO66IeeBdmHpuS', 'Dylan', 'Jordan', 'djordan@gmail.com', 'Caretaker', '0134967284', NULL, 1),
(5, 'medic2', '$2y$10$26V/K4ZHrHSwbcEYYz..l.i.kMz9IlNiEr.ML2QATaqe5/zdflnoG', 'DR', 'Ayuni', 'drayu@gmail.com', 'Medical', '0134568279', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Behavior`
--
ALTER TABLE `Behavior`
  ADD PRIMARY KEY (`behaviorID`),
  ADD KEY `catID` (`catID`),
  ADD KEY `caretakerID` (`caretakerID`);

--
-- Indexes for table `Cat`
--
ALTER TABLE `Cat`
  ADD PRIMARY KEY (`catID`);

--
-- Indexes for table `DailyCare`
--
ALTER TABLE `DailyCare`
  ADD PRIMARY KEY (`careID`),
  ADD KEY `catID` (`catID`),
  ADD KEY `caretakerID` (`caretakerID`);

--
-- Indexes for table `DailyTasks`
--
ALTER TABLE `DailyTasks`
  ADD PRIMARY KEY (`taskID`),
  ADD KEY `caretakerID` (`caretakerID`);

--
-- Indexes for table `HealthRecord`
--
ALTER TABLE `HealthRecord`
  ADD PRIMARY KEY (`recordID`),
  ADD KEY `catID` (`catID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `MedicalSchedule`
--
ALTER TABLE `MedicalSchedule`
  ADD PRIMARY KEY (`scheduleID`),
  ADD KEY `catID` (`catID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Behavior`
--
ALTER TABLE `Behavior`
  MODIFY `behaviorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Cat`
--
ALTER TABLE `Cat`
  MODIFY `catID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `DailyCare`
--
ALTER TABLE `DailyCare`
  MODIFY `careID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `DailyTasks`
--
ALTER TABLE `DailyTasks`
  MODIFY `taskID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `HealthRecord`
--
ALTER TABLE `HealthRecord`
  MODIFY `recordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `MedicalSchedule`
--
ALTER TABLE `MedicalSchedule`
  MODIFY `scheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Behavior`
--
ALTER TABLE `Behavior`
  ADD CONSTRAINT `behavior_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `Cat` (`catID`) ON DELETE CASCADE,
  ADD CONSTRAINT `behavior_ibfk_2` FOREIGN KEY (`caretakerID`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `DailyCare`
--
ALTER TABLE `DailyCare`
  ADD CONSTRAINT `dailycare_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `Cat` (`catID`) ON DELETE CASCADE,
  ADD CONSTRAINT `dailycare_ibfk_2` FOREIGN KEY (`caretakerID`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `DailyTasks`
--
ALTER TABLE `DailyTasks`
  ADD CONSTRAINT `dailytasks_ibfk_1` FOREIGN KEY (`caretakerID`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `HealthRecord`
--
ALTER TABLE `HealthRecord`
  ADD CONSTRAINT `healthrecord_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `Cat` (`catID`) ON DELETE CASCADE,
  ADD CONSTRAINT `healthrecord_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `MedicalSchedule`
--
ALTER TABLE `MedicalSchedule`
  ADD CONSTRAINT `medicalschedule_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `Cat` (`catID`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicalschedule_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `users` (`userID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

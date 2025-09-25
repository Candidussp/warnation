-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 04:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warnation`
--

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `health` int(11) DEFAULT 300,
  `maxHealth` int(11) DEFAULT 300,
  `energy` int(11) DEFAULT 500,
  `maxEnergy` int(11) DEFAULT 500,
  `ammo` int(11) DEFAULT 20,
  `maxAmmo` int(11) DEFAULT 20,
  `cash` bigint(20) DEFAULT 10000,
  `gold` bigint(20) DEFAULT 0,
  `xp` bigint(20) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `skillPoints` int(11) DEFAULT 0,
  `critChance` float DEFAULT 0,
  `dodgeChance` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_update` datetime NOT NULL DEFAULT current_timestamp(),
  `last_regen` datetime NOT NULL DEFAULT current_timestamp(),
  `max_health` int(11) NOT NULL DEFAULT 300,
  `max_energy` int(11) NOT NULL DEFAULT 500,
  `max_ammo` int(11) NOT NULL DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `username`, `password`, `health`, `maxHealth`, `energy`, `maxEnergy`, `ammo`, `maxAmmo`, `cash`, `gold`, `xp`, `level`, `skillPoints`, `critChance`, `dodgeChance`, `created_at`, `updated_at`, `last_update`, `last_regen`, `max_health`, `max_energy`, `max_ammo`) VALUES
(1, 'testuser', '$2y$10$sckKKBRSG0SBGICRTcWui.UiJf9fathQEJLypeGpltgW2TGfuup9q', 255, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 12:34:25', '2025-09-03 18:50:03', '2025-09-03 20:50:03', '2025-09-03 08:29:57', 300, 500, 20),
(2, 'candy', '$2y$10$GRL1eJPw9qBkQly05mDepuVjbbqODpXn0n8Z/e/qCA8LBN.eXqub2', 166, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 12:38:39', '2025-09-07 16:39:33', '2025-09-07 18:39:33', '2025-09-03 08:29:57', 300, 500, 20),
(3, 'boy', '$2y$10$SYKeQOWEwebCgMrliin8LeFPzss422skpDFUlU49SeTG3FgbnWLUC', 300, 300, 500, 500, 20, 20, 28254, 0, 1920, 1, 0, 0, 0, '2025-08-31 12:50:00', '2025-09-12 17:34:19', '2025-09-12 19:06:29', '2025-09-12 18:34:19', 300, 500, 20),
(4, 'some people', '$2y$10$.nGM5RTaxFRmo9KhCL0bIOqiEVywAg8ITMo5ZQaKA09KIUW57PvC.', 300, 300, 500, 500, 20, 20, 11430, 0, 67, 1, 0, 0, 0, '2025-08-31 12:59:40', '2025-09-04 20:10:24', '2025-09-04 21:41:19', '2025-09-04 21:10:24', 300, 500, 20),
(5, 'gay', '$2y$10$T2ZhppnRGiumtODuYRc5cOxxxMjV8FiM/KQ1DyHY8QyJCpa7W6Pw.', 0, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 13:35:53', '2025-09-05 06:24:30', '2025-09-05 08:24:30', '2025-09-03 08:29:57', 300, 500, 20),
(6, 'can', '$2y$10$Yfw.Kit.MNOCRltnJW7f0ejJ0LkWKUl7.NF2DgiVsWj3mNZEs9SiS', 226, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 13:40:24', '2025-09-03 18:50:22', '2025-09-03 20:50:22', '2025-09-03 08:29:57', 300, 500, 20),
(7, 'see', '$2y$10$BvxRAJnNwrDOjGPoBZwDNOtlaQ/BnYR5RtHHb0riNemmr6eY6/rym', 234, 300, 500, 500, 11, 20, 11583, 0, 234, 1, 0, 0, 0, '2025-08-31 13:44:06', '2025-09-03 17:47:56', '2025-09-03 19:47:55', '2025-09-03 08:29:57', 300, 500, 20),
(9, 'job', '$2y$10$vQaCUhC5vfiFWJpuY03W4utWn36gbJSHuF0/ceiOstNPKJMYM7pLu', 209, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 14:04:20', '2025-09-12 17:06:29', '2025-09-12 19:06:29', '2025-09-03 08:29:57', 300, 500, 20),
(10, 'gee', '$2y$10$WfToN0e.JP6i7zmQzIU7R.5.FOVeJTpcihRcpxj8hIRw0HfuSZZBW', 300, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 14:09:31', '2025-09-03 07:29:57', '2025-09-03 07:41:11', '2025-09-03 08:29:57', 300, 500, 20),
(11, 'ben', '$2y$10$DadYlPV1/.8/ghExEJmAGewNrjDdtsf/XZ.PT7AYnZ2wVn3KdE0p6', 300, 300, 500, 500, 20, 20, 11207, 0, 181, 1, 0, 0, 0, '2025-08-31 14:12:46', '2025-09-23 19:07:13', '2025-09-21 12:26:34', '2025-09-23 20:07:13', 300, 500, 20),
(12, 'joy', '$2y$10$oFcgD.nKiwlSX0uNwNxSXOD/bRSAMlFHsvG4gaggrdpZo8BQnp/36', 300, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-08-31 17:12:27', '2025-09-03 07:29:57', '2025-09-03 08:20:18', '2025-09-03 08:29:57', 300, 500, 20),
(14, 'Batman', '$2y$10$1tauXDvUk0wbeHKr4y98Ze4giJhlAVu1z0hD53Z76dJw6WCWuLgXK', 300, 300, 500, 500, 20, 20, 28389, 2450, 1788, 1, 0, 0, 0, '2025-09-01 08:52:21', '2025-09-25 00:10:25', '2025-09-08 10:53:26', '2025-09-25 01:10:25', 300, 500, 20),
(15, 'SUB ZERO', '$2y$10$gnf7nENl/9d9thQaLqMgm.MVKM92m4MZ033EtS/IQTjAjxx5gHFqS', 300, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-09-02 07:16:27', '2025-09-03 07:29:58', '2025-09-02 08:16:27', '2025-09-03 08:29:58', 300, 500, 20),
(16, 'Angel Dewie', '$2y$10$6HGQmUpf5BtJvLxOKk.ct.k0OVhCTn/ilVEfjO8dHwX2ooT9MUlxq', 272, 300, 500, 500, 16, 20, 11286, 0, 80, 1, 0, 0, 0, '2025-09-06 06:42:13', '2025-09-19 13:09:29', '2025-09-19 15:09:29', '2025-09-06 07:42:35', 300, 500, 20),
(17, 'Sam', '$2y$10$AvExvThvAff3a9BWoegfn.0F6TTqkpIJdU1ADFvWM8PBfa1V.tFxW', 278, 300, 500, 500, 20, 20, 10000, 0, 0, 1, 0, 0, 0, '2025-09-09 16:01:14', '2025-09-21 10:26:34', '2025-09-21 12:26:34', '2025-09-09 17:34:32', 300, 500, 20);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

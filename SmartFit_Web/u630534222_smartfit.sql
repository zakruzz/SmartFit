-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 04, 2025 at 05:08 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u630534222_smartfit`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_missions`
--

CREATE TABLE `daily_missions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `pushups` int(11) DEFAULT NULL,
  `situps` int(11) DEFAULT NULL,
  `squatjumps` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_missions`
--

INSERT INTO `daily_missions` (`id`, `user_id`, `date`, `pushups`, `situps`, `squatjumps`, `completed`) VALUES
(1, 5, '2025-05-16', 21, 10, 15, 0);

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `pushups` int(11) DEFAULT NULL,
  `situps` int(11) DEFAULT NULL,
  `squatjumps` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(256) NOT NULL,
  `iv` binary(16) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `height` decimal(5,1) DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `level` int(11) NOT NULL,
  `min_pushups` int(11) NOT NULL,
  `min_situps` int(11) NOT NULL,
  `min_squatjumps` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `calories_burned` decimal(10,2) DEFAULT 0.00,
  `id_device` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `iv`, `name`, `height`, `weight`, `gender`, `age`, `level`, `min_pushups`, `min_situps`, `min_squatjumps`, `created_at`, `calories_burned`, `id_device`) VALUES
(5, 'ahmadfairuzzakiwidyatna@gmail.com', '$2y$10$R5vIszAK.wdXcSj1W66RdOxNKSXKG/M0zCRJQQuUO9GR8XPCp8Oeq', 0x00000000000000000000000000000000, 'Ahmad Fairuz Zaki Widyatna Zaki Widyatna', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-05-15 23:49:46', 0.00, NULL),
(11, 'SnRpczN4WkJGWDdUb1F0NVVGcVIwam5QandzRDd1QXhGSTRRdUgwQTBwND0=', '$2y$10$3f6nkOmXcSpH0gmfcFSgLe6EG8dmHAlpUtGq5KD6.nPWrXV5MBg2O', 0x1d3507fdfc26cf5932a3e857a386baac, 'Ahmad Fairuz SpareTechWeb Zaki Widyatna', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-05-16 03:39:57', 0.00, NULL),
(12, 'Ni9xOWJITlMyUWNyK1lRdDY2SkIxN3FpM2dLN3JTRHpKTFhYeWprTWdJcW1FREFFYTJ1V0lRY3lPZ21sZ0pXdg==', '$2y$10$5du4Dvi.jP02iwUsvdzjn.R5WDFxHj.a.sAPWK34bUa.K0v/ZFYey', 0xf7e7e73b3d13699eaf561db85b2d8665, 'Jacky', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-05-16 13:03:45', 0.00, NULL),
(13, 'eGV5U0lzUTIvc0Q3WE1QanRJZzlLQ1JBNHRFaU1lQ3M1NkNTYlROeFYzST0=', '$2y$10$LBQffR6GqXtfsdayEYfZiOnV1yHp.0KyE0HbeWIACafe7qkcGZiDK', 0x196769cea4b15edc9981a3c90538227c, 'Nugroho Indra', 173.0, 53.0, 'Male', 22, 1, 0, 0, 0, '2025-05-28 08:19:17', 0.27, 'helfa-12345'),
(14, 'blFMc043ZFl0NkV5SllWdnREK1EvOWtwaFhYWDlRaWR2SlNqa0gwZW8zWT0=', '$2y$10$hNrWBvAd0eG180FdlKOoG.mQZBmRAjrUbBTn8GIQf9uDEj4hTz8TS', 0x5eca4920f1724e6abc63ba06fcc46960, 'Lintang', 170.0, 50.0, 'Male', 21, 1, 0, 0, 0, '2025-05-28 13:59:20', 0.63, NULL),
(15, 'WkRxUTNZbnV3VlJsd3UvVFRrUWhZNGRFbFFDWTBlbXZiOW5QNzZmellWVT0=', '$2y$10$w9PD1fK2zoeH5E23EhzRuO3FtdCmnxCcTY9fvdfchlO/UU4xW5PAK', 0xd19af16497e09aa9cdb64d30b2ab75c8, 'Ahmad Radhy', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-05-29 10:48:13', 0.00, NULL),
(16, 'MFhoaWt4Ym5PM2N0bm10VFZRNFBoQlpaQUFKaUJiUGtXZHJPNXEzbHNYRT0=', '$2y$10$CJmWSk9..2DE0.WxSGcQ9emCgRt0GwdjWDeItdVWEHAywxcZfWFry', 0xf5c47dfc859aa5a69588f921558e52b5, 'Lalu Muhamad Jaelani', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-06-01 10:02:10', 0.00, NULL),
(17, 'U0pKZEZTVHRlUExXSlhjRlQzU0pxaWZRZG1QL2JKYm5qcEJqUlg4bm5JWT0=', '$2y$10$9Rt1h0BJxte7Va1RuSa8bu.yMT/300v74TaOoNueJ4eSVPaU3KvoO', 0x964a37e622f000726df4f29931dbefe6, 'Ahmad Fairuz Zaki Widyatna', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-06-02 03:12:35', 0.00, NULL),
(18, 'aTgraHJnbENwaFBCb0xLaHh2Vlo1U1czRVpNTUt4TGI2L3B5NndjR0xEVT0=', '$2y$10$n5j1AeHuZldPg0y2nkculu9esQ3pvwaoUhB9pQMzynNI.ojAZxQxq', 0xfc9e6f0fed218d7c642a898c03392204, 'Ahmad Fairuz Zaki Widyatna', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-06-02 03:18:05', 2.15, 'helfa-12345'),
(19, 'UkNVUFkxQmQ2MFZIWjZ6bHVVTjQ4WVVkakNVeDdWcHROcnRMcmNIdFVhbz0=', '$2y$10$JEdfnQudCInsk66.XeU.1.QTStHD1Y2SB3FBV/ZajSU5SruYy3h..', 0x09240b720c9570a54e0870b688bc64d6, 'Fairuz Zaki', NULL, NULL, NULL, NULL, 1, 0, 0, 0, '2025-06-04 04:17:43', 0.00, 'helfa-12345');

-- --------------------------------------------------------

--
-- Table structure for table `workout_logs`
--

CREATE TABLE `workout_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movement_type` varchar(50) NOT NULL,
  `reps` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_logs`
--

INSERT INTO `workout_logs` (`id`, `user_id`, `movement_type`, `reps`, `created_at`) VALUES
(1, 14, 'situp', 1, '2025-05-28 14:41:41'),
(2, 14, 'situp', 2, '2025-05-28 14:41:42'),
(3, 14, 'situp', 3, '2025-05-28 14:42:05'),
(4, 14, 'situp', 4, '2025-05-28 14:42:05'),
(5, 14, 'pushup', 1, '2025-05-28 14:42:06'),
(6, 14, 'situp', 1, '2025-05-28 14:44:48'),
(7, 14, 'pushup', 1, '2025-05-28 14:44:48'),
(8, 14, 'situp', 2, '2025-05-28 14:44:48'),
(9, 14, 'situp', 3, '2025-05-28 14:44:48'),
(10, 14, 'pushup', 2, '2025-05-28 14:44:50'),
(11, 14, 'pushup', 3, '2025-05-28 14:44:50'),
(12, 14, 'pushup', 4, '2025-05-28 14:44:52'),
(13, 13, 'situp', 1, '2025-05-30 07:56:28'),
(14, 13, 'situp', 1, '2025-05-30 07:56:29'),
(15, 13, 'situp', 1, '2025-05-30 07:56:30'),
(16, 13, 'situp', 1, '2025-05-30 07:56:37'),
(17, 13, 'situp', 1, '2025-05-30 07:56:38'),
(18, 13, 'situp', 1, '2025-05-30 07:56:39'),
(19, 13, 'situp', 1, '2025-05-30 07:56:41'),
(20, 13, 'situp', 1, '2025-05-30 07:56:42'),
(21, 13, 'situp', 1, '2025-05-30 07:56:43'),
(22, 13, 'situp', 1, '2025-05-30 07:56:45'),
(23, 13, 'situp', 1, '2025-05-30 07:57:04'),
(24, 18, 'situp', 1, '2025-06-02 04:24:06'),
(25, 18, 'situp', 1, '2025-06-02 04:24:06'),
(26, 18, 'situp', 1, '2025-06-02 04:24:08'),
(27, 18, 'situp', 1, '2025-06-02 04:24:08'),
(28, 18, 'situp', 1, '2025-06-02 04:24:09'),
(29, 18, 'situp', 1, '2025-06-02 04:24:09'),
(30, 18, 'situp', 1, '2025-06-02 04:24:10'),
(31, 18, 'situp', 1, '2025-06-02 04:24:10'),
(32, 18, 'situp', 1, '2025-06-02 04:24:12'),
(33, 18, 'situp', 1, '2025-06-02 04:24:12'),
(34, 18, 'situp', 1, '2025-06-02 04:24:13'),
(35, 18, 'situp', 1, '2025-06-02 04:24:13'),
(36, 18, 'situp', 1, '2025-06-02 04:24:14'),
(37, 18, 'situp', 1, '2025-06-02 04:24:14'),
(38, 18, 'situp', 1, '2025-06-02 04:24:16'),
(39, 18, 'situp', 1, '2025-06-02 04:24:16'),
(40, 18, 'situp', 1, '2025-06-02 04:24:25'),
(41, 18, 'situp', 1, '2025-06-02 04:24:25'),
(42, 18, 'situp', 1, '2025-06-02 04:24:26'),
(43, 18, 'situp', 1, '2025-06-02 04:24:26'),
(44, 18, 'situp', 1, '2025-06-02 04:24:27'),
(45, 18, 'situp', 1, '2025-06-02 04:24:27'),
(46, 18, 'situp', 1, '2025-06-02 04:24:29'),
(47, 18, 'situp', 1, '2025-06-02 04:24:29'),
(48, 18, 'situp', 1, '2025-06-02 04:24:30'),
(49, 18, 'situp', 1, '2025-06-02 04:24:30'),
(50, 18, 'situp', 1, '2025-06-02 04:24:31'),
(51, 18, 'situp', 1, '2025-06-02 04:24:31'),
(52, 18, 'situp', 1, '2025-06-02 04:24:33'),
(53, 18, 'situp', 1, '2025-06-02 04:24:33'),
(54, 18, 'situp', 1, '2025-06-02 04:24:34'),
(55, 18, 'situp', 1, '2025-06-02 04:24:34'),
(56, 18, 'situp', 1, '2025-06-02 04:24:35'),
(57, 18, 'situp', 1, '2025-06-02 04:24:35'),
(58, 18, 'situp', 1, '2025-06-02 04:24:38'),
(59, 18, 'situp', 1, '2025-06-02 04:24:38'),
(60, 18, 'situp', 1, '2025-06-02 04:24:38'),
(61, 18, 'situp', 1, '2025-06-02 04:24:38'),
(62, 18, 'situp', 1, '2025-06-02 04:24:39'),
(63, 18, 'situp', 1, '2025-06-02 04:24:39'),
(64, 18, 'situp', 1, '2025-06-02 04:24:41'),
(65, 18, 'situp', 1, '2025-06-02 04:24:41'),
(66, 18, 'situp', 1, '2025-06-02 04:24:42'),
(67, 18, 'situp', 1, '2025-06-02 04:24:42'),
(68, 18, 'situp', 1, '2025-06-02 04:24:43'),
(69, 18, 'situp', 1, '2025-06-02 04:24:43'),
(70, 18, 'situp', 1, '2025-06-02 04:24:45'),
(71, 18, 'situp', 1, '2025-06-02 04:24:45'),
(72, 18, 'situp', 1, '2025-06-02 04:24:46'),
(73, 18, 'situp', 1, '2025-06-02 04:24:46'),
(74, 18, 'situp', 1, '2025-06-02 04:24:47'),
(75, 18, 'situp', 1, '2025-06-02 04:24:47'),
(76, 18, 'situp', 1, '2025-06-02 04:24:48'),
(77, 18, 'situp', 1, '2025-06-02 04:24:48'),
(78, 18, 'situp', 1, '2025-06-02 04:24:50'),
(79, 18, 'situp', 1, '2025-06-02 04:24:50'),
(80, 18, 'pushup', 1, '2025-06-02 04:24:51'),
(81, 18, 'pushup', 1, '2025-06-02 04:24:51'),
(82, 18, 'situp', 1, '2025-06-02 04:26:02'),
(83, 18, 'situp', 1, '2025-06-02 04:26:02'),
(84, 18, 'situp', 1, '2025-06-02 04:27:16'),
(85, 18, 'situp', 1, '2025-06-02 04:27:16'),
(86, 18, 'situp', 1, '2025-06-02 04:27:20'),
(87, 18, 'situp', 1, '2025-06-02 04:27:20'),
(88, 18, 'situp', 1, '2025-06-02 04:27:24'),
(89, 18, 'situp', 1, '2025-06-02 04:27:24'),
(90, 18, 'situp', 1, '2025-06-02 04:27:28'),
(91, 18, 'situp', 1, '2025-06-02 04:27:28'),
(92, 18, 'situp', 1, '2025-06-02 04:40:37'),
(93, 18, 'situp', 1, '2025-06-02 04:40:41'),
(94, 18, 'situp', 1, '2025-06-02 04:40:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_missions`
--
ALTER TABLE `daily_missions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workout_logs`
--
ALTER TABLE `workout_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_missions`
--
ALTER TABLE `daily_missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `workout_logs`
--
ALTER TABLE `workout_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_missions`
--
ALTER TABLE `daily_missions`
  ADD CONSTRAINT `daily_missions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `workout_logs`
--
ALTER TABLE `workout_logs`
  ADD CONSTRAINT `workout_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

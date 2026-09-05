-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 05, 2026 at 10:35 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u595335768_alinnthit_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `store_id`, `parent_id`, `name`, `slug`, `description`, `image_path`, `icon`, `created_at`, `updated_at`) VALUES
(126, 1, NULL, 'Chargers & Cables', 'chargers-cables', NULL, 'categories/MF4ODlNndeKmXTgr9u7LzRAyFBaSqDZ29rQj8FyM.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:45:29'),
(127, 1, 126, 'Charging Cable', 'charging-cable', NULL, 'categories/I4CEBPaEb1ae6qyvErlf41FzsE4dRzt3Jc72iHc2.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 12:15:33'),
(128, 1, NULL, 'Audio & Earphones', 'audio-earphones', NULL, 'categories/rH4Z5WRpYuUksNxhXPOrHQ3ZRMiZZmIkYHhjdQsb.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:44:52'),
(129, 1, 128, 'Wired Earphone', 'wired-earphone', NULL, 'categories/MyLFwPrGzAlfuUMkmCNmQWlOufS0gSJ8VbFNLLz8.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 15:05:09'),
(130, 1, 126, 'Charger Set', 'charger-set', NULL, 'categories/old15boefSMbUdtCkTbYqSO4muee4Jge7EBtlNPB.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 12:15:10'),
(131, 1, NULL, 'Phone Spare Parts', 'phone-spare-parts', NULL, 'categories/oUnSf1QNm4MqDW6UXai576svfniAvDKXI94oKRg5.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:46:47'),
(132, 1, 131, 'Phone Battery', 'phone-battery', NULL, 'categories/yDSrXGv6HgX1UvdEgZhcS1vM2HWRw6K2GBlpG8jX.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 13:32:03'),
(133, 1, NULL, 'Power & Storage', 'power-storage', NULL, 'categories/Q8haJ85U9TPhINqd25G530XGnra5E2WHKXhoNnTk.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:46:58'),
(134, 1, 133, 'Power Bank', 'power-bank', NULL, 'categories/H9DSKBR0jJRLMYVt8Cn1kLGBXZuf2TvTOATiA4RD.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 13:36:00'),
(135, 1, 126, 'Car Charger', 'car-charger', NULL, 'categories/gydjEkXxerukrjBE2HZFJVCrQzexRu57km4SaZps.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 12:14:25'),
(136, 1, NULL, 'Phone Accessories', 'phone-accessories', NULL, 'categories/Os1tuu7hDIrXUJQztc7pACD2WI7UXwZgU2VTaFNo.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:54:27'),
(137, 1, 136, 'Phone Holder / Stand', 'phone-holder-stand', NULL, 'categories/ap9Aaj2mOmBxCErrz4YrckBAWLo4IoG5y40GnqqD.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 13:30:22'),
(138, 1, NULL, 'Electronics & Gadgets', 'electronics-gadgets', NULL, 'categories/QiXfdYRyeWut7nugNNr76PdscaKmB2RYRErjTgNh.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:54:11'),
(139, 1, 138, 'Mini Fan', 'mini-fan', NULL, 'categories/dfWcbjbmds0dkEIrZizb70e49URI736F2Ps6xJzu.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 12:16:17'),
(140, 1, NULL, 'CCTV & Network', 'cctv-network', NULL, 'categories/0WmiX4bfCLDOlyFJxsbSN7iqI3DOELp3T9KiXHYN.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 11:45:14'),
(141, 1, 140, 'CCTV Accessories', 'cctv-accessories', NULL, 'categories/0Fksd3cTp9AVHebL1sjUz1AOPnYUAjKbcxw4qoPM.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 15:12:52'),
(142, 1, 140, 'CCTV Camera', 'cctv-camera', NULL, 'categories/dtRipoSfsFqd0N14FA8VWnb37Al9iCsZuvFdVQAZ.webp', NULL, '2026-08-13 14:21:07', '2026-08-14 15:12:30'),
(143, 1, 126, 'Charger Adapter', 'charger-adapter', NULL, 'categories/pJ5pTKhHVmhXUjNc2RT2ucLjq76T7E7j17feMwm9.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 12:14:51'),
(144, 1, 136, 'Screen Protector', 'screen-protector', NULL, 'categories/iUKHo5ShXD5ZcKC2xPXcwCf5CNaxKLidfb1qrnyP.webp', NULL, '2026-08-13 14:21:07', '2026-08-15 13:30:47'),
(145, 1, 133, 'Memory Card', 'memory-card', NULL, 'categories/xoJbPXzXGVPGGkBMeuBJQRwPWyx79anhpMeM4fjL.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:35:44'),
(146, 1, 131, 'Touch LCD', 'touch-lcd', NULL, 'categories/6KFlGA2qhCqc9ISXl1Nnbu6wl3YEhMoecI8rzlsY.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:35:28'),
(147, 1, 131, 'OCA Glass', 'oca-glass', NULL, 'categories/yDkDp6QkaYrNEr0gtmVznzmpr2qtd9Abo3U88Frx.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:31:41'),
(148, 1, NULL, 'Body & Back Cover', 'body-back-cover', NULL, 'categories/YealuxjTGsKzHckTTCYb1wyZ7c5qjCxkDZfdbXp0.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 11:45:06'),
(149, 1, 148, 'Back Glass', 'back-glass', NULL, 'categories/GO5w6kVQmUeDuKVJvUnSuBeBaJYXguVU4jMJFuet.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 15:10:04'),
(150, 1, 136, 'Phone Cover', 'phone-cover', NULL, 'categories/M16Tk9it3VNuPel6uskFei2lS3nalqbMcFWQfBzs.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:33:13'),
(151, 1, 131, 'Charging Port', 'charging-port', NULL, 'categories/cbzVCEl5s0Hcqc4n1V6WiFVYjlFlyeVqrEKI7WDv.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:31:27'),
(152, 1, 138, 'Mouse', 'mouse', NULL, 'categories/hMhHwoUUWWku89q6MtVv9rrzFD5GL7Uv8hwPhsOg.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 12:16:29'),
(153, 1, 138, 'LED Light', 'led-light', NULL, 'categories/q7ojp3SVmnxlXawNfyNgxVnJfVrq8bl6b80Cajym.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 12:16:05'),
(154, 1, 128, 'Bluetooth Earphone', 'bluetooth-earphone', NULL, 'categories/KAYk4HOqgIxJl034ON2yqdx5NaC00qHpsQ8TvZ45.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 15:02:56'),
(155, 1, 148, 'Body Frame', 'body-frame', NULL, 'categories/i69bZAj3d9ptRlexO8KiPkehTe3tNETyraC3ZjRS.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 15:10:20'),
(156, 1, 131, 'Power Switch', 'power-switch', NULL, 'categories/PQp2A4lKQb0o1bYEmAGaOM0MnPLEMcQeOK8b2hDm.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:33:32'),
(157, 1, 136, 'Sticker', 'sticker', NULL, 'categories/ECw8TwNo9DPUr59kSEcEle9pmLrx8IdAogEWw1I9.webp', NULL, '2026-08-13 14:21:08', '2026-08-15 13:31:10'),
(158, 1, 128, 'Bluetooth Speaker', 'bluetooth-speaker', NULL, 'categories/HlLsUa13MJ1idXJjvYzo6L0xaDX4x51ylrvYbaWO.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 15:03:20'),
(160, 1, 128, 'Microphone', 'microphone', NULL, 'categories/lgwS7HZVajy0UQpsVf7Ph4m4pvehBQZ8BndBWm16.webp', NULL, '2026-08-13 14:21:08', '2026-08-14 15:04:53'),
(161, 1, 131, 'Touch', 'touch', NULL, 'categories/HOAqeV2FAuAU9l1esOafPNxZBpVyo4k9tMaVWlvS.webp', NULL, '2026-08-14 13:04:41', '2026-08-15 13:35:15'),
(162, 1, 136, 'Water Bag', 'water-bag', NULL, NULL, NULL, '2026-08-19 08:32:37', '2026-08-19 08:32:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_store_id_slug_unique` (`store_id`,`slug`),
  ADD UNIQUE KEY `categories_store_id_name_unique` (`store_id`,`name`),
  ADD KEY `categories_parent_id_index` (`parent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 05, 2026 at 10:41 AM
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
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `store_id`, `name`, `slug`, `logo_path`, `created_at`, `updated_at`) VALUES
(183, 1, 'Other', 'other', 'brands/brand-183.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:35'),
(184, 1, '5G Epoch', '5g-epoch', 'brands/brand-184.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(185, 1, 'Dac', 'dac', 'brands/brand-185.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(186, 1, '9D', '9d', 'brands/brand-186.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(187, 1, 'Antclan', 'antclan', 'brands/brand-187.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(188, 1, 'Nokia', 'nokia', 'brands/brand-188.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:35'),
(189, 1, 'Baivati', 'baivati', 'brands/brand-189.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(190, 1, 'Xinger', 'xinger', 'brands/brand-190.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:35'),
(191, 1, 'Rotary', 'rotary', 'brands/brand-191.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:35'),
(192, 1, 'Dahua', 'dahua', 'brands/brand-192.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(193, 1, 'Denmen', 'denmen', 'brands/brand-193.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(194, 1, 'Eap', 'eap', 'brands/brand-194.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(195, 1, 'Etb', 'etb', 'brands/brand-195.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(196, 1, 'Fast', 'fast', 'brands/brand-196.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(197, 1, 'Hc', 'hc', 'brands/brand-197.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(198, 1, 'Hd Plus', 'hd-plus', 'brands/brand-198.webp', '2026-08-13 14:21:07', '2026-08-15 12:23:34'),
(199, 1, 'Hikvision', 'hikvision', 'brands/brand-199.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(200, 1, 'Hoco', 'hoco', 'brands/brand-200.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(201, 1, 'Huawei', 'huawei', 'brands/brand-201.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(202, 1, 'Huang', 'huang', 'brands/brand-202.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(203, 1, 'Infinix', 'infinix', 'brands/brand-203.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(204, 1, 'Imou', 'imou', 'brands/brand-204.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(205, 1, 'Apple', 'apple', 'brands/brand-205.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:34'),
(206, 1, 'Vivo', 'vivo', 'brands/brand-206.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(207, 1, 'Ipka', 'ipka', 'brands/brand-207.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(208, 1, 'Itel', 'itel', 'brands/brand-208.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(209, 1, 'Jeqane', 'jeqane', 'brands/brand-209.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(210, 1, 'Kenbo', 'kenbo', 'brands/brand-210.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(211, 1, 'Kt', 'kt', 'brands/brand-211.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(212, 1, 'Kmib', 'kmib', 'brands/brand-212.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(213, 1, 'Lenovo', 'lenovo', 'brands/brand-213.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(214, 1, 'Yk', 'yk', 'brands/brand-214.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(215, 1, 'U-Winn', 'u-winn', 'brands/brand-215.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(216, 1, 'Oppo', 'oppo', 'brands/brand-216.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(217, 1, 'Redmi', 'redmi', 'brands/brand-217.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(218, 1, 'Tecno', 'tecno', 'brands/brand-218.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(219, 1, 'Pinjie', 'pinjie', 'brands/brand-219.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(220, 1, 'Pixi', 'pixi', 'brands/brand-220.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(221, 1, 'Remax', 'remax', 'brands/brand-221.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(222, 1, 'Revo', 'revo', 'brands/brand-222.webp', '2026-08-13 14:21:08', '2026-08-15 12:23:35'),
(223, 1, 'Smart Three', 'smart-three', 'brands/brand-223.webp', '2026-08-13 14:21:09', '2026-08-15 12:23:35'),
(224, 1, 'Super X', 'super-x', 'brands/brand-224.webp', '2026-08-13 14:21:09', '2026-08-15 12:23:35'),
(225, 1, 'Samsung', 'samsung', 'brands/brand-225.webp', '2026-08-13 14:21:09', '2026-08-15 12:23:35'),
(226, 1, 'Xiaomi', 'xiaomi', 'brands/brand-226.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(227, 1, 'Stereo', 'stereo', 'brands/brand-227.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(228, 1, 'Sport', 'sport', 'brands/brand-228.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(229, 1, 'Tdk', 'tdk', 'brands/brand-229.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(230, 1, 'Vdm', 'vdm', 'brands/brand-230.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(231, 1, 'Wster', 'wster', 'brands/brand-231.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(232, 1, 'X Cable', 'x-cable', 'brands/brand-232.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(233, 1, 'Xinude', 'xinude', 'brands/brand-233.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(234, 1, 'Yd', 'yd', 'brands/brand-234.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(235, 1, 'Realme', 'realme', 'brands/brand-235.webp', '2026-08-13 14:21:10', '2026-08-15 12:23:35'),
(236, 1, 'RELKA', 'relka', NULL, '2026-08-19 08:26:22', '2026-08-19 08:26:22'),
(237, 1, 'MANVE', 'manve', NULL, '2026-08-19 08:27:19', '2026-08-19 08:27:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_store_id_slug_unique` (`store_id`,`slug`),
  ADD UNIQUE KEY `brands_store_id_name_unique` (`store_id`,`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for barakah_grove
CREATE DATABASE IF NOT EXISTS `barakah_grove` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `barakah_grove`;

-- Dumping structure for table barakah_grove.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','paid','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table barakah_grove.orders: ~1 rows (approximately)
INSERT INTO `orders` (`id`, `user_id`, `invoice_no`, `subtotal`, `total`, `status`, `created_at`) VALUES
	(2, 2, 'BGV-20260819130124-531', 106000.00, 106000.00, 'paid', '2026-08-19 13:01:24');

-- Dumping structure for table barakah_grove.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `product_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `quantity` int unsigned NOT NULL,
  `total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_order` (`order_id`),
  KEY `fk_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table barakah_grove.order_items: ~5 rows (approximately)
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total`) VALUES
	(2, 2, 7, 'Silver Crest Warmer', 30000.00, 1, 30000.00),
	(3, 2, 9, 'Master Chef Flask', 14000.00, 1, 14000.00),
	(4, 2, 10, 'Gift Master Kettle', 12000.00, 1, 12000.00),
	(5, 2, 11, 'Small Solar Fan', 40000.00, 1, 40000.00),
	(6, 2, 13, 'Oniye Abdullahi Masud', 10000.00, 1, 10000.00);

-- Dumping structure for table barakah_grove.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table barakah_grove.products: ~8 rows (approximately)
INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `created_at`, `updated_at`) VALUES
	(5, 'Plate Array', 'An Item for Storing Plates after Washing, to enhance proper drying.', 10000.00, 'product_20260819125721_e36b0f4f4411d861.jpg', 10, '2026-08-17 19:40:34', '2026-08-19 12:57:21'),
	(6, 'Livstor Hot Plate', 'Livstor Hot Plate for Cooking Food, Easy and Affordable', 20000.00, 'product_20260819125742_c3a643f82fe98861.jpg', 50, '2026-08-17 19:41:48', '2026-08-19 12:57:42'),
	(7, 'Silver Crest Warmer', 'Silver Crest Warmer is used for warming food after cooking to reserve the taste and temperature. It is affordable and durable', 30000.00, 'product_20260819125805_d117c84bc1a83147.jpg', 999, '2026-08-17 19:43:21', '2026-08-19 13:01:25'),
	(8, 'The Super Blender', 'The Super Blender contain 2 in 1 blender and an Unbreakable Jar.', 100000.00, 'product_20260819125824_b1b1aac469a11cd1.jpg', 200, '2026-08-17 19:45:33', '2026-08-19 12:58:24'),
	(9, 'Master Chef Flask', 'Durable and Affordable', 14000.00, 'product_20260819125914_c3632e940052d7c6.jpg', 29, '2026-08-17 19:46:42', '2026-08-19 13:01:25'),
	(10, 'Gift Master Kettle', 'It is a German Whistle Kettle specially made to preserve good health, it is affordable and good.', 12000.00, 'product_20260819125937_37e96f23e7e6dc1b.jpg', 2985, '2026-08-17 19:48:35', '2026-08-19 13:01:25'),
	(11, 'Small Solar Fan', '4 in 1 Rechargeable Lamp with Led Lamp, it lasts and affordable.', 40000.00, 'product_20260819125954_8eb140406fb36ae1.jpg', 199, '2026-08-17 19:50:17', '2026-08-19 13:01:25'),
	(13, 'Oniye Abdullahi Masud', 'aajkjksa', 10000.00, 'product_20260819125608_2862083090f47b4e.png', 1999, '2026-08-19 12:56:08', '2026-08-19 13:01:25');

-- Dumping structure for table barakah_grove.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('customer','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table barakah_grove.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
	(2, 'Abdullahi Masud Oniye', 'oniyeabdullahi00@gmail.com', '09015621510', '$2y$10$p3M4d1QPjauAHD/y2/GpDe9yGR7FKZ0VQ.eqJoWCZbXyrVGNFa6m.', 'customer', '2026-08-17 18:36:02'),
	(4, 'Barakah Grove Admin', 'admin.barakahgrove@gmail.com', '07045141032', '$2y$10$M0HAZQZFNeCZlqUpmrDBceFGdpGS2uexQYAgS80RVfHZHOXKQuzJK', 'admin', '2026-08-17 18:53:38');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

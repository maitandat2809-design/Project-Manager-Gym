/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `coach_id` int(11) DEFAULT NULL,
  `booking_date` datetime NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `coach_seen` tinyint(1) DEFAULT 0,
  `coach_message` text DEFAULT NULL,
  `user_seen` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_phone` varchar(20) DEFAULT NULL,
  `user_address` text DEFAULT NULL,
  `coach_phone` varchar(20) DEFAULT NULL,
  `coach_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `coach_id` (`coach_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `bookings` VALUES (8,3,3,'2026-03-15 20:15:00','','2026-03-14 11:15:12',1,'',1,'2026-03-23 07:24:39',NULL,NULL,NULL,NULL),(9,3,4,'2026-03-21 18:17:00','confirmed','2026-03-14 11:17:47',1,'',1,'2026-03-15 03:21:11',NULL,NULL,NULL,NULL),(10,3,4,'2026-03-17 09:55:00','confirmed','2026-03-15 02:55:50',1,'',1,'2026-03-15 03:21:11',NULL,NULL,NULL,NULL),(11,3,4,'2026-03-29 10:03:00','pending','2026-03-15 03:03:29',1,'Sorry I don\'t change train for you because I don\'t time',1,'2026-05-31 03:30:11',NULL,NULL,NULL,NULL),(12,3,4,'2026-03-21 01:21:00','','2026-03-15 03:21:32',1,'',1,'2026-03-15 03:24:00',NULL,NULL,NULL,NULL),(13,3,4,'2026-03-21 01:22:00','confirmed','2026-03-15 03:22:11',1,'',1,'2026-03-15 03:24:00',NULL,NULL,NULL,NULL),(14,3,3,'2026-03-27 03:01:00','confirmed','2026-03-15 04:01:31',1,'ok pro\r\n',1,'2026-03-23 07:24:39',NULL,NULL,NULL,NULL),(15,3,3,'2026-03-28 09:46:00','','2026-03-28 02:46:08',1,'',1,'2026-05-29 06:30:09',NULL,NULL,NULL,NULL),(17,3,3,'2026-05-31 10:35:00','cancelled','2026-05-31 03:35:23',1,NULL,0,'2026-06-10 09:56:17',NULL,NULL,NULL,NULL),(18,8,3,'2026-05-31 11:36:00','confirmed','2026-05-31 04:36:24',1,'',0,'2026-06-10 09:57:02',NULL,NULL,NULL,NULL),(19,3,3,'2026-06-12 14:42:00','pending','2026-06-12 07:43:00',0,NULL,0,'2026-06-12 07:43:00','0888236757','lamtuoi155@gmail.com',NULL,NULL),(20,3,3,'2026-06-13 14:48:00','pending','2026-06-12 07:48:08',0,NULL,0,'2026-06-12 07:48:08','0888236757','lamtuoi155@gmail.com',NULL,NULL),(21,3,3,'2026-06-13 15:54:00','pending','2026-06-12 08:54:59',0,NULL,0,'2026-06-12 08:54:59','0888236757','lamtuoi155@gmail.com',NULL,NULL),(22,3,3,'2026-06-13 15:54:00','pending','2026-06-12 10:26:38',0,NULL,0,'2026-06-12 10:26:38','0888236757','lamtuoi155@gmail.com',NULL,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coach_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coach_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `coach_id` (`coach_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `coach_reviews_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coach_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coaches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `coaches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `coaches` VALUES (3,6,'Yoga & Pilates','Professional fitness instructor',25.00,'assets/img/avatars/user_6_1781247203.jpg'),(4,7,'Yoga & Pilates','Professional fitness instructor',3000.00,NULL);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `order_items` VALUES (25,25,24,1,10.00),(26,26,24,2,10.00),(27,27,24,1,10.00),(28,28,24,1,10.00),(29,29,25,1,15.00),(30,30,26,1,12.00),(31,31,22,1,25.00),(32,31,24,1,10.00);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_seen` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `orders` VALUES (7,3,45.99,'completed','2026-03-14 10:44:32',1),(8,3,45.99,'completed','2026-03-14 10:47:38',1),(9,3,19.99,'completed','2026-03-14 10:50:13',1),(10,3,45.99,'completed','2026-03-14 10:59:56',1),(11,3,1211.00,'completed','2026-03-14 11:19:33',1),(12,3,59.99,'completed','2026-03-15 02:30:52',1),(13,3,249.00,'completed','2026-03-15 02:35:58',1),(14,3,29.50,'completed','2026-03-15 02:38:42',1),(15,3,59.99,'completed','2026-03-15 02:40:04',1),(16,3,45.00,'completed','2026-03-15 02:42:35',1),(17,3,29.50,'completed','2026-03-15 02:47:09',1),(18,3,22.00,'completed','2026-03-15 02:48:11',1),(19,3,39.99,'completed','2026-03-15 02:50:02',1),(21,3,3794.99,'completed','2026-03-15 03:43:46',1),(22,6,557.99,'completed','2026-06-10 10:01:19',1),(23,6,59.99,'completed','2026-06-10 10:04:16',1),(24,6,249.00,'completed','2026-06-10 10:06:59',1),(25,3,10.00,'completed','2026-06-12 07:49:17',1),(26,3,20.00,'completed','2026-06-12 07:55:02',1),(27,3,10.00,'completed','2026-06-12 07:57:56',1),(28,3,10.00,'completed','2026-06-12 08:08:01',0),(29,3,15.00,'completed','2026-06-12 08:14:16',0),(30,3,12.00,'completed','2026-06-12 08:19:21',0),(31,3,35.00,'completed','2026-06-12 08:24:52',0);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `password_resets` VALUES (1,'maitandat2809@gmail.com','4d755302b68d13abde21ef597f3a9f6063ed681ba7ccc63f98d2b3f3ec9a6ec5','2026-03-15 04:18:05','2026-03-15 02:18:05');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `sales_count` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT 'supplement',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `products` VALUES (22,'GymX Performance Training Shirt','Lightweight, breathable, and moisture-wicking fabric designed for peak performance and comfort.',25.00,100,'https://images.unsplash.com/photo-1581655353564-df123a1eb820?q=80&w=2070&auto=format&fit=crop',1,'accessory'),(24,'Harbinger - 4.5\" Foam Core Belt','Premium quality fitness equipment/supplement.',10.00,11,'assets/img/products/prod_6a2bb03c81298.jpg',6,'accessory'),(25,'Labrada - Muscle Mass Gainer (12 Lbs)','Premium quality fitness equipment/supplement.',15.00,20,'assets/img/products/prod_6a2bb1a9b51d9.jpg',1,'supplement'),(26,'Mutant - MASS EXTREME 2500 (6 Lbs)','Premium quality fitness equipment/supplement.',12.00,12,'assets/img/products/prod_6a2bb1d2ea040.jpg',1,'supplement'),(27,'Redcon1 - Mass Gainer (5.89 Lbs)','Premium quality fitness equipment/supplement.',30.00,23,'assets/img/products/prod_6a2bb1f1b3c70.jpg',0,'supplement'),(28,'Scitec Nutrition - 100% Whey Isolate (1816g)','Premium quality fitness equipment/supplement.',34.00,30,'assets/img/products/prod_6a2bb214edc73.jpg',0,'supplement'),(29,'Nutrex Shaker Bottle (600ml)','Premium quality fitness equipment/supplement.',5.00,5,'assets/img/products/prod_6a2bb24d5e900.jpg',0,'accessory'),(30,'Gym pants','Premium quality fitness equipment/supplement.',20.00,5,'assets/img/products/prod_6a2bb2c10bca9.webp',0,'accessory'),(31,'SpartaFit Socks - White','Premium quality fitness equipment/supplement.',10.00,5,'assets/img/products/prod_6a2bb35de81af.webp',0,'accessory'),(32,'Harbinger 6 Inch Padded Leather Belt','Premium quality fitness equipment/supplement.',80.00,10,'assets/img/products/prod_6a2bb399d652c.webp',0,'accessory');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','coach','manager') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `users` VALUES (3,'Dat User','maitandat2809@gmail.com','$2y$10$EDa8iCaJv3BRksSf.elw.uauwB/wdVsC2z8R7RbKsd7elzD2i2dOG','user','2026-03-12 01:38:33','assets/img/avatars/user_3_1773281518.jpg',22,'Handsome'),(5,'ADMIN','lamtuoi155@gmail.com','$2y$10$G7lVLe28tk99qEXjhO4dQ.lykrUBrNhNQ7hqlMezMsLUkFY69QqvO','admin','2026-03-14 10:41:15',NULL,0,''),(6,'Thanh Coach PT','thanhptggbc220216@fpt.edu.vn','$2y$10$8Y5Zht/bIKl.aeNKFmmKOO4VFK6DIe5DNupoubdPM.LBlg4h.bB/G','coach','2026-03-14 11:06:46','assets/img/avatars/user_6_1781247203.jpg',25,''),(7,'Dat Coach','datprogramer2809@gmail.com','$2y$10$kGRAaRJ0xGZmjP//f5qOLOWVe5NXiNw5AUwh5sEj9qBBafB.9hGGW','coach','2026-03-14 11:17:08',NULL,0,''),(8,'Dat Manager','Datmtgcc210217@fpt.edu.vn','$2y$10$fqKeGiC0rbI5w49MSFp.JeuIpn8M6XALjIa75WKW6dDVeWSIVbu4K','user','2026-03-15 02:35:34',NULL,0,''),(9,'Manager Gym','managergym283@gmail.com','$2y$10$nTQCBm5CZxF1Y806HnQgBuEkLgIpcbZggZKwrBegbVnvvjEIOkFie','manager','2026-06-12 08:06:36',NULL,NULL,NULL);

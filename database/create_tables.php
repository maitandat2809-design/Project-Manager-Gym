<?php
$host = 'sql202.infinityfree.com';
$dbname = 'if0_42164663_gym';
$username = 'if0_42164663';
$password = 'd9mAzS8Q7XWF';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected!</p>";
} catch(PDOException $e) {
    die("<p style='color:red'>❌ Connection failed: " . $e->getMessage() . "</p>");
}

$sqls = [];

// 1. Create users table
$sqls[] = "CREATE TABLE IF NOT EXISTS `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 2. Create orders table
$sqls[] = "CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_seen` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 3. Create products table
$sqls[] = "CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `sales_count` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT 'supplement',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 4. Create password_resets table
$sqls[] = "CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Insert manager user
$managerPass = password_hash('gymx2024', PASSWORD_DEFAULT);
$adminPass = password_hash('gymx2024', PASSWORD_DEFAULT);
$sqls[] = "INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`) VALUES
  ('Manager Gym', 'managergym283@gmail.com', '$managerPass', 'manager'),
  ('ADMIN', 'lamtuoi155@gmail.com', '$adminPass', 'admin')";

// Add FK for orders after users exists
$sqls[] = "ALTER TABLE `orders` ADD CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE";
$sqls[] = "ALTER TABLE `order_items` ADD CONSTRAINT FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE";

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ OK: " . substr($sql, 0, 60) . "...</p>";
    } catch(PDOException $e) {
        echo "<p style='color:orange'>⚠️ " . substr($sql, 0, 60) . "... → " . $e->getMessage() . "</p>";
    }
}

echo "<br><h2 style='color:green'>✅ DONE! Try login now with:</h2>";
echo "<p>Email: <b>managergym283@gmail.com</b></p>";
echo "<p>Password: <b>gymx2024</b></p>";

@unlink(__FILE__);
?>

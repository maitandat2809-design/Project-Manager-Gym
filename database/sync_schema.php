<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== GYM MANAGER DATABASE SCHEMA SYNC ===\n\n";

$alterQueries = [
    // Products table
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS name_vi VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS description_vi TEXT DEFAULT NULL",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'supplement'",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS has_weight_variants TINYINT(1) DEFAULT 0",

    // Coaches table
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS zalo VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS gmail VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS room_name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS room_number VARCHAR(50) DEFAULT NULL",

    // Orders table
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cod'",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_seen TINYINT(1) DEFAULT 0",

    // Order items table
    "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant VARCHAR(50) DEFAULT NULL",

    // Bookings table
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS admin_seen TINYINT(1) DEFAULT 0",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_seen TINYINT(1) DEFAULT 0",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_seen TINYINT(1) DEFAULT 0",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_message TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_address TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_address TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS class_name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS specialty VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS room_number VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS training_plan TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS attendance ENUM('pending', 'present', 'absent') DEFAULT 'pending'",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_rating TINYINT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_review TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS user_reviewed_at DATETIME DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_session_notes TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS coach_notes_at DATETIME DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS review_seen_by_coach TINYINT(1) DEFAULT 0",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS notes_seen_by_user TINYINT(1) DEFAULT 0",

    // Users table
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS age INT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00"
];

foreach ($alterQueries as $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] Executed: $sql\n";
    } catch (PDOException $e) {
        echo "[ERROR] $sql\n      Message: " . $e->getMessage() . "\n";
    }
}

echo "\nChecking required tables existence...\n";
$tables = ['product_reviews', 'coach_reviews', 'membership_plans', 'member_subscriptions', 'password_resets'];

// Product Reviews
$pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Coach Reviews
$pdo->exec("CREATE TABLE IF NOT EXISTS coach_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Password Resets
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

echo "\n=== ALL SCHEMA SYNCS COMPLETED SUCCESSFULLY ===\n";

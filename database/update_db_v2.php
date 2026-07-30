<?php
require_once 'config/db.php';

$sql = "
-- 1. Create Product Reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Create Coach Reviews table
CREATE TABLE IF NOT EXISTS coach_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Add sales counter to products table if not exists (or we can calculate dynamically but column is better for sorting)
ALTER TABLE products ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0;
";

try {
    $pdo->exec($sql);
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>Review tables created and products table updated.</p>";
    echo "<a href='index.php'>Go to Home</a>";
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>

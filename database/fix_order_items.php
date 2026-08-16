<?php
require_once 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "<h1>Bảng 'order_items' đã được tạo thành công!</h1>";
    echo "<a href='/Gym Manager/dashboards/profile.php'>Quay lại Hồ sơ</a>";
} catch (PDOException $e) {
    echo "<h1>Lỗi khi tạo bảng:</h1> " . $e->getMessage();
}
?>

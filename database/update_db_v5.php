<?php
require_once 'config/db.php';

try {
    // Add category column to products table
    $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT 'supplement'");
    
    // Categorize existing products based on name
    $pdo->exec("UPDATE products SET category = 'accessory' WHERE name LIKE '%Tạ%' OR name LIKE '%Dây%' OR name LIKE '%Con lăn%' OR name LIKE '%Găng tay%' OR name LIKE '%Đai%' OR name LIKE '%Ghế%' OR name LIKE '%Xà%' OR name LIKE '%Thảm%'");
    
    echo "<h1>Cập nhật cơ sở dữ liệu thành công!</h1>";
    echo "<p>Đã thêm cột 'category' vào bảng products và phân loại tự động các sản phẩm hiện có.</p>";
    echo "<a href='shop.php'>Quay lại cửa hàng</a> | <a href='admin.php?page=products'>Quản lý sản phẩm</a>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Cột category đã tồn tại.";
        // Vẫn chạy cập nhật category dựa theo tên để đảm bảo dữ liệu mới thêm cũng được phân loại
        $pdo->exec("UPDATE products SET category = 'accessory' WHERE name LIKE '%Tạ%' OR name LIKE '%Dây%' OR name LIKE '%Con lăn%' OR name LIKE '%Găng tay%' OR name LIKE '%Đai%' OR name LIKE '%Ghế%' OR name LIKE '%Xà%' OR name LIKE '%Thảm%'");
        echo "<br>Đã cập nhật lại phân loại cho sản phẩm mới.";
    } else {
        echo "Lỗi: " . $e->getMessage();
    }
}
?>

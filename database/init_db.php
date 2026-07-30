<?php
require_once 'config/db.php';

try {
    $sql = file_get_contents('setup.sql');
    
    // The setup.sql might contain multiple statements
    // PDO exec handles multiple statements if the driver allows it, 
    // but it's safer to split or use a specific method.
    // However, for MySQL, exec() usually works for multiple statements.
    
    $pdo->exec($sql);
    
    echo "<h1>Cài đặt Cơ sở dữ liệu Thành công!</h1>";
    echo "<p>Các bảng đã được tạo và dữ liệu mẫu đã được thêm.</p>";
    echo "<a href='index.php'>Quay lại Trang chủ</a>";
} catch (PDOException $e) {
    echo "<h1>Lỗi Cài đặt:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

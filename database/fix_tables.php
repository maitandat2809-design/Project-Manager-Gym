<?php
require_once 'config/db.php';

try {
    // Check if columns exist and add them if not
    $columns = [
        'avatar' => "VARCHAR(255) DEFAULT NULL",
        'age' => "INT DEFAULT NULL",
        'nickname' => "VARCHAR(100) DEFAULT NULL"
    ];

    foreach ($columns as $column => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
            echo "Đã thêm cột: $column<br>";
        } else {
            echo "Cột $column đã tồn tại.<br>";
        }
    }

    echo "<h1>Cấu trúc bảng users đã được cập nhật thành công!</h1>";
    echo "<a href='index.php'>Quay lại Trang chủ</a>";
} catch (PDOException $e) {
    echo "<h1>Lỗi:</h1> " . $e->getMessage();
}
?>

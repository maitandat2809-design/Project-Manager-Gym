<?php
include_once __DIR__ . '/config/db.php';
try {
    // Add manager to role enum just in case it wasn't there (though we saw update_roles.php)
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'coach', 'manager') DEFAULT 'user'");
    
    // Add seen flag for orders for admin/manager web notification
    $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'admin_seen'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN admin_seen TINYINT(1) DEFAULT 0");
    }
    
    echo "Database updated for Manager & Admin notifications.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

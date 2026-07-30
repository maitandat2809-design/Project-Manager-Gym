<?php
include 'config/db.php';
try {
    $pdo->exec("ALTER TABLE order_items ADD COLUMN variant VARCHAR(50) DEFAULT NULL");
    echo "Success";
} catch (Exception $e) {
    echo $e->getMessage();
}

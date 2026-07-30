<?php
include_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL");
    echo "Phone column added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

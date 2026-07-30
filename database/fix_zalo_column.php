<?php
include_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE coaches ADD COLUMN IF NOT EXISTS zalo VARCHAR(20) DEFAULT NULL");
    $pdo->exec("ALTER TABLE coaches ADD COLUMN IF NOT EXISTS gmail VARCHAR(100) DEFAULT NULL");
    echo "Columns added successfully or already exist.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE bookings 
                ADD COLUMN class_name VARCHAR(255) NULL,
                ADD COLUMN specialty VARCHAR(255) NULL,
                ADD COLUMN room_number VARCHAR(50) NULL,
                ADD COLUMN training_plan TEXT NULL,
                ADD COLUMN attendance VARCHAR(50) DEFAULT 'pending'");
    echo "Successfully updated bookings table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>

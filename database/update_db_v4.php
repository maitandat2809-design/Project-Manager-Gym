<?php
require_once 'config/db.php';

try {
    $sql = "
    ALTER TABLE bookings 
    ADD COLUMN IF NOT EXISTS user_phone VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS user_address TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS coach_phone VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS coach_address TEXT DEFAULT NULL;
    ";
    
    $pdo->exec($sql);
    echo "Columns added to bookings table successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

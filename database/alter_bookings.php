<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN admin_seen TINYINT(1) NOT NULL DEFAULT 0");
    echo "Column admin_seen added to bookings successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

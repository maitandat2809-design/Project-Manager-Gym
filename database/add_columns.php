<?php
include_once 'config/db.php';
try {
    $pdo->query("ALTER TABLE orders ADD COLUMN phone VARCHAR(20) NULL, ADD COLUMN address TEXT NULL");
    echo "Columns added to orders successfully.\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

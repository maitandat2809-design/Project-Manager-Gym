<?php
require_once 'config/db.php';

$queries = [
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS room_name VARCHAR(100) NULL AFTER gmail",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS room_number VARCHAR(20) NULL AFTER room_name",
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>✔ OK: " . htmlspecialchars($sql) . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠ " . $e->getMessage() . "</p>";
    }
}

echo "<p><strong>Done!</strong> <a href='manager.php?page=coaches'>Quay lại Manager</a></p>";

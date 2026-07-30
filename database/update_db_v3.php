<?php
require_once 'config/db.php';

$sql = "
ALTER TABLE coaches ADD COLUMN IF NOT EXISTS zalo VARCHAR(20) DEFAULT NULL;
ALTER TABLE coaches ADD COLUMN IF NOT EXISTS gmail VARCHAR(100) DEFAULT NULL;
";

try {
    $pdo->exec($sql);
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>Zalo and Gmail columns added to coaches table.</p>";
    echo "<a href='admin.php?page=coaches'>Go to Coach Management</a>";
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>

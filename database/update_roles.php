<?php
require_once 'config/db.php';

try {
    // Check current column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch();
    $type = $column['Type'];

    // Update the ENUM to include 'manager'
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'coach', 'manager') DEFAULT 'user'");
    
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>Role 'manager' has been added to the system.</p>";
    echo "<a href='admin.php'>Go to Admin Dashboard</a>";
} catch (PDOException $e) {
    echo "<h1>Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

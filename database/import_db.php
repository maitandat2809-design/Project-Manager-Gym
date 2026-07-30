<?php
$host = 'sql202.infinityfree.com';
$dbname = 'if0_42164663_gym';
$username = 'if0_42164663';
$password = 'd9mAzS8Q7XWF';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('gym_manager_utf8.sql');
    if ($sql) {
        // Execute the SQL queries
        $pdo->exec($sql);
        echo "<h1>DATABASE IMPORTED SUCCESSFULLY!</h1>";
        echo "<p>All tables and data have been created.</p>";
    } else {
        echo "<h1>SQL FILE EMPTY OR NOT FOUND!</h1>";
    }
} catch(PDOException $e) {
    echo "<h1>DATABASE IMPORT ERROR!</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}

// Cleanup
@unlink('gym_manager_utf8.sql');
@unlink(__FILE__);
?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'sql202.infinityfree.com';
$dbname = 'if0_42164663_gym';
$username = 'if0_42164663';
$password = 'd9mAzS8Q7XWF';

echo "<h2>Step 1: DB Connection</h2>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected successfully!</p>";
} catch(PDOException $e) {
    die("<p style='color:red'>❌ Connection FAILED: " . $e->getMessage() . "</p>");
}

echo "<h2>Step 2: Tables in DB</h2>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (empty($tables)) {
    echo "<p style='color:red'>❌ NO TABLES FOUND - Database is empty! Import SQL first.</p>";
} else {
    echo "<p style='color:green'>✅ Tables found: " . implode(', ', $tables) . "</p>";
}

echo "<h2>Step 3: Users Table</h2>";
try {
    $users = $pdo->query("SELECT id, name, email, role FROM users")->fetchAll();
    echo "<p style='color:green'>✅ " . count($users) . " users found:</p><ul>";
    foreach ($users as $u) {
        echo "<li><b>{$u['email']}</b> - Role: {$u['role']}</li>";
    }
    echo "</ul>";
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

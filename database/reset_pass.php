<?php
$host = 'sql202.infinityfree.com';
$dbname = 'if0_42164663_gym';
$username = 'if0_42164663';
$password = 'd9mAzS8Q7XWF';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $new_pass = password_hash('123456', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE email = 'managergym283@gmail.com'")->execute([$new_pass]);
    
    echo "Password updated successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

@unlink(__FILE__);
?>

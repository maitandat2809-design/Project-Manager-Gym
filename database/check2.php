<?php
require 'config/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'managergym283@gmail.com'");
$stmt->execute();
print_r($stmt->fetchAll());
?>

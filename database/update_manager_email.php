<?php
require 'config/db.php';
$stmt = $pdo->prepare("UPDATE users SET email = 'managergym283@gmail.com' WHERE role = 'manager'");
$stmt->execute();
echo "Manager email updated to managergym283@gmail.com\n";
?>

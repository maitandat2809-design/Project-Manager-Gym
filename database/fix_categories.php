<?php
require 'config/db.php';

$pdo->exec("UPDATE products SET category = 'supplement' WHERE id IN (25, 26, 27, 28) OR name LIKE '%Whey%' OR name LIKE '%Protein%'");
$pdo->exec("UPDATE products SET category = 'accessory' WHERE id IN (22, 24, 29, 30, 31, 32)");

echo 'Fixed';
?>

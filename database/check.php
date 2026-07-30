<?php
require 'config/db.php';
$res = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'manager'")->fetchAll();
print_r($res);
?>

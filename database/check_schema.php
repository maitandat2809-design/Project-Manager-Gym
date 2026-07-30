<?php
include_once 'config/db.php';
$res = [];
$tables = ['users', 'orders', 'bookings'];
foreach ($tables as $table) {
    $stmt = $pdo->query("DESCRIBE $table");
    $res[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($res, JSON_PRETTY_PRINT);

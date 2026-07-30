<?php
require 'config/db.php';
$coaches = $pdo->query('SELECT c.id, u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id')->fetchAll(PDO::FETCH_ASSOC);
print_r($coaches);
?>

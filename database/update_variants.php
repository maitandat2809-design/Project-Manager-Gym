<?php
include 'config/db.php';
try {
    $pdo->exec("UPDATE products SET has_weight_variants = 1 WHERE category = 'supplement'");
    echo "Success updating existing products";
} catch (Exception $e) {
    echo $e->getMessage();
}

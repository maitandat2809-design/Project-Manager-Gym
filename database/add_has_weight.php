<?php
include 'config/db.php';
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN has_weight_variants TINYINT(1) DEFAULT 0");
    echo "Success";
} catch (Exception $e) {
    echo $e->getMessage();
}

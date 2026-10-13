<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');
echo "=== ADDING MEMBERSHIP LOYALTY & RENEWAL COLUMNS ===\n\n";

$queries = [
    // member_subscriptions table
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS is_renewal TINYINT(1) DEFAULT 0",
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,0) DEFAULT 0",
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS bonus_days INT(11) DEFAULT 0",
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS bonus_pt INT(11) DEFAULT 0",
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS loyalty_gifts TEXT DEFAULT NULL",
    "ALTER TABLE member_subscriptions ADD COLUMN IF NOT EXISTS renewal_parent_id INT(11) DEFAULT NULL",

    // orders table (member discount on shop)
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS member_discount DECIMAL(10,2) DEFAULT 0.00"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] Executed: $sql\n";
    } catch (PDOException $e) {
        echo "[INFO/ERROR] $sql\n      Message: " . $e->getMessage() . "\n";
    }
}

echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";

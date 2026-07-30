<?php
$files = ['manager.php', 'admin.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Update Chart Query
        $old_query = "SELECT MONTH(created_at) as month, SUM(total_price) as revenue \n                                             FROM orders \n                                             WHERE YEAR(created_at) = ? AND status = 'completed'\n                                             GROUP BY MONTH(created_at)";
        $new_query = "SELECT MONTH(o.created_at) as month, SUM(o.total_price) as revenue \n                                             FROM orders o\n                                             WHERE YEAR(o.created_at) = ? AND o.status = 'completed'\n                                             AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)\n                                             GROUP BY MONTH(o.created_at)";
        
        $content = str_replace($old_query, $new_query, $content);
        
        // Update Table Loop
        $old_loop = "\$items = \$stmt_items->fetchAll();\n                                \$items_str";
        $new_loop = "\$items = \$stmt_items->fetchAll();\n                                if (empty(\$items)) continue;\n                                \$items_str";
        
        $content = str_replace($old_loop, $new_loop, $content);
        
        file_put_contents($file, $content);
        echo "Updated $file successfully.\n";
    }
}

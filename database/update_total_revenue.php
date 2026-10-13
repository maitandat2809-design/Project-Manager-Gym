<?php
$files = ['manager.php', 'admin.php'];

$search = <<<HTML
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Yearly Report - <?php echo htmlspecialchars(\$selected_year); ?></h2>
                    <form method="GET" style="display: flex; align-items: center; gap: 10px;">
HTML;

$replace = <<<HTML
                <?php \$total_yearly_revenue = array_sum(\$monthly_revenue); ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2>Yearly Report - <?php echo htmlspecialchars(\$selected_year); ?></h2>
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-top: 5px;">
                            Total Revenue: <strong style="color: #10b981; font-size: 1.5rem;"><?php echo number_format(\$total_yearly_revenue, 0, ',', '.'); ?>đ</strong>
                        </p>
                    </div>
                    <form method="GET" style="display: flex; align-items: center; gap: 10px;">
HTML;

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if (strpos($content, $search) !== false) {
            $new_content = str_replace($search, $replace, $content);
            file_put_contents($file, $new_content);
            echo "Updated $file successfully.\n";
        } else {
            echo "Pattern not found in $file.\n";
        }
    }
}

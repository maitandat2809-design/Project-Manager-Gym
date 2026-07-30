<?php
$vi_keys = ['monthly_revenue_chart' => 'Biểu Đồ Doanh Thu Hàng Tháng'];
$en_keys = ['monthly_revenue_chart' => 'Monthly Revenue Chart'];

function appendLang($file, $keys) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = preg_replace('/\\?>\s*$/', '', $content);
        
        $new_vars = "";
        foreach ($keys as $k => $v) {
            $new_vars .= "    '$k' => '" . addslashes($v) . "',\n";
        }
        
        if (preg_match('/(.*)(\];)(.*)/s', $content, $matches)) {
            $content = rtrim($matches[1], "\n,") . ",\n" . $new_vars . $matches[2] . $matches[3];
            file_put_contents($file, $content . "\n?>\n");
            echo "Updated $file\n";
        }
    }
}

appendLang('languages/vi.php', $vi_keys);
appendLang('languages/en.php', $en_keys);

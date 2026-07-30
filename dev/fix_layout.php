<?php
$files = ['manager.php', 'admin.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace('<div class="table-responsive">', '<div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">', $content);
        file_put_contents($file, $content);
    }
}
echo "Done replacing in manager.php and admin.php.\n";

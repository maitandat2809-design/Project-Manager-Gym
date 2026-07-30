<?php
$files = ['manager.php', 'admin.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace HLV
        $content = str_replace('<i class="fas fa-user-tie"></i> HLV <?php echo htmlspecialchars($c_name); ?>', 
                               '<i class="fas fa-user-tie"></i> Coach <?php echo htmlspecialchars($c_name); ?>', $content);
        
        // Replace Tổng lịch đặt:
        $content = str_replace('Tổng lịch đặt: <?php echo count($c_bookings); ?>', 
                               'Total Bookings: <?php echo count($c_bookings); ?>', $content);
                               
        file_put_contents($file, $content);
        echo "Fixed languages in $file.\n";
    }
}

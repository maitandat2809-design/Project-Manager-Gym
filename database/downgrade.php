<?php
require 'config/db.php';
$pdo->exec("UPDATE users SET role = 'user' WHERE email = 'Datmtgcc210217@fpt.edu.vn'");
echo "Old manager downgraded\n";
?>

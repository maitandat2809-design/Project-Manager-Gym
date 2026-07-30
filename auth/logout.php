<?php
session_start();
session_destroy();
header("Location: /Gym Manager/auth/login.php");
exit();
?>

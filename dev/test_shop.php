<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
$_SESSION['lang'] = 'vi';
$_SESSION['user_name'] = 'Test';
include 'shop.php';
?>

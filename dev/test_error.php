<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Force login as manager
$_SESSION['user_id'] = 1; // Assuming 1 exists
$_SESSION['user_name'] = 'Test';
$_SESSION['role'] = 'manager';
include 'includes/header.php';
?>
Hello

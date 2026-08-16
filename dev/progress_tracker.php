<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Redirect to profile.php where the progress tracker is integrated
header("Location: /Gym Manager/dashboards/profile.php");
exit();
?>

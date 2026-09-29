<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: /Gym Manager/dashboards/coach_dashboard.php" . $query);
exit;

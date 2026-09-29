<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'coach') {
        header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule");
        exit;
    } elseif ($_SESSION['role'] === 'manager') {
        header("Location: /Gym Manager/dashboards/manager.php" . $query);
        exit;
    }
}

header("Location: /Gym Manager/dashboards/admin.php" . $query);
exit;

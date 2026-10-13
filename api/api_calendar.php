<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['user_id'];

$events = [];

try {
    if ($role === 'admin' || $role === 'manager') {
        // Manager sees all bookings
        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_date, b.status, u.name as user_name, c_u.name as coach_name 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users c_u ON c.user_id = c_u.id
        ");
        $stmt->execute();
    } elseif ($role === 'coach') {
        // Coach sees their own bookings
        $stmt_coach = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
        $stmt_coach->execute([$user_id]);
        $coach_id = $stmt_coach->fetchColumn();

        if (!$coach_id) {
            echo json_encode([]);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_date, b.status, u.name as user_name, c_u.name as coach_name 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users c_u ON c.user_id = c_u.id
            WHERE b.coach_id = ?
        ");
        $stmt->execute([$coach_id]);
    } else {
        // User sees their own bookings
        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_date, b.status, u.name as user_name, c_u.name as coach_name 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users c_u ON c.user_id = c_u.id
            WHERE b.user_id = ?
        ");
        $stmt->execute([$user_id]);
    }

    $bookings = $stmt->fetchAll();

    foreach ($bookings as $b) {
        $color = '#3b82f6'; // default primary
        if ($b['status'] === 'confirmed') $color = '#10b981'; // green
        if ($b['status'] === 'cancelled') $color = '#ef4444'; // red
        if ($b['status'] === 'pending') $color = '#f59e0b'; // orange

        $is_vi_cal = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi');
        $client_label = $is_vi_cal ? 'Khách' : 'Client';
        $title = $time . " | " . $client_label . ": " . $b['user_name'] . " | Coach: " . $b['coach_name'];

        $events[] = [
            'id' => $b['id'],
            'title' => $title,
            'start' => $b['booking_date'],
            'color' => $color,
            'extendedProps' => [
                'status' => $b['status'],
                'user_name' => $b['user_name'],
                'coach_name' => $b['coach_name'],
                'time' => $time
            ]
        ];
    }
    
    echo json_encode($events);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

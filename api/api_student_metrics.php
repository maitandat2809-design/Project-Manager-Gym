<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if (!$student_id) {
    echo json_encode(['error' => 'Invalid student ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, recorded_date, weight, muscle_mass, fat_mass, bmi, notes 
        FROM body_metrics 
        WHERE user_id = ? 
        ORDER BY recorded_date ASC
    ");
    $stmt->execute([$student_id]);
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'metrics' => $metrics]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

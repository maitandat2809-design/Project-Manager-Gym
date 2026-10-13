<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/lang.php';

$field = trim($_REQUEST['field'] ?? '');
$value = trim($_REQUEST['value'] ?? '');
$exclude_id = isset($_REQUEST['exclude_id']) ? intval($_REQUEST['exclude_id']) : 0;

if (empty($field) || empty($value)) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Missing parameters.'
    ]);
    exit();
}

$exists = false;
$message = '';
$valid = true;

if ($field === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL) || !preg_match('/^[^@\s]+@gmail\.com$/i', $value)) {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'valid' => false,
            'field' => 'email',
            'message' => $current_lang === 'vi' ? 'Email phải có định dạng @gmail.com.' : 'Email must use a @gmail.com address.'
        ]);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
    $stmt->execute([$value, $exclude_id]);
    if ($stmt->fetch()) {
        $exists = true;
        $message = __('email_exists_error');
    } else {
        $message = __('email_available');
    }
} elseif ($field === 'phone') {
    if (!preg_match('/^[0-9]{10}$/', $value)) {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'valid' => false,
            'field' => 'phone',
            'message' => $current_lang === 'vi' ? 'Số điện thoại phải gồm đúng 10 chữ số.' : 'Phone number must contain exactly 10 digits.'
        ]);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ? AND phone IS NOT NULL AND phone != ''");
    $stmt->execute([$value, $exclude_id]);
    if ($stmt->fetch()) {
        $exists = true;
        $message = __('phone_exists_error');
    } else {
        $message = __('phone_available');
    }
} else {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Invalid field.'
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'exists' => $exists,
    'valid' => $valid,
    'field' => $field,
    'message' => $message
]);

<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../includes/lang.php';

// Receive input
$data = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($data['message'] ?? ''));

if (empty($message)) {
    echo json_encode(['reply' => __('ai_chat_welcome')]);
    exit;
}

// Simulated AI Logic (Keyword based for now, supports bilingual triggers)
$reply = __('ai_chat_default');

if (preg_match('/(giá|chi phí|bao nhiêu|tiền|gói|price|cost|how much|fee|package|membership)/i', $message)) {
    $reply = __('ai_chat_price');
} elseif (preg_match('/(hlv|huấn luyện|coach|pt|dạy|trainer)/i', $message)) {
    $reply = __('ai_chat_coaches');
} elseif (preg_match('/(lịch|đặt|book|thời gian|giờ|schedule|time|hour)/i', $message)) {
    $reply = __('ai_chat_schedule');
} elseif (preg_match('/(chào|hello|hi|hey)/i', $message)) {
    $reply = __('ai_chat_greeting');
} elseif (preg_match('/(địa chỉ|ở đâu|vị trí|address|where|location)/i', $message)) {
    $reply = __('ai_chat_address');
}

// Simulate typing delay
usleep(1000000); // 1 second

echo json_encode([
    'reply' => $reply,
    'time' => date('H:i')
]);
?>

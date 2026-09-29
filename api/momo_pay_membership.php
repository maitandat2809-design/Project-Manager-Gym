<?php
// ============================================================
// Initiate MoMo QR Payment for Membership Subscriptions
// Creates pending subscription, stores info in session,
// redirects to QR payment page (momo_qr.php)
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập trước.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$plan_id = (int)($input['plan_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Gói tập không hợp lệ.']);
    exit;
}

// Fetch plan info
$stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id=? AND is_active=1");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Gói tập không tồn tại.']);
    exit;
}

// Check existing active subscription
$stmt2 = $pdo->prepare("SELECT id FROM member_subscriptions WHERE user_id=? AND status IN ('active','pending')");
$stmt2->execute([$user_id]);
if ($stmt2->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bạn đang có thẻ thành viên hoạt động. Vui lòng xóa thẻ cũ trước khi đăng ký mới.']);
    exit;
}

// Create pending subscription record
$start_date = date('Y-m-d');
$end_date   = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
$price_vnd  = (int)$plan['price']; // already in VND
$amount     = max(1000, $price_vnd);

try {
    $stmt3 = $pdo->prepare("INSERT INTO member_subscriptions (user_id, plan_id, start_date, end_date, price_paid, payment_method, status) VALUES (?,?,?,?,?,'momo','pending')");
    $stmt3->execute([$user_id, $plan_id, $start_date, $end_date, $price_vnd]);
    $sub_id = $pdo->lastInsertId();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi tạo đăng ký: ' . $e->getMessage()]);
    exit;
}

// --- Store pending payment info in session ---
$_SESSION['momo_pending'] = [
    'type'      => 'membership',
    'sub_id'    => $sub_id,
    'plan_name' => $plan['name_vi'] ?? $plan['name'],
    'amount'    => $amount,
    'created'   => time(),
];

// Return QR payment page URL
echo json_encode([
    'success' => true,
    'payUrl'  => '/Gym Manager/pages/momo_qr.php',
]);

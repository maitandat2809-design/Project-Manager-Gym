<?php
// ============================================================
// Initiate MoMo QR Payment for Membership Subscriptions
// Creates pending subscription, stores info in session,
// redirects to QR payment page (momo_qr.php)
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// The application default is English. Vietnamese is used only after an
// explicit language selection and is retained for the payment hand-off.
$is_vi = isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Vui lòng đăng nhập trước.' : 'Please log in first.']);
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
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Gói tập không hợp lệ.' : 'Invalid membership plan.']);
    exit;
}

// Fetch plan info
$stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id=? AND is_active=1");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Gói tập không tồn tại.' : 'Membership plan not found.']);
    exit;
}

require_once __DIR__ . '/../includes/membership_loyalty.php';

// Clean up any stale unpaid pending subscription for this user
$stmt_clean = $pdo->prepare("DELETE FROM member_subscriptions WHERE user_id = ? AND status = 'pending'");
$stmt_clean->execute([$user_id]);

// Calculate loyalty status & perks for this user
$loyalty_info = getUserMembershipLoyaltyInfo($pdo, $user_id);
$perks        = getPlanLoyaltyPerks($plan, $loyalty_info);

$start_date      = $perks['start_date'];
$end_date        = $perks['end_date'];
$price_vnd       = (int)$perks['final_price'];
$amount          = max(1000, $price_vnd);
$is_renewal      = $perks['is_renewal'] ? 1 : 0;
$discount_amount = (float)$perks['discount_amount'];
$bonus_days      = (int)$perks['bonus_days'];
$bonus_pt        = (int)$perks['bonus_pt'];
$total_pt        = (int)$perks['total_pt'];
$renewal_parent  = ($perks['is_same_plan_renewal'] && !empty($loyalty_info['active_sub']['id'])) ? (int)$loyalty_info['active_sub']['id'] : null;
$gifts_json      = !empty($perks['gifts']) ? json_encode($perks['gifts'], JSON_UNESCAPED_UNICODE) : null;

try {
    $stmt3 = $pdo->prepare("
        INSERT INTO member_subscriptions 
        (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, 
         is_renewal, discount_amount, bonus_days, bonus_pt, loyalty_gifts, renewal_parent_id, pt_sessions_total, pt_sessions_used) 
        VALUES (?,?,?,?,?,'momo','pending',?,?,?,?,?,?,?,0)
    ");
    $stmt3->execute([
        $user_id, $plan_id, $start_date, $end_date, $price_vnd,
        $is_renewal, $discount_amount, $bonus_days, $bonus_pt, $gifts_json, $renewal_parent, $total_pt
    ]);
    $sub_id = $pdo->lastInsertId();
} catch (Exception $e) {
    $prefix = $is_vi ? 'Lỗi tạo đăng ký: ' : 'Unable to create membership subscription: ';
    echo json_encode(['success' => false, 'message' => $prefix . $e->getMessage()]);
    exit;
}

// --- Store pending payment info in session ---
$_SESSION['momo_pending'] = [
    'type'            => 'membership',
    'sub_id'          => $sub_id,
    'plan_name'       => ($is_vi && !empty($plan['name_vi'])) ? $plan['name_vi'] : $plan['name'],
    'amount'          => $amount,
    'is_renewal'      => $is_renewal,
    'discount_amount' => $discount_amount,
    'bonus_days'      => $bonus_days,
    'bonus_pt'        => $bonus_pt,
    'gifts'           => $perks['gifts'],
    'created'         => time(),
];

// Return QR payment page URL
echo json_encode([
    'success' => true,
    'payUrl'  => '/Gym Manager/pages/momo_qr.php',
]);

<?php
/**
 * API: Check Payment Status
 * Frontend polls this endpoint sau khi user bấm "Xác nhận đã chuyển khoản"
 * để biết khi nào Casso webhook kích hoạt subscription
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthenticated']);
    exit();
}

$subscription_id = intval($_GET['sub_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($subscription_id <= 0) {
    echo json_encode(['error' => 'invalid']);
    exit();
}

$stmt = $pdo->prepare("
    SELECT ms.id, ms.status, ms.start_date, ms.end_date, ms.price_paid, ms.payment_method, ms.casso_tid,
           mp.name, mp.name_vi, mp.price
    FROM member_subscriptions ms
    JOIN membership_plans mp ON ms.plan_id = mp.id
    WHERE ms.id = ? AND ms.user_id = ?
");
$stmt->execute([$subscription_id, $user_id]);
$sub = $stmt->fetch();

if (!$sub) {
    echo json_encode(['error' => 'not_found']);
    exit();
}

$current_lang = $_SESSION['lang'] ?? 'en';
$plan_name = ($current_lang === 'vi' && !empty($sub['name_vi'])) ? $sub['name_vi'] : $sub['name'];

echo json_encode([
    'status'        => $sub['status'],
    'activated'     => $sub['status'] === 'active',
    'plan_name'     => $plan_name,
    'price'         => number_format($sub['price_paid'], 0, ',', '.') . 'đ',
    'start_date'    => date('d/m/Y', strtotime($sub['start_date'])),
    'end_date'      => date('d/m/Y', strtotime($sub['end_date'])),
    'payment_method'=> $sub['payment_method'] === 'bank_transfer'
                        ? ($current_lang === 'vi' ? 'Chuyển khoản ngân hàng' : 'Bank Transfer')
                        : ($current_lang === 'vi' ? 'Tiền mặt tại quầy' : 'Cash at Counter'),
    'casso_tid'     => $sub['casso_tid'] ?? null,
]);

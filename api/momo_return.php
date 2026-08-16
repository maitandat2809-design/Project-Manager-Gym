<?php
// ============================================================
// MoMo Return Handler — user lands here after paying in MoMo app
// Redirects to the appropriate success/failure page
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/momo_helper.php';

$data       = $_GET;
$resultCode = (int)($data['resultCode'] ?? -1);
$orderId    = $data['orderId'] ?? '';
$extra      = json_decode(base64_decode($data['extraData'] ?? ''), true);
$type       = $extra['type']    ?? '';

if ($resultCode === 0) {
    // Payment success — IPN should have already updated DB
    // Redirect to appropriate success page
    if ($type === 'shop_order') {
        $_SESSION['momo_success'] = [
            'order_id' => $extra['id'] ?? 0,
            'amount'   => $data['amount'] ?? 0,
        ];
        header("Location: /Gym%20Manager/pages/momo_success.php?type=shop");
    } else {
        $_SESSION['momo_success'] = [
            'sub_id'    => $extra['id'] ?? 0,
            'plan_name' => $extra['plan_name'] ?? '',
            'amount'    => $data['amount'] ?? 0,
        ];
        header("Location: /Gym%20Manager/pages/momo_success.php?type=membership");
    }
} else {
    // Payment cancelled/failed
    $_SESSION['momo_error'] = "Thanh toán MoMo thất bại hoặc bị hủy. Vui lòng thử lại.";
    if ($type === 'membership') {
        header("Location: /Gym%20Manager/pages/membership.php");
    } else {
        header("Location: /Gym%20Manager/pages/checkout.php");
    }
}
exit;

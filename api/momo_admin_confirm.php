<?php
// ============================================================
// MoMo Admin Confirm Payment API
// Called by admin/manager dashboard to confirm MoMo payment received
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$type     = $input['type']     ?? 'shop';
$order_id = (int)($input['order_id'] ?? 0);
$sub_id   = (int)($input['sub_id']   ?? 0);

try {
    if ($type === 'shop' && $order_id) {
        $stmt = $pdo->prepare("UPDATE orders SET momo_confirmed=1, payment_method='momo', admin_seen=0 WHERE id = ?");
        $stmt->execute([$order_id]);

        require_once __DIR__ . '/../includes/order_mailer.php';
        sendMoMoOrderSuccessEmail($pdo, $order_id);

        echo json_encode(['success' => true, 'message' => 'Order confirmed']);

    } elseif ($type === 'membership' && $sub_id) {
        $stmt = $pdo->prepare("UPDATE member_subscriptions SET status='active', payment_method='momo', momo_confirmed=1, admin_seen=0 WHERE id = ?");
        $stmt->execute([$sub_id]);
        echo json_encode(['success' => true, 'message' => 'Membership confirmed']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

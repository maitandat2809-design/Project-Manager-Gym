<?php
// ============================================================
// MoMo QR Confirm / Cancel API
// Called by momo_qr.php to confirm or cancel a QR payment
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'confirm';   // 'confirm' | 'cancel'
$type   = $input['type']   ?? 'shop';      // 'shop' | 'membership'

$pending = $_SESSION['momo_pending'] ?? null;
if (!$pending) {
    echo json_encode(['success' => false, 'message' => 'Payment information was not found.']);
    exit;
}

$order_id = (int)($pending['order_id'] ?? 0);
$sub_id   = (int)($pending['sub_id']   ?? 0);
$amount   = (int)($pending['amount']   ?? 0);
$plan_name = $pending['plan_name'] ?? '';

// ── CANCEL ───────────────────────────────────────────────────
if ($action === 'cancel') {
    try {
        if ($type === 'membership' && $sub_id) {
            $pdo->prepare("DELETE FROM member_subscriptions WHERE id = ? AND user_id = ?")->execute([$sub_id, $_SESSION['user_id']]);
        } elseif ($type === 'shop' && $order_id) {
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
            $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?")->execute([$order_id, $_SESSION['user_id']]);
        }
    } catch (Exception $e) {
        error_log("momo_confirm_qr cancel error: " . $e->getMessage());
    }

    unset($_SESSION['momo_pending']);
    echo json_encode(['success' => true]);
    exit;
}

// ── CONFIRM ──────────────────────────────────────────────────
try {
    if ($type === 'membership' && $sub_id) {
        // Check subscription exists for this user
        $chk = $pdo->prepare("SELECT id FROM member_subscriptions WHERE id = ? AND user_id = ?");
        $chk->execute([$sub_id, $_SESSION['user_id']]);
        if (!$chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Membership registration was not found.']);
            exit;
        }
        // Activate membership
        $stmt = $pdo->prepare("UPDATE member_subscriptions SET status='active', payment_method='momo' WHERE id = ? AND user_id = ?");
        $stmt->execute([$sub_id, $_SESSION['user_id']]);

        // Store success info for success page
        $_SESSION['momo_success'] = [
            'sub_id'    => $sub_id,
            'plan_name' => $plan_name,
            'amount'    => $amount,
            'qr_mode'   => true,
        ];

    } elseif ($type === 'shop' && $order_id) {
        // Check order exists for this user
        $chk = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $chk->execute([$order_id, $_SESSION['user_id']]);
        if (!$chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order was not found.']);
            exit;
        }
        // Confirm order payment - keep status='pending' for delivery tracking like COD, mark momo_confirmed=1
        $stmt = $pdo->prepare("UPDATE orders SET status='pending', payment_method='momo', momo_confirmed=1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);

        require_once __DIR__ . '/../includes/order_mailer.php';
        sendMoMoOrderSuccessEmail($pdo, $order_id);

        // Update stock
        if (!empty($pending['items'])) {
            $stmt_sales = $pdo->prepare("UPDATE products SET sales_count=sales_count+?, stock=GREATEST(0,stock-?) WHERE id=?");
            foreach ($pending['items'] as $item) {
                $stmt_sales->execute([$item['qty'], $item['qty'], $item['id']]);
            }
        }

        // Store success info for success page
        $_SESSION['momo_success'] = [
            'order_id' => $order_id,
            'amount'   => $amount,
            'qr_mode'  => true,
        ];

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid payment type.']);
        exit;
    }

    unset($_SESSION['momo_pending']);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("momo_confirm_qr error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}

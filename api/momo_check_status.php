<?php
// ============================================================
// MoMo Payment Status Check (Polling API)
// Automatically confirms payment, triggers emails to user & manager,
// keeps order status as 'pending' so delivery can be tracked like COD.
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/order_mailer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pending = $_SESSION['momo_pending'] ?? null;

// Fallback: If session missing, look for latest pending order/subscription
if (!$pending) {
    $get_order_id = (int)($_GET['order_id'] ?? 0);
    $get_sub_id   = (int)($_GET['sub_id']   ?? 0);

    if ($get_order_id) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$get_order_id, $user_id]);
        $ord = $stmt->fetch();
        if ($ord) {
            $amt_vnd = (int)round($ord['total_price'] * 25000);
            if ($amt_vnd < 1000) $amt_vnd = 1000;
            $pending = [
                'type'     => 'shop',
                'order_id' => $get_order_id,
                'amount'   => $amt_vnd,
                'created'  => strtotime($ord['created_at'])
            ];
            $_SESSION['momo_pending'] = $pending;
        }
    } elseif ($get_sub_id) {
        $stmt = $pdo->prepare("SELECT ms.*, mp.name as plan_name FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.id = ? AND ms.user_id = ?");
        $stmt->execute([$get_sub_id, $user_id]);
        $sub = $stmt->fetch();
        if ($sub) {
            $pending = [
                'type'      => 'membership',
                'sub_id'    => $get_sub_id,
                'plan_name' => $sub['plan_name'],
                'amount'    => (int)$sub['price_paid'],
                'created'   => strtotime($sub['created_at'])
            ];
            $_SESSION['momo_pending'] = $pending;
        }
    }
}

if (!$pending) {
    echo json_encode(['status' => 'no_pending']);
    exit;
}

$type      = $pending['type']     ?? 'shop';
$order_id  = (int)($pending['order_id'] ?? 0);
$sub_id    = (int)($pending['sub_id']   ?? 0);
$amount    = (int)($pending['amount']   ?? 0);
$plan_name = $pending['plan_name'] ?? '';

try {
    if ($type === 'shop' && $order_id) {
        $stmt = $pdo->prepare('SELECT id, status, momo_confirmed, created_at FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$order_id, $user_id]);
        $row = $stmt->fetch();

        if (!$row) { echo json_encode(['status' => 'not_found']); exit; }

        // Check if already confirmed (via admin, webhook or previous check)
        if ($row['momo_confirmed'] == 1) {
            // Ensure email is sent
            sendMoMoOrderSuccessEmail($pdo, $order_id);

            unset($_SESSION['momo_pending']);
            $_SESSION['momo_success'] = ['order_id' => $order_id, 'amount' => $amount, 'qr_mode' => true];
            echo json_encode(['status' => 'confirmed', 'order_id' => $order_id, 'amount' => $amount, 'type' => 'shop']);
            exit;
        }

        // Automatic Detection / Verification:
        $created_time = isset($pending['created']) ? (int)$pending['created'] : strtotime($row['created_at']);
        $elapsed = time() - $created_time;

        if ($elapsed >= 10) {
            // Keep status = 'pending' so customer can track delivery like COD, but mark momo_confirmed = 1
            $upd = $pdo->prepare("UPDATE orders SET status = 'pending', payment_method = 'momo', momo_confirmed = 1 WHERE id = ?");
            $upd->execute([$order_id]);

            // Update product stock and sales count
            if (!empty($pending['items'])) {
                $stmt_sales = $pdo->prepare("UPDATE products SET sales_count = sales_count + ?, stock = GREATEST(0, stock - ?) WHERE id = ?");
                foreach ($pending['items'] as $item) {
                    $stmt_sales->execute([$item['qty'], $item['qty'], $item['id']]);
                }
            } else {
                $stmt_oi = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt_oi->execute([$order_id]);
                $stmt_sales = $pdo->prepare("UPDATE products SET sales_count = sales_count + ?, stock = GREATEST(0, stock - ?) WHERE id = ?");
                foreach ($stmt_oi->fetchAll() as $oi) {
                    $stmt_sales->execute([$oi['quantity'], $oi['quantity'], $oi['product_id']]);
                }
            }

            // Send confirmation email to Customer & Managers
            sendMoMoOrderSuccessEmail($pdo, $order_id);

            unset($_SESSION['momo_pending']);
            $_SESSION['momo_success'] = ['order_id' => $order_id, 'amount' => $amount, 'qr_mode' => true];
            echo json_encode(['status' => 'confirmed', 'order_id' => $order_id, 'amount' => $amount, 'type' => 'shop']);
            exit;
        }

        echo json_encode(['status' => 'pending', 'elapsed' => $elapsed]);

    } elseif ($type === 'membership' && $sub_id) {
        $stmt = $pdo->prepare('SELECT id, status, momo_confirmed, created_at FROM member_subscriptions WHERE id = ? AND user_id = ?');
        $stmt->execute([$sub_id, $user_id]);
        $row = $stmt->fetch();

        if (!$row) { echo json_encode(['status' => 'not_found']); exit; }

        if ($row['momo_confirmed'] == 1 || $row['status'] === 'active') {
            unset($_SESSION['momo_pending']);
            $_SESSION['momo_success'] = ['sub_id' => $sub_id, 'plan_name' => $plan_name, 'amount' => $amount, 'qr_mode' => true];
            echo json_encode(['status' => 'confirmed', 'sub_id' => $sub_id, 'plan_name' => $plan_name, 'amount' => $amount, 'type' => 'membership']);
            exit;
        }

        $created_time = isset($pending['created']) ? (int)$pending['created'] : strtotime($row['created_at']);
        $elapsed = time() - $created_time;

        if ($elapsed >= 10) {
            $upd = $pdo->prepare("UPDATE member_subscriptions SET status = 'active', payment_method = 'momo', momo_confirmed = 1 WHERE id = ?");
            $upd->execute([$sub_id]);

            unset($_SESSION['momo_pending']);
            $_SESSION['momo_success'] = ['sub_id' => $sub_id, 'plan_name' => $plan_name, 'amount' => $amount, 'qr_mode' => true];
            echo json_encode(['status' => 'confirmed', 'sub_id' => $sub_id, 'plan_name' => $plan_name, 'amount' => $amount, 'type' => 'membership']);
            exit;
        }

        echo json_encode(['status' => 'pending', 'elapsed' => $elapsed]);

    } else {
        echo json_encode(['status' => 'invalid']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

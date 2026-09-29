<?php
// ============================================================
// MoMo / SePay / VietQR Automated Webhook Receiver
// Compatible with SePay.vn / Casso.vn / Bank SMS Webhooks
// ============================================================
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true) ?? [];

// Log webhook for debugging
file_put_contents(__DIR__ . '/../momo_webhook.log', date('Y-m-d H:i:s') . " - " . $raw_input . PHP_EOL, FILE_APPEND);

$content = $data['content'] ?? $data['description'] ?? $data['orderInfo'] ?? '';
$amount  = (int)($data['amount'] ?? $data['transferAmount'] ?? 0);

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'No content found']);
    exit;
}

try {
    // Check if content matches Shop Order (e.g., GymX Don 44 or Order #44)
    if (preg_match('/(?:GymX\s*Don|Order|Don\s*hang)\s*#?(\d+)/i', $content, $matches)) {
        $order_id = (int)$matches[1];

        $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            $upd = $pdo->prepare("UPDATE orders SET status = 'pending', payment_method = 'momo', momo_confirmed = 1 WHERE id = ?");
            $upd->execute([$order_id]);

            // Update inventory
            $stmt_oi = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt_oi->execute([$order_id]);
            $stmt_sales = $pdo->prepare("UPDATE products SET sales_count = sales_count + ?, stock = GREATEST(0, stock - ?) WHERE id = ?");
            foreach ($stmt_oi->fetchAll() as $oi) {
                $stmt_sales->execute([$oi['quantity'], $oi['quantity'], $oi['product_id']]);
            }

            require_once __DIR__ . '/../includes/order_mailer.php';
            sendMoMoOrderSuccessEmail($pdo, $order_id);

            echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Order payment confirmed via Webhook']);
            exit;
        }
    }

    // Check if content matches Membership (e.g., GymX Goi VIP or Sub #12)
    if (preg_match('/(?:GymX\s*Goi|Sub|Subscription)\s*#?(\d+)/i', $content, $matches)) {
        $sub_id = (int)$matches[1];
        $upd = $pdo->prepare("UPDATE member_subscriptions SET status = 'active', payment_method = 'momo', momo_confirmed = 1 WHERE id = ?");
        $upd->execute([$sub_id]);
        echo json_encode(['success' => true, 'sub_id' => $sub_id, 'message' => 'Subscription marked active via Webhook']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'No matching pattern in content: ' . $content]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

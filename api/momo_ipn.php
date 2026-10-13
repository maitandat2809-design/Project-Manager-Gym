<?php
// ============================================================
// MoMo IPN (Instant Payment Notification) Handler
// MoMo calls this endpoint server-to-server after payment.
// This file MUST be publicly accessible from the internet.
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/momo_helper.php';

// Read JSON body from MoMo
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid request body']);
    exit;
}

// ① Verify signature
if (!verifyMoMoSignature($data)) {
    error_log("MoMo IPN: signature mismatch for orderId={$data['orderId']}");
    http_response_code(403);
    echo json_encode(['message' => 'Invalid signature']);
    exit;
}

// ② Only process successful payments (resultCode 0)
if ((int)$data['resultCode'] !== 0) {
    error_log("MoMo IPN: payment failed, resultCode={$data['resultCode']}, orderId={$data['orderId']}");
    echo json_encode(['message' => 'Payment not successful']);
    exit;
}

// ③ Decode extraData to know the context (shop_order or membership)
$extra    = json_decode(base64_decode($data['extraData'] ?? ''), true);
$type     = $extra['type']    ?? '';
$refId    = (int)($extra['id'] ?? 0);
$userId   = (int)($extra['user_id'] ?? 0);
$transId  = $data['transId'] ?? '';

try {
    if ($type === 'shop_order' && $refId > 0) {
        // ── Mark shop order as completed ──────────────────────────────
        $stmt = $pdo->prepare("UPDATE orders SET status='completed', payment_method='momo', admin_seen=0 WHERE id=? AND status='pending'");
        $stmt->execute([$refId]);

        // Send confirmation emails
        sendOrderConfirmationEmail($pdo, $refId);

    } elseif ($type === 'membership' && $refId > 0) {
        // ── Activate membership subscription ──────────────────────────
        $stmt = $pdo->prepare("UPDATE member_subscriptions SET status='active', payment_confirmed_at=NOW(), casso_tid=? WHERE id=? AND status='pending'");
        $stmt->execute([$transId, $refId]);

        // Send membership activation email
        sendMembershipActivationEmail($pdo, $refId);
    }

    // Respond 200 OK so MoMo doesn't retry
    http_response_code(200);
    echo json_encode(['message' => 'Success']);

} catch (Exception $e) {
    error_log("MoMo IPN DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Server error']);
}


// ─────────────────── Email Helpers ───────────────────────────

function sendOrderConfirmationEmail(PDO $pdo, int $orderId): void
{
    try {
        // Fetch order + user
        $stmt = $pdo->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order || empty($order['user_email'])) return;

        // Fetch items
        $stmt2 = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt2->execute([$orderId]);
        $items = $stmt2->fetchAll();

        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lamtuoi155@gmail.com';
        $mail->Password   = 'hpst uazz wdsq sbnf';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager Shop');
        $mail->addAddress($order['user_email'], $order['user_name']);

        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= "<li style='padding:6px 0;border-bottom:1px solid #f1f5f9;'>"
                       . htmlspecialchars($item['name']) . " × " . $item['quantity']
                       . " — <strong>" . number_format($item['price'] * $item['quantity'], 0, ',', '.') . " VND</strong></li>";
        }

        // Webhooks run without a browser session; notification emails are English-only.
        $is_vi_ipn = false;

        $mail->isHTML(true);
        $mail->Subject = $is_vi_ipn ? "✅ [Gym Manager] Đơn hàng #$orderId đã thanh toán qua MoMo thành công!" : "✅ [Gym Manager] Order #$orderId paid successfully via MoMo!";
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;'>
            <h2 style='color:#a21caf;margin-top:0;'>💜 " . ($is_vi_ipn ? "Thanh toán MoMo thành công!" : "MoMo Payment Successful!") . "</h2>
            <p>" . ($is_vi_ipn ? "Xin chào <strong>{$order['user_name']}</strong>, đơn hàng của bạn đã được xác nhận thanh toán qua Ví MoMo." : "Hello <strong>{$order['user_name']}</strong>, your order payment has been confirmed via MoMo Wallet.") . "</p>
            <div style='background:#f8fafc;padding:16px;border-radius:10px;margin:16px 0;'>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Mã đơn hàng:" : "Order ID:") . "</strong> #$orderId</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Tổng tiền:" : "Total:") . "</strong> " . number_format($order['total_price'], 0, ',', '.') . " VND</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Phương thức:" : "Method:") . "</strong> MoMo Wallet 💜</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Trạng thái:" : "Status:") . "</strong> <span style='color:#16a34a;font-weight:700;'>" . ($is_vi_ipn ? "Đã thanh toán ✓" : "Paid ✓") . "</span></p>
            </div>
            <h4>" . ($is_vi_ipn ? "Sản phẩm đã mua:" : "Purchased items:") . "</h4>
            <ul style='padding-left:0;list-style:none;'>$itemRows</ul>
            <p style='color:#64748b;font-size:13px;'>" . ($is_vi_ipn ? "Cảm ơn bạn đã tin tưởng Gym Manager! Đơn hàng sẽ được giao sớm nhất có thể." : "Thank you for choosing Gym Manager! Your order will be dispatched as soon as possible.") . "</p>
        </div>";
        $mail->send();

        // Also notify managers
        $managers = $pdo->query("SELECT email, name FROM users WHERE role='manager'")->fetchAll();
        if ($managers) {
            $mail2 = clone $mail;
            $mail2->clearAddresses();
            $mail2->Subject = $is_vi_ipn ? "🚨 [Gym Manager] Đơn MoMo #$orderId đã thanh toán — Cần xử lý!" : "🚨 [Gym Manager] MoMo Order #$orderId Paid — Action Required!";
            foreach ($managers as $mgr) {
                if (!empty($mgr['email'])) $mail2->addAddress($mgr['email'], $mgr['name']);
            }
            $mail2->send();
        }

    } catch (Exception $e) {
        error_log("IPN order email error: " . $e->getMessage());
    }
}

function sendMembershipActivationEmail(PDO $pdo, int $subId): void
{
    try {
        $stmt = $pdo->prepare("SELECT ms.*, mp.name AS plan_name, mp.name_vi AS plan_name_vi, u.name AS user_name, u.email AS user_email FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id JOIN users u ON ms.user_id = u.id WHERE ms.id = ?");
        $stmt->execute([$subId]);
        $sub = $stmt->fetch();
        if (!$sub || empty($sub['user_email'])) return;

        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lamtuoi155@gmail.com';
        $mail->Password   = 'hpst uazz wdsq sbnf';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager Membership');
        $mail->addAddress($sub['user_email'], $sub['user_name']);
        
        // Webhooks run without a browser session; notification emails are English-only.
        $is_vi_ipn = false;
        $p_name = ($is_vi_ipn && !empty($sub['plan_name_vi'])) ? $sub['plan_name_vi'] : $sub['plan_name'];

        $mail->isHTML(true);
        $mail->Subject = $is_vi_ipn ? "🎉 [Gym Manager] Thẻ Thành Viên {$p_name} đã được kích hoạt!" : "🎉 [Gym Manager] Membership {$p_name} Activated!";
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;'>
            <h2 style='color:#a21caf;margin-top:0;'>💜 " . ($is_vi_ipn ? "Thẻ Thành Viên Đã Kích Hoạt!" : "Membership Activated!") . "</h2>
            <p>" . ($is_vi_ipn ? "Xin chào" : "Hello") . " <strong>{$sub['user_name']}</strong>!</p>
            <p>" . ($is_vi_ipn ? "Gói thành viên <strong>{$p_name}</strong> của bạn đã được thanh toán thành công qua Ví MoMo và kích hoạt ngay lập tức." : "Your membership package <strong>{$p_name}</strong> has been successfully paid via MoMo Wallet and activated immediately.") . "</p>
            <div style='background:#f8fafc;padding:16px;border-radius:10px;margin:16px 0;'>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Gói tập:" : "Plan:") . "</strong> {$p_name}</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Hiệu lực từ:" : "Valid from:") . "</strong> " . date('d/m/Y', strtotime($sub['start_date'])) . "</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Hết hạn:" : "Expires:") . "</strong> " . date('d/m/Y', strtotime($sub['end_date'])) . "</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Số tiền:" : "Amount:") . "</strong> " . number_format($sub['price_paid'], 0, ',', '.') . " VND</p>
                <p style='margin:4px 0;'><strong>" . ($is_vi_ipn ? "Thanh toán qua:" : "Method:") . "</strong> MoMo Wallet 💜</p>
            </div>
            <p style='color:#64748b;font-size:13px;'>" . ($is_vi_ipn ? "Vui lòng đến quầy giao dịch của phòng tập để nhận thẻ thành viên vật lý. Cảm ơn bạn!" : "Please visit our reception counter to receive your physical membership card. Thank you!") . "</p>
        </div>";
        $mail->send();

    } catch (Exception $e) {
        error_log("IPN membership email error: " . $e->getMessage());
    }
}

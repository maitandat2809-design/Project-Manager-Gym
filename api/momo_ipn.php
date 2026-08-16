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
                       . " — <strong>" . number_format($item['price'] * $item['quantity'], 0, ',', '.') . "đ</strong></li>";
        }

        $mail->isHTML(true);
        $mail->Subject = "✅ [Gym Manager] Đơn hàng #$orderId đã thanh toán qua MoMo thành công!";
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;'>
            <h2 style='color:#a21caf;margin-top:0;'>💜 Thanh toán MoMo thành công!</h2>
            <p>Xin chào <strong>{$order['user_name']}</strong>, đơn hàng của bạn đã được xác nhận thanh toán qua Ví MoMo.</p>
            <div style='background:#f8fafc;padding:16px;border-radius:10px;margin:16px 0;'>
                <p style='margin:4px 0;'><strong>Mã đơn hàng:</strong> #$orderId</p>
                <p style='margin:4px 0;'><strong>Tổng tiền:</strong> " . number_format($order['total_price'], 0, ',', '.') . "đ</p>
                <p style='margin:4px 0;'><strong>Phương thức:</strong> Ví MoMo 💜</p>
                <p style='margin:4px 0;'><strong>Trạng thái:</strong> <span style='color:#16a34a;font-weight:700;'>Đã thanh toán ✓</span></p>
            </div>
            <h4>Sản phẩm đã mua:</h4>
            <ul style='padding-left:0;list-style:none;'>$itemRows</ul>
            <p style='color:#64748b;font-size:13px;'>Cảm ơn bạn đã tin tưởng Gym Manager! Đơn hàng sẽ được giao sớm nhất có thể.</p>
        </div>";
        $mail->send();

        // Also notify managers
        $managers = $pdo->query("SELECT email, name FROM users WHERE role='manager'")->fetchAll();
        if ($managers) {
            $mail2 = clone $mail;
            $mail2->clearAddresses();
            $mail2->Subject = "🚨 [Gym Manager] Đơn MoMo #$orderId đã thanh toán — Cần xử lý!";
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
        $stmt = $pdo->prepare("SELECT ms.*, mp.name AS plan_name, u.name AS user_name, u.email AS user_email FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id JOIN users u ON ms.user_id = u.id WHERE ms.id = ?");
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
        $mail->isHTML(true);
        $mail->Subject = "🎉 [Gym Manager] Thẻ Thành Viên {$sub['plan_name']} đã được kích hoạt!";
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;'>
            <h2 style='color:#a21caf;margin-top:0;'>💜 Thẻ Thành Viên Đã Kích Hoạt!</h2>
            <p>Xin chào <strong>{$sub['user_name']}</strong>!</p>
            <p>Gói thành viên <strong>{$sub['plan_name']}</strong> của bạn đã được thanh toán thành công qua Ví MoMo và kích hoạt ngay lập tức.</p>
            <div style='background:#f8fafc;padding:16px;border-radius:10px;margin:16px 0;'>
                <p style='margin:4px 0;'><strong>Gói tập:</strong> {$sub['plan_name']}</p>
                <p style='margin:4px 0;'><strong>Hiệu lực từ:</strong> " . date('d/m/Y', strtotime($sub['start_date'])) . "</p>
                <p style='margin:4px 0;'><strong>Hết hạn:</strong> " . date('d/m/Y', strtotime($sub['end_date'])) . "</p>
                <p style='margin:4px 0;'><strong>Số tiền:</strong> " . number_format($sub['price_paid'], 0, ',', '.') . "đ</p>
                <p style='margin:4px 0;'><strong>Thanh toán qua:</strong> Ví MoMo 💜</p>
            </div>
            <p style='color:#64748b;font-size:13px;'>Vui lòng đến quầy giao dịch của phòng tập để nhận thẻ thành viên vật lý. Cảm ơn bạn!</p>
        </div>";
        $mail->send();

    } catch (Exception $e) {
        error_log("IPN membership email error: " . $e->getMessage());
    }
}

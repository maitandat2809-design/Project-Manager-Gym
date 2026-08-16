<?php
/**
 * Casso Webhook – Tự động nạp tiền vào ví hoặc kích hoạt membership
 * khi ngân hàng MB Bank nhận được tiền chuyển khoản.
 *
 * Đăng ký URL này tại: https://my.casso.vn > Settings > Tích hợp > Webhook
 * URL: https://yourdomain.com/Gym Manager/api/casso_webhook.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payment.php';

header('Content-Type: application/json');

// 1. Xác thực Secure-Token từ Casso
$headers = array_change_key_case(getallheaders());
$secure_token = $headers['secure-token'] ?? '';

if (defined('CASSO_WEBHOOK_TOKEN') && CASSO_WEBHOOK_TOKEN !== 'YOUR_CASSO_WEBHOOK_SECRET_TOKEN_HERE') {
    if ($secure_token !== CASSO_WEBHOOK_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 1, 'message' => 'Unauthorized']);
        exit();
    }
}

// 2. Đọc payload
$payload = file_get_contents('php://input');
$data    = json_decode($payload, true);

if (!$data || !isset($data['data'])) {
    // Respond OK to avoid Casso retries for empty pings
    echo json_encode(['error' => 0, 'message' => 'no data']);
    exit();
}

$processed = 0;

foreach ($data['data'] as $txn) {
    $tid    = $txn['tid'] ?? '';
    $amount = (int)($txn['amount'] ?? 0);
    $desc   = strtoupper($txn['description'] ?? '');
    $when   = $txn['when'] ?? date('Y-m-d H:i:s');

    // Chỉ xử lý tiền VÀO (credit)
    if ($amount <= 0) continue;

    $handled = false;

    // ── Case B: Thanh toán membership – nội dung MEM{sub_id} ───────────────
    if (preg_match('/\bMEM(\d+)\b/', $desc, $m)) {
        $sub_id = (int)$m[1];

        $sub_s = $pdo->prepare("SELECT ms.*, mp.price as plan_price, mp.name as plan_name, mp.name_vi,
                                       u.email as user_email, u.name as user_name
                                FROM member_subscriptions ms
                                JOIN membership_plans mp ON ms.plan_id=mp.id
                                JOIN users u ON ms.user_id=u.id
                                WHERE ms.id=? AND ms.status='pending'");
        $sub_s->execute([$sub_id]);
        $sub = $sub_s->fetch();

        if ($sub) {
            $expected  = (int)$sub['plan_price'];
            $tolerance = max(1000, (int)($expected * 0.02));

            if (abs($amount - $expected) <= $tolerance) {
                $pdo->prepare("UPDATE member_subscriptions SET status='active', admin_seen=1, casso_tid=?, payment_confirmed_at=NOW() WHERE id=?")
                    ->execute([$tid, $sub_id]);
                $handled = true;
                $processed++;
                error_log("Casso: Membership MEM$sub_id activated TID=$tid");
                sendMembershipEmail($sub, $amount);
            }
        }
    }
}

echo json_encode(['error' => 0, 'message' => 'success', 'processed' => $processed]);

// ─── Gửi email kích hoạt thẻ thành viên ─────────────────────────────────────
function sendMembershipEmail(array $sub, int $amount): void {
    try {
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet   = 'UTF-8';
        $mail->isSMTP();
        $mail->Host      = 'smtp.gmail.com';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'lamtuoi155@gmail.com';
        $mail->Password  = 'hpst uazz wdsq sbnf';
        $mail->SMTPSecure= PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;

        $mail->setFrom('lamtuoi155@gmail.com', 'GymX Fitness Center');
        $mail->addAddress($sub['user_email'], $sub['user_name']);

        $plan_name  = $sub['name_vi'] ?: $sub['plan_name'];
        $price_fmt  = number_format($amount, 0, ',', '.');
        $start_date = date('d/m/Y', strtotime($sub['start_date']));
        $end_date   = date('d/m/Y', strtotime($sub['end_date']));

        $mail->isHTML(true);
        $mail->Subject = '✅ GymX – Thẻ Thành Viên Đã Được Kích Hoạt!';
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
            <div style='background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:2rem;text-align:center;border-radius:20px 20px 0 0;'>
                <h1 style='color:#f59e0b;'>✅ Thẻ Đã Kích Hoạt!</h1>
            </div>
            <div style='background:#f8fafc;padding:2rem;'>
                <p>Xin chào <strong>{$sub['user_name']}</strong>, thẻ của bạn đã được kích hoạt sau khi xác nhận thanh toán.</p>
                <div style='background:#fff;border-radius:16px;padding:1.5rem;border:1px solid #e2e8f0;'>
                    <table style='width:100%;border-collapse:collapse;'>
                        <tr><td style='padding:8px 0;color:#64748b;'>Gói Thẻ:</td><td style='font-weight:700;'>{$plan_name}</td></tr>
                        <tr style='border-top:1px dashed #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Số Tiền:</td><td style='font-weight:800;color:#10b981;'>{$price_fmt}đ</td></tr>
                        <tr style='border-top:1px dashed #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Hiệu Lực:</td><td>{$start_date} → {$end_date}</td></tr>
                        <tr style='border-top:1px dashed #e2e8f0;'><td style='padding:8px 0;color:#64748b;'>Trạng Thái:</td><td><span style='background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-weight:700;'>✓ ĐANG HOẠT ĐỘNG</span></td></tr>
                    </table>
                </div>
            </div>
        </div>";
        $mail->send();
    } catch (\Exception $e) {
        error_log("Membership email error: " . $e->getMessage());
    }
}

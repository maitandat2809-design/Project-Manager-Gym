<?php
/**
 * API: Tạo đơn đăng ký membership - trạng thái chờ thanh toán thật
 * Subscription chỉ được kích hoạt khi Casso webhook xác nhận tiền đã về tài khoản
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

header('Content-Type: application/json');

$current_lang = $_SESSION['lang'] ?? 'en';
$is_vi = ($current_lang === 'vi');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Vui lòng đăng nhập để mua thẻ thành viên.' : 'Please log in to purchase a membership.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? 'buy';

if ($action === 'cancel_membership' || $action === 'delete_membership') {
    $sub_id = intval($_POST['sub_id'] ?? 0);
    if ($sub_id > 0) {
        $stmt_c = $pdo->prepare("DELETE FROM member_subscriptions WHERE id = ? AND user_id = ?");
        $stmt_c->execute([$sub_id, $user_id]);
        if ($stmt_c->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => $is_vi ? 'Đã xóa thẻ thành viên trước đó thành công.' : 'Previous membership deleted successfully.']);
            exit();
        }
    }
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Không thể xóa thẻ thành viên.' : 'Failed to delete membership.']);
    exit();
}

$plan_id        = intval($_POST['plan_id'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cod';
$note           = trim($_POST['note'] ?? '');

if ($plan_id <= 0) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Vui lòng chọn gói thành viên hợp lệ.' : 'Please select a valid membership plan.']);
    exit();
}

// Fetch plan
$stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ? AND is_active = 1");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Gói thành viên không tồn tại.' : 'Membership plan not found.']);
    exit();
}

// Check if user already has ANY active or pending subscription
$stmt_check_any = $pdo->prepare("
    SELECT ms.id, ms.status, mp.name, mp.name_vi 
    FROM member_subscriptions ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.user_id = ? AND ms.status IN ('active', 'pending') AND (ms.status = 'pending' OR ms.end_date >= CURDATE())
    ORDER BY ms.id DESC LIMIT 1
");
$stmt_check_any->execute([$user_id]);
$existing_sub = $stmt_check_any->fetch();

if ($existing_sub) {
    $existing_plan_name = ($current_lang === 'vi' && !empty($existing_sub['name_vi'])) ? $existing_sub['name_vi'] : $existing_sub['name'];
    $msg = ($current_lang === 'en')
        ? "You cannot register multiple memberships. You currently have an active/pending membership (\"{$existing_plan_name}\"). You must delete your previous membership before registering a new one."
        : "Bạn không thể đăng ký nhiều gói thẻ thành viên cùng lúc. Bạn hiện có gói \"{$existing_plan_name}\" đang hoạt động hoặc chờ xử lý. Vui lòng xóa thẻ thành viên trước đó để đăng ký thẻ mới.";

    echo json_encode([
        'success' => false,
        'has_existing' => true,
        'existing_sub_id' => $existing_sub['id'],
        'existing_plan_name' => $existing_plan_name,
        'message' => $msg
    ]);
    exit();
}

$plan_name = ($current_lang === 'vi' && !empty($plan['name_vi'])) ? $plan['name_vi'] : $plan['name'];

try {
    // Tạo đơn mới với trạng thái pending
    $start_date = date('Y-m-d');
    $end_date   = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));

    $stmt_insert = $pdo->prepare("
        INSERT INTO member_subscriptions 
        (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, note, admin_seen) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 0)
    ");
    $stmt_insert->execute([$user_id, $plan_id, $start_date, $end_date, $plan['price'], $payment_method, $note]);
    $subscription_id = $pdo->lastInsertId();
    $price_formatted = number_format($plan['price'], 0, ',', '.') . 'đ';

    // Send email notification to Managers via PHPMailer
    try {
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $buyer_info = $stmt_u->fetch();

        $stmt_mgrs = $pdo->query("SELECT email, name FROM users WHERE role = 'manager'");
        $managers  = $stmt_mgrs->fetchAll();

        if (!empty($managers)) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';
            $mail->Password   = 'hpst uazz wdsq sbnf';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('lamtuoi155@gmail.com', 'GymX Membership System');

            $has_mgr = false;
            foreach ($managers as $mgr) {
                if (!empty($mgr['email'])) {
                    $mail->addAddress($mgr['email'], $mgr['name']);
                    $has_mgr = true;
                }
            }

            if ($has_mgr) {
                $mail->isHTML(true);
                $mail->Subject = "💳 [GymX Notification] Gói Thẻ Thành Viên Mới Chờ Manager Xác Nhận - #" . $subscription_id;
                
                $b_name  = $buyer_info['name'] ?? 'Khách hàng';
                $b_email = $buyer_info['email'] ?? '';

                $m_body = "<div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
                $m_body .= "<div style='background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 20px; border-radius: 15px; text-align: center;'>";
                $m_body .= "<h2 style='margin: 0; font-size: 1.5rem;'>💳 Đơn Đăng Ký Thẻ Thành Viên Mới</h2>";
                $m_body .= "<p style='margin: 5px 0 0; opacity: 0.95;'>Vui lòng kiểm tra và xác nhận thẻ cho hội viên.</p></div>";

                $m_body .= "<div style='background: #f8fafc; padding: 18px; border-radius: 15px; margin: 20px 0; border: 1px solid #e2e8f0;'>";
                $m_body .= "<strong>Hội Viên:</strong> " . htmlspecialchars($b_name) . " (" . htmlspecialchars($b_email) . ")<br>";
                $m_body .= "<strong>Gói Đăng Ký:</strong> " . htmlspecialchars($plan_name) . "<br>";
                $m_body .= "<strong>Giá Tiền:</strong> " . $price_formatted . "<br>";
                $m_body .= "<strong>Phương Thức:</strong> " . strtoupper($payment_method) . "<br>";
                $m_body .= "<strong>Trạng Thái:</strong> <span style='color: #d97706; font-weight: bold;'>PENDING (Chờ Manager Xác Nhận)</span><br>";
                if ($note) {
                    $m_body .= "<strong>Ghi Chú:</strong> " . htmlspecialchars($note) . "<br>";
                }
                $m_body .= "</div>";

                $m_body .= "<div style='text-align: center; margin-top: 25px; display: flex; gap: 10px; justify-content: center;'>";
                $m_body .= "<a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=approve&sub_id=" . $subscription_id . "' style='background: #16a34a; color: white; padding: 12px 24px; border-radius: 10px; font-weight: bold; text-decoration: none; display: inline-block;'>✅ Xác Nhận / Kích Hoạt Thẻ</a>";
                $m_body .= "<a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=reject&sub_id=" . $subscription_id . "' style='background: #dc2626; color: white; padding: 12px 24px; border-radius: 10px; font-weight: bold; text-decoration: none; display: inline-block; margin-left: 10px;'>❌ Từ Chối Yêu Cầu</a>";
                $m_body .= "</div></div>";

                $mail->Body = $m_body;
                $mail->send();
            }
        }
    } catch (Exception $mailEx) {
        error_log("Membership Registration Mail Error: " . $mailEx->getMessage());
    }

    $price_formatted = number_format($plan['price'], 0, ',', '.') . 'đ';

    echo json_encode([
        'success'          => true,
        'awaiting_payment' => true,
        'subscription_id'  => $subscription_id,
        'plan_name'        => $plan_name,
        'price'            => $price_formatted,
        'price_raw'        => intval($plan['price']),
        'payment_method'   => $payment_method,
    ]);

} catch (Exception $e) {
    error_log("Membership create error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Đã xảy ra lỗi. Vui lòng thử lại.' : 'An error occurred. Please try again.']);
}

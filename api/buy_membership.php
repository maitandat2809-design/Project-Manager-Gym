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
$is_vi = false;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Vui lòng đăng nhập để mua thẻ thành viên.' : 'Please log in to purchase a membership.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

require_once __DIR__ . '/../includes/membership_loyalty.php';

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

// Clean up any stale unpaid pending subscription for this user
$stmt_clean_pending = $pdo->prepare("DELETE FROM member_subscriptions WHERE user_id = ? AND status = 'pending'");
$stmt_clean_pending->execute([$user_id]);

// Calculate loyalty status & perks for this user
$loyalty_info = getUserMembershipLoyaltyInfo($pdo, $user_id);
$perks        = getPlanLoyaltyPerks($plan, $loyalty_info);

$start_date      = $perks['start_date'];
$end_date        = $perks['end_date'];
$price_to_pay    = (float)$perks['final_price'];
$is_renewal      = $perks['is_renewal'] ? 1 : 0;
$discount_amount = (float)$perks['discount_amount'];
$bonus_days      = (int)$perks['bonus_days'];
$bonus_pt        = (int)$perks['bonus_pt'];
$total_pt        = (int)$perks['total_pt'];
$renewal_parent  = ($perks['is_same_plan_renewal'] && !empty($loyalty_info['active_sub']['id'])) ? (int)$loyalty_info['active_sub']['id'] : null;
$gifts_json      = !empty($perks['gifts']) ? json_encode($perks['gifts'], JSON_UNESCAPED_UNICODE) : null;

$plan_name = ($current_lang === 'vi' && !empty($plan['name_vi'])) ? $plan['name_vi'] : $plan['name'];

try {
    // Insert new subscription record with status 'pending'
    $stmt_insert = $pdo->prepare("
        INSERT INTO member_subscriptions 
        (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, note, admin_seen,
         is_renewal, discount_amount, bonus_days, bonus_pt, loyalty_gifts, renewal_parent_id, pt_sessions_total, pt_sessions_used) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 0, ?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt_insert->execute([
        $user_id,
        $plan_id,
        $start_date,
        $end_date,
        $price_to_pay,
        $payment_method,
        $note,
        $is_renewal,
        $discount_amount,
        $bonus_days,
        $bonus_pt,
        $gifts_json,
        $renewal_parent,
        $total_pt
    ]);
    $subscription_id = $pdo->lastInsertId();

    $price_formatted    = number_format($price_to_pay, 0, ',', '.') . ' VND';
    $orig_formatted     = number_format($plan['price'], 0, ',', '.') . ' VND';
    $discount_formatted = number_format($discount_amount, 0, ',', '.') . ' VND';

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
                $mail_subject_prefix = $is_vi 
                    ? ($is_renewal ? "🌟 [GymX Tri Ân Hội Viên] Đơn GIA HẠN / HỘI VIÊN THÂN THIẾT" : "💳 [GymX Notification] Gói Thẻ Thành Viên Mới")
                    : ($is_renewal ? "🌟 [GymX Loyalty] MEMBERSHIP RENEWAL Application" : "💳 [GymX Notification] New Membership Registration");
                $mail->Subject = "{$mail_subject_prefix} #" . $subscription_id . ($is_vi ? " - Chờ Manager Xác Nhận" : " - Pending Approval");
                
                $b_name  = $buyer_info['name'] ?? ($is_vi ? 'Khách hàng' : 'Customer');
                $b_email = $buyer_info['email'] ?? '';

                $header_bg = $is_renewal ? 'linear-gradient(135deg, #b45309, #d97706)' : 'linear-gradient(135deg, #1e40af, #3b82f6)';
                $header_title = $is_vi 
                    ? ($is_renewal ? '🌟 Đơn Gia Hạn / Hội Viên Thân Thiết Mới' : '💳 Đơn Đăng Ký Thẻ Thành Viên Mới')
                    : ($is_renewal ? '🌟 Membership Renewal Application' : '💳 New Membership Registration');
                $header_desc = $is_vi ? 'Vui lòng kiểm tra và xác nhận thẻ cho hội viên.' : 'Please review and confirm membership for this member.';

                $m_body = "<div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
                $m_body .= "<div style='background: {$header_bg}; color: white; padding: 20px; border-radius: 15px; text-align: center;'>";
                $m_body .= "<h2 style='margin: 0; font-size: 1.5rem;'>{$header_title}</h2>";
                $m_body .= "<p style='margin: 5px 0 0; opacity: 0.95;'>{$header_desc}</p></div>";

                $m_body .= "<div style='background: #f8fafc; padding: 18px; border-radius: 15px; margin: 20px 0; border: 1px solid #e2e8f0;'>";
                $m_body .= "<strong>" . ($is_vi ? "Hội Viên:" : "Member:") . "</strong> " . htmlspecialchars($b_name) . " (" . htmlspecialchars($b_email) . ")<br>";
                $m_body .= "<strong>" . ($is_vi ? "Gói Đăng Ký:" : "Plan:") . "</strong> " . htmlspecialchars($plan_name) . "<br>";
                
                if ($is_renewal) {
                    $m_body .= "<strong>" . ($is_vi ? "Hạng Hội Viên:" : "Loyalty Tier:") . "</strong> <span style='color: #b45309; font-weight: bold;'>💎 " . htmlspecialchars($loyalty_info['tier_badge']) . "</span><br>";
                    $m_body .= "<strong>" . ($is_vi ? "Giá Gốc:" : "Original Price:") . "</strong> <del style='color: #94a3b8;'>" . $orig_formatted . "</del><br>";
                    $m_body .= "<strong>" . ($is_vi ? "Ưu Đãi Giảm Giá" : "Discount") . " (-{$perks['discount_percent']}%):</strong> <span style='color: #16a34a; font-weight: bold;'>-{$discount_formatted}</span><br>";
                    $m_body .= "<strong>" . ($is_vi ? "Số Tiền Cần Thu:" : "Amount Due:") . "</strong> <span style='color: #dc2626; font-size: 1.15rem; font-weight: 800;'>" . $price_formatted . "</span><br>";
                    $m_body .= "<strong>" . ($is_vi ? "Thời Hạn Thẻ:" : "Duration:") . "</strong> " . date('d/m/Y', strtotime($start_date)) . " → " . date('d/m/Y', strtotime($end_date)) . " (" . ($is_vi ? "Đã cộng thêm <strong>+{$bonus_days} ngày tặng miễn phí</strong>" : "Includes <strong>+{$bonus_days} bonus days</strong>") . ")<br>";
                    $m_body .= "<strong>" . ($is_vi ? "Buổi PT 1:1:" : "1:1 PT Sessions:") . "</strong> " . $total_pt . ($is_vi ? " buổi (Đã bao gồm +{$bonus_pt} buổi tặng kèm)<br>" : " sessions (Includes +{$bonus_pt} bonus sessions)<br>");
                    $m_body .= "<strong>" . ($is_vi ? "Quà Tặng Kèm:" : "Perks & Gifts:") . "</strong> " . ($is_vi ? "Bình nước GymX Shaker VIP Pro + 2 Guest Pass mời bạn bè + InBody & Dinh Dưỡng" : "GymX Shaker VIP Pro + 2 Guest Passes + InBody Assessment") . "<br>";
                } else {
                    $m_body .= "<strong>" . ($is_vi ? "Giá Tiền:" : "Price:") . "</strong> " . $price_formatted . "<br>";
                    $m_body .= "<strong>" . ($is_vi ? "Thời Hạn Thẻ:" : "Duration:") . "</strong> " . date('d/m/Y', strtotime($start_date)) . " → " . date('d/m/Y', strtotime($end_date)) . "<br>";
                }

                $m_body .= "<strong>" . ($is_vi ? "Phương Thức:" : "Payment Method:") . "</strong> " . strtoupper($payment_method) . "<br>";
                $m_body .= "<strong>" . ($is_vi ? "Trạng Thái:" : "Status:") . "</strong> <span style='color: #d97706; font-weight: bold;'>PENDING " . ($is_vi ? "(Chờ Manager Xác Nhận)" : "(Pending Approval)") . "</span><br>";
                if ($note) {
                    $m_body .= "<strong>" . ($is_vi ? "Ghi Chú:" : "Note:") . "</strong> " . htmlspecialchars($note) . "<br>";
                }
                $m_body .= "</div>";

                $m_body .= "<div style='text-align: center; margin-top: 25px; display: flex; gap: 10px; justify-content: center;'>";
                $m_body .= "<a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=approve&sub_id=" . $subscription_id . "' style='background: #16a34a; color: white; padding: 12px 24px; border-radius: 10px; font-weight: bold; text-decoration: none; display: inline-block;'>✅ " . ($is_vi ? "Xác Nhận / Kích Hoạt Thẻ" : "Approve / Activate Membership") . "</a>";
                $m_body .= "<a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=reject&sub_id=" . $subscription_id . "' style='background: #dc2626; color: white; padding: 12px 24px; border-radius: 10px; font-weight: bold; text-decoration: none; display: inline-block; margin-left: 10px;'>❌ " . ($is_vi ? "Từ Chối Yêu Cầu" : "Reject Application") . "</a>";
                $m_body .= "</div></div>";

                $mail->Body = $m_body;
                $mail->send();
            }
        }
    } catch (Exception $mailEx) {
        error_log("Membership Registration Mail Error: " . $mailEx->getMessage());
    }

    echo json_encode([
        'success'          => true,
        'awaiting_payment' => true,
        'subscription_id'  => $subscription_id,
        'plan_name'        => $plan_name,
        'price'            => $price_formatted,
        'price_raw'        => intval($price_to_pay),
        'original_price'   => $orig_formatted,
        'discount_amount'  => $discount_formatted,
        'discount_percent' => $perks['discount_percent'],
        'bonus_days'       => $bonus_days,
        'bonus_pt'         => $bonus_pt,
        'total_pt'         => $total_pt,
        'is_renewal'       => (bool)$is_renewal,
        'renewal_type'     => $perks['renewal_type'],
        'start_date'       => date('d/m/Y', strtotime($start_date)),
        'end_date'         => date('d/m/Y', strtotime($end_date)),
        'gifts'            => $perks['gifts'],
        'payment_method'   => $payment_method,
    ]);

} catch (Exception $e) {
    error_log("Membership create error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Đã xảy ra lỗi. Vui lòng thử lại.' : 'An error occurred. Please try again.']);
}

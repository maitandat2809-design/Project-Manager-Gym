<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendBookingCancellationEmail')) {
    /**
     * Send email notification to user, coach, and managers when a workout session is cancelled by user
     */
    function sendBookingCancellationEmail($pdo, $booking_id, $reason = '') {
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, u.phone as client_phone,
                       c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                LEFT JOIN coaches c ON b.coach_id = c.id
                LEFT JOIN users c_u ON c.user_id = c_u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $b = $stmt->fetch();

            if (!$b) {
                return false;
            }

            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';     
            $mail->Password   = 'hpst uazz wdsq sbnf';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');

            // Add user email as Primary Recipient
            if (!empty($b['client_email'])) {
                $mail->addAddress($b['client_email'], $b['client_name']);
            }

            // Add coach email if assigned
            if (!empty($b['coach_email'])) {
                $mail->addCC($b['coach_email'], $b['coach_name']);
            }

            // Add Managers and Admins
            $stmt_mgr = $pdo->query("SELECT name, email FROM users WHERE role IN ('manager', 'admin')");
            $managers = $stmt_mgr->fetchAll();
            foreach ($managers as $mgr) {
                if (!empty($mgr['email']) && $mgr['email'] !== $b['client_email'] && $mgr['email'] !== $b['coach_email']) {
                    $mail->addCC($mgr['email'], $mgr['name']);
                }
            }

            $formatted_date = date('l, d/m/Y - H:i', strtotime($b['booking_date']));
            
            $mail->isHTML(true);
            $mail->Subject = "❌ [Gym Manager] Workout Session Cancelled (#" . $b['id'] . ")";

            $mail_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff; color: #1c2340;'>
                <div style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #ef4444;'>
                    <h2 style='color: #dc2626; margin: 0; font-size: 22px;'>❌ Workout Session Cancelled</h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 4px;'>Workout Session Cancellation Notice</p>
                </div>
                
                <p style='color: #334155; font-size: 15px; margin-top: 20px;'>
                    Hello <strong>" . htmlspecialchars($b['client_name']) . "</strong>,<br>
                    Your workout session has been updated to <strong>CANCELLED</strong> in your personal and system calendars.
                </p>

                <div style='background: #fef2f2; padding: 18px; border-left: 4px solid #ef4444; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 6px 0;'><strong>Booking ID:</strong> #" . $b['id'] . "</p>
                    <p style='margin: 6px 0;'><strong>Client:</strong> " . htmlspecialchars($b['client_name']) . " (" . htmlspecialchars($b['client_email'] ?? 'N/A') . ")</p>
                    <p style='margin: 6px 0;'><strong>Coach:</strong> " . htmlspecialchars($b['coach_name'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Date & Time:</strong> <span style='color: #dc2626; font-weight: bold;'>" . $formatted_date . "</span></p>
                    <p style='margin: 6px 0;'><strong>Status:</strong> <span style='color: #dc2626; font-weight: bold;'>CANCELLED</span></p>";

            if (!empty($reason)) {
                $mail_body .= "<p style='margin: 6px 0;'><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
            }

            $mail_body .= "
                </div>

                <p style='color: #64748b; font-size: 13px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px;'>
                    This automated email was sent to the client, coach, and Gym Manager team.<br>The session has been updated in the system.
                </p>
            </div>";

            $mail->Body = $mail_body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Cancellation email error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendNewBookingNotificationEmail')) {
    /**
     * Send email notification to coach, client, and managers when a new workout session is booked
     */
    function sendNewBookingNotificationEmail($pdo, $booking_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
                       c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
                       c.room_number as coach_room_number
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                LEFT JOIN coaches c ON b.coach_id = c.id
                LEFT JOIN users c_u ON c.user_id = c_u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $b = $stmt->fetch();

            if (!$b) {
                return false;
            }

            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';     
            $mail->Password   = 'hpst uazz wdsq sbnf';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');

            // Send Primary email to Coach
            if (!empty($b['coach_email'])) {
                $mail->addAddress($b['coach_email'], $b['coach_name']);
            }

            // CC to Client
            if (!empty($b['client_email']) && $b['client_email'] !== $b['coach_email']) {
                $mail->addCC($b['client_email'], $b['client_name']);
            }

            // CC to Managers and Admins
            $stmt_mgr = $pdo->query("SELECT name, email FROM users WHERE role IN ('manager', 'admin')");
            $managers = $stmt_mgr->fetchAll();
            foreach ($managers as $mgr) {
                if (!empty($mgr['email']) && $mgr['email'] !== $b['client_email'] && $mgr['email'] !== $b['coach_email']) {
                    $mail->addCC($mgr['email'], $mgr['name']);
                }
            }

            $formatted_date = date('l, d/m/Y - H:i', strtotime($b['booking_date']));
            $class_display = !empty($b['class_name']) ? htmlspecialchars($b['class_name']) : '1-on-1 Personal Training Session';
            $room_info = !empty($b['room_number'])
                ? $b['room_number']
                : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'To be confirmed');

            $mail->isHTML(true);
            $mail->Subject = "📅 [Gym Manager] New Workout Booking (#" . $b['id'] . ")";

            $mail_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff; color: #1c2340;'>
                <div style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #0062ff;'>
                    <h2 style='color: #0062ff; margin: 0; font-size: 22px;'>📅 New Workout Booking</h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 4px;'>New Workout Booking Notification</p>
                </div>
                
                <p style='color: #334155; font-size: 15px; margin-top: 20px;'>
                    Hello Coach <strong>" . htmlspecialchars($b['coach_name'] ?? 'Coach') . "</strong>,<br>
                    Client <strong>" . htmlspecialchars($b['client_name'] ?? 'Client') . "</strong> has successfully booked a new session with you.
                </p>

                <div style='background: #f0f7ff; padding: 18px; border-left: 4px solid #0062ff; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 6px 0;'><strong>Booking ID:</strong> #" . $b['id'] . "</p>
                    <p style='margin: 6px 0;'><strong>Client:</strong> " . htmlspecialchars($b['client_name'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Phone:</strong> " . htmlspecialchars($b['client_phone'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Email:</strong> " . htmlspecialchars($b['client_email'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Session Type:</strong> " . $class_display . "</p>
                    <p style='margin: 6px 0;'><strong>Room:</strong> " . htmlspecialchars($room_info) . "</p>
                    <p style='margin: 6px 0;'><strong>Date & Time:</strong> <span style='color: #0062ff; font-weight: bold;'>" . $formatted_date . "</span></p>
                    <p style='margin: 6px 0;'><strong>Status:</strong> <span style='color: #d97706; font-weight: bold;'>" . strtoupper($b['status']) . "</span></p>";

            if (!empty($b['training_plan'])) {
                $mail_body .= "<p style='margin: 6px 0;'><strong>Notes / Plan:</strong> " . htmlspecialchars($b['training_plan']) . "</p>";
            }

            $mail_body .= "
                </div>

                <p style='color: #166534; background: #dcfce7; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center; font-size: 14px;'>
                    ✅ This session has been added to your teaching calendar and Coach Dashboard.
                </p>

                <p style='color: #64748b; font-size: 13px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 20px;'>
                    This automated email was sent to the coach, client, and Gym Manager team.
                </p>
            </div>";

            $mail->Body = $mail_body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("New booking email notification error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendMembershipExpirationEmail')) {
    /**
     * Send email notification to client when their PT membership sessions run out / expire
     */
    function sendMembershipExpirationEmail($pdo, $sub_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT ms.*, u.name as client_name, u.email as client_email, mp.name as plan_name, mp.name_vi as plan_name_vi, mp.pt_sessions
                FROM member_subscriptions ms
                JOIN users u ON ms.user_id = u.id
                JOIN membership_plans mp ON ms.plan_id = mp.id
                WHERE ms.id = ?
            ");
            $stmt->execute([$sub_id]);
            $sub = $stmt->fetch();

            if (!$sub || empty($sub['client_email'])) {
                return false;
            }

            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';     
            $mail->Password   = 'hpst uazz wdsq sbnf';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');
            $mail->addAddress($sub['client_email'], $sub['client_name']);

            $is_vi_exp = false;
            $plan_title = $sub['plan_name'];
            $pt_total = (int)($sub['pt_sessions_total'] > 0 ? $sub['pt_sessions_total'] : $sub['pt_sessions']);
            $pt_used = (int)$sub['pt_sessions_used'];

            $mail->isHTML(true);
            $mail->Subject = $is_vi_exp 
                ? "⚠️ [Gym Manager] Thông Báo Hết Hạn Gói PT (" . $pt_used . "/" . $pt_total . " Buổi Tập)"
                : "⚠️ [Gym Manager] PT Membership Sessions Expired (" . $pt_used . "/" . $pt_total . " Sessions)";

            $mail_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff; color: #1c2340;'>
                <div style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #d97706;'>
                    <h2 style='color: #d97706; margin: 0; font-size: 22px;'>" . ($is_vi_exp ? "⚠️ Gói Tập PT Của Bạn Đã Hết Hạn" : "⚠️ Your PT Sessions Have Expired") . "</h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 4px;'>PT Membership Sessions Expired</p>
                </div>
                
                <p style='color: #334155; font-size: 15px; margin-top: 20px;'>
                    " . ($is_vi_exp 
                        ? "Xin chào <strong>" . htmlspecialchars($sub['client_name']) . "</strong>,<br>HLV đã hoàn tất điểm danh tất cả <strong>" . $pt_used . "/" . $pt_total . " buổi tập</strong> thuộc gói <strong>" . htmlspecialchars($plan_title) . "</strong> của bạn."
                        : "Hello <strong>" . htmlspecialchars($sub['client_name']) . "</strong>,<br>Your coach has logged attendance for all <strong>" . $pt_used . "/" . $pt_total . " sessions</strong> under your <strong>" . htmlspecialchars($plan_title) . "</strong> membership plan.") . "
                </p>

                <div style='background: #fffbeb; padding: 18px; border-left: 4px solid #d97706; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 6px 0;'><strong>" . ($is_vi_exp ? "Gói tập:" : "Plan:") . "</strong> " . htmlspecialchars($plan_title) . "</p>
                    <p style='margin: 6px 0;'><strong>" . ($is_vi_exp ? "Tổng số buổi:" : "Total sessions:") . "</strong> " . $pt_total . ($is_vi_exp ? " buổi" : " sessions") . "</p>
                    <p style='margin: 6px 0;'><strong>" . ($is_vi_exp ? "Đã hoàn thành:" : "Completed:") . "</strong> <span style='color: #d97706; font-weight: bold;'>" . $pt_used . " / " . $pt_total . ($is_vi_exp ? " buổi ✅" : " sessions ✅") . "</span></p>
                    <p style='margin: 6px 0;'><strong>" . ($is_vi_exp ? "Số buổi còn lại:" : "Remaining:") . "</strong> <span style='color: #dc2626; font-weight: bold;'>0 " . ($is_vi_exp ? "buổi (ĐÃ HẾT HẠN)" : "sessions (EXPIRED)") . "</span></p>
                </div>

                <p style='color: #334155; font-size: 14px; font-weight: bold; margin-bottom: 15px; text-align: center;'>
                    " . ($is_vi_exp ? "Vui lòng chọn 1 trong 2 lựa chọn bên dưới để tiếp tục duy trì tập luyện cùng HLV:" : "Please choose one of the options below to continue training with your coach:") . "
                </p>

                <div style='display: flex; gap: 12px; margin: 20px 0; justify-content: center; flex-wrap: wrap;'>
                    <a href='http://localhost/Gym%20Manager/pages/membership.php' style='background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;'>
                        🔄 " . ($is_vi_exp ? "1. Gia Hạn Gói Tập (Renew)" : "1. Renew Plan") . "
                    </a>
                    <a href='http://localhost/Gym%20Manager/pages/membership.php' style='background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;'>
                        ✨ " . ($is_vi_exp ? "2. Chọn Gói Khác (Choose New)" : "2. Choose New Plan") . "
                    </a>
                </div>

                <p style='color: #64748b; font-size: 13px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 20px;'>
                    " . ($is_vi_exp ? "Email này được gửi tự động từ Hệ Thống Gym Manager." : "This automated email was sent by the Gym Manager System.") . "
                </p>
            </div>";

            $mail->Body = $mail_body;
            $mail->send();

            // Mark as notified in database
            $pdo->prepare("UPDATE member_subscriptions SET expiry_notified = 1 WHERE id = ?")->execute([$sub_id]);
            return true;
        } catch (Exception $e) {
            error_log("Membership expiration email error: " . $e->getMessage());
            return false;
        }
    }
}
?>


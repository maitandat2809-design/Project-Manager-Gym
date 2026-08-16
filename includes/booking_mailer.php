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
            $mail->Subject = "❌ [Gym Manager] Thông Báo Hủy Buổi Tập / Workout Session Cancelled (#" . $b['id'] . ")";

            $mail_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff; color: #1c2340;'>
                <div style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #ef4444;'>
                    <h2 style='color: #dc2626; margin: 0; font-size: 22px;'>❌ Thông Báo Hủy Buổi Tập</h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 4px;'>Workout Session Cancellation Notice</p>
                </div>
                
                <p style='color: #334155; font-size: 15px; margin-top: 20px;'>
                    Xin chào <strong>" . htmlspecialchars($b['client_name']) . "</strong>,<br>
                    Buổi tập của bạn đã được cập nhật trạng thái <strong>HỦY / CANCELLED</strong> thành công trên lịch cá nhân và lịch hệ thống.
                </p>

                <div style='background: #fef2f2; padding: 18px; border-left: 4px solid #ef4444; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 6px 0;'><strong>Mã buổi tập (Booking ID):</strong> #" . $b['id'] . "</p>
                    <p style='margin: 6px 0;'><strong>Học viên (Client):</strong> " . htmlspecialchars($b['client_name']) . " (" . htmlspecialchars($b['client_email'] ?? 'N/A') . ")</p>
                    <p style='margin: 6px 0;'><strong>HLV Đảm Nhận (Coach):</strong> " . htmlspecialchars($b['coach_name'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Thời gian (Date & Time):</strong> <span style='color: #dc2626; font-weight: bold;'>" . $formatted_date . "</span></p>
                    <p style='margin: 6px 0;'><strong>Trạng thái (Status):</strong> <span style='color: #dc2626; font-weight: bold;'>ĐÃ HỦY / CANCELLED</span></p>";

            if (!empty($reason)) {
                $mail_body .= "<p style='margin: 6px 0;'><strong>Lý do hủy (Reason):</strong> " . htmlspecialchars($reason) . "</p>";
            }

            $mail_body .= "
                </div>

                <p style='color: #64748b; font-size: 13px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px;'>
                    Email này được tự động gửi đến Học viên, HLV và Ban Quản Lý Gym Manager.<br>Lịch hẹn đã được cập nhật lại trên hệ thống.
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
            $room_info = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
            $class_display = !empty($b['class_name']) ? htmlspecialchars($b['class_name']) : 'Buổi Tập Cá Nhân (1-on-1)';

            $mail->isHTML(true);
            $mail->Subject = "📅 [Gym Manager] Thông Báo Lịch Tập Mới / New Workout Booking (#" . $b['id'] . ")";

            $mail_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff; color: #1c2340;'>
                <div style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #0062ff;'>
                    <h2 style='color: #0062ff; margin: 0; font-size: 22px;'>📅 Thông Báo Lịch Tập Mới</h2>
                    <p style='color: #64748b; font-size: 14px; margin-top: 4px;'>New Workout Booking Notification</p>
                </div>
                
                <p style='color: #334155; font-size: 15px; margin-top: 20px;'>
                    Xin chào HLV <strong>" . htmlspecialchars($b['coach_name'] ?? 'HLV') . "</strong>,<br>
                    Học viên <strong>" . htmlspecialchars($b['client_name'] ?? 'Học viên') . "</strong> đã đăng ký lịch tập mới thành công với bạn trên hệ thống.
                </p>

                <div style='background: #f0f7ff; padding: 18px; border-left: 4px solid #0062ff; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 6px 0;'><strong>Mã buổi tập (Booking ID):</strong> #" . $b['id'] . "</p>
                    <p style='margin: 6px 0;'><strong>Học viên (Client):</strong> " . htmlspecialchars($b['client_name'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Số điện thoại (Phone):</strong> " . htmlspecialchars($b['client_phone'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Email:</strong> " . htmlspecialchars($b['client_email'] ?? 'N/A') . "</p>
                    <p style='margin: 6px 0;'><strong>Loại buổi tập / Gói dịch vụ:</strong> " . $class_display . "</p>
                    <p style='margin: 6px 0;'><strong>Phòng tập (Room):</strong> " . htmlspecialchars($room_info) . "</p>
                    <p style='margin: 6px 0;'><strong>Thời gian (Date & Time):</strong> <span style='color: #0062ff; font-weight: bold;'>" . $formatted_date . "</span></p>
                    <p style='margin: 6px 0;'><strong>Trạng thái (Status):</strong> <span style='color: #d97706; font-weight: bold;'>" . strtoupper($b['status']) . "</span></p>";

            if (!empty($b['training_plan'])) {
                $mail_body .= "<p style='margin: 6px 0;'><strong>Ghi chú / Kế hoạch:</strong> " . htmlspecialchars($b['training_plan']) . "</p>";
            }

            $mail_body .= "
                </div>

                <p style='color: #166534; background: #dcfce7; padding: 12px; border-radius: 8px; font-weight: bold; text-align: center; font-size: 14px;'>
                    ✅ Lịch tập này đã tự động cập nhật vào Lịch dạy & Dashboard của HLV.
                </p>

                <p style='color: #64748b; font-size: 13px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 20px;'>
                    Email này được gửi tự động đến HLV, Học viên và Ban Quản Lý Gym Manager.
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
?>

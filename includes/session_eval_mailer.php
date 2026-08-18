<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email when user evaluates a training session (Dual English & Vietnamese)
 */
function sendSessionReviewEmail($booking_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   u_client.name as client_name, u_client.email as client_email,
                   u_coach.name as coach_name, u_coach.email as coach_email,
                   c.specialty
            FROM bookings b
            JOIN users u_client ON b.user_id = u_client.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users u_coach ON c.user_id = u_coach.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$b || !$b['user_rating']) return false;

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
        if (!empty($b['client_email'])) {
            $mail->addAddress($b['client_email'], $b['client_name']);
        }
        if (!empty($b['coach_email'])) {
            $mail->addAddress($b['coach_email'], $b['coach_name']);
        }

        $mail->isHTML(true);
        $stars = str_repeat('⭐', intval($b['user_rating']));
        $mail->Subject = "⭐ [Gym Manager] Session Review / Đánh giá buổi tập từ " . $b['client_name'] . " - " . date('d/m/Y', strtotime($b['booking_date']));

        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
        $body .= "<div style='text-align: center; margin-bottom: 20px;'>";
        $body .= "<h1 style='color: #0062ff; margin: 0;'>GYM<span style='color:#f59e0b;'>X</span> MANAGER</h1>";
        $body .= "<p style='color: #64748b; font-size: 0.9rem;'>Training Session Feedback System / Hệ Thống Đánh Giá Buổi Tập</p>";
        $body .= "</div>";

        $body .= "<div style='background: linear-gradient(135deg, #eff6ff, #dbeafe); padding: 20px; border-radius: 16px; margin-bottom: 20px;'>";
        $body .= "<h2 style='color: #1e40af; margin-top: 0; font-size: 1.3rem;'>🌟 Training Session Review / Đánh Giá Buổi Tập</h2>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Client / Khách hàng:</strong> " . htmlspecialchars($b['client_name']) . "</p>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Coach / HLV:</strong> " . htmlspecialchars($b['coach_name']) . "</p>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Date & Time / Thời gian:</strong> " . date('H:i - d/m/Y', strtotime($b['booking_date'])) . "</p>";
        $body .= "</div>";

        $body .= "<div style='background: #fffbebf5; border: 2px solid #fde68a; padding: 20px; border-radius: 16px; margin-bottom: 20px;'>";
        $body .= "<p style='font-size: 1.3rem; font-weight: bold; color: #d97706; margin: 0 0 10px 0;'>Rating / Hài lòng: " . $stars . " (" . $b['user_rating'] . "/5)</p>";
        $body .= "<p style='font-size: 1rem; color: #1e293b; margin: 0; line-height: 1.6;'><strong>Feedback / Nhận xét:</strong><br>\"" . nl2br(htmlspecialchars($b['user_review'] ?: 'No comment / Không có bình luận')) . "\"</p>";
        $body .= "</div>";

        $body .= "<p style='color: #64748b; font-size: 0.85rem; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px;'>Thank you for choosing Gym Manager!</p>";
        $body .= "</div>";

        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Session review email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email when Coach submits training notes for the User (Dual English & Vietnamese)
 */
function sendCoachSessionNotesEmail($booking_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   u_client.name as client_name, u_client.email as client_email,
                   u_coach.name as coach_name, u_coach.email as coach_email,
                   c.specialty
            FROM bookings b
            JOIN users u_client ON b.user_id = u_client.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users u_coach ON c.user_id = u_coach.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$b || !$b['coach_session_notes']) return false;

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
        if (!empty($b['client_email'])) {
            $mail->addAddress($b['client_email'], $b['client_name']);
        }
        if (!empty($b['coach_email'])) {
            $mail->addAddress($b['coach_email'], $b['coach_name']);
        }

        $mail->isHTML(true);
        $mail->Subject = "📝 [Gym Manager] Coach Session Notes / Ghi chú từ HLV " . $b['coach_name'] . " - " . date('d/m/Y', strtotime($b['booking_date']));

        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
        $body .= "<div style='text-align: center; margin-bottom: 20px;'>";
        $body .= "<h1 style='color: #0062ff; margin: 0;'>GYM<span style='color:#f59e0b;'>X</span> MANAGER</h1>";
        $body .= "<p style='color: #64748b; font-size: 0.9rem;'>Session Performance & Improvement Notes / Ghi Chú Cải Thiện Buổi Tập</p>";
        $body .= "</div>";

        $body .= "<div style='background: linear-gradient(135deg, #f0fdf4, #dcfce7); padding: 20px; border-radius: 16px; margin-bottom: 20px;'>";
        $body .= "<h2 style='color: #166534; margin-top: 0; font-size: 1.3rem;'>💪 Session Improvement Focus / Ghi Chú & Định Hướng Cải Thiện</h2>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Client / Khách hàng:</strong> " . htmlspecialchars($b['client_name']) . "</p>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Coach / HLV:</strong> " . htmlspecialchars($b['coach_name']) . "</p>";
        $body .= "<p style='margin: 5px 0; color: #334155;'><strong>Date & Time / Thời gian:</strong> " . date('H:i - d/m/Y', strtotime($b['booking_date'])) . "</p>";
        $body .= "</div>";

        $body .= "<div style='background: #f8fafc; border-left: 4px solid #16a34a; padding: 20px; border-radius: 12px; margin-bottom: 20px;'>";
        $body .= "<p style='font-size: 1rem; color: #0f172a; margin: 0; line-height: 1.6;'><strong>Notes from Coach / Ghi chú từ HLV:</strong><br>\"" . nl2br(htmlspecialchars($b['coach_session_notes'])) . "\"</p>";
        $body .= "</div>";

        $body .= "<p style='color: #64748b; font-size: 0.85rem; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px;'>Keep up the good work for your next session!</p>";
        $body .= "</div>";

        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Coach session notes email error: " . $e->getMessage());
        return false;
    }
}
?>

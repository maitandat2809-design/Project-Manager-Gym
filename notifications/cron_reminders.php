<?php
// cron_reminders.php
// This script should be run via a cron job or Windows Task Scheduler every 5-10 minutes.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // We look for bookings that:
    // 1. Are confirmed
    // 2. Have not been reminded yet (reminded = 0 or NULL)
    // 3. Are scheduled to happen within the next 65 minutes, but are at least 55 minutes away.
    // However, to be safer with cron timing, we just check if it's strictly within the next 1 hour (plus 5-10 mins buffer).
    // A better approach: booking_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 65 MINUTE)
    
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, b.booking_date, b.class_name,
               u.name as user_name, u.email as user_email,
               c_u.name as coach_name, c_u.email as coach_email
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        JOIN coaches c ON b.coach_id = c.id
        JOIN users c_u ON c.user_id = c_u.id
        WHERE b.status = 'confirmed' 
          AND (b.reminded = 0 OR b.reminded IS NULL)
          AND b.booking_date > NOW() 
          AND b.booking_date <= DATE_ADD(NOW(), INTERVAL 65 MINUTE)
    ");
    $stmt->execute();
    $upcoming_bookings = $stmt->fetchAll();

    $count = 0;

    foreach ($upcoming_bookings as $booking) {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';
            $mail->Password   = 'hpst uazz wdsq sbnf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lamtuoi155@gmail.com', 'GymX Reminders');
            
            // Add User
            if (!empty($booking['user_email'])) {
                $mail->addAddress($booking['user_email'], $booking['user_name']);
            }
            // Add Coach
            if (!empty($booking['coach_email'])) {
                $mail->addAddress($booking['coach_email'], $booking['coach_name']);
            }

            $mail->isHTML(true);
            $mail->Subject = "⏰ REMINDER: Upcoming Training Session in 1 Hour!";
            
            $time_formatted = date('H:i - l, d M Y', strtotime($booking['booking_date']));
            $is_open_class = !empty($booking['class_name']);

            $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
            $body .= "<h2 style='color: #f59e0b;'>Session Reminder</h2>";
            $body .= "<p>Hello,</p>";
            $body .= "<p>This is a friendly reminder that your training session will start in <strong>less than 1 hour</strong>.</p>";
            $body .= "<div style='background: #fffbeb; padding: 15px; border-radius: 12px; border-left: 4px solid #f59e0b; margin: 20px 0;'>";
            if ($is_open_class) {
                $body .= "<strong>Class Name:</strong> " . $booking['class_name'] . "<br>";
            } else {
                $body .= "<strong>Student:</strong> " . ($booking['user_name'] ?? 'N/A') . "<br>";
            }
            $body .= "<strong>Coach:</strong> " . $booking['coach_name'] . "<br>";
            $body .= "<strong>Time:</strong> " . $time_formatted . "<br>";
            $body .= "</div>";
            $body .= "<p>Please make sure to arrive at the gym 5-10 minutes early to warm up!</p>";
            $body .= "<p>See you soon!</p>";
            $body .= "</div>";

            $mail->Body = $body;
            $mail->send();

            // Mark as reminded
            $update_stmt = $pdo->prepare("UPDATE bookings SET reminded = 1 WHERE id = ?");
            $update_stmt->execute([$booking['booking_id']]);
            
            $count++;

        } catch (Exception $e) {
            echo "Error sending reminder for booking ID {$booking['booking_id']}: {$mail->ErrorInfo}\n";
        }
    }

    echo "Processed {$count} reminders at " . date('Y-m-d H:i:s') . "\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>

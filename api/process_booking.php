<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: /Gym Manager/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    $message = $_POST['coach_message'] ?? '';
    
    $status = ($action == 'confirm') ? 'confirmed' : 'rejected';
    
    // Update booking
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?, coach_message = ?, coach_seen = 1, user_seen = 0, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$status, $message, $booking_id]);

    // --- Start PHPMailer Notification to User ---
    try {
        // Fetch User and Booking details
        $stmt_info = $pdo->prepare("
            SELECT b.booking_date, u.email as user_email, u.name as user_name, coach_u.name as coach_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN coaches c ON b.coach_id = c.id 
            JOIN users coach_u ON c.user_id = coach_u.id 
            WHERE b.id = ?
        ");
        $stmt_info->execute([$booking_id]);
        $info = $stmt_info->fetch();

        if ($info && $info['user_email']) {
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';

            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';     
            $mail->Password   = 'hpst uazz wdsq sbnf';   
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('lamtuoi155@gmail.com', 'GymX Training System');
            $mail->addAddress($info['user_email'], $info['user_name']);

            // Also Notify Managers
            $stmt_staff = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
            $stmt_staff->execute();
            $staff = $stmt_staff->fetchAll();
            foreach ($staff as $s) {
                if ($s['email'] && $s['email'] !== $info['user_email']) {
                    $mail->addAddress($s['email'], $s['name']);
                }
            }

            // Content
            $mail->isHTML(true);
            $subject_status = ($status == 'confirmed') ? "✅ Confirmed" : "❌ Returned";
            $mail->Subject = "GymX: Coach " . $info['coach_name'] . " has responded to your request - " . $subject_status;

            $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
            $mail_body .= "<h2 style='color: " . ($status == 'confirmed' ? '#10b981' : '#ef4444') . ";'>Booking Update</h2>";
            $mail_body .= "<p>Hello <strong>" . $info['user_name'] . "</strong>,</p>";
            $mail_body .= "<p>Coach <strong>" . $info['coach_name'] . "</strong> has " . ($status == 'confirmed' ? 'confirmed' : 'rejected') . " your training request.</p>";
            
            $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
            $mail_body .= "<strong>Scheduled:</strong> " . date('l, F d, Y - H:i', strtotime($info['booking_date'])) . "<br>";
            $mail_body .= "<strong>Status:</strong> " . strtoupper($status) . "<br>";
            if ($message) {
                $mail_body .= "<strong>Coach's Message:</strong> <br><em style='color: #475569;'>\"" . nl2br(htmlspecialchars($message)) . "\"</em>";
            }
            $mail_body .= "</div>";

            $mail_body .= "<p>You can check more details in your profile dashboard.</p>";
            $mail_body .= "<p style='color: #64748b; font-size: 0.9rem;'>Stay focused on your goals!<br>Team GymX</p></div>";

            $mail->Body = $mail_body;
            $mail->send();
        }
    } catch (Exception $e) {
        error_log("User Notif Mail Error: " . $mail->ErrorInfo);
    }
    // --- End PHPMailer Notification ---
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header("Location: /Gym Manager/index.php");
    exit();
}
?>

<?php
require 'config/db.php';
require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lamtuoi155@gmail.com';     
    $mail->Password   = 'hpst uazz wdsq sbnf';   
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $user_email = 'lamtuoi155@gmail.com'; // Simulate user email
    
    // 1. Send to Customer
    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Premium Shop');
    if ($user_email) {
        $mail->addAddress($user_email, 'Test User'); 
        $mail->isHTML(true);
        $mail->Subject = "GymX: Your Order Confirmation";
        $mail->Body = "Customer body";
        $mail->send();
        echo "Customer email sent.\n";
    }

    // 2. Send to Managers
    $mail->clearAddresses();
    $stmt_mgrs = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
    $stmt_mgrs->execute();
    $managers = $stmt_mgrs->fetchAll();

    $has_manager = false;
    foreach ($managers as $mgr) {
        if ($mgr['email']) {
            $mail->addAddress($mgr['email'], $mgr['name']);
            $has_manager = true;
        }
    }

    if ($has_manager) {
        $mail->Subject = "URGENT: New Order";
        $mail->Body = "Manager body";
        $mail->send();
        echo "Manager email sent.\n";
    }

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>

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

    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Test');
    
    // Send to managergym283@gmail.com
    $mail->addAddress('managergym283@gmail.com', 'Manager');
    $mail->Subject = 'Test Email for Gym Manager';
    $mail->Body    = 'This is a test notification from the GymX system.';
    $mail->send();
    echo "Message sent successfully to managergym283@gmail.com\n";

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>

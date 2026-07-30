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
    
    // First send
    $mail->addAddress('lamtuoi155@gmail.com', 'Customer');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email 1';
    $mail->Body    = 'This is a test 1';
    $mail->send();
    echo "Message 1 sent\n";

    // Second send
    $mail->clearAddresses();
    $mail->addAddress('managergym283@gmail.com', 'Manager');
    $mail->Subject = 'Test Email 2';
    $mail->Body    = 'This is a test 2';
    $mail->send();
    echo "Message 2 sent\n";

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>

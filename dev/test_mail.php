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
    $mail->addAddress('Datmtgcc210217@fpt.edu.vn', 'Manager');
    
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test';

    $mail->send();
    echo "Message has been sent successfully\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>

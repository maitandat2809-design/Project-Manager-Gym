<?php 
include_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate a random 8-character password
        $new_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($update_stmt->execute([$hashed_password, $user['id']])) {
            
            // Send Email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';     
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Security');
                $mail->addAddress($email, $user['name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "GymX: Your New Password";
                
                $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 15px;'>";
                $body .= "<h2 style='color: #0062ff; text-align: center;'>GymX Password Reset</h2>";
                $body .= "<p>Hello <strong>" . $user['name'] . "</strong>,</p>";
                $body .= "<p>We received a request to reset your password. Here is your new temporary password:</p>";
                $body .= "<div style='background: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; color: #1e293b; border-radius: 10px; margin: 20px 0; letter-spacing: 2px;'>$new_password</div>";
                $body .= "<p>Please log in using this password and change it in your profile settings immediately for security.</p>";
                $body .= "<p style='text-align: center;'><a href='http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/login.php' style='background: #0062ff; color: white; padding: 10px 25px; text-decoration: none; border-radius: 50px; font-weight: bold;'>Login Now</a></p>";
                $body .= "<p style='color: #64748b; font-size: 0.9rem; margin-top: 30px;'>If you did not request this, please contact support.</p>";
                $body .= "</div>";

                $mail->Body = $body;
                $mail->send();
                $message = __('temp_pw_sent');
            } catch (Exception $e) {
                $error = __('mail_send_error') . $mail->ErrorInfo;
            }
        } else {
            $error = __('fail_update_pw');
        }
    } else {
        $error = __('no_account_found');
    }
}
?>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('forgot_password_title'); ?></h2>
        <div class="underline"></div>
        <p style="margin-top: 10px; color: var(--text-muted);"><?php echo __('forgot_password_subtitle'); ?></p>
    </div>

    <?php if($error): ?>
        <p style="color: #ef4444; text-align: center; margin-bottom: 2rem; background: #fee2e2; padding: 1rem; border-radius: 15px; border: 1px solid #fecaca;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if($message): ?>
        <p style="color: #16a34a; text-align: center; margin-bottom: 2rem; background: #f0fdf4; padding: 1.5rem; border-radius: 20px; border: 1px solid #bbf7d0; line-height: 1.6;"><?php echo $message; ?></p>
        <div style="text-align: center;">
            <a href="/Gym Manager/auth/login.php" class="btn-register" style="padding: 1rem 2.5rem; display: inline-block;"><?php echo __('go_to_login'); ?></a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label><?php echo __('email_address'); ?></label>
                <input type="email" name="email" required placeholder="your-email@example.com">
            </div>
            <button type="submit" class="btn-auth"><?php echo __('send_new_password'); ?></button>
        </form>
        
        <div class="auth-switch">
            <?php echo __('remember_password'); ?> <a href="/Gym Manager/auth/login.php"><?php echo __('back_to_login'); ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

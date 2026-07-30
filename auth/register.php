<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!isset($_POST['agreement'])) {
        $error = __('agree_terms_error');
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = __('email_exists_error');
        } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $phone])) {
            
            // Send welcome email
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Training System');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = __('welcome_email_subject');
                
                $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                $body .= "<h2 style='color: #0062ff;'>" . __('welcome_email_h2') . "</h2>";
                $body .= "<p>" . sprintf(__('welcome_email_p1'), htmlspecialchars($name)) . "</p>";
                $body .= "<p>" . __('welcome_email_p2') . "</p>";
                $body .= "<p>" . __('welcome_email_acc_info') . "</p>";
                $body .= "<ul>";
                $body .= "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
                $body .= "<li><strong>" . __('phone_number') . ":</strong> " . htmlspecialchars($phone ? $phone : __('not_provided')) . "</li>";
                $body .= "</ul>";
                $body .= "<p>" . __('welcome_email_p3') . "</p>";
                $body .= "<p style='margin-top: 20px; font-size: 0.9em; color: #666;'>" . __('welcome_email_footer') . "</p>";
                $body .= "</div>";
 
                $mail->Body = $body;
                $mail->send();
            } catch (Exception $e) {
                // Log mail error if any
                error_log("Mail Error for Registration: " . $mail->ErrorInfo);
            }

            header("Location: /Gym Manager/auth/login.php?success=" . urlencode(__('registration_success')));
            exit();
        } else {
            $error = __('something_went_wrong');
        }
    }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('register'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if($error): ?>
        <p style="color: #ff4444; text-align: center; margin-bottom: 1rem;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if($success): ?>
        <p style="color: #00C851; text-align: center; margin-bottom: 1rem;"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label><?php echo __('full_name'); ?></label>
            <input type="text" name="name" required placeholder="<?php echo __('promo_name'); ?>">
        </div>
        <div class="form-group">
            <label><?php echo __('phone_number'); ?></label>
            <input type="text" name="phone" required placeholder="<?php echo __('phone_number'); ?> (e.g. 0912345678)" pattern="[0-9]+" title="<?php echo __('phone_invalid'); ?>">
        </div>
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" required placeholder="example@gmail.com">
        </div>
        <div class="form-group">
            <label><?php echo __('password'); ?></label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; margin-top: 1rem;">
            <input type="checkbox" name="agreement" required id="user_agreement" style="width: 18px; height: 18px; cursor: pointer;">
            <label for="user_agreement" style="font-size: 0.9rem; color: var(--text-main); cursor: pointer; user-select: none;">
                <?php echo __('agree_to_terms'); ?> <a href="/Gym Manager/pages/terms.php" style="color: var(--primary); text-decoration: underline;"><?php echo __('terms_of_service'); ?></a> <?php echo __('and'); ?> <a href="/Gym Manager/pages/privacy.php" style="color: var(--primary); text-decoration: underline;"><?php echo __('privacy_policy'); ?></a>.
            </label>
        </div>
        <button type="submit" class="btn-auth"><?php echo __('create_account'); ?></button>
    </form>
    
    <div class="auth-switch">
        <?php echo __('already_have_account'); ?> <a href="/Gym Manager/auth/login.php"><?php echo __('login_now'); ?></a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

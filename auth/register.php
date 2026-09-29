<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $clean_phone = preg_replace('/[^0-9]/', '', $phone);

    if (!isset($_POST['agreement'])) {
        $error = __('agree_terms_error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('email_invalid_format');
    } elseif (strlen($clean_phone) !== 10) {
        $error = __('phone_invalid');
    } else {
        $check_email = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
        $check_email->execute([$email]);
        
        $check_phone = $pdo->prepare("SELECT id FROM users WHERE (phone = ? OR phone = ?) AND phone IS NOT NULL AND phone != ''");
        $check_phone->execute([$phone, $clean_phone]);

        if ($check_email->fetch()) {
            $error = __('email_exists_error');
        } elseif ($check_phone->fetch()) {
            $error = __('phone_exists_error');
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $password, $phone])) {
                $user_id = $pdo->lastInsertId();

                // Auto log in the newly registered user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'user';
                
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
                    error_log("Mail Error for Registration: " . $mail->ErrorInfo);
                }

                header("Location: /Gym Manager/index.php");
                exit();
            } else {
                $error = __('something_went_wrong');
            }
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<style>
.input-feedback-msg {
    font-size: 0.82rem;
    font-weight: 600;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s ease;
}
.input-feedback-msg.error {
    color: #ef4444;
}
.input-feedback-msg.success {
    color: #10b981;
}
.input-feedback-msg.loading {
    color: #64748b;
}
input.input-error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
}
input.input-success {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
}
</style>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('register'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if($error): ?>
        <p style="color: #ff4444; text-align: center; margin-bottom: 1rem; font-weight: 600; background: #fef2f2; padding: 0.8rem; border-radius: 12px; border: 1px solid #fca5a5;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if($success): ?>
        <p style="color: #00C851; text-align: center; margin-bottom: 1rem; font-weight: 600;"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" id="registerForm" novalidate>
        <div class="form-group">
            <label><?php echo __('full_name'); ?></label>
            <input type="text" name="name" id="registerName" placeholder="<?php echo __('promo_name'); ?>">
            <div id="nameFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('phone_number'); ?></label>
            <input type="text" name="phone" id="registerPhone" placeholder="<?php echo __('phone_number'); ?> (e.g. 0912345678)">
            <div id="phoneFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" id="registerEmail" placeholder="example@gmail.com">
            <div id="emailFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('password'); ?></label>
            <input type="password" name="password" id="registerPassword" placeholder="••••••••">
            <div id="passwordFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; margin-top: 1rem;">
            <input type="checkbox" name="agreement" id="user_agreement" style="width: 18px; height: 18px; cursor: pointer;">
            <label for="user_agreement" style="font-size: 0.9rem; color: var(--text-main); cursor: pointer; user-select: none;">
                <?php echo __('agree_to_terms'); ?> <a href="/Gym Manager/pages/terms.php" style="color: var(--primary); text-decoration: underline;"><?php echo __('terms_of_service'); ?></a> <?php echo __('and'); ?> <a href="/Gym Manager/pages/privacy.php" style="color: var(--primary); text-decoration: underline;"><?php echo __('privacy_policy'); ?></a>.
            </label>
        </div>
        <button type="submit" class="btn-auth" id="registerSubmitBtn"><?php echo __('create_account'); ?></button>
    </form>
    
    <div class="auth-switch">
        <?php echo __('already_have_account'); ?> <a href="/Gym Manager/auth/login.php"><?php echo __('login_now'); ?></a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('registerName');
    const phoneInput = document.getElementById('registerPhone');
    const emailInput = document.getElementById('registerEmail');
    const passInput = document.getElementById('registerPassword');

    const nameFeedback = document.getElementById('nameFeedback');
    const phoneFeedback = document.getElementById('phoneFeedback');
    const emailFeedback = document.getElementById('emailFeedback');
    const passFeedback = document.getElementById('passwordFeedback');

    const registerForm = document.getElementById('registerForm');

    let phoneTimer = null;
    let emailTimer = null;

    const MSG_REQ_NAME = <?php echo json_encode(__('field_required_name')); ?>;
    const MSG_REQ_PHONE = <?php echo json_encode(__('field_required_phone')); ?>;
    const MSG_REQ_EMAIL = <?php echo json_encode(__('field_required_email')); ?>;
    const MSG_REQ_PASS = <?php echo json_encode(__('field_required_password')); ?>;
    const MSG_MIN_PASS = <?php echo json_encode(__('password_min_length')); ?>;
    const MSG_PASS_OK = <?php echo json_encode(__('password_valid')); ?>;
    const MSG_INVALID_EMAIL = <?php echo json_encode(__('email_invalid_format')); ?>;

    function validateName() {
        const val = nameInput.value.trim();
        if (!val) {
            nameFeedback.className = 'input-feedback-msg error';
            nameFeedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_REQ_NAME;
            nameInput.classList.add('input-error');
            nameInput.classList.remove('input-success');
            return false;
        } else {
            nameFeedback.innerHTML = '';
            nameInput.classList.remove('input-error');
            return true;
        }
    }

    function validatePassword() {
        const val = passInput.value;
        if (!val) {
            passFeedback.className = 'input-feedback-msg error';
            passFeedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_REQ_PASS;
            passInput.classList.add('input-error');
            passInput.classList.remove('input-success');
            return false;
        } else if (val.length < 6) {
            passFeedback.className = 'input-feedback-msg error';
            passFeedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_MIN_PASS;
            passInput.classList.add('input-error');
            passInput.classList.remove('input-success');
            return false;
        } else {
            passFeedback.className = 'input-feedback-msg success';
            passFeedback.innerHTML = '<i class="fas fa-check-circle"></i> ' + MSG_PASS_OK;
            passInput.classList.add('input-success');
            passInput.classList.remove('input-error');
            return true;
        }
    }

    function checkField(field, inputEl, feedbackEl, reqMsg) {
        const val = inputEl.value.trim();
        if (!val) {
            feedbackEl.className = 'input-feedback-msg error';
            feedbackEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + reqMsg;
            inputEl.classList.add('input-error');
            inputEl.classList.remove('input-success');
            inputEl.dataset.duplicate = 'false';
            return false;
        }

        if (field === 'email') {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(val)) {
                feedbackEl.className = 'input-feedback-msg error';
                feedbackEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_INVALID_EMAIL;
                inputEl.classList.add('input-error');
                inputEl.classList.remove('input-success');
                inputEl.dataset.duplicate = 'false';
                return false;
            }
        }

        feedbackEl.className = 'input-feedback-msg loading';
        feedbackEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kiểm tra...';

        return fetch('/Gym Manager/api/check_duplicate.php?field=' + field + '&value=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    feedbackEl.className = 'input-feedback-msg error';
                    feedbackEl.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
                    inputEl.classList.add('input-error');
                    inputEl.classList.remove('input-success');
                    inputEl.dataset.duplicate = 'true';
                    return false;
                } else if (data.valid === false) {
                    feedbackEl.className = 'input-feedback-msg error';
                    feedbackEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + data.message;
                    inputEl.classList.add('input-error');
                    inputEl.classList.remove('input-success');
                    inputEl.dataset.duplicate = 'false';
                    return false;
                } else {
                    feedbackEl.className = 'input-feedback-msg success';
                    feedbackEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    inputEl.classList.add('input-success');
                    inputEl.classList.remove('input-error');
                    inputEl.dataset.duplicate = 'false';
                    return true;
                }
            })
            .catch(err => {
                feedbackEl.innerHTML = '';
                inputEl.classList.remove('input-error', 'input-success');
                return true;
            });
    }

    if (nameInput) {
        nameInput.addEventListener('blur', validateName);
        nameInput.addEventListener('input', function() {
            if (nameInput.value.trim()) validateName();
        });
    }

    if (passInput) {
        passInput.addEventListener('blur', validatePassword);
        passInput.addEventListener('input', validatePassword);
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            clearTimeout(phoneTimer);
            phoneTimer = setTimeout(() => checkField('phone', phoneInput, phoneFeedback, MSG_REQ_PHONE), 350);
        });
        phoneInput.addEventListener('blur', function() {
            checkField('phone', phoneInput, phoneFeedback, MSG_REQ_PHONE);
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimer);
            emailTimer = setTimeout(() => checkField('email', emailInput, emailFeedback, MSG_REQ_EMAIL), 350);
        });
        emailInput.addEventListener('blur', function() {
            checkField('email', emailInput, emailFeedback, MSG_REQ_EMAIL);
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const isNameValid = validateName();
            const isPassValid = validatePassword();
            const isPhoneEmpty = !phoneInput.value.trim();
            const isEmailEmpty = !emailInput.value.trim();
            const agreementInput = document.getElementById('user_agreement');
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'vi') === 'vi';

            if (isPhoneEmpty) checkField('phone', phoneInput, phoneFeedback, MSG_REQ_PHONE);
            if (isEmailEmpty) checkField('email', emailInput, emailFeedback, MSG_REQ_EMAIL);

            if (!isNameValid) {
                e.preventDefault();
                showGymXAlert(MSG_REQ_NAME, isVi ? 'Thiếu Thông Tin' : 'Name Required', '👤');
                nameInput.focus();
                return false;
            }

            if (isPhoneEmpty || phoneInput.dataset.duplicate === 'true' || phoneInput.classList.contains('input-error')) {
                e.preventDefault();
                showGymXAlert(isVi ? 'Vui lòng kiểm tra lại số điện thoại (10 chữ số).' : 'Please enter a valid 10-digit phone number.', isVi ? 'Lỗi Số Điện Thoại' : 'Invalid Phone', '📱');
                phoneInput.focus();
                return false;
            }

            if (isEmailEmpty || emailInput.dataset.duplicate === 'true' || emailInput.classList.contains('input-error')) {
                e.preventDefault();
                showGymXAlert(isVi ? 'Vui lòng kiểm tra lại địa chỉ email.' : 'Please check your email address.', isVi ? 'Lỗi Email' : 'Invalid Email', '📧');
                emailInput.focus();
                return false;
            }

            if (!isPassValid) {
                e.preventDefault();
                showGymXAlert(MSG_MIN_PASS, isVi ? 'Lỗi Mật Khẩu' : 'Invalid Password', '🔒');
                passInput.focus();
                return false;
            }

            if (agreementInput && !agreementInput.checked) {
                e.preventDefault();
                showGymXAlert(isVi ? 'Bạn cần đồng ý với Điều khoản dịch vụ và Chính sách bảo mật.' : 'You must agree to the Terms of Service and Privacy Policy.', isVi ? 'Điều Khoản Dịch Vụ' : 'Terms Agreement', '📜');
                agreementInput.focus();
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


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

    $is_valid_gmail = filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[^@\s]+@gmail\.com$/i', $email);
    $is_valid_phone = preg_match('/^[0-9]{10}$/', $phone);

    if (!isset($_POST['agreement'])) {
        $error = __('agree_terms_error');
    } elseif (!$is_valid_gmail) {
        $error = $current_lang === 'vi' ? 'Email phải có định dạng @gmail.com.' : 'Email must use a @gmail.com address.';
    } elseif (!$is_valid_phone) {
        $error = $current_lang === 'vi' ? 'Số điện thoại phải gồm đúng 10 chữ số, không chứa chữ hoặc ký tự đặc biệt.' : 'Phone number must contain exactly 10 digits and no letters or special characters.';
    } else {
        $check_email = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
        $check_email->execute([$email]);
        
        $check_phone = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone IS NOT NULL AND phone != ''");
        $check_phone->execute([$phone]);

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

                // Automatically grant 7 days free training trial subscription
                try {
                    $stmt_plan = $pdo->prepare("SELECT id FROM membership_plans WHERE duration_days = 7 ORDER BY id DESC LIMIT 1");
                    $stmt_plan->execute();
                    $plan_row = $stmt_plan->fetch();

                    if (!$plan_row) {
                        $stmt_in_plan = $pdo->prepare("
                            INSERT INTO membership_plans (
                                name, name_vi, description, description_vi, price, duration_days,
                                features, features_vi, badge, badge_color, is_popular, is_active, sort_order, pt_sessions
                            ) VALUES (
                                '7 Days Free Trial',
                                '7 Ngày Tập Miễn Phí',
                                '7 days free training trial with full gym access and privileges.',
                                'Gói trải nghiệm 7 ngày tập luyện miễn phí với đầy đủ tiện ích phòng gym.',
                                0,
                                7,
                                'Full Gym Access for 7 Days|Free Locker & Shower|1 InBody Analysis|1-on-1 PT Consultation',
                                'Tập luyện toàn bộ khu vực trong 7 ngày|Tủ locker & phòng tắm nóng lạnh|1 lần đo chỉ số InBody miễn phí|Tư vấn lộ trình 1-1 cùng HLV',
                                '7 DAYS FREE',
                                '#00b4ff',
                                0,
                                0,
                                0,
                                1
                            )
                        ");
                        $stmt_in_plan->execute();
                        $trial_plan_id = (int)$pdo->lastInsertId();
                    } else {
                        $trial_plan_id = (int)$plan_row['id'];
                    }

                    $start_date = date('Y-m-d');
                    $end_date   = date('Y-m-d', strtotime('+7 days'));

                    $stmt_sub = $pdo->prepare("
                        INSERT INTO member_subscriptions 
                        (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, note, admin_seen, pt_sessions_total, pt_sessions_used)
                        VALUES (?, ?, ?, ?, 0, 'cod', 'active', ?, 1, 1, 0)
                    ");
                    $stmt_sub->execute([
                        $user_id,
                        $trial_plan_id,
                        $start_date,
                        $end_date,
                        'Kích hoạt 7 ngày tập luyện miễn phí (7 Days Free Training)'
                    ]);
                } catch (Exception $sub_ex) {
                    error_log("Trial subscription error: " . $sub_ex->getMessage());
                }
                
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
                    $body .= "<li><strong>" . __('active_membership_title') . ":</strong> 7 " . __('days_remaining') . " " . __('promo_free') . " (7 Days Free Training)</li>";
                    $body .= "</ul>";
                    $body .= "<p>" . __('welcome_email_p3') . "</p>";
                    $body .= "<p style='margin-top: 20px; font-size: 0.9em; color: #666;'>" . __('welcome_email_footer') . "</p>";
                    $body .= "</div>";
     
                    $mail->Body = $body;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mail Error for Registration: " . $mail->ErrorInfo);
                }

                $_SESSION['registered_trial_success'] = true;
                header("Location: /Gym Manager/dashboards/profile.php?welcome_trial=1");
                exit();
            } else {
                $error = __('something_went_wrong');
            }
        }
    }
}

include_once __DIR__ . '/../includes/header.php';

$pref_name = trim($_POST['name'] ?? $_GET['name'] ?? '');
$pref_phone = trim($_POST['phone'] ?? $_GET['phone'] ?? '');
$is_promo = (isset($_GET['promo']) && $_GET['promo'] === '7days_free') || (isset($_POST['promo']) && $_POST['promo'] === '7days_free') || (!empty($pref_name) && !empty($pref_phone));
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

    <?php if ($is_promo): ?>
        <div style="background: linear-gradient(135deg, #00b4ff, #0062ff); color: white; padding: 14px 18px; border-radius: 14px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 14px; box-shadow: 0 6px 20px rgba(0, 180, 255, 0.28);">
            <div style="font-size: 1.8rem; background: rgba(255,255,255,0.22); width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">🎁</div>
            <div>
                <strong style="display: block; font-size: 0.98rem; font-weight: 800; letter-spacing: 0.3px;"><?php echo __('promo_claim_banner_title'); ?></strong>
                <span style="font-size: 0.83rem; opacity: 0.95;"><?php echo __('promo_claim_banner_desc'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="registerForm" novalidate>
        <input type="hidden" name="promo" value="<?php echo $is_promo ? '7days_free' : ''; ?>">
        <div class="form-group">
            <label><?php echo __('full_name'); ?></label>
            <input type="text" name="name" id="registerName" value="<?php echo htmlspecialchars($pref_name); ?>" placeholder="<?php echo __('promo_name'); ?>">
            <div id="nameFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('phone_number'); ?></label>
            <input type="tel" name="phone" id="registerPhone" value="<?php echo htmlspecialchars($pref_phone); ?>" placeholder="<?php echo __('phone_number'); ?> (e.g. 0912345678)" inputmode="numeric" minlength="10" maxlength="10" pattern="[0-9]{10}" required>
            <div id="phoneFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" id="registerEmail" placeholder="example@gmail.com" pattern="[A-Za-z0-9._%+-]+@gmail\.com" title="Use a valid @gmail.com address" required>
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
    const MSG_GMAIL_ONLY = <?php echo json_encode($current_lang === 'vi' ? 'Email phải có định dạng @gmail.com.' : 'Email must use a @gmail.com address.'); ?>;
    const MSG_PHONE_FORMAT = <?php echo json_encode($current_lang === 'vi' ? 'Số điện thoại phải gồm đúng 10 chữ số.' : 'Phone number must contain exactly 10 digits.'); ?>;

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
            const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            if (!emailRegex.test(val)) {
                feedbackEl.className = 'input-feedback-msg error';
                feedbackEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_GMAIL_ONLY;
                inputEl.classList.add('input-error');
                inputEl.classList.remove('input-success');
                inputEl.dataset.duplicate = 'false';
                return false;
            }
        }

        if (field === 'phone' && !/^[0-9]{10}$/.test(val)) {
            feedbackEl.className = 'input-feedback-msg error';
            feedbackEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_PHONE_FORMAT;
            inputEl.classList.add('input-error');
            inputEl.classList.remove('input-success');
            inputEl.dataset.duplicate = 'false';
            return false;
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
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'en') === 'vi';

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

    // Auto validate prefilled fields and focus next empty field
    if (nameInput && nameInput.value.trim()) {
        validateName();
    }
    if (phoneInput && phoneInput.value.trim()) {
        checkField('phone', phoneInput, phoneFeedback, MSG_REQ_PHONE);
    }
    if (nameInput && nameInput.value.trim() && phoneInput && phoneInput.value.trim() && emailInput && !emailInput.value.trim()) {
        setTimeout(function() {
            emailInput.focus();
        }, 300);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


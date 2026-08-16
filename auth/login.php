<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config/db.php';

$error = '';
$show_register_link = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = __('account_not_exist_register');
        $show_register_link = true;
    } elseif (!password_verify($password, $user['password'])) {
        $error = __('incorrect_password');
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        header("Location: /Gym Manager/index.php");
        exit();
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
.input-feedback-msg.info {
    color: #3b82f6;
}
.input-feedback-msg.success {
    color: #10b981;
}
input.input-error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
}
</style>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('login'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if($error): ?>
        <div style="color: #ef4444; text-align: center; margin-bottom: 1.5rem; font-weight: 600; background: #fef2f2; padding: 0.9rem 1.2rem; border-radius: 12px; border: 1px solid #fca5a5; font-size: 0.95rem; line-height: 1.5;">
            <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i><?php echo htmlspecialchars($error); ?>
            <?php if($show_register_link): ?>
                <div style="margin-top: 8px;">
                    <a href="/Gym Manager/auth/register.php" style="color: var(--primary); text-decoration: underline; font-weight: 700;">
                        <?php echo __('register_now'); ?> &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <p style="color: #00C851; background: rgba(0,200,81,0.1); padding: 1rem; border-radius: 10px; text-align: center; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </p>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" id="loginEmail" required placeholder="example@gmail.com" oninvalid="if(this.validity.valueMissing){this.setCustomValidity('<?php echo addslashes(__('field_required_email')); ?>');}else{this.setCustomValidity('<?php echo addslashes(__('email_invalid_format')); ?>');}" oninput="this.setCustomValidity('')">
            <div id="loginEmailFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('password'); ?></label>
            <input type="password" name="password" id="loginPassword" required placeholder="••••••••" oninvalid="this.setCustomValidity('<?php echo addslashes(__('field_required_password')); ?>')" oninput="this.setCustomValidity('')">
            <div id="loginPassFeedback" class="input-feedback-msg"></div>
        </div>
        <button type="submit" class="btn-auth"><?php echo __('login'); ?></button>
    </form>
    
    <div class="auth-switch">
        <a href="/Gym Manager/auth/forgot_password.php" style="font-size: 0.9rem; display: block; margin-bottom: 1rem; opacity: 0.8;"><?php echo __('forgot_password_link'); ?></a>
        <?php echo __('dont_have_account'); ?> <a href="/Gym Manager/auth/register.php"><?php echo __('register_now'); ?></a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('loginEmail');
    const emailFeedback = document.getElementById('loginEmailFeedback');
    let timer = null;

    const MSG_NOT_EXIST = <?php echo json_encode(__('account_not_exist_register')); ?>;
    const MSG_INVALID_EMAIL = <?php echo json_encode(__('email_invalid_format')); ?>;

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            clearTimeout(timer);
            const val = emailInput.value.trim();
            if (!val) {
                emailFeedback.innerHTML = '';
                emailInput.classList.remove('input-error');
                return;
            }
            
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(val)) {
                emailFeedback.className = 'input-feedback-msg error';
                emailFeedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + MSG_INVALID_EMAIL;
                emailInput.classList.add('input-error');
                return;
            }

            timer = setTimeout(function() {
                fetch('/Gym Manager/api/check_duplicate.php?field=email&value=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && !data.exists) {
                            emailFeedback.className = 'input-feedback-msg error';
                            emailFeedback.innerHTML = '<i class="fas fa-user-slash"></i> ' + MSG_NOT_EXIST + ' <a href="/Gym Manager/auth/register.php" style="text-decoration:underline;margin-left:4px;font-weight:700;color:inherit;"><?php echo __('register_now'); ?> &rarr;</a>';
                            emailInput.classList.add('input-error');
                        } else {
                            emailFeedback.innerHTML = '';
                            emailInput.classList.remove('input-error');
                        }
                    })
                    .catch(() => {});
            }, 350);
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

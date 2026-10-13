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
    } elseif (($user['status'] ?? 'active') !== 'active') {
        $error = 'This account is inactive. Please contact an administrator.';
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

    <form method="POST" id="loginForm" novalidate>
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" id="loginEmail" placeholder="example@gmail.com">
            <div id="loginEmailFeedback" class="input-feedback-msg"></div>
        </div>
        <div class="form-group">
            <label><?php echo __('password'); ?></label>
            <div style="position: relative;">
                <input type="password" name="password" id="loginPassword" placeholder="••••••••" style="padding-right: 44px;">
                <button type="button" id="togglePassBtn" onclick="togglePasswordMask()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 1.1rem; padding: 4px;" title="Show or hide password">
                    <i class="fas fa-eye" id="togglePassIcon"></i>
                </button>
            </div>
            <div id="loginPassFeedback" class="input-feedback-msg"></div>
        </div>

        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.5rem; font-size: 0.88rem;">
            <a href="/Gym Manager/auth/forgot_password.php" style="color: var(--primary); font-weight: 700; text-decoration: none; font-size: 0.85rem;"><?php echo __('forgot_password_link'); ?></a>
        </div>

        <button type="submit" class="btn-auth"><?php echo __('login'); ?></button>
    </form>
    
    <div class="auth-switch" style="margin-top: 1.5rem;">
        <?php echo __('dont_have_account'); ?> <a href="/Gym Manager/auth/register.php"><?php echo __('register_now'); ?></a>
    </div>
</div>

<script>
function togglePasswordMask() {
    const passInput = document.getElementById('loginPassword');
    const icon = document.getElementById('togglePassIcon');
    if (!passInput) return;

    if (passInput.type === 'password') {
        passInput.type = 'text';
        icon.className = 'fas fa-eye-slash';
        icon.style.color = 'var(--primary)';
    } else {
        passInput.type = 'password';
        icon.className = 'fas fa-eye';
        icon.style.color = '#64748b';
    }
}

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
            }, 400);
        });
    }

    const loginForm = document.getElementById('loginForm');
    const passInput = document.getElementById('loginPassword');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const emailVal = emailInput ? emailInput.value.trim() : '';
            const passVal = passInput ? passInput.value.trim() : '';

            if (!emailVal) {
                e.preventDefault();
                showGymXAlert(
                    'Please enter your email address.',
                    'Email Required',
                    '📧'
                );
                if (emailInput) emailInput.focus();
                return false;
            }

            if (!passVal) {
                e.preventDefault();
                showGymXAlert(
                    'Please enter your password.',
                    'Password Required',
                    '🔒'
                );
                if (passInput) passInput.focus();
                return false;
            }

        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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
.demo-pill-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 11px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    transition: all 0.25s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.demo-pill-btn:hover {
    border-color: var(--gold);
    background: #fffdf5;
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(201, 168, 76, 0.18);
}
.demo-pill-btn.active {
    border-color: var(--primary);
    background: #f0f6ff;
    box-shadow: 0 0 0 2px rgba(0, 98, 255, 0.25);
}
.demo-pill-btn .role-icon {
    font-size: 1.15rem;
    flex-shrink: 0;
}
.demo-pill-btn .role-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.demo-pill-btn .role-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1.25;
}
.demo-pill-btn .role-email {
    font-size: 0.68rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('login'); ?></h2>
        <div class="underline"></div>
    </div>

    <!-- Quick Demo Accounts Selector Card -->
    <div class="demo-accounts-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(26, 39, 68, 0.03), rgba(201, 168, 76, 0.08)); border: 1px solid rgba(201, 168, 76, 0.35); border-radius: 20px; padding: 1.2rem 1.3rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
            <span style="font-weight: 800; font-size: 0.88rem; color: var(--primary); display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fas fa-key" style="color: var(--gold);"></i> <?php echo __('demo_accounts_hint'); ?>
            </span>
            <span style="font-size: 0.75rem; color: #475569; background: #ffffff; padding: 3px 10px; border-radius: 20px; border: 1px solid #cbd5e1; font-weight: 700; shadow: 0 1px 3px rgba(0,0,0,0.05);">
                Pass: <b style="color: var(--primary);">123456</b>
            </span>
        </div>
        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.8rem; margin-top: 0; line-height: 1.4;">
            <?php echo __('demo_click_to_fill'); ?>
        </p>
        <div class="demo-account-pills" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('lamtuoi155@gmail.com', '123456', this)" title="Admin Account">
                <span class="role-icon">👑</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_admin'); ?></span>
                    <span class="role-email">lamtuoi155@gmail...</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('managergym283@gmail.com', '123456', this)" title="Manager Account">
                <span class="role-icon">💼</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_manager'); ?></span>
                    <span class="role-email">managergym283@...</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('thanhptggbc220216@fpt.edu.vn', '123456', this)" title="Coach / PT Account">
                <span class="role-icon">🏋️</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_coach'); ?></span>
                    <span class="role-email">thanhptggbc2202...</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('maitandat2809@gmail.com', '123456', this)" title="User Account">
                <span class="role-icon">👤</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_user'); ?></span>
                    <span class="role-email">maitandat2809@g...</span>
                </div>
            </button>
        </div>
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
function fillDemoAcc(email, pass, btnElement) {
    const emailInput = document.getElementById('loginEmail');
    const passInput = document.getElementById('loginPassword');
    
    if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = pass;
        
        emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        passInput.dispatchEvent(new Event('input', { bubbles: true }));
        
        document.querySelectorAll('.demo-pill-btn').forEach(b => b.classList.remove('active'));
        if (btnElement) {
            btnElement.classList.add('active');
        }

        emailInput.style.transition = 'all 0.3s ease';
        passInput.style.transition = 'all 0.3s ease';
        emailInput.style.borderColor = 'var(--gold)';
        passInput.style.borderColor = 'var(--gold)';
        setTimeout(() => {
            emailInput.style.borderColor = '';
            passInput.style.borderColor = '';
        }, 500);
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
            }, 350);
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

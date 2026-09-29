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

    <!-- Saved Accounts Section (Dynamic LocalStorage) -->
    <div id="savedAccountsContainer" style="display: none; margin-bottom: 1.5rem; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem;">
            <span style="font-weight: 800; font-size: 0.88rem; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-user-shield" style="color: #10b981;"></i> <?php echo $current_lang === 'vi' ? 'Tài Khoản Đã Lưu Trên Thiết Bị' : 'Saved Accounts on Device'; ?>
            </span>
            <button type="button" onclick="clearSavedAccounts()" style="background: none; border: none; color: #ef4444; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                <?php echo $current_lang === 'vi' ? 'Xóa tất cả' : 'Clear All'; ?>
            </button>
        </div>
        <div id="savedAccountsList" style="display: flex; flex-direction: column; gap: 8px;"></div>
    </div>

    <!-- Quick Demo Accounts Selector Card -->
    <div class="demo-accounts-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(26, 39, 68, 0.03), rgba(201, 168, 76, 0.08)); border: 1px solid rgba(201, 168, 76, 0.35); border-radius: 20px; padding: 1.2rem 1.3rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
            <span style="font-weight: 800; font-size: 0.88rem; color: var(--primary); display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fas fa-key" style="color: var(--gold);"></i> <?php echo __('demo_accounts_hint'); ?>
            </span>
            <span style="font-size: 0.75rem; color: #475569; background: #ffffff; padding: 3px 10px; border-radius: 20px; border: 1px solid #cbd5e1; font-weight: 700; shadow: 0 1px 3px rgba(0,0,0,0.05);">
                Pass: <b style="color: var(--primary);">•••••• (123456)</b>
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
                    <span class="role-email">lamt***5@gmail.com</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('managergym283@gmail.com', '123456', this)" title="Manager Account">
                <span class="role-icon">💼</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_manager'); ?></span>
                    <span class="role-email">man***3@gmail.com</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('thanhptggbc220216@fpt.edu.vn', '123456', this)" title="Coach / PT Account">
                <span class="role-icon">🏋️</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_coach'); ?></span>
                    <span class="role-email">tha***6@fpt.edu.vn</span>
                </div>
            </button>

            <button type="button" class="demo-pill-btn" onclick="fillDemoAcc('maitandat2809@gmail.com', '123456', this)" title="User Account">
                <span class="role-icon">👤</span>
                <div class="role-info">
                    <span class="role-title"><?php echo __('role_user'); ?></span>
                    <span class="role-email">mai***9@gmail.com</span>
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
                <button type="button" id="togglePassBtn" onclick="togglePasswordMask()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 1.1rem; padding: 4px;" title="Ẩn/Hiện mật khẩu">
                    <i class="fas fa-eye" id="togglePassIcon"></i>
                </button>
            </div>
            <div id="loginPassFeedback" class="input-feedback-msg"></div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.88rem;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #475569; font-weight: 600; margin: 0; user-select: none;">
                <input type="checkbox" id="rememberAccount" checked style="width: 17px; height: 17px; accent-color: var(--primary); cursor: pointer;">
                <span><?php echo $current_lang === 'vi' ? 'Ghi nhớ tài khoản trên thiết bị này' : 'Remember account on this device'; ?></span>
            </label>
            <a href="/Gym Manager/auth/forgot_password.php" style="color: var(--primary); font-weight: 700; text-decoration: none; font-size: 0.85rem;"><?php echo __('forgot_password_link'); ?></a>
        </div>

        <button type="submit" class="btn-auth"><?php echo __('login'); ?></button>
    </form>
    
    <div class="auth-switch" style="margin-top: 1.5rem;">
        <?php echo __('dont_have_account'); ?> <a href="/Gym Manager/auth/register.php"><?php echo __('register_now'); ?></a>
    </div>
</div>

<script>
function maskEmail(email) {
    if (!email || !email.includes('@')) return email;
    const parts = email.split('@');
    const name = parts[0];
    const domain = parts[1];
    if (name.length <= 3) {
        return name[0] + '***@' + domain;
    }
    return name.substring(0, 3) + '***' + name.substring(name.length - 1) + '@' + domain;
}

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

function getSavedAccounts() {
    try {
        const data = localStorage.getItem('gymx_saved_accounts');
        return data ? JSON.parse(data) : [];
    } catch(e) {
        return [];
    }
}

function saveAccount(email, pass = '123456') {
    if (!email) return;
    let list = getSavedAccounts();
    list = list.filter(item => item.email.toLowerCase() !== email.toLowerCase());
    list.unshift({ email: email, pass: pass, date: new Date().toLocaleDateString() });
    if (list.length > 5) list = list.slice(0, 5);
    localStorage.setItem('gymx_saved_accounts', JSON.stringify(list));
    renderSavedAccounts();
}

function removeSavedAccount(email, event) {
    if (event) event.stopPropagation();
    let list = getSavedAccounts();
    list = list.filter(item => item.email.toLowerCase() !== email.toLowerCase());
    localStorage.setItem('gymx_saved_accounts', JSON.stringify(list));
    renderSavedAccounts();
}

function clearSavedAccounts() {
    localStorage.removeItem('gymx_saved_accounts');
    renderSavedAccounts();
}

function renderSavedAccounts() {
    const container = document.getElementById('savedAccountsContainer');
    const listEl = document.getElementById('savedAccountsList');
    if (!container || !listEl) return;

    const list = getSavedAccounts();
    if (list.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    listEl.innerHTML = '';

    list.forEach(item => {
        const masked = maskEmail(item.email);
        const div = document.createElement('div');
        div.style.cssText = 'display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 12px; cursor: pointer; transition: all 0.2s ease;';
        div.onmouseover = function() { this.style.borderColor = 'var(--gold)'; this.style.background = '#fffdf5'; };
        div.onmouseout = function() { this.style.borderColor = '#e2e8f0'; this.style.background = '#f8fafc'; };
        div.onclick = function() { fillDemoAcc(item.email, item.pass || '123456', null); };

        div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.1rem;">👤</span>
                <div>
                    <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b;">${masked}</div>
                    <div style="font-size: 0.72rem; color: #64748b;">${item.email} (Đã lưu)</div>
                </div>
            </div>
            <button type="button" onclick="removeSavedAccount('${item.email}', event)" style="background: none; border: none; color: #94a3b8; font-size: 0.88rem; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'" title="Xóa tài khoản này">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        listEl.appendChild(div);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    renderSavedAccounts();

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
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'vi') === 'vi';
            const rememberChk = document.getElementById('rememberAccount');

            if (!emailVal) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập địa chỉ Email.' : 'Please enter your email address.',
                    isVi ? 'Thiếu Thông Tin' : 'Email Required',
                    '📧'
                );
                if (emailInput) emailInput.focus();
                return false;
            }

            if (!passVal) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập mật khẩu.' : 'Please enter your password.',
                    isVi ? 'Thiếu Thông Tin' : 'Password Required',
                    '🔒'
                );
                if (passInput) passInput.focus();
                return false;
            }

            if (rememberChk && rememberChk.checked) {
                saveAccount(emailVal, passVal);
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        header("Location: /Gym Manager/index.php");
        exit();
    } else {
        $error = __('incorrect_email_password');
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="section-title">
        <h2><?php echo __('login'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if($error): ?>
        <p style="color: #ff4444; text-align: center; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <p style="color: #00C851; background: rgba(0,200,81,0.1); padding: 1rem; border-radius: 10px; text-align: center; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label><?php echo __('email'); ?></label>
            <input type="email" name="email" required placeholder="example@gmail.com">
        </div>
        <div class="form-group">
            <label><?php echo __('password'); ?></label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn-auth"><?php echo __('login'); ?></button>
    </form>
    
    <div class="auth-switch">
        <a href="/Gym Manager/auth/forgot_password.php" style="font-size: 0.9rem; display: block; margin-bottom: 1rem; opacity: 0.8;"><?php echo __('forgot_password_link'); ?></a>
        <?php echo __('dont_have_account'); ?> <a href="/Gym Manager/auth/register.php"><?php echo __('register_now'); ?></a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

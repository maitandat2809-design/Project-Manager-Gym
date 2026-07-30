<?php 
include_once __DIR__ . '/../includes/header.php';

$message = '';
$error = '';
$valid_token = false;

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];

    // Verify token
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$email, $token]);
    $reset_request = $stmt->fetch();

    if ($reset_request) {
        $valid_token = true;
    } else {
        $error = "This reset link is invalid or has expired.";
    }
} else {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();

            // Update user password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);

            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);

            $pdo->commit();
            $message = "Password updated successfully! You can now <a href='login.php' style='color: var(--primary); font-weight: 700;'>Login</a>.";
            $valid_token = false; // Hide form
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="auth-container">
    <div class="section-title">
        <h2>Set New Password</h2>
        <div class="underline"></div>
        <p style="margin-top: 10px; color: var(--text-muted);">Please enter your new password below.</p>
    </div>

    <?php if($error): ?>
        <p style="color: #ef4444; text-align: center; margin-bottom: 2rem; background: #fee2e2; padding: 1rem; border-radius: 15px; border: 1px solid #fecaca;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if($message): ?>
        <p style="color: #16a34a; text-align: center; margin-bottom: 2rem; background: #f0fdf4; padding: 1.5rem; border-radius: 20px; border: 1px solid #bbf7d0; line-height: 1.6;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if($valid_token): ?>
    <form method="POST">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" required placeholder="••••••••" minlength="6">
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required placeholder="••••••••" minlength="6">
        </div>
        <button type="submit" class="btn-auth">Update Password</button>
    </form>
    <?php endif; ?>
    
    <div class="auth-switch">
        Back to <a href="/Gym Manager/auth/login.php">Login</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

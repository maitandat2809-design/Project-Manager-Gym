<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php';
?>

<div style="padding: 4rem 2rem; max-width: 900px; margin: 2rem auto 4rem; background: white; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 3rem; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900; letter-spacing: -1px; margin-bottom: 1rem;">Privacy Policy</h1>
        <div style="width: 60px; height: 6px; background: var(--grad-primary); margin: 0 auto; border-radius: 10px;"></div>
        <p style="color: var(--text-muted); margin-top: 1rem;">Last updated: <?php echo date('F d, Y'); ?></p>
    </div>

    <div style="color: var(--text-main); line-height: 1.8; font-size: 1.05rem;">
        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">1. Information We Collect</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">When you register an account or interact with GymX, we may collect personal information such as your full name, email address, phone number, physical address, and payment transaction details. We also collect usage data to improve our services.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">2. How We Use Your Information</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">We use your personal information to:</p>
        <ul style="margin-bottom: 1.5rem; color: var(--text-muted); padding-left: 1.5rem;">
            <li style="margin-bottom: 0.5rem;">Create and manage your GymX account.</li>
            <li style="margin-bottom: 0.5rem;">Process your shop orders and schedule personal training sessions.</li>
            <li style="margin-bottom: 0.5rem;">Send important administrative emails, updates, and promotional content (you can opt out at any time).</li>
        </ul>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">3. Data Sharing & Third Parties</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">GymX will NEVER sell or rent your personal information to third parties. We may share your data only with trusted service providers (like payment processors and email services) strictly necessary to operate our platform securely.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">4. Data Security</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Your account is protected by a password hashed with strong encryption algorithms. While we strive to use commercially acceptable means to protect your personal information, remember that no method of transmission over the Internet is 100% secure.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">5. Your Privacy Rights</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">You have the right to access, update, or delete your personal information at any time. If you wish to completely remove your data from our servers, please contact GymX administrative support.</p>

        <div style="margin-top: 3rem; text-align: center;">
            <a href="/Gym Manager/auth/register.php" class="btn-auth" style="display: inline-block; padding: 1rem 2.5rem; width: auto; border-radius: 30px; text-decoration: none;">Back to Registration</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

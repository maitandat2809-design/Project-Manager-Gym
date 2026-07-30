<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php';
?>

<div style="padding: 4rem 2rem; max-width: 900px; margin: 2rem auto 4rem; background: white; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 3rem; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900; letter-spacing: -1px; margin-bottom: 1rem;">Terms of Service</h1>
        <div style="width: 60px; height: 6px; background: var(--grad-primary); margin: 0 auto; border-radius: 10px;"></div>
        <p style="color: var(--text-muted); margin-top: 1rem;">Last updated: <?php echo date('F d, Y'); ?></p>
    </div>

    <div style="color: var(--text-main); line-height: 1.8; font-size: 1.05rem;">
        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">1. Acceptance of Terms</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">By accessing and registering an account on the GymX platform, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">2. User Conduct & Facility Rules</h3>
        <ul style="margin-bottom: 1.5rem; color: var(--text-muted); padding-left: 1.5rem;">
            <li style="margin-bottom: 0.5rem;">Members must treat all gym equipment, staff, and fellow members with respect.</li>
            <li style="margin-bottom: 0.5rem;">Proper athletic attire and footwear are required at all times within the facility.</li>
            <li style="margin-bottom: 0.5rem;">You are strictly prohibited from sharing your account credentials with third parties.</li>
        </ul>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">3. Memberships, Bookings & Shop Purchases</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">All class and personal training bookings must be canceled at least 24 hours in advance. Failure to do so may result in a cancellation fee. Products purchased through the GymX Shop are non-refundable unless verified as defective upon receipt.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">4. Health & Safety Liability</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Consult with a physician before beginning any new exercise program. GymX is not liable for any injuries, health complications, or loss of personal property occurring within the premises, except where caused by our gross negligence.</p>

        <h3 style="color: var(--primary); margin-top: 2rem; margin-bottom: 1rem; font-size: 1.5rem;">5. Account Termination</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">GymX reserves the right to suspend or terminate your account without notice if you violate any of these Terms of Service or engage in conduct deemed harmful to the GymX community.</p>

        <div style="margin-top: 3rem; text-align: center;">
            <a href="/Gym Manager/auth/register.php" class="btn-auth" style="display: inline-block; padding: 1rem 2.5rem; width: auto; border-radius: 30px; text-decoration: none;">Back to Registration</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// Secure page - check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 120px 10%; min-height: 80vh;">
    <div class="section-title">
        <h2 style="font-size: 3rem;">HELP & <span style="background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">SUPPORT</span></h2>
        <div class="underline"></div>
        <p style="color: var(--text-muted); font-size: 1.1rem;">How can we assist you today, <?php echo htmlspecialchars($_SESSION['user_name']); ?>?</p>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
        <div class="card" style="padding: 2.5rem; text-align: center;">
            <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem;">
                <i class="fas fa-phone-alt"></i>
            </div>
            <h3 class="card-title">Hotline Hot</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Urgent issue? Contact our Admin directly via our 24/7 emergency hotline.</p>
            <a href="tel:0966456789" class="btn-auth" style="display: block; text-decoration: none; line-height: 1.4; padding-top: 1.1rem;">Call Now</a>
        </div>

        <!-- Support Card 2: Email Us -->
        <div class="card" style="padding: 2.5rem; text-align: center;">
            <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h3 class="card-title">Email Support</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Send us your detailed inquiry and we will get back to you within 2-4 business hours.</p>
            <a href="mailto:support@gymx.com" class="btn-card" style="display: inline-block;">Email Now</a>
        </div>

        <!-- Support Card 3: FAQs -->
        <div class="card" style="padding: 2.5rem; text-align: center;">
            <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem;">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3 class="card-title">Knowledge Base</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Browse our extensive FAQ and help articles to find quick answers to common questions.</p>
            <a href="/Gym Manager/pages/about.php" class="btn-card" style="display: inline-block;">View FAQs</a>
        </div>
    </div>

    <!-- Contact Form Section -->
    <div class="auth-container" style="max-width: 800px; margin-top: 5rem;">
        <h2 style="text-align: center; margin-bottom: 2.5rem;">Send us a message</h2>
        <form action="#" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" placeholder="What is this regarding?" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none;">
                        <option>General Inquiry</option>
                        <option>Billing & Payment</option>
                        <option>Membership Plans</option>
                        <option>Coach Feedback</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Message</label>
                <textarea style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 150px;" placeholder="How can we help you?"></textarea>
            </div>
            <button type="submit" class="btn-auth">Submit Request</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = __('careers_apply_success');
}
?>

<div class="careers-page-container" style="padding: 120px 2rem 4rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h1 style="font-size: 3.5rem; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900; letter-spacing: -1px; margin-bottom: 1rem;"><?php echo __('careers_title'); ?></h1>
        <div style="width: 80px; height: 6px; background: var(--grad-primary); margin: 0 auto; border-radius: 10px;"></div>
        <p style="color: var(--text-muted); margin-top: 1.5rem; font-size: 1.2rem;"><?php echo __('careers_subtitle'); ?></p>
    </div>

    <!-- Intro Section -->
    <div style="background: white; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); padding: 3rem; margin-bottom: 4rem; text-align: center;">
        <p style="color: var(--text-main); font-size: 1.2rem; line-height: 1.8; max-width: 800px; margin: 0 auto;"><?php echo __('careers_intro'); ?></p>
    </div>

    <!-- Positions Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2.5rem; margin-bottom: 5rem;">
        <!-- Position 1 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1.5rem;"><i class="fas fa-dumbbell"></i></div>
                <h3 style="color: var(--text-main); font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;"><?php echo __('careers_pos1_title'); ?></h3>
                <p style="color: var(--text-muted); line-height: 1.7; font-size: 0.95rem; margin-bottom: 1.5rem;"><?php echo __('careers_pos1_desc'); ?></p>
            </div>
            <a href="#apply-form" class="btn-card" style="align-self: flex-start; text-decoration: none;"><?php echo __('careers_apply_btn'); ?></a>
        </div>

        <!-- Position 2 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1.5rem;"><i class="fas fa-om"></i></div>
                <h3 style="color: var(--text-main); font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;"><?php echo __('careers_pos2_title'); ?></h3>
                <p style="color: var(--text-muted); line-height: 1.7; font-size: 0.95rem; margin-bottom: 1.5rem;"><?php echo __('careers_pos2_desc'); ?></p>
            </div>
            <a href="#apply-form" class="btn-card" style="align-self: flex-start; text-decoration: none;"><?php echo __('careers_apply_btn'); ?></a>
        </div>

        <!-- Position 3 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1.5rem;"><i class="fas fa-concierge-bell"></i></div>
                <h3 style="color: var(--text-main); font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem;"><?php echo __('careers_pos3_title'); ?></h3>
                <p style="color: var(--text-muted); line-height: 1.7; font-size: 0.95rem; margin-bottom: 1.5rem;"><?php echo __('careers_pos3_desc'); ?></p>
            </div>
            <a href="#apply-form" class="btn-card" style="align-self: flex-start; text-decoration: none;"><?php echo __('careers_apply_btn'); ?></a>
        </div>
    </div>

    <!-- Application Form -->
    <div id="apply-form" class="auth-container reveal" style="max-width: 800px; margin: 0 auto; background: white; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); padding: 3rem;">
        <h2 style="text-align: center; margin-bottom: 2.5rem; font-size: 2rem; font-weight: 900;"><?php echo __('careers_apply_btn'); ?></h2>
        
        <?php if ($message): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 1.5rem; border-radius: 15px; text-align: center; margin-bottom: 2rem; font-weight: 600; font-size: 1.1rem;">
                <i class="fas fa-check-circle" style="margin-right: 0.5rem; font-size: 1.3rem;"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="#apply-form" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label style="font-weight: 700; margin-bottom: 0.5rem; display: block;"><?php echo __('promo_name'); ?></label>
                    <input type="text" placeholder="John Doe" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; margin-bottom: 0.5rem; display: block;">Email</label>
                    <input type="email" placeholder="example@email.com" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="form-group">
                    <label style="font-weight: 700; margin-bottom: 0.5rem; display: block;"><?php echo __('promo_phone'); ?></label>
                    <input type="text" placeholder="0987654321" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 700; margin-bottom: 0.5rem; display: block;">Position</label>
                    <select required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none;">
                        <option><?php echo __('careers_pos1_title'); ?></option>
                        <option><?php echo __('careers_pos2_title'); ?></option>
                        <option><?php echo __('careers_pos3_title'); ?></option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: 700; margin-bottom: 0.5rem; display: block;">Introduce Yourself / Cover Letter</label>
                <textarea style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 150px;" placeholder="Tell us why you want to join GymX..."></textarea>
            </div>
            <button type="submit" class="btn-auth" style="margin-top: 2rem; width: 100%; border-radius: 15px; padding: 1.2rem; font-weight: 800; cursor: pointer;"><?php echo __('careers_apply_btn'); ?></button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

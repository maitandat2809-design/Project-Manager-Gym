<?php
session_start();
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header" style="background: linear-gradient(rgba(10, 25, 47, 0.8), rgba(10, 25, 47, 0.9)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop') center/cover; padding: 6rem 2rem 4rem; text-align: center;">
    <div class="eu-eyebrow" style="color: var(--gold-light); margin-bottom: 1rem;"><?php echo __('card_title_facilities'); ?></div>
    <h1 style="color: white; font-family: 'Cormorant Garamond', serif; font-size: 3.5rem; margin-bottom: 1rem;"><?php echo __('equipment_modal_title'); ?></h1>
    <p style="color: #cbd5e1; max-width: 600px; margin: 0 auto; font-size: 1.1rem;"><?php echo __('card_desc_facilities'); ?></p>
</section>

<section style="padding: 5rem 2rem; background: var(--bg-main);">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; gap: 5rem;">

        <?php
        function renderEquipmentSection($title, $icon, $items) {
            echo '<div class="eq-section-wrapper">';
            echo '<div style="text-align: center; margin-bottom: 3rem;">';
            echo '<div class="eu-eyebrow" style="color: var(--primary); margin-bottom: 0.5rem;"><i class="'.$icon.'" style="margin-right: 8px;"></i>Discipline</div>';
            echo '<h2 style="font-family: \'Cormorant Garamond\', serif; font-size: 2.8rem; color: var(--text-main);">'.$title.'</h2>';
            echo '</div>';
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">';
            
            foreach ($items as $item) {
                echo '<div style="background: var(--bg-card); border-radius: 15px; overflow: hidden; box-shadow: var(--shadow-premium); border: 1px solid var(--border); transition: transform 0.3s;" class="eq-item-card">';
                echo '<img src="'.$item['img'].'" style="width: 100%; height: 220px; object-fit: cover; display: block;" alt="'.$item['name'].'">';
                echo '<div style="padding: 2rem;">';
                echo '<h3 style="font-family: \'Cormorant Garamond\', serif; font-size: 1.8rem; color: var(--text-main); margin-bottom: 1rem;">'.$item['name'].'</h3>';
                echo '<p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 0;">'.$item['desc'].'</p>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '<div style="text-align: center; margin-top: 3.5rem;"><a href="/Gym Manager/pages/coaches.php" class="btn-card" style="display: inline-block;">'.__('find_coach').'</a></div>';
            echo '</div>';
        }
        
        $gymStmt = $pdo->prepare("SELECT name, description as `desc`, image as img FROM equipment WHERE discipline = 'gym' AND status = 'active' ORDER BY id ASC");
        $gymStmt->execute();
        $gymItems = $gymStmt->fetchAll(PDO::FETCH_ASSOC);

        $muayStmt = $pdo->prepare("SELECT name, description as `desc`, image as img FROM equipment WHERE discipline = 'muay' AND status = 'active' ORDER BY id ASC");
        $muayStmt->execute();
        $muayItems = $muayStmt->fetchAll(PDO::FETCH_ASSOC);

        $yogaStmt = $pdo->prepare("SELECT name, description as `desc`, image as img FROM equipment WHERE discipline = 'yoga' AND status = 'active' ORDER BY id ASC");
        $yogaStmt->execute();
        $yogaItems = $yogaStmt->fetchAll(PDO::FETCH_ASSOC);

        renderEquipmentSection(__('eq_gym_title'), 'fas fa-dumbbell', $gymItems);
        renderEquipmentSection(__('eq_muay_title'), 'fas fa-hand-rock', $muayItems);
        renderEquipmentSection(__('eq_yoga_title'), 'fas fa-om', $yogaItems);
        ?>

    </div>
</section>

<style>
.eq-item-card:hover {
    transform: translateY(-10px) !important;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

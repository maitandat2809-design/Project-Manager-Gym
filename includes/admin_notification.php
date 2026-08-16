<?php
// Notification Modal for Admin and Manager regarding new orders, bookings, and membership registrations
if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')) {
    
    // 1. Acknowledge alerts if requested
    if (isset($_POST['acknowledge_orders'])) {
        try {
            $pdo->exec("UPDATE orders SET admin_seen = 1 WHERE admin_seen = 0");
            $pdo->exec("UPDATE bookings SET admin_seen = 1 WHERE admin_seen = 0");
            $pdo->exec("UPDATE member_subscriptions SET admin_seen = 1 WHERE admin_seen = 0");
        } catch (PDOException $e) {
            // Ignore if column update fails
        }
    }

    try {
        // 2. Fetch unread orders
        $stmt_o = $pdo->query("
            SELECT o.*, u.name as customer_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.admin_seen = 0
            ORDER BY o.created_at DESC
        ");
        $new_orders = $stmt_o->fetchAll();

        // 3. Fetch unread bookings
        $stmt_b = $pdo->query("
            SELECT b.*, u.name as customer_name, c_u.name as coach_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN coaches c ON b.coach_id = c.id 
            JOIN users c_u ON c.user_id = c_u.id 
            WHERE b.admin_seen = 0
            ORDER BY b.created_at DESC
        ");
        $new_bookings = $stmt_b->fetchAll();

        // 4. Fetch unread membership subscriptions
        $stmt_m = $pdo->query("
            SELECT ms.*, u.name as customer_name, mp.name as plan_name, mp.name_vi as plan_name_vi 
            FROM member_subscriptions ms 
            JOIN users u ON ms.user_id = u.id 
            JOIN membership_plans mp ON ms.plan_id = mp.id 
            WHERE ms.admin_seen = 0
            ORDER BY ms.created_at DESC
        ");
        $new_memberships = $stmt_m->fetchAll();

        $has_notifications = !empty($new_orders) || !empty($new_bookings) || !empty($new_memberships);
    } catch (PDOException $e) {
        $new_orders = [];
        $new_bookings = [];
        $new_memberships = [];
        $has_notifications = false;
    }

    if ($has_notifications): ?>
        <!-- Notification Modal for Admin/Manager -->
        <div id="adminNotificationModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 10001; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
            <div style="background: white; width: 90%; max-width: 580px; border-radius: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; animation: slideUp 0.4s ease-out;">
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); padding: 2.5rem; text-align: center; color: white;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 2px solid white;">
                        <i class="fas fa-bell" style="font-size: 2.5rem;"></i>
                    </div>
                    <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">New System Alerts!</h2>
                    <p style="opacity: 0.9;">You have new memberships, bookings, or orders pending review.</p>
                </div>
                
                <div style="padding: 2rem; max-height: 400px; overflow-y: auto;">
                    <!-- Memberships -->
                    <?php foreach($new_memberships as $nm): 
                        $plan_display = ($_SESSION['lang'] ?? 'vi') === 'vi' ? ($nm['plan_name_vi'] ?? $nm['plan_name']) : $nm['plan_name'];
                    ?>
                        <div style="display: flex; gap: 1.2rem; align-items: center; padding: 1.2rem; background: #fff7ed; border-radius: 20px; margin-bottom: 1rem; border: 1px solid #ffedd5;">
                            <div style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #ea580c;">
                                <i class="fas fa-id-card" style="font-size: 1.2rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 3px;">Thẻ Thành Viên: <?php echo htmlspecialchars($nm['customer_name']); ?></h4>
                                <p style="color: #ea580c; font-weight: 600; font-size: 0.9rem;">Gói: <?php echo htmlspecialchars($plan_display); ?> (Chờ xác nhận)</p>
                            </div>
                            <div style="text-align: right; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">
                                <?php echo date('H:i', strtotime($nm['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Bookings -->
                    <?php foreach($new_bookings as $nb): ?>
                        <div style="display: flex; gap: 1.2rem; align-items: center; padding: 1.2rem; background: #eff6ff; border-radius: 20px; margin-bottom: 1rem; border: 1px solid #bfdbfe;">
                            <div style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #3b82f6;">
                                <i class="fas fa-calendar-alt" style="font-size: 1.2rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 3px;">Booking: <?php echo htmlspecialchars($nb['customer_name']); ?></h4>
                                <p style="color: #3b82f6; font-weight: 600; font-size: 0.9rem;">With Coach <?php echo htmlspecialchars($nb['coach_name']); ?></p>
                            </div>
                            <div style="text-align: right; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">
                                <?php echo date('H:i', strtotime($nb['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Orders -->
                    <?php foreach($new_orders as $no): ?>
                        <div style="display: flex; gap: 1.2rem; align-items: center; padding: 1.2rem; background: #f8fafc; border-radius: 20px; margin-bottom: 1rem; border: 1px solid #e2e8f0;">
                            <div style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #10b981;">
                                <i class="fas fa-shopping-cart" style="font-size: 1.2rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 3px;">Order: <?php echo htmlspecialchars($no['customer_name']); ?></h4>
                                <p style="color: #10b981; font-weight: 800; font-size: 1rem;">
                                    $<?php echo number_format($no['total_price'], 2); ?>
                                </p>
                            </div>
                            <div style="text-align: right; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">
                                <?php echo date('H:i', strtotime($no['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="padding: 2rem; border-top: 1px solid #f1f5f9; display: flex; gap: 1rem;">
                    <form method="POST" style="flex: 1;">
                        <button type="submit" name="acknowledge_orders" class="btn-auth" style="margin: 0; width: 100%; border-radius: 15px; background: #3b82f6; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);">Confirm Receipt</button>
                    </form>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-muted); border-radius: 15px; text-decoration: none; font-weight: 700; height: 55px;">Go to Dashboard</a>
                </div>
            </div>
        </div>
        
        <style>
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    <?php endif;
}
?>

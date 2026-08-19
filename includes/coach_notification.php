<?php
// Check for new bookings for coaches
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'coach') {
    // 1. Get coach ID
    $stmt_c = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
    $stmt_c->execute([$_SESSION['user_id']]);
    $coach_id = $stmt_c->fetchColumn();

    if ($coach_id) {
        // 2. Clear notification if form submitted
        if (isset($_POST['acknowledge_booking'])) {
            $stmt_ack = $pdo->prepare("UPDATE bookings SET coach_seen = 1 WHERE coach_id = ? AND coach_seen = 0");
            $stmt_ack->execute([$coach_id]);
        }

        // 3. Fetch unread bookings
        $stmt_b = $pdo->prepare("
            SELECT b.*, u.name as user_name, u.avatar as user_avatar, u.email as user_email
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.coach_id = ? AND b.coach_seen = 0
            ORDER BY b.booking_date DESC
        ");
        $stmt_b->execute([$coach_id]);
        $new_bookings = $stmt_b->fetchAll();

        if (!empty($new_bookings)): 
            $is_vi_cn = isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi';
            ?>
            <!-- Notification Modal for Coach -->
            <div id="coachNotificationModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
                <div style="background: white; width: 90%; max-width: 550px; border-radius: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; animation: zoomIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    <div style="background: var(--grad-primary); padding: 2.5rem; text-align: center; color: white;">
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 2px solid white;">
                            <i class="fas fa-calendar-check" style="font-size: 2.5rem;"></i>
                        </div>
                        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo $is_vi_cn ? 'Bạn Có Lịch Đặt Mới!' : 'You Have New Bookings!'; ?></h2>
                        <p style="opacity: 0.9;"><?php echo $is_vi_cn ? 'Tin tuyệt vời! Học viên đang rất mong muốn tập luyện cùng bạn.' : 'Great news! Clients are eager to train with you.'; ?></p>
                    </div>
                    
                    <div style="padding: 2rem; max-height: 400px; overflow-y: auto;">
                        <?php foreach($new_bookings as $nb): ?>
                            <div style="display: flex; gap: 1.2rem; align-items: center; padding: 1.2rem; background: #f8fafc; border-radius: 20px; margin-bottom: 1rem; border: 1px solid #e2e8f0;">
                                <img src="<?php echo $nb['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($nb['user_name']).'&background=0062ff&color=fff&size=50'; ?>" 
                                     style="width: 55px; height: 55px; border-radius: 15px; object-fit: cover;">
                                <div style="flex: 1;">
                                    <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 3px;"><?php echo $nb['user_name']; ?></h4>
                                    <p style="color: var(--primary); font-weight: 600; font-size: 0.85rem;">
                                        <i class="far fa-clock"></i> <?php echo date('M d, H:i', strtotime($nb['booking_date'])); ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: inline-block; padding: 4px 10px; background: #dcfce7; color: #166534; border-radius: 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;"><?php echo $is_vi_cn ? 'MỚI' : 'New'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="padding: 2rem; border-top: 1px solid #f1f5f9; display: flex; gap: 1rem;">
                        <form method="POST" style="flex: 1;">
                            <button type="submit" name="acknowledge_booking" class="btn-auth" style="margin: 0; width: 100%; border-radius: 15px;"><?php echo $is_vi_cn ? 'Xác Nhận Tất Cả' : 'Acknowledge All'; ?></button>
                        </form>
                        <a href="admin.php?page=bookings" class="action-btn" style="flex: 1; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-muted); border-radius: 15px; text-decoration: none; font-weight: 700;"><?php echo $is_vi_cn ? 'Xem Lịch Tập' : 'View Dashboard'; ?></a>
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes zoomIn {
                    from { opacity: 0; transform: scale(0.9) translateY(20px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
                }
            </style>
        <?php endif;
    }
}
?>

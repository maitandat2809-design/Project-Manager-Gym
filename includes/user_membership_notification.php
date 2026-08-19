<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle dismissal request
if (isset($_GET['dismiss_mem_expiry']) && isset($_GET['sub_id'])) {
    $dismiss_sub_id = (int)$_GET['sub_id'];
    $_SESSION['dismissed_mem_expiry_' . $dismiss_sub_id] = true;
    
    // Redirect to remove query parameters from URL
    $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $clean_url);
    exit;
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $user_id = (int)$_SESSION['user_id'];
    
    try {
        // Query active or recently expired subscription with used PT sessions >= total
        $stmt_ex = $pdo->prepare("
            SELECT ms.id, ms.pt_sessions_total, ms.pt_sessions_used, mp.name as plan_name, mp.name_vi as plan_name_vi
            FROM member_subscriptions ms
            JOIN membership_plans mp ON ms.plan_id = mp.id
            WHERE ms.user_id = ? 
              AND ms.pt_sessions_total > 0 
              AND ms.pt_sessions_used >= ms.pt_sessions_total
              AND ms.status IN ('active', 'expired')
            ORDER BY ms.id DESC
            LIMIT 1
        ");
        $stmt_ex->execute([$user_id]);
        $expired_sub = $stmt_ex->fetch();

        if ($expired_sub) {
            $sub_id = (int)$expired_sub['id'];
            
            // Check if dismissed in this session
            if (!isset($_SESSION['dismissed_mem_expiry_' . $sub_id])) {
                $plan_display = !empty($expired_sub['plan_name_vi']) ? $expired_sub['plan_name_vi'] : $expired_sub['plan_name'];
                $pt_total = (int)$expired_sub['pt_sessions_total'];
                $pt_used = (int)$expired_sub['pt_sessions_used'];
                
                $is_vi = isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi';
                ?>
                <!-- USER MEMBERSHIP EXPIRATION POPUP MODAL -->
                <div id="userMembershipExpiryModal" style="display: flex; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.78); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
                    <div style="background: #ffffff; border-radius: 20px; width: 100%; max-width: 520px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 1px solid rgba(226, 232, 240, 0.8); border-top: 5px solid #d97706; text-align: center; animation: modalPop 0.3s ease-out;">
                        
                        <!-- Header Banner -->
                        <div style="background: linear-gradient(135deg, #1c2340, #0f172a); color: #ffffff; padding: 1.8rem 1.5rem 1.4rem 1.5rem; position: relative;">
                            <div style="width: 60px; height: 60px; background: rgba(217, 119, 6, 0.18); color: #f59e0b; border: 2px solid #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem auto; font-size: 1.6rem; box-shadow: 0 8px 20px rgba(217, 119, 6, 0.3);">
                                <i class="fas fa-hourglass-end"></i>
                            </div>
                            <h3 style="margin: 0; font-family: 'DM Serif Display', Georgia, serif; font-size: 1.5rem; color: #f59e0b; font-weight: 400;">
                                <?php echo $is_vi ? 'Gói Tập PT Của Bạn Đã Hết Hạn' : 'PT Session Package Expired'; ?>
                            </h3>
                            <p style="margin: 4px 0 0 0; color: #94a3b8; font-size: 0.85rem;">
                                <?php echo $is_vi ? 'Thông báo trạng thái thẻ thành viên' : 'Membership Quota Status Notification'; ?>
                            </p>
                        </div>

                        <!-- Body Content -->
                        <div style="padding: 1.6rem 1.8rem;">
                            <p style="color: #334155; font-size: 0.95rem; line-height: 1.6; margin: 0 0 1.2rem 0;">
                                <?php if ($is_vi): ?>
                                    Gói tập <strong><?php echo htmlspecialchars($plan_display); ?></strong> của bạn đã hoàn thành đủ <strong><?php echo $pt_used; ?>/<?php echo $pt_total; ?> buổi tập</strong> với HLV. 
                                    Số buổi còn lại hiện tại là <strong>0 buổi</strong>.
                                <?php else: ?>
                                    Your package <strong><?php echo htmlspecialchars($plan_display); ?></strong> has completed all <strong><?php echo $pt_used; ?>/<?php echo $pt_total; ?> PT sessions</strong> with your coach. 
                                    Remaining sessions count is now <strong>0</strong>.
                                <?php endif; ?>
                            </p>

                            <!-- Stat Badge Box -->
                            <div style="background: #fffbeb; border: 1px dashed #f59e0b; border-radius: 12px; padding: 0.9rem 1.2rem; margin-bottom: 1.4rem; display: flex; justify-content: space-around; align-items: center;">
                                <div>
                                    <div style="font-size: 0.75rem; color: #78350f; text-transform: uppercase; font-weight: 700;">
                                        <?php echo $is_vi ? 'Đã Tham Gia' : 'Completed'; ?>
                                    </div>
                                    <div style="font-size: 1.3rem; font-weight: 800; color: #d97706;">
                                        <?php echo $pt_used; ?> / <?php echo $pt_total; ?> <?php echo $is_vi ? 'buổi' : 'sessions'; ?>
                                    </div>
                                </div>
                                <div style="width: 1px; height: 35px; background: #fde68a;"></div>
                                <div>
                                    <div style="font-size: 0.75rem; color: #78350f; text-transform: uppercase; font-weight: 700;">
                                        <?php echo $is_vi ? 'Trạng Thái' : 'Status'; ?>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 800; color: #dc2626;">
                                        <?php echo $is_vi ? '⚠️ HẾT HẠN' : '⚠️ EXPIRED'; ?>
                                    </div>
                                </div>
                            </div>

                            <p style="color: #475569; font-size: 0.88rem; margin: 0 0 1.2rem 0; font-weight: 600;">
                                <?php echo $is_vi ? 'Vui lòng chọn 1 trong 2 lựa chọn bên dưới để tiếp tục duy trì tập luyện:' : 'Please choose one of the options below to continue training:'; ?>
                            </p>

                            <!-- Two Main Action Buttons -->
                            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.2rem;">
                                <!-- Option 1: Gia Hạn Gói Tập -->
                                <a href="/Gym Manager/pages/membership.php" style="padding: 0.9rem 1.2rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; border-radius: 12px; font-weight: 700; font-size: 0.95rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: transform 0.2s;">
                                    <i class="fas fa-sync-alt"></i> 
                                    <span><?php echo $is_vi ? '1. Gia Hạn Gói Tập Hiện Tại' : '1. Renew Current Package'; ?></span>
                                </a>

                                <!-- Option 2: Chọn Gói Khác -->
                                <a href="/Gym Manager/pages/membership.php" style="padding: 0.9rem 1.2rem; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; border-radius: 12px; font-weight: 700; font-size: 0.95rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35); transition: transform 0.2s;">
                                    <i class="fas fa-gem"></i> 
                                    <span><?php echo $is_vi ? '2. Đăng Ký Chọn Gói Mới Khác' : '2. Choose Another Package'; ?></span>
                                </a>
                            </div>

                            <!-- Dismiss / Close Button -->
                            <a href="?dismiss_mem_expiry=1&sub_id=<?php echo $sub_id; ?>" style="display: inline-block; color: #94a3b8; font-size: 0.82rem; text-decoration: underline; cursor: pointer; padding: 4px 8px;">
                                <?php echo $is_vi ? 'Đóng thông báo này (Nhắc lại sau)' : 'Dismiss notification (Remind later)'; ?>
                            </a>
                        </div>
                    </div>
                </div>

                <style>
                @keyframes modalPop {
                    from { opacity: 0; transform: scale(0.92); }
                    to { opacity: 1; transform: scale(1); }
                }
                </style>
                <?php
            }
        }
    } catch (Exception $e) {
        error_log("User membership expiry notification error: " . $e->getMessage());
    }
}
?>

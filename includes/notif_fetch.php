<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/lang.php';

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$curr_lang = $_SESSION['lang'] ?? 'en';

if ($role == 'coach') {
    // 1. Unseen ratings/reviews from users
    $stmt_reviews = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.avatar as client_avatar
        FROM bookings b
        JOIN coaches c ON b.coach_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE c.user_id = ? AND b.user_rating IS NOT NULL
        ORDER BY b.user_reviewed_at DESC LIMIT 5
    ");
    $stmt_reviews->execute([$user_id]);
    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

    // 2. Attended/ended sessions needing coach notes
    $stmt_need_notes = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.avatar as client_avatar
        FROM bookings b
        JOIN coaches c ON b.coach_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE c.user_id = ? 
          AND b.status != 'cancelled' 
          AND b.attendance != 'absent' 
          AND b.coach_session_notes IS NULL
          AND (b.attendance = 'present' OR b.status = 'completed' OR DATE_ADD(b.booking_date, INTERVAL 1 HOUR) <= NOW())
        ORDER BY b.booking_date DESC LIMIT 5
    ");
    $stmt_need_notes->execute([$user_id]);
    $need_notes = $stmt_need_notes->fetchAll(PDO::FETCH_ASSOC);

    // 3. General booking requests
    $stmt_b = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.avatar as client_avatar 
        FROM bookings b 
        JOIN coaches c ON b.coach_id = c.id 
        JOIN users u ON b.user_id = u.id
        WHERE c.user_id = ? 
        ORDER BY b.created_at DESC LIMIT 5
    ");
    $stmt_b->execute([$user_id]);
    $bookings = $stmt_b->fetchAll(PDO::FETCH_ASSOC);

    $has_coach_notifs = !empty($reviews) || !empty($need_notes) || !empty($bookings);

    if (!$has_coach_notifs) {
        echo '<div class="no-notif"><i class="fas fa-bell-slash"></i><p>' . __('no_notifications') . '</p></div>';
    } else {
        // Section: Unseen user ratings
        if (!empty($reviews)) {
            echo '<div style="padding: 6px 12px; background: #fffbebf5; color: #d97706; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin-bottom: 8px;"><i class="fas fa-star"></i> ' . __('eval_reviews_header') . '</div>';
            foreach ($reviews as $r) {
                $is_unread = $r['review_seen_by_coach'] == 0 ? 'unread' : '';
                $stars = str_repeat('⭐', intval($r['user_rating']));
                $client_img = $r['client_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($r['client_name']).'&background=0062ff&color=fff&size=50';
                
                echo '<div class="notif-item ' . $is_unread . '" style="border-left: 3px solid #f59e0b;">';
                echo '<div style="display: flex; gap: 0.8rem; align-items: flex-start;">';
                echo '<img src="' . $client_img . '" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover;">';
                echo '<div style="flex: 1;">';
                echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
                echo '<h4 style="margin: 0; font-size: 0.9rem;">' . htmlspecialchars($r['client_name']) . '</h4>';
                echo '<span style="color: #d97706; font-weight: 700; font-size: 0.8rem;">' . $stars . '</span>';
                echo '</div>';
                echo '<p style="color: #475569; font-size: 0.82rem; margin: 4px 0 0 0;">"' . htmlspecialchars($r['user_review'] ?: ($curr_lang === 'en' ? 'Rated session quality' : 'Đã đánh giá buổi tập')) . '"</p>';
                echo '<span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 4px;"><i class="far fa-clock"></i> ' . date('d/m/Y H:i', strtotime($r['user_reviewed_at'] ?: $r['booking_date'])) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Section: Sessions needing coach notes
        if (!empty($need_notes)) {
            echo '<div style="padding: 6px 12px; background: #f0fdf4; color: #16a34a; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin: 10px 0 8px 0;"><i class="fas fa-edit"></i> ' . __('eval_need_notes_header') . '</div>';
            foreach ($need_notes as $nn) {
                $client_img = $nn['client_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($nn['client_name']).'&background=0062ff&color=fff&size=50';
                $session_label = htmlspecialchars($nn['client_name']) . ' (' . date('d/m/Y H:i', strtotime($nn['booking_date'])) . ')';
                
                echo '<div class="notif-item unread" style="border-left: 3px solid #16a34a;">';
                echo '<div style="display: flex; gap: 0.8rem; align-items: center;">';
                echo '<img src="' . $client_img . '" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover;">';
                echo '<div style="flex: 1;">';
                echo '<h4 style="margin: 0; font-size: 0.88rem;">' . htmlspecialchars($nn['client_name']) . '</h4>';
                echo '<p style="color: #16a34a; font-weight: 600; font-size: 0.8rem; margin: 2px 0 6px 0;">' . __('eval_notes_needed') . '</p>';
                echo '<button type="button" class="btn-notif-action btn-accept" style="width: 100%; justify-content: center; padding: 6px 12px; font-size: 0.8rem;" onclick="openCoachNotesModal(' . $nn['id'] . ', \'' . addslashes($session_label) . '\', \'' . addslashes($nn['coach_session_notes'] ?? '') . '\')"><i class="fas fa-pen"></i> ' . __('eval_add_notes') . '</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Section: General Booking Requests
        if (!empty($bookings)) {
            echo '<div style="padding: 6px 12px; background: #eff6ff; color: #1d4ed8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin: 10px 0 8px 0;"><i class="far fa-calendar-alt"></i> ' . ($curr_lang === 'en' ? 'Session Bookings' : 'Đặt Lịch Tập') . '</div>';
            foreach ($bookings as $n) {
                $is_unread = $n['coach_seen'] == 0 ? 'unread' : '';
                $status_class = 'status-' . $n['status'] . '-notif';
                $client_img = $n['client_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($n['client_name']).'&background=0062ff&color=fff&size=50';

                echo '<div class="notif-item ' . $is_unread . '">';
                echo '<div style="display: flex; gap: 0.8rem; align-items: flex-start;">';
                echo '<img src="' . $client_img . '" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover;">';
                echo '<div style="flex: 1;">';
                echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
                echo '<h4 style="margin:0; font-size:0.88rem;">' . htmlspecialchars($n['client_name']) . '</h4>';
                echo '<span class="notif-status ' . $status_class . '">' . strtoupper($n['status']) . '</span>';
                echo '</div>';
                echo '<p style="margin:4px 0;"><i class="far fa-calendar-alt"></i> ' . date('d/m/Y, H:i', strtotime($n['booking_date'])) . '</p>';
                
                if ($n['status'] == 'pending') {
                    echo '<div class="quick-reply-form" style="margin-top: 8px; border: none; padding: 0;">';
                    echo '<form action="process_booking.php" method="POST">';
                    echo '<input type="hidden" name="booking_id" value="' . $n['id'] . '">';
                    echo '<textarea name="coach_message" placeholder="' . ($curr_lang === 'en' ? 'Send quick reply...' : 'Gửi phản hồi nhanh...') . '" rows="2" style="margin-bottom: 6px; font-size:0.8rem;"></textarea>';
                    echo '<div class="reply-actions">';
                    echo '<button type="submit" name="action" value="confirm" class="btn-notif-action btn-accept"><i class="fas fa-check"></i> ' . __('confirm') . '</button>';
                    echo '<button type="submit" name="action" value="reject" class="btn-notif-action btn-reject"><i class="fas fa-times"></i> ' . ($curr_lang === 'en' ? 'Reject' : 'Từ chối') . '</button>';
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Mark coach reviews as seen
        $pdo->prepare("UPDATE bookings b JOIN coaches c ON b.coach_id = c.id SET b.review_seen_by_coach = 1, b.coach_seen = 1 WHERE c.user_id = ?")->execute([$user_id]);
    }
} elseif ($role == 'admin' || $role == 'manager') {
    // Admin / Manager Notification view
    $stmt_orders = $pdo->query("
        SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC LIMIT 5
    ");
    $orders = $stmt_orders->fetchAll();

    $stmt_mem = $pdo->query("
        SELECT ms.*, u.name as customer_name, mp.name as plan_name, mp.name_vi as plan_name_vi 
        FROM member_subscriptions ms 
        JOIN users u ON ms.user_id = u.id 
        JOIN membership_plans mp ON ms.plan_id = mp.id 
        ORDER BY ms.created_at DESC LIMIT 5
    ");
    $memberships = $stmt_mem->fetchAll();

    if (empty($orders) && empty($memberships)) {
        echo '<div class="no-notif"><i class="fas fa-bell-slash"></i><p>' . __('no_notifications') . '</p></div>';
    } else {
        foreach ($memberships as $m) {
            $is_unread = $m['admin_seen'] == 0 ? 'unread' : '';
            $p_name = $curr_lang === 'vi' ? ($m['plan_name_vi'] ?? $m['plan_name']) : $m['plan_name'];
            echo '<a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="notif-item ' . $is_unread . '">';
            echo '<h4>💳 ' . ($curr_lang === 'en' ? 'Membership Card #' : 'Thẻ Thành Viên #') . $m['id'] . ' (' . ($curr_lang === 'en' ? 'Pending' : 'Chờ xác nhận') . ')</h4>';
            echo '<p>' . ($curr_lang === 'en' ? 'Client: ' : 'Khách hàng: ') . '<strong>' . htmlspecialchars($m['customer_name']) . '</strong> — ' . ($curr_lang === 'en' ? 'Plan: ' : 'Gói: ') . '<strong>' . htmlspecialchars($p_name) . '</strong></p>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">';
            echo '<span class="price" style="font-size: 0.85rem; color: #ea580c; font-weight: 700;">' . number_format($m['price_paid'], 0, ',', '.') . 'đ</span>';
            echo '<span class="notif-time" style="margin:0;">' . date('M d, H:i', strtotime($m['created_at'])) . '</span>';
            echo '</div>';
            echo '</a>';
        }

        foreach ($orders as $o) {
            $is_unread = $o['admin_seen'] == 0 ? 'unread' : '';
            echo '<a href="/Gym Manager/dashboards/manager.php?page=orders" class="notif-item ' . $is_unread . '">';
            echo '<h4>🛒 ' . ($curr_lang === 'en' ? 'New Order #' : 'Đơn Hàng Mới #') . $o['id'] . '</h4>';
            echo '<p>' . ($curr_lang === 'en' ? 'From: ' : 'Từ: ') . '<strong>' . htmlspecialchars($o['customer_name']) . '</strong></p>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">';
            echo '<span class="price" style="font-size: 0.9rem;">$' . number_format($o['total_price'], 2) . '</span>';
            echo '<span class="notif-time" style="margin:0;">' . date('M d, H:i', strtotime($o['created_at'])) . '</span>';
            echo '</div>';
            echo '</a>';
        }
        $pdo->exec("UPDATE orders SET admin_seen = 1 WHERE admin_seen = 0");
        $pdo->exec("UPDATE member_subscriptions SET admin_seen = 1 WHERE admin_seen = 0");
    }
} else {
    // User Notification View
    // 1. Unreviewed completed/ended sessions
    $stmt_unrated = $pdo->prepare("
        SELECT b.*, u.name as coach_name, u.avatar as coach_avatar, c.specialty
        FROM bookings b
        JOIN coaches c ON b.coach_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE b.user_id = ? 
          AND b.status != 'cancelled' 
          AND b.attendance != 'absent' 
          AND b.user_rating IS NULL
          AND (b.attendance = 'present' OR b.status = 'completed' OR DATE_ADD(b.booking_date, INTERVAL 1 HOUR) <= NOW())
        ORDER BY b.booking_date DESC LIMIT 5
    ");
    $stmt_unrated->execute([$user_id]);
    $unrated = $stmt_unrated->fetchAll(PDO::FETCH_ASSOC);

    // 2. Coach session performance notes
    $stmt_notes = $pdo->prepare("
        SELECT b.*, u.name as coach_name, u.avatar as coach_avatar
        FROM bookings b
        JOIN coaches c ON b.coach_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE b.user_id = ? AND b.coach_session_notes IS NOT NULL
        ORDER BY b.coach_notes_at DESC LIMIT 5
    ");
    $stmt_notes->execute([$user_id]);
    $notes_list = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Coach replies / status update
    $stmt_replies = $pdo->prepare("
        SELECT b.*, u.name as coach_name 
        FROM bookings b 
        JOIN coaches c ON b.coach_id = c.id 
        JOIN users u ON c.user_id = u.id
        WHERE b.user_id = ? AND b.coach_message IS NOT NULL
        ORDER BY b.updated_at DESC LIMIT 5
    ");
    $stmt_replies->execute([$user_id]);
    $replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);

    $has_user_notifs = !empty($unrated) || !empty($notes_list) || !empty($replies);

    if (!$has_user_notifs) {
        echo '<div class="no-notif"><i class="fas fa-bell-slash"></i><p>' . __('no_notifications') . '</p></div>';
    } else {
        // Section: Unreviewed Sessions
        if (!empty($unrated)) {
            echo '<div style="padding: 6px 12px; background: #fffbebf5; color: #d97706; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin-bottom: 8px;"><i class="fas fa-star"></i> ' . __('eval_session_title') . '</div>';
            foreach ($unrated as $un) {
                $coach_img = $un['coach_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($un['coach_name']).'&background=0062ff&color=fff&size=50';
                $session_label = ($curr_lang === 'en' ? 'Coach ' : 'HLV ') . htmlspecialchars($un['coach_name']) . ' (' . date('d/m/Y H:i', strtotime($un['booking_date'])) . ')';
                
                echo '<div class="notif-item unread" style="border-left: 3px solid #f59e0b;">';
                echo '<div style="display: flex; gap: 0.8rem; align-items: center;">';
                echo '<img src="' . $coach_img . '" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover;">';
                echo '<div style="flex: 1;">';
                echo '<h4 style="margin:0; font-size:0.88rem;">' . ($curr_lang === 'en' ? 'Coach ' : 'HLV ') . htmlspecialchars($un['coach_name']) . '</h4>';
                echo '<p style="color: #d97706; font-size:0.8rem; font-weight: 600; margin: 2px 0 6px 0;">' . __('eval_session_completed') . '</p>';
                echo '<button type="button" class="btn-notif-action btn-accept" style="width:100%; justify-content: center; padding: 6px 12px; font-size:0.8rem; background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="openUserEvalModal(' . $un['id'] . ', \'' . addslashes($session_label) . '\')"><i class="fas fa-star"></i> ' . __('eval_rate_now') . '</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Section: Coach Session Performance Notes
        if (!empty($notes_list)) {
            echo '<div style="padding: 6px 12px; background: #f0fdf4; color: #16a34a; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin: 10px 0 8px 0;"><i class="fas fa-clipboard-check"></i> ' . __('eval_notes_title') . '</div>';
            foreach ($notes_list as $nl) {
                $is_unread = $nl['notes_seen_by_user'] == 0 ? 'unread' : '';
                $coach_img = $nl['coach_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($nl['coach_name']).'&background=0062ff&color=fff&size=50';
                
                echo '<div class="notif-item ' . $is_unread . '" style="border-left: 3px solid #16a34a;">';
                echo '<div style="display: flex; gap: 0.8rem; align-items: flex-start;">';
                echo '<img src="' . $coach_img . '" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover;">';
                echo '<div style="flex: 1;">';
                echo '<h4 style="margin:0; font-size:0.88rem;">' . ($curr_lang === 'en' ? 'Coach ' : 'HLV ') . htmlspecialchars($nl['coach_name']) . '</h4>';
                echo '<div style="margin-top: 4px; padding: 8px 10px; background: #f8fafc; border-radius: 8px; font-size: 0.8rem; color: #1e293b;">';
                echo '<strong>' . ($curr_lang === 'en' ? 'Improvement Notes:' : 'Ghi chú cải thiện:') . '</strong><br>"' . htmlspecialchars($nl['coach_session_notes']) . '"';
                echo '</div>';
                echo '<span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 4px;"><i class="far fa-clock"></i> ' . date('d/m/Y H:i', strtotime($nl['coach_notes_at'] ?: $nl['booking_date'])) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Section: General Coach replies
        if (!empty($replies)) {
            echo '<div style="padding: 6px 12px; background: #eff6ff; color: #1d4ed8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px; margin: 10px 0 8px 0;"><i class="far fa-comment-dots"></i> ' . ($curr_lang === 'en' ? 'Coach Replies' : 'Phản Hồi Từ HLV') . '</div>';
            foreach ($replies as $n) {
                $is_unread = $n['user_seen'] == 0 ? 'unread' : '';
                $status_class = 'status-' . $n['status'] . '-notif';
                
                echo '<a href="/Gym Manager/dashboards/profile.php" class="notif-item ' . $is_unread . '">';
                echo '<h4>' . ($curr_lang === 'en' ? 'Coach ' : 'HLV ') . htmlspecialchars($n['coach_name']) . ($curr_lang === 'en' ? ' replied' : ' đã phản hồi') . '</h4>';
                echo '<p style="color: var(--text-main); font-weight: 600; margin: 4px 0;">"' . htmlspecialchars(getLocalizedCoachMsg($n['coach_message'])) . '"</p>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">';
                echo '<span class="notif-time" style="margin:0;">' . date('d/m/Y H:i', strtotime($n['updated_at'])) . '</span>';
                echo '<span class="notif-status ' . $status_class . '">' . strtoupper($n['status']) . '</span>';
                echo '</div>';
                echo '</a>';
            }
        }

        // Mark user seen flags
        $pdo->prepare("UPDATE bookings SET notes_seen_by_user = 1, user_seen = 1 WHERE user_id = ? AND (coach_message IS NOT NULL OR coach_session_notes IS NOT NULL)")->execute([$user_id]);
    }
}
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($role == 'coach') {
    // Fetch bookings for this coach
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.avatar as client_avatar 
        FROM bookings b 
        JOIN coaches c ON b.coach_id = c.id 
        JOIN users u ON b.user_id = u.id
        WHERE c.user_id = ? 
        ORDER BY b.created_at DESC LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    if (empty($notifications)) {
        echo '<div class="no-notif"><i class="fas fa-calendar-times"></i><p>No booking requests yet.</p></div>';
    } else {
        foreach ($notifications as $n) {
            $is_unread = $n['coach_seen'] == 0 ? 'unread' : '';
            $status_class = 'status-' . $n['status'] . '-notif';
            
            echo '<div class="notif-item ' . $is_unread . '">';
            echo '<div style="display: flex; gap: 1rem; align-items: flex-start;">';
            echo '<img src="' . ($n['client_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($n['client_name']).'&background=0062ff&color=fff&size=50') . '" style="width: 40px; height: 40px; border-radius: 12px; object-fit: cover;">';
            echo '<div style="flex: 1;">';
            echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
            echo '<h4>' . htmlspecialchars($n['client_name']) . '</h4>';
            echo '<span class="notif-status ' . $status_class . '">' . $n['status'] . '</span>';
            echo '</div>';
            echo '<p><i class="far fa-calendar-alt"></i> ' . date('M d, H:i', strtotime($n['booking_date'])) . '</p>';
            
            if ($n['status'] == 'pending') {
                echo '<div class="quick-reply-form" style="margin-top: 10px; border: none; padding: 0;">';
                echo '<form action="process_booking.php" method="POST">';
                echo '<input type="hidden" name="booking_id" value="' . $n['id'] . '">';
                echo '<textarea name="coach_message" placeholder="Type a quick message..." rows="2" style="margin-bottom: 8px;"></textarea>';
                echo '<div class="reply-actions">';
                echo '<button type="submit" name="action" value="confirm" class="btn-notif-action btn-accept"><i class="fas fa-check"></i> Accept</button>';
                echo '<button type="submit" name="action" value="reject" class="btn-notif-action btn-reject"><i class="fas fa-times"></i> Reject</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
            } elseif ($n['coach_message']) {
                echo '<div style="margin-top: 8px; padding: 10px; background: #f8fafc; border-radius: 10px; font-size: 0.8rem; border-left: 3px solid var(--primary);">';
                echo '<strong style="color: var(--primary); display: block; margin-bottom: 2px;">Your Reply:</strong>';
                echo htmlspecialchars($n['coach_message']);
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }
} elseif ($role == 'admin' || $role == 'manager') {
    // Fetch new orders & membership applications for admin/manager
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
        echo '<div class="no-notif"><i class="fas fa-bell-slash"></i><p>No new notifications yet.</p></div>';
    } else {
        foreach ($memberships as $m) {
            $is_unread = $m['admin_seen'] == 0 ? 'unread' : '';
            $p_name = ($_SESSION['lang'] ?? 'vi') === 'vi' ? ($m['plan_name_vi'] ?? $m['plan_name']) : $m['plan_name'];
            echo '<a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="notif-item ' . $is_unread . '">';
            echo '<h4>💳 Thẻ Thành Viên #' . $m['id'] . ' (Chờ xác nhận)</h4>';
            echo '<p>Khách hàng: <strong>' . htmlspecialchars($m['customer_name']) . '</strong> — Gói: <strong>' . htmlspecialchars($p_name) . '</strong></p>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">';
            echo '<span class="price" style="font-size: 0.85rem; color: #ea580c; font-weight: 700;">' . number_format($m['price_paid'], 0, ',', '.') . 'đ</span>';
            echo '<span class="notif-time" style="margin:0;">' . date('M d, H:i', strtotime($m['created_at'])) . '</span>';
            echo '</div>';
            echo '</a>';
        }

        foreach ($orders as $o) {
            $is_unread = $o['admin_seen'] == 0 ? 'unread' : '';
            echo '<a href="/Gym Manager/dashboards/manager.php?page=orders" class="notif-item ' . $is_unread . '">';
            echo '<h4>🛒 New Order #' . $o['id'] . '</h4>';
            echo '<p>From: <strong>' . htmlspecialchars($o['customer_name']) . '</strong></p>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">';
            echo '<span class="price" style="font-size: 0.9rem;">$' . number_format($o['total_price'], 2) . '</span>';
            echo '<span class="notif-time" style="margin:0;">' . date('M d, H:i', strtotime($o['created_at'])) . '</span>';
            echo '</div>';
            echo '</a>';
        }
        // Mark all as seen for admin/manager
        $pdo->exec("UPDATE orders SET admin_seen = 1 WHERE admin_seen = 0");
        $pdo->exec("UPDATE member_subscriptions SET admin_seen = 1 WHERE admin_seen = 0");
    }
} else {
    // Fetch responses for this user
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as coach_name 
        FROM bookings b 
        JOIN coaches c ON b.coach_id = c.id 
        JOIN users u ON c.user_id = u.id
        WHERE b.user_id = ? AND b.coach_message IS NOT NULL
        ORDER BY b.updated_at DESC LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    if (empty($notifications)) {
        echo '<div class="no-notif"><i class="fas fa-bell-slash"></i><p>No new responses.</p></div>';
    } else {
        foreach ($notifications as $n) {
            $is_unread = $n['user_seen'] == 0 ? 'unread' : '';
            $status_class = 'status-' . $n['status'] . '-notif';
            
            echo '<a href="/Gym Manager/dashboards/profile.php" class="notif-item ' . $is_unread . '">';
            echo '<h4>Coach ' . htmlspecialchars($n['coach_name']) . ' replied</h4>';
            echo '<p style="color: var(--text-main); font-weight: 600; margin: 5px 0;">"' . htmlspecialchars($n['coach_message']) . '"</p>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">';
            echo '<span class="notif-time" style="margin:0;">' . date('M d, H:i', strtotime($n['updated_at'])) . '</span>';
            echo '<span class="notif-status ' . $status_class . '">' . strtoupper($n['status']) . '</span>';
            echo '</div>';
            echo '</a>';
        }
        // Mark user notifications as seen
        $pdo->prepare("UPDATE bookings SET user_seen = 1 WHERE user_id = ? AND coach_message IS NOT NULL AND user_seen = 0")->execute([$user_id]);
    }
}
?>

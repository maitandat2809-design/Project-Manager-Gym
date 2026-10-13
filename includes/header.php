<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/lang.php';

// Handle user membership notification dismissal before output starts
if (isset($_GET['dismiss_mem_expiry']) && isset($_GET['sub_id'])) {
    $dismiss_sub_id = (int)$_GET['sub_id'];
    $_SESSION['dismissed_mem_expiry_' . $dismiss_sub_id] = true;
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(200);
        exit;
    }
    
    $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
    if (!headers_sent()) {
        header("Location: " . $clean_url);
        exit;
    }
}

// Temporarily hide a role notification for the current session. Notifications
// stay unread so the user can be reminded again after signing in later.
if (isset($_GET['dismiss_notification'])) {
    $dismiss_type = (string)$_GET['dismiss_notification'];
    $current_role = $_SESSION['role'] ?? '';
    $dismiss_key = null;
    if ($dismiss_type === 'coach' && $current_role === 'coach') {
        $dismiss_key = 'dismissed_coach_notifications';
    } elseif ($dismiss_type === 'staff' && in_array($current_role, ['admin', 'manager'], true)) {
        $dismiss_key = 'dismissed_staff_notifications_' . $current_role;
    }

    if ($dismiss_key !== null) {
        $_SESSION[$dismiss_key] = true;
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(200);
            exit;
        }
        $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
        if (!headers_sent()) {
            header("Location: " . $clean_url);
            exit;
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $stmt_role = $pdo->prepare("SELECT role, name FROM users WHERE id = ?");
    $stmt_role->execute([$_SESSION['user_id']]);
    if ($user_role_info = $stmt_role->fetch()) {
        $_SESSION['role'] = $user_role_info['role'];
        $_SESSION['user_name'] = $user_role_info['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Manager - Reach your dream physique</title>
    <link rel="stylesheet" href="/Gym Manager/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <nav>
        <div class="nav-inner">
            <div class="nav-brand-container">
            <a href="/Gym Manager/index.php" class="logo">GYM<span>X</span></a>
        </div>
        
        <!-- Main Navigation Links -->
        <ul class="nav-links">
            <li><a href="/Gym Manager/index.php"><?php echo __('home'); ?></a></li>
            <?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
            <li class="nav-item-dropdown">
                <a href="/Gym Manager/pages/coaches.php" class="dropdown-trigger"><?php echo __('programs_label'); ?> <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 3px;"></i></a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="/Gym Manager/pages/coaches.php" class="dropdown-all-coaches">
                            <span class="dropdown-icon"><i class="fas fa-users" style="color: var(--primary);"></i></span>
                            <span class="dropdown-text" style="font-weight: 700;"><?php echo __('all_coaches'); ?></span>
                        </a>
                    </li>
                    <?php 
                    $stmt_spec = $pdo->query("SELECT DISTINCT specialty FROM coaches WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
                    $raw_specs = $stmt_spec->fetchAll(PDO::FETCH_COLUMN);
                    $specialties = [];
                    foreach ($raw_specs as $s) {
                        $norm = trim($s);
                        $norm = preg_replace('/\s*&\s*kickboxing/i', '', $norm);
                        $norm = trim($norm);
                        if (strcasecmp($norm, 'gym') === 0) {
                            $norm = 'Gym & Fitness';
                        } elseif (stripos($norm, 'muay') !== false || strcasecmp($norm, 'kickboxing') === 0) {
                            $norm = 'Muay Thai';
                        }
                        if (!in_array($norm, $specialties)) {
                            $specialties[] = $norm;
                        }
                    }
                    sort($specialties);
                    foreach($specialties as $spec): 
                        $icon = 'fa-medal';
                        if(stripos($spec, 'gym') !== false) $icon = 'fa-dumbbell';
                        elseif(stripos($spec, 'yoga') !== false) $icon = 'fa-leaf';
                        elseif(stripos($spec, 'muay') !== false) $icon = 'fa-fist-raised';
                        
                        $url_spec = urlencode(strtolower(str_replace(' ', '_', $spec)));
                    ?>
                        <li>
                            <a href="/Gym Manager/pages/coaches.php?discipline=<?php echo $url_spec; ?>">
                                <span class="dropdown-icon"><i class="fas <?php echo $icon; ?>"></i></span>
                                <span class="dropdown-text"><?php echo htmlspecialchars($spec); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
            <li><a href="/Gym Manager/features/calendar.php"><?php echo __('calendar'); ?></a></li>
            <?php endif; ?>
            <?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
            <li><a href="/Gym Manager/pages/membership.php"><?php echo __('membership_nav'); ?></a></li>
            <?php endif; ?>
            <?php if(!isset($_SESSION['role']) || ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin')): ?>
            <li><a href="/Gym Manager/pages/shop.php"><?php echo __('shop'); ?></a></li>
            <?php $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
            <li>
                <a href="/Gym Manager/pages/cart.php" class="nav-cart-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <span><?php echo __('cart'); ?></span>
                    <span class="nav-badge"><?php echo $cart_count; ?></span>
                </a>
            </li>
            <?php endif; ?>
            <li><a href="/Gym Manager/pages/about.php"><?php echo __('about'); ?></a></li>
        </ul>

        <!-- Right Side Actions Container -->
        <div class="nav-actions-container">
            <ul class="nav-actions">
                <li class="language-indicator" aria-label="Language: English">
                    <img src="https://flagcdn.com/w40/gb.png" class="lang-flag" alt="English">
                    <span>EN</span>
                </li>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li><a href="/Gym Manager/dashboards/admin.php" class="nav-role-btn"><i class="fas fa-shield-alt"></i> <?php echo __('admin'); ?></a></li>
                    <?php elseif($_SESSION['role'] == 'manager'): ?>
                        <li><a href="/Gym Manager/dashboards/manager.php" class="nav-role-btn"><i class="fas fa-user-shield"></i> <?php echo __('manager_panel'); ?></a></li>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == 'coach' || $_SESSION['role'] == 'user' || $_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                        <li class="notification-wrapper">
                            <?php
                            $notif_count = 0;
                            if($_SESSION['role'] == 'coach') {
                                $stmt_n = $pdo->prepare("
                                    SELECT 
                                        (SELECT COUNT(*) FROM bookings b JOIN coaches c ON b.coach_id = c.id WHERE c.user_id = ? AND b.coach_seen = 0) +
                                        (SELECT COUNT(*) FROM bookings b JOIN coaches c ON b.coach_id = c.id WHERE c.user_id = ? AND b.user_rating IS NOT NULL AND b.review_seen_by_coach = 0) +
                                        (SELECT COUNT(*) FROM bookings b JOIN coaches c ON b.coach_id = c.id WHERE c.user_id = ? AND b.attendance = 'present' AND b.coach_session_notes IS NULL)
                                ");
                                $stmt_n->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                                $notif_count = $stmt_n->fetchColumn();
                            } elseif($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') {
                                $stmt_n = $pdo->query("SELECT (SELECT COUNT(*) FROM orders WHERE admin_seen = 0) + (SELECT COUNT(*) FROM member_subscriptions WHERE admin_seen = 0) + (SELECT COUNT(*) FROM bookings WHERE admin_seen = 0)");
                                $notif_count = $stmt_n->fetchColumn();
                            } else {
                                $stmt_n = $pdo->prepare("
                                    SELECT 
                                        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND user_seen = 0 AND coach_message IS NOT NULL) +
                                        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND (attendance = 'present' OR status = 'confirmed') AND user_rating IS NULL) +
                                        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND coach_session_notes IS NOT NULL AND notes_seen_by_user = 0)
                                ");
                                $stmt_n->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                                $notif_count = $stmt_n->fetchColumn();
                            }
                            ?>
                            <a href="#" class="notif-bell" id="notifBell" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <?php if($notif_count > 0): ?>
                                    <span class="notif-dot"><?php echo $notif_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="notif-dropdown" id="notifDropdown">
                                <!-- Content loaded via PHP/AJAX -->
                                <div class="notif-header">
                                    <h3>Notifications</h3>
                                </div>
                                <div class="notif-body">
                                    <?php if($notif_count == 0): ?>
                                        <p class="no-notif">No new notifications</p>
                                    <?php else: ?>
                                        <p class="loading-notif">Loading...</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>

                    <li>
                        <?php
                        $stmt_nav = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                        $stmt_nav->execute([$_SESSION['user_id']]);
                        $nav_user = $stmt_nav->fetch();

                        // Fetch active/pending membership subscription plan badge for user
                        $user_mem = false;
                        try {
                            $stmt_mem = $pdo->prepare("
                                SELECT mp.name, mp.name_vi, mp.badge, mp.badge_color, ms.status
                                FROM member_subscriptions ms
                                JOIN membership_plans mp ON ms.plan_id = mp.id
                                WHERE ms.user_id = ? AND ms.status IN ('active', 'pending') AND (ms.status = 'pending' OR ms.end_date >= CURDATE())
                                ORDER BY ms.id DESC LIMIT 1
                            ");
                            $stmt_mem->execute([$_SESSION['user_id']]);
                            $user_mem = $stmt_mem->fetch();
                        } catch (Exception $e) {
                            $user_mem = false;
                        }

                        // Only an active subscription is shown as a membership tier. Pending
                        // payments and accounts without a plan keep a neutral profile treatment.
                        $is_active_member = $user_mem && ($user_mem['status'] === 'active');
                        $mem_badge_text = '';
                        $mem_badge_bg = '#64748b';
                        $profile_membership_class = ' profile-pill-guest';
                        $profile_tier_icon = '';

                        if ($is_active_member) {
                            $raw_tier = strtoupper(trim((string) ($user_mem['badge'] ?: $user_mem['name'])));
                            if (strpos($raw_tier, 'WELLNESS') !== false || stripos($user_mem['name'], 'wellness') !== false) {
                                $membership_tier = 'wellness';
                                $mem_badge_text = 'WELLNESS';
                                $profile_tier_icon = 'fa-leaf';
                            } elseif (strpos($raw_tier, 'ATHLETIC') !== false || stripos($user_mem['name'], 'athletic') !== false || stripos($user_mem['name'], 'kickboxing') !== false) {
                                $membership_tier = 'athletic';
                                $mem_badge_text = 'ATHLETIC';
                                $profile_tier_icon = 'fa-bolt';
                            } else {
                                $membership_tier = 'starter';
                                $mem_badge_text = 'STARTER';
                                $profile_tier_icon = 'fa-star';
                            }

                            $profile_membership_class = ' profile-pill-member member-tier-' . $membership_tier;
                            $mem_badge_bg = !empty($user_mem['badge_color'])
                                ? $user_mem['badge_color']
                                : 'linear-gradient(135deg, #d97706, #f59e0b)';
                        } elseif ($user_mem && $user_mem['status'] === 'pending') {
                            $mem_badge_text = ($_SESSION['lang'] ?? 'en') === 'vi' ? 'ĐANG CHỜ' : 'PENDING';
                            $mem_badge_bg = '#d97706';
                            $profile_membership_class = ' profile-pill-pending';
                        } else {
                            // A user with no membership must not be presented as a Starter member.
                            $mem_badge_text = ($_SESSION['role'] ?? 'user') === 'user'
                                ? (($_SESSION['lang'] ?? 'en') === 'vi' ? 'CHƯA CÓ GÓI' : 'NO PLAN')
                                : strtoupper($_SESSION['role'] ?? 'USER');
                        }

                        $nav_avatar_fallback = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=0062ff&color=fff&size=40';
                        $nav_avatar = ($nav_user && !empty($nav_user['avatar']))
                            ? get_image_url($nav_user['avatar'])
                            : $nav_avatar_fallback;
                        ?>
                        <a href="/Gym Manager/dashboards/profile.php" class="profile-pill<?php echo $profile_membership_class; ?>" title="<?php echo htmlspecialchars($mem_badge_text); ?>">
                            <img src="<?php echo htmlspecialchars($nav_avatar); ?>" alt="User Avatar" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($nav_avatar_fallback, ENT_QUOTES); ?>';">
                            <div class="profile-info">
                                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <span class="profile-plan-badge" style="background: <?php echo $mem_badge_bg; ?>;">
                                    <?php if ($profile_tier_icon): ?><i class="fas <?php echo $profile_tier_icon; ?>" aria-hidden="true"></i><?php endif; ?>
                                    <?php echo htmlspecialchars($mem_badge_text); ?>
                                </span>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="nav-logout-btn" title="<?php echo __('logout'); ?>" onclick="openLogoutModal(event);">
                            <i class="fas fa-sign-out-alt"></i>
                            <span><?php echo __('logout'); ?></span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="auth-btns">
                        <a href="/Gym Manager/auth/login.php" class="btn-login"><?php echo __('login'); ?></a>
                        <a href="/Gym Manager/auth/register.php" class="btn-register"><?php echo __('register'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        </div>
    </nav>

    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(6px);">
        <div style="background: white; padding: 2.2rem 2rem; border-radius: 24px; width: 90%; max-width: 440px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid rgba(226, 232, 240, 0.8); position: relative;">
            <div style="width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.2rem auto; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 style="margin: 0 0 0.5rem 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?php echo __('logout_confirm_title'); ?></h3>
            <p style="color: #64748b; font-size: 0.95rem; line-height: 1.5; margin: 0 0 1.8rem 0;"><?php echo __('logout_confirm_msg'); ?></p>
            <div style="display: flex; gap: 0.8rem; justify-content: center;">
                <button type="button" onclick="closeLogoutModal()" style="flex: 1; padding: 0.9rem 1.2rem; background: #f1f5f9; color: #475569; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    <?php echo __('cancel'); ?>
                </button>
                <a href="/Gym Manager/auth/logout.php" style="flex: 1; padding: 0.9rem 1.2rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); transition: all 0.2s;">
                    <i class="fas fa-sign-out-alt" style="margin-right: 6px;"></i> <?php echo __('confirm_logout_btn'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/coach_notification.php'; ?>
    <?php include_once __DIR__ . '/admin_notification.php'; ?>
    <?php include_once __DIR__ . '/user_membership_notification.php'; ?>
    <?php include_once __DIR__ . '/session_eval_modals.php'; ?>

    <script>
        function openLogoutModal(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.style.display = 'flex';
            }
            return false;
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Language Switcher Toggle
        const langToggle = document.querySelector('.lang-switcher');
        if (langToggle) {
            langToggle.addEventListener('click', function(e) {
                if (e.target.closest('a')) return; // Don't toggle if clicking a link
                this.classList.toggle('active');
                e.stopPropagation();
            });
        }

        document.addEventListener('click', function(e) {
            if (langToggle) langToggle.classList.remove('active');
            if (typeof notifDropdown !== 'undefined' && notifDropdown) notifDropdown.classList.remove('active');
            const logoutModal = document.getElementById('logoutConfirmModal');
            if (logoutModal && e.target === logoutModal) {
                closeLogoutModal();
            }
        });

        // Notification Logic
        const notifBell = document.getElementById('notifBell');
        const notifDropdown = document.getElementById('notifDropdown');
        const notifBody = document.querySelector('.notif-body');

        if (notifBell) {
            notifBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (notifDropdown) {
                    notifDropdown.classList.toggle('active');
                    
                    if (notifDropdown.classList.contains('active')) {
                        fetch('/Gym Manager/includes/notif_fetch.php')
                            .then(response => response.text())
                            .then(html => {
                                if (notifBody) notifBody.innerHTML = html;
                            });
                    }
                }
            });
        }
        
        if (notifDropdown) {
            notifDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    </script>


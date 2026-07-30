<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Manager - Reach your dream physique</title>
    <link rel="stylesheet" href="/Gym Manager/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom Premium CTA Button in Navbar */
        .nav-consult-btn {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff !important;
            padding: 8px 16px !important;
            border-radius: 30px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-consult-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #ffffff !important;
        }
        .nav-consult-btn:active {
            transform: translateY(0);
        }
        .nav-consult-btn i {
            font-size: 0.9rem;
            animation: pulse-icon 2s infinite;
        }
        @keyframes pulse-icon {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <nav>
        <a href="/Gym Manager/index.php" class="logo">GYM<span>X</span></a>
        
        <!-- Main Navigation Links -->
        <ul class="nav-links">
            <li><a href="/Gym Manager/index.php"><?php echo __('home'); ?></a></li>
            <li class="nav-item-dropdown">
                <a href="#" class="dropdown-trigger"><?php echo __('programs_label'); ?> <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 3px;"></i></a>
                <ul class="dropdown-menu">
                    <?php 
                    $stmt_spec = $pdo->query("SELECT DISTINCT specialty FROM coaches WHERE specialty IS NOT NULL AND specialty != ''");
                    $specialties = $stmt_spec->fetchAll(PDO::FETCH_COLUMN);
                    foreach($specialties as $spec): 
                        $icon = 'fa-medal';
                        if(stripos($spec, 'gym') !== false) $icon = 'fa-dumbbell';
                        elseif(stripos($spec, 'yoga') !== false) $icon = 'fa-leaf';
                        elseif(stripos($spec, 'muay') !== false) $icon = 'fa-fist-raised';
                        
                        $url_spec = urlencode(strtolower(str_replace(' ', '_', $spec)));
                    ?>
                        <li><a href="/Gym Manager/pages/coaches.php?discipline=<?php echo $url_spec; ?>"><i class="fas <?php echo $icon; ?>" style="width: 20px;"></i> <?php echo htmlspecialchars($spec); ?></a></li>
                    <?php endforeach; ?>
                    <?php if(empty($specialties)): ?>
                        <li><a href="#">No programs yet</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager'): ?>
            <li><a href="/Gym Manager/pages/shop.php"><?php echo __('shop'); ?></a></li>
            <?php endif; ?>
            <?php if(!isset($_SESSION['role'])): ?>
            <li><a href="/Gym Manager/pages/coaches.php"><?php echo __('coaches'); ?></a></li>
            <?php endif; ?>
            <li><a href="/Gym Manager/pages/about.php"><?php echo __('about'); ?></a></li>
            <li><a href="/Gym Manager/features/calendar.php"><?php echo __('calendar'); ?></a></li>
            <li><a href="#" class="nav-consult-btn" onclick="openConsultationWizard(); return false;"><i class="fas fa-headset"></i><?php echo __('nav_consultation'); ?></a></li>
        </ul>

        <!-- Right Side Actions -->
        <ul class="nav-actions">
            <!-- Language Switcher - Traditional Style -->
            <li class="lang-switcher">
                <?php if($_SESSION['lang'] == 'vi'): ?>
                    <img src="https://flagcdn.com/w40/vn.png" class="lang-flag" alt="VI">
                    <span>VI</span>
                <?php else: ?>
                    <img src="https://flagcdn.com/w40/gb.png" class="lang-flag" alt="EN">
                    <span>EN</span>
                <?php endif; ?>
                <i class="fas fa-chevron-down"></i>
                
                <div class="lang-dropdown">
                    <?php if($_SESSION['lang'] != 'vi'): ?>
                        <a href="?lang=vi" class="lang-item">
                            <img src="https://flagcdn.com/w40/vn.png" class="lang-flag" alt="VI">
                            <span>VI</span>
                        </a>
                    <?php endif; ?>
                    <?php if($_SESSION['lang'] != 'en'): ?>
                        <a href="?lang=en" class="lang-item">
                            <img src="https://flagcdn.com/w40/gb.png" class="lang-flag" alt="EN">
                            <span>EN</span>
                        </a>
                    <?php endif; ?>
                </div>
            </li>

            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <li><a href="/Gym Manager/dashboards/admin.php"><?php echo __('admin'); ?></a></li>
                <?php elseif($_SESSION['role'] == 'manager'): ?>
                    <li><a href="/Gym Manager/dashboards/manager.php"><?php echo __('manager_panel'); ?></a></li>
                <?php endif; ?>

                <?php if($_SESSION['role'] == 'coach' || $_SESSION['role'] == 'user' || $_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                    <li class="notification-wrapper">
                        <?php
                        $notif_count = 0;
                        if($_SESSION['role'] == 'coach') {
                            $stmt_n = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN coaches c ON b.coach_id = c.id WHERE c.user_id = ? AND b.coach_seen = 0");
                            $stmt_n->execute([$_SESSION['user_id']]);
                            $notif_count = $stmt_n->fetchColumn();
                        } elseif($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') {
                            $stmt_n = $pdo->query("SELECT COUNT(*) FROM orders WHERE admin_seen = 0");
                            $notif_count = $stmt_n->fetchColumn();
                        } else {
                            $stmt_n = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND user_seen = 0 AND coach_message IS NOT NULL");
                            $stmt_n->execute([$_SESSION['user_id']]);
                            $notif_count = $stmt_n->fetchColumn();
                        }
                        ?>
                        <a href="#" class="notif-bell" id="notifBell">
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
                
                <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager'): ?>
                <?php $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
                <li><a href="/Gym Manager/pages/cart.php"><?php echo __('cart'); ?> (<?php echo $cart_count; ?>)</a></li>
                <?php endif; ?>
                
                <li>
                    <?php
                    $stmt_nav = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                    $stmt_nav->execute([$_SESSION['user_id']]);
                    $nav_user = $stmt_nav->fetch();
                    ?>
                    <a href="/Gym Manager/dashboards/profile.php" class="profile-pill">
                        <img src="<?php echo ($nav_user && $nav_user['avatar']) ? $nav_user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name']).'&background=0062ff&color=fff&size=40'; ?>" 
                             style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid var(--primary);">
                        <span><?php echo $_SESSION['user_name']; ?></span>
                    </a>
                </li>
                <li><a href="/Gym Manager/auth/logout.php"><?php echo __('logout'); ?></a></li>
            <?php else: ?>
                <li class="auth-btns">
                    <a href="/Gym Manager/auth/login.php" class="btn-login"><?php echo __('login'); ?></a>
                    <a href="/Gym Manager/auth/register.php" class="btn-register"><?php echo __('register'); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php include_once __DIR__ . '/coach_notification.php'; ?>
    <?php include_once __DIR__ . '/admin_notification.php'; ?>

    <script>
        // Language Switcher Toggle
        const langToggle = document.querySelector('.lang-switcher');
        if (langToggle) {
            langToggle.addEventListener('click', function(e) {
                if (e.target.closest('a')) return; // Don't toggle if clicking a link
                this.classList.toggle('active');
                e.stopPropagation();
            });
        }

        document.addEventListener('click', function() {
            if (langToggle) langToggle.classList.remove('active');
            if (notifDropdown) notifDropdown.classList.remove('active');
        });

        // Notification Logic
        const notifBell = document.getElementById('notifBell');
        const notifDropdown = document.getElementById('notifDropdown');
        const notifBody = document.querySelector('.notif-body');

        if (notifBell) {
            notifBell.addEventListener('click', function(e) {
                const isCoach = <?php echo json_encode(isset($_SESSION['role']) && $_SESSION['role'] === 'coach'); ?>;
                
                if (isCoach) {
                    window.location.href = '/Gym Manager/notifications/coach_notifications.php';
                } else {
                    e.preventDefault();
                    e.stopPropagation();
                    notifDropdown.classList.toggle('active');
                    
                    if (notifDropdown.classList.contains('active')) {
                        fetch('/Gym Manager/includes/notif_fetch.php')
                            .then(response => response.text())
                            .then(html => {
                                notifBody.innerHTML = html;
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
    
    <?php include_once __DIR__ . '/consultation_wizard.php'; ?>

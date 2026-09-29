<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

if (!isset($_GET['id'])) {
    header("Location: /Gym Manager/pages/coaches.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT c.*, u.name as coach_name, u.email, u.avatar FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$id]);
$coach = $stmt->fetch();

if (!$coach) {
    include_once __DIR__ . '/../includes/header.php';
    echo "<section><h1>" . __('coach_not_found') . "</h1></section>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$message = '';
$error = '';



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_now']) && isset($_SESSION['user_id'])) {
    $raw_dates_input = $_POST['booking_dates'] ?? ($_POST['booking_date'] ?? '');
    $booking_dates = [];
    if (is_array($raw_dates_input)) {
        $booking_dates = $raw_dates_input;
    } elseif (!empty($raw_dates_input)) {
        if (strpos($raw_dates_input, '[') === 0) {
            $booking_dates = json_decode($raw_dates_input, true) ?: [];
        } else {
            $booking_dates = explode(',', $raw_dates_input);
        }
    }

    $valid_dates = [];
    foreach ($booking_dates as $d) {
        $trimmed = trim($d);
        if (!empty($trimmed)) {
            $ts = strtotime($trimmed);
            if ($ts && $ts >= time()) {
                $valid_dates[] = date('Y-m-d H:i:s', $ts);
            }
        }
    }
    $valid_dates = array_values(array_unique($valid_dates));
    sort($valid_dates);

    $user_phone = $_POST['user_phone'] ?? '';
    $user_address = $_POST['user_address'] ?? '';
    
    // Validation: phone format, valid future dates
    if (!preg_match('/^[0-9]{10}$/', $user_phone)) {
        $error = __('phone_invalid');
    } elseif (empty($valid_dates)) {
        $error = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi')
            ? "⚠️ Vui lòng chọn ít nhất một khung giờ tập luyện."
            : "⚠️ Please select at least one workout time slot.";
    } else {
        // Check for conflicts: Chỉ bắt lỗi khi trùng ngày, giờ và huấn luyện viên
        $conflict_found = false;
        $conflict_msg = '';
        $selected_slots = [];

        foreach ($valid_dates as $b_date) {
            $requested_time = strtotime($b_date);
            $day_hour_key = date('Y-m-d H', $requested_time);

            // Kiểm tra trùng lặp trong cùng 1 lần chọn đặt
            if (in_array($day_hour_key, $selected_slots)) {
                $conflict_found = true;
                $slot_fmt = date('d/m/Y H:i', $requested_time);
                $conflict_msg = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi')
                    ? "⚠️ Bạn đang chọn trùng khung giờ $slot_fmt cho HLV này. Vui lòng chọn khung giờ khác!"
                    : "⚠️ Duplicate time slot $slot_fmt selected. Please choose another time!";
                break;
            }
            $selected_slots[] = $day_hour_key;

            // Chỉ bắt lỗi khi trùng ngày, giờ và huấn luyện viên này
            $conflict_stmt = $pdo->prepare("
                SELECT id FROM bookings 
                WHERE coach_id = ? 
                  AND status NOT IN ('cancelled', 'rejected') 
                  AND DATE(booking_date) = DATE(?) 
                  AND HOUR(booking_date) = HOUR(?)
            ");
            $conflict_stmt->execute([$id, $b_date, $b_date]);

            if ($conflict_stmt->fetch()) {
                $conflict_found = true;
                $slot_fmt = date('d/m/Y H:i', $requested_time);
                $conflict_msg = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi')
                    ? "⚠️ Huấn luyện viên đã có lịch tập vào khung giờ $slot_fmt (trùng ngày, giờ và HLV). Vui lòng chọn khung giờ khác!"
                    : "⚠️ The coach already has a session at $slot_fmt. Please choose another slot!";
                break;
            }
        }

        if ($conflict_found) {
            $error = $conflict_msg;
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status, user_phone, user_address) VALUES (?, ?, ?, 'pending', ?, ?)");
                $booking_ids = [];

                foreach ($valid_dates as $b_date) {
                    $stmt->execute([$_SESSION['user_id'], $id, $b_date, $user_phone, $user_address]);
                    $booking_ids[] = $pdo->lastInsertId();
                }

                // Save info back to user profile
                $stmt_u = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ? AND (phone IS NULL OR address IS NULL OR phone != ? OR address != ?)");
                $stmt_u->execute([$user_phone, $user_address, $_SESSION['user_id'], $user_phone, $user_address]);
                $pdo->commit();

                // Send Email Notifications
                require_once __DIR__ . '/../includes/booking_mailer.php';
                foreach ($booking_ids as $b_id) {
                    sendNewBookingNotificationEmail($pdo, $b_id);
                }

                $message = count($valid_dates) > 1 
                    ? ((isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi')
                        ? "🎉 Đã gửi thành công yêu cầu đặt " . count($valid_dates) . " khung giờ tập! HLV sẽ liên hệ xác nhận sớm nhất."
                        : "🎉 Successfully requested " . count($valid_dates) . " workout sessions! The coach will confirm shortly.")
                    : __('booking_success');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = __('booking_error');
            }
        }
    }
} elseif (isset($_POST['book_now'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];
    
    // Check if user has a confirmed booking with this coach
    $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND coach_id = ? AND status = 'confirmed'");
    $check_stmt->execute([$user_id, $id]);
    
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO coach_reviews (coach_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$id, $user_id, $rating, $comment])) {
            $message = __('review_success');
        } else {
            $error = __('review_error');
        }
    } else {
        $error = __('review_restriction');
    }
}

// Fetch Reviews
$rev_stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.avatar 
    FROM coach_reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.coach_id = ? 
    ORDER BY r.created_at DESC
");
$rev_stmt->execute([$id]);
$reviews = $rev_stmt->fetchAll();

// Fetch Average Rating
$avg_stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as count FROM coach_reviews WHERE coach_id = ?");
$avg_stmt->execute([$id]);
$rating_data = $avg_stmt->fetch();

// Get current user info for autofill
$curr_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt_curr = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
    $stmt_curr->execute([$_SESSION['user_id']]);
    $curr_user = $stmt_curr->fetch();
}
?>

<section class="coach-detail-page-wrap">
    <?php if($message): ?>
        <p style="color: #00C851; text-align: center; margin-bottom: 1.5rem; background: rgba(0,200,81,0.1); padding: 1rem 1.5rem; border-radius: 12px; font-weight: 700;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if($error): ?>
        <div style="background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; padding: 1.25rem 1.5rem; border-radius: 16px; margin-bottom: 1.5rem; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12); line-height: 1.6; max-width: 800px; margin-left: auto; margin-right: auto;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Two-Column Split Coach Dashboard (Left: Profile, Right: Booking) -->
    <div class="coach-dashboard-split-grid">
        <!-- LEFT COLUMN: Thông tin hồ sơ Coach -->
        <div class="coach-dashboard-left-col">
            <div class="coach-profile-card">
                <?php 
                $display_img = $coach['avatar'] ?: ($coach['image'] ?: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=2070&auto=format&fit=crop');
                ?>
                <div class="coach-profile-top-header">
                    <div class="coach-avatar-wrapper">
                        <img src="<?php echo $display_img; ?>" class="coach-avatar-img" alt="<?php echo htmlspecialchars($coach['coach_name']); ?>">
                        <div class="coach-badge-status">
                            <span class="status-dot"></span>
                            <span><?php echo __('ready_for_booking'); ?></span>
                        </div>
                    </div>

                    <div class="coach-top-info">
                        <div class="coach-tags-row">
                            <span class="specialty-pill"><?php echo htmlspecialchars($coach['specialty']); ?></span>
                            <?php if (!empty($coach['room_name']) || !empty($coach['room_number'])): ?>
                                <span class="room-pill">
                                    <i class="fas fa-door-open" style="color: var(--primary);"></i>
                                    <?php if (!empty($coach['room_name'])): ?>
                                        <span><?php echo htmlspecialchars($coach['room_name']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($coach['room_number'])): ?>
                                        <span class="room-num"><?php echo ((($_SESSION['lang'] ?? 'en') === 'vi') ? 'Phòng ' : 'Room ') . htmlspecialchars($coach['room_number']); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h1 class="coach-title-name"><?php echo __('coach_label') . ' ' . htmlspecialchars($coach['coach_name']); ?></h1>

                        <div class="coach-meta-chips-row">
                            <span class="meta-chip-item price-chip">
                                💰 <strong>$<?php echo number_format((float)$coach['hourly_rate'], 2); ?></strong><?php echo __('hour_label'); ?>
                            </span>
                            <span class="meta-chip-item email-chip">
                                📧 <span><?php echo htmlspecialchars($coach['email']); ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Review Coach Action Button -->
                <div class="coach-review-trigger-wrapper" style="margin-bottom: 1.2rem;">
                    <button type="button" id="btnToggleReviews" class="review-coach-trigger-btn" title="<?php echo __('view_reviews_btn'); ?>" style="width: 100%; justify-content: center; padding: 0.8rem 1rem;">
                        <span class="stars-gold">
                            <?php 
                            $rating = round($rating_data['avg_rating']);
                            for($i=1; $i<=5; $i++) echo $i <= $rating ? "★" : "☆";
                            ?>
                        </span>
                        <span class="review-badge-label"><?php echo __('review_coach'); ?> (<?php echo $rating_data['count']; ?>)</span>
                        <i class="fas fa-chevron-down chevron-indicator"></i>
                    </button>
                </div>

                <?php if (!empty($coach['bio'])): ?>
                    <div class="coach-bio-box">
                        <h4 class="coach-section-mini-title">
                            <i class="fas fa-user-tie" style="color: var(--primary);"></i>
                            <?php echo (($_SESSION['lang'] ?? 'en') === 'vi') ? 'Giới Thiệu Huấn Luyện Viên' : 'About Coach'; ?>
                        </h4>
                        <p class="coach-bio-text"><?php echo nl2br(htmlspecialchars($coach['bio'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'manager')): ?>
                    <div class="coach-quick-contact-row">
                        <?php if(!empty($coach['zalo'] ?? null)): ?>
                            <a href="<?php echo (strpos($coach['zalo'], 'http') === 0) ? $coach['zalo'] : 'https://zalo.me/'.$coach['zalo']; ?>" 
                               target="_blank" class="contact-btn zalo" style="flex: 1; justify-content: center;">
                                <img src="https://img.icons8.com/color/48/zalo.png" style="width: 20px;" alt="Zalo">
                                <?php echo __('contact_zalo'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if(!empty($coach['gmail'] ?? null)): ?>
                            <a href="mailto:<?php echo $coach['gmail']; ?>" class="contact-btn gmail" style="flex: 1; justify-content: center;">
                                <img src="https://img.icons8.com/color/48/gmail-new.png" style="width: 20px;" alt="Gmail">
                                <?php echo __('send_gmail'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Dùng để đặt lịch tập -->
        <div class="coach-dashboard-right-col">
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach'): ?>
            <div id="bookingSection" class="coach-booking-card-simple">
                <div class="booking-simple-header">
                    <h3 class="booking-simple-title">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
                        <?php echo __('book_session_title'); ?>
                    </h3>
                    <p class="booking-simple-subtitle"><?php echo __('book_session_subtitle'); ?></p>
                </div>

                <form method="POST" id="coachBookingForm">
                    <div class="form-group" style="margin-bottom: 1.2rem;">
                        <label class="simple-form-label">
                            <span><i class="fas fa-phone-alt" style="color: var(--primary); margin-right: 6px;"></i> <?php echo __('your_phone_label'); ?></span>
                            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal;">(10 chữ số)</span>
                        </label>
                        <input type="text" name="user_phone" required placeholder="090x xxx xxx" pattern="[0-9]{10}" title="<?php echo __('phone_invalid'); ?>"
                               value="<?php echo isset($curr_user['phone']) ? htmlspecialchars($curr_user['phone']) : ''; ?>"
                               class="simple-form-input">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.2rem;">
                        <label class="simple-form-label">
                            <span><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 6px;"></i> <?php echo __('your_address_label'); ?></span>
                        </label>
                        <input type="text" name="user_address" required placeholder="<?php echo __('address_placeholder'); ?>" 
                               value="<?php echo isset($curr_user['address']) ? htmlspecialchars($curr_user['address']) : ''; ?>"
                               class="simple-form-input">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.4rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                            <label class="simple-form-label" style="margin: 0;">
                                <span><i class="fas fa-clock" style="color: var(--primary); margin-right: 6px;"></i> <?php echo __('select_date_time_label'); ?></span>
                            </label>
                            <button type="button" id="btnAddSlot" class="btn-add-extra-slot" title="Thêm khung giờ tập khác trong ngày">
                                <i class="fas fa-plus-circle"></i> <?php echo (($_SESSION['lang'] ?? 'en') === 'en') ? '+ Add another time' : '+ Thêm giờ tập'; ?>
                            </button>
                        </div>

                        <div id="bookingDatesContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="booking-date-row">
                                <input type="datetime-local" name="booking_dates[]" required 
                                       min="<?php echo date('Y-m-d\TH:i'); ?>"
                                       class="simple-form-input booking-date-field">
                            </div>
                        </div>

                        <!-- Suggestions & Hints Box -->
                        <div class="booking-hints-box">
                            <div class="hints-head">
                                <i class="fas fa-lightbulb" style="color: #f59e0b;"></i>
                                <span><?php echo (($_SESSION['lang'] ?? 'en') === 'en') ? 'Suggested workout slots:' : 'Gợi ý khung giờ tập thuận tiện:'; ?></span>
                            </div>
                            <div class="hints-chips-list" id="quickHintPills">
                                <!-- Populated by JS -->
                            </div>
                            <div class="hints-note">
                                <i class="fas fa-info-circle" style="color: #0284c7;"></i>
                                <span><?php echo (($_SESSION['lang'] ?? 'en') === 'en')
                                    ? 'Coach accepts bookings from 06:00 to 22:00 (60 mins/session). Click any suggestion above to autofill.'
                                    : 'HLV nhận lịch từ 06:00 đến 22:00 hàng ngày (mỗi buổi 60 phút). Bấm vào gợi ý để điền nhanh hoặc tự chọn theo lịch của bạn.'; ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="book_now" class="cta-btn booking-simple-submit-btn">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        <?php echo __('send_booking_request'); ?>
                    </button>

                    <a href="/Gym Manager/pages/coaches.php" class="back-to-coaches-link">
                        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_coaches'); ?>
                    </a>
                </form>
            </div>
            <?php else: ?>
            <div class="coach-booking-card-simple" style="text-align: center; padding: 3rem 2rem;">
                <div style="width: 70px; height: 70px; background: rgba(0,98,255,0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.2rem; color: var(--primary); font-size: 1.8rem;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-main);">
                    <?php echo (($_SESSION['lang'] ?? 'en') === 'en') ? 'Coach View' : 'Tài Khoản Huấn Luyện Viên'; ?>
                </h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    <?php echo (($_SESSION['lang'] ?? 'en') === 'en') 
                        ? 'You are logged in as a Coach. To view and manage appointments requested by trainees, visit your schedule manager.'
                        : 'Bạn đang đăng nhập dưới quyền Huấn luyện viên. Để kiểm tra danh sách lịch tập học viên đã đặt, vui lòng truy cập trang quản lý lịch tập.'; ?>
                </p>
                <a href="/Gym Manager/coach/schedule.php" class="cta-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 0.85rem 1.8rem;">
                    <i class="fas fa-calendar-alt"></i> <?php echo (($_SESSION['lang'] ?? 'en') === 'en') ? 'Go to My Schedule' : 'Quản Lý Lịch Tập Của Tôi'; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collapsible Reviews Drawer ("Xổ ra toàn bộ đánh giá" - Full width below the split dashboard) -->
    <div id="coachReviewsDrawer" class="coach-reviews-drawer" style="display: none;">
        <div class="drawer-header-bar">
            <div class="drawer-title-group">
                <h3 class="drawer-title">
                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                    <?php echo __('review_coach_title'); ?>
                </h3>
                <span class="drawer-rating-tag">
                    ★ <?php echo number_format((float)$rating_data['avg_rating'], 1); ?> / 5.0 
                    (<?php echo $rating_data['count']; ?> <?php echo __('reviews'); ?>)
                </span>
            </div>
            <button type="button" id="btnCloseReviews" class="drawer-collapse-btn">
                <i class="fas fa-times"></i> <?php echo __('collapse_reviews'); ?>
            </button>
        </div>

        <div class="drawer-reviews-body-grid">
            <!-- Review List -->
            <div class="reviews-feed-col">
                <?php if(empty($reviews)): ?>
                    <div class="empty-reviews-state">
                        <i class="far fa-comment-dots" style="font-size: 2.2rem; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;"><?php echo __('no_reviews_yet'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="reviews-feed-list">
                        <?php foreach($reviews as $rev): ?>
                            <div class="review-bubble-card">
                                <div class="review-bubble-top">
                                    <img src="<?php echo $rev['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($rev['user_name']).'&background=0062ff&color=fff&size=50'; ?>" 
                                         class="rev-user-avatar" alt="<?php echo htmlspecialchars($rev['user_name']); ?>">
                                    <div>
                                        <h4 class="rev-user-name"><?php echo htmlspecialchars($rev['user_name']); ?></h4>
                                        <div class="rev-stars">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? "★" : "☆"; ?>
                                        </div>
                                    </div>
                                    <span class="rev-date"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></span>
                                </div>
                                <p class="rev-comment-text"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Write Review -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="write-review-card-col">
                    <h4 class="write-review-heading">
                        <i class="fas fa-pen" style="color: var(--primary);"></i>
                        <?php echo __('leave_review'); ?>
                    </h4>
                    <form method="POST" action="#coachReviewsDrawer">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label style="color: var(--text-main); margin-bottom: 0.5rem; display: block; font-weight: 700; font-size: 0.9rem;"><?php echo __('rating_label'); ?></label>
                            <select name="rating" required style="width: 100%; padding: 0.8rem 1rem; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; color: var(--text-main); outline: none; font-size: 0.95rem; font-weight: 600;">
                                <option value="5">5 ★ - <?php echo __('stars_5'); ?></option>
                                <option value="4">4 ★ - <?php echo __('stars_4'); ?></option>
                                <option value="3">3 ★ - <?php echo __('stars_3'); ?></option>
                                <option value="2">2 ★ - <?php echo __('stars_2'); ?></option>
                                <option value="1">1 ★ - <?php echo __('stars_1'); ?></option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label style="color: var(--text-main); margin-bottom: 0.5rem; display: block; font-weight: 700; font-size: 0.9rem;"><?php echo __('comment_label'); ?></label>
                            <textarea name="comment" required placeholder="<?php echo __('comment_placeholder'); ?>" 
                                      style="width: 100%; padding: 0.9rem 1rem; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; color: var(--text-main); outline: none; min-height: 100px; font-size: 0.95rem; resize: vertical;"></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn-auth" style="width: 100%; padding: 0.85rem; font-weight: 700; font-size: 0.95rem; border-radius: 12px;">
                            <i class="fas fa-paper-plane" style="margin-right: 6px;"></i> <?php echo __('post_review'); ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="write-review-card-col" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 18px; padding: 2rem 1.5rem;">
                    <i class="fas fa-lock" style="font-size: 2rem; color: #94a3b8; margin-bottom: 12px;"></i>
                    <h4 style="margin: 0 0 6px; font-size: 1.05rem; color: var(--text-main); font-weight: 700;">
                        <?php echo (($_SESSION['lang'] ?? 'en') === 'vi') ? 'Đăng nhập để viết đánh giá' : 'Log in to write a review'; ?>
                    </h4>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.2rem;">
                        <?php echo (($_SESSION['lang'] ?? 'en') === 'vi') ? 'Bạn cần hoàn thành buổi tập với HLV này để gửi nhận xét chân thực.' : 'You need to complete a session with this coach to write a verified review.'; ?>
                    </p>
                    <a href="/Gym Manager/auth/login.php" class="cta-btn" style="padding: 0.65rem 1.5rem; font-size: 0.88rem; border-radius: 10px; text-decoration: none;">
                        <?php echo (($_SESSION['lang'] ?? 'en') === 'vi') ? 'Đăng Nhập Ngay' : 'Log In Now'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* Page Layout */
.coach-detail-page-wrap {
    max-width: 1240px;
    margin: 0 auto;
    padding: 1.5rem 1.25rem 3.5rem;
}

/* Two-Column Split Coach Dashboard */
.coach-dashboard-split-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
    margin-bottom: 2.5rem;
}
.coach-dashboard-left-col,
.coach-dashboard-right-col {
    min-width: 0;
}

/* 1. Coach Profile Card (Left Column) */
.coach-profile-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 2.2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.coach-profile-top-header {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1.4rem;
}

.coach-avatar-wrapper {
    position: relative;
    width: 140px;
    height: 140px;
    flex: 0 0 140px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 24px rgba(0,0,0,0.12);
    border: 3px solid #ffffff;
}

.coach-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.coach-avatar-wrapper:hover .coach-avatar-img {
    transform: scale(1.05);
}

.coach-badge-status {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(16, 185, 129, 0.95);
    backdrop-filter: blur(4px);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #ffffff;
    animation: pulseDot 1.5s infinite;
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.85); }
}

.coach-top-info {
    flex: 1;
    min-width: 0;
}

.coach-tags-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.specialty-pill {
    background: rgba(0, 98, 255, 0.08);
    color: var(--primary);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.room-pill {
    background: #f1f5f9;
    color: var(--text-main);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.room-num {
    background: var(--primary);
    color: #ffffff;
    padding: 1px 7px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

.coach-title-name {
    font-size: 1.7rem;
    font-weight: 900;
    color: var(--text-main);
    margin: 0 0 0.6rem;
    line-height: 1.25;
}

.coach-meta-chips-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 0;
}

.meta-chip-item {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-main);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 5px 12px;
    border-radius: 12px;
}
.price-chip strong {
    color: var(--primary);
}

/* Interactive Review Coach Trigger Button */
.review-coach-trigger-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    color: #92400e;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.92rem;
    cursor: pointer;
    transition: all 0.25s ease;
}
.review-coach-trigger-btn:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
}
.review-coach-trigger-btn.open {
    background: #fef3c7;
    border-color: #d97706;
}
.review-coach-trigger-btn .stars-gold {
    color: #f59e0b;
    letter-spacing: 1px;
    font-size: 0.95rem;
}
.review-coach-trigger-btn .review-badge-label {
    font-weight: 800;
}
.review-coach-trigger-btn .chevron-indicator {
    font-size: 0.75rem;
    color: #b45309;
    transition: transform 0.25s ease;
}

.coach-bio-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    margin-bottom: 1.4rem;
}
.coach-section-mini-title {
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.coach-bio-text {
    font-size: 0.92rem;
    line-height: 1.6;
    color: #475569;
    margin: 0;
}

.coach-quick-contact-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* 2. Reviews Collapsible Drawer */
.coach-reviews-drawer {
    background: #ffffff;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
    animation: slideDownFade 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideDownFade {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.drawer-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1.2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 12px;
}
.drawer-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}
.drawer-title {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}
.drawer-rating-tag {
    background: #fffbeb;
    color: #b45309;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    border: 1px solid #fde68a;
}
.drawer-collapse-btn {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 6px 14px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.drawer-collapse-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.drawer-reviews-body-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 2.5rem;
    align-items: start;
}
.reviews-feed-list {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-height: 480px;
    overflow-y: auto;
    padding-right: 6px;
}
.review-bubble-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.2rem 1.4rem;
}
.review-bubble-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.6rem;
}
.rev-user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.rev-user-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-main);
}
.rev-stars {
    color: #f59e0b;
    font-size: 0.8rem;
    letter-spacing: 1px;
}
.rev-date {
    margin-left: auto;
    font-size: 0.78rem;
    color: #94a3b8;
}
.rev-comment-text {
    margin: 0;
    font-size: 0.92rem;
    color: #475569;
    line-height: 1.55;
}
.write-review-card-col {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.5rem;
}
.write-review-heading {
    margin: 0 0 1.2rem;
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}
.empty-reviews-state {
    text-align: center;
    padding: 2.5rem 1rem;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
}

/* 2. Simple Booking Card (Right Column) */
.coach-booking-card-simple {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 2.2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    width: 100%;
    max-width: 100%;
    margin: 0;
}
.booking-simple-header {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}
.booking-simple-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.booking-simple-subtitle {
    font-size: 0.92rem;
    color: var(--text-muted);
    margin: 0;
}

.active-session-warning-box {
    background: #fff8e6;
    border: 1.5px solid #fde68a;
    border-radius: 16px;
    padding: 1.2rem 1.5rem;
    margin-bottom: 1.5rem;
    color: #92400e;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.1);
}
.warning-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-weight: 800;
    font-size: 0.95rem;
    color: #b45309;
}
.warning-desc {
    margin: 0;
    font-size: 0.88rem;
    line-height: 1.5;
    color: #78350f;
}

.booking-room-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 98, 255, 0.05);
    border: 1px solid rgba(0, 98, 255, 0.15);
    padding: 0.75rem 1.2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.92rem;
    color: var(--text-main);
}
.booking-room-badge .room-tag {
    background: var(--primary);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 2px 10px;
    border-radius: 12px;
    margin-left: auto;
}

.simple-form-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.5rem;
}
.simple-form-input {
    width: 100%;
    padding: 0.95rem 1.1rem;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    color: var(--text-main);
    outline: none;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.2s ease;
}
.simple-form-input:focus {
    background: #ffffff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(0,98,255,0.08);
}

.btn-add-extra-slot {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: var(--primary);
    font-weight: 700;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}
.btn-add-extra-slot:hover {
    background: #e2e8f0;
    border-color: var(--primary);
}

.booking-date-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-remove-date-row {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
    font-size: 0.9rem;
}
.btn-remove-date-row:hover {
    background: #dc2626;
    color: #ffffff;
}

/* Hints / Suggestions Box */
.booking-hints-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    margin-top: 0.8rem;
}
.hints-head {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.6rem;
}
.hints-chips-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 0.6rem;
}
.hint-chip-btn {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.hint-chip-btn:hover {
    border-color: var(--primary);
    background: rgba(0, 98, 255, 0.06);
    color: var(--primary);
    transform: translateY(-1px);
}
.hints-note {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.4;
}

.booking-simple-submit-btn {
    width: 100%;
    border: none;
    cursor: pointer;
    padding: 1rem;
    font-size: 1.05rem;
    font-weight: 800;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 6px 18px rgba(0,98,255,0.25);
    transition: all 0.25s ease;
}
.back-to-coaches-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    margin-top: 0.9rem;
    padding: 0.85rem 1.2rem;
    background: #f8fafc;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.25s ease;
}
.back-to-coaches-link:hover {
    background: #e2e8f0;
    border-color: var(--primary);
    color: var(--primary);
}

/* Responsive */
@media (max-width: 992px) {
    .coach-dashboard-split-grid {
        grid-template-columns: 1fr;
        gap: 1.8rem;
    }
    .coach-profile-card,
    .coach-booking-card-simple {
        padding: 1.8rem 1.4rem;
    }
    .drawer-reviews-body-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 576px) {
    .coach-profile-top-header {
        flex-direction: column;
        text-align: center;
    }
    .coach-avatar-wrapper {
        margin: 0 auto;
    }
    .coach-tags-row, 
    .coach-meta-chips-row, 
    .coach-quick-contact-row {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : '<?php echo $_SESSION['lang'] ?? 'en'; ?>') === 'vi';

    // 1. Review Coach Drawer Toggle
    const btnToggleReviews = document.getElementById("btnToggleReviews");
    const coachReviewsDrawer = document.getElementById("coachReviewsDrawer");
    const btnCloseReviews = document.getElementById("btnCloseReviews");

    function toggleReviewsDrawer(open) {
        if (!coachReviewsDrawer) return;
        const isCurrentlyOpen = coachReviewsDrawer.style.display !== "none";
        const shouldOpen = (typeof open === "boolean") ? open : !isCurrentlyOpen;

        if (shouldOpen) {
            coachReviewsDrawer.style.display = "block";
            if (btnToggleReviews) {
                btnToggleReviews.classList.add("open");
                const icon = btnToggleReviews.querySelector(".chevron-indicator");
                if (icon) icon.className = "fas fa-chevron-up chevron-indicator";
            }
            coachReviewsDrawer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            coachReviewsDrawer.style.display = "none";
            if (btnToggleReviews) {
                btnToggleReviews.classList.remove("open");
                const icon = btnToggleReviews.querySelector(".chevron-indicator");
                if (icon) icon.className = "fas fa-chevron-down chevron-indicator";
            }
        }
    }

    if (btnToggleReviews) {
        btnToggleReviews.addEventListener("click", function() {
            toggleReviewsDrawer();
        });
    }
    if (btnCloseReviews) {
        btnCloseReviews.addEventListener("click", function() {
            toggleReviewsDrawer(false);
        });
    }

    // Auto open drawer if submitting review or requested via parameter/hash
    <?php if (isset($_POST['submit_review']) || (isset($_GET['tab']) && $_GET['tab'] === 'reviews')): ?>
        toggleReviewsDrawer(true);
    <?php else: ?>
        if (window.location.hash === '#reviews' || window.location.hash === '#coachReviewsDrawer') {
            toggleReviewsDrawer(true);
        }
    <?php endif; ?>

    // 2. Populate Quick Workout Time Suggestions (Hints)
    const quickHintPills = document.getElementById("quickHintPills");
    const bookingDatesContainer = document.getElementById("bookingDatesContainer");

    function generateHints() {
        if (!quickHintPills) return;
        const now = new Date();
        const suggestions = [];

        // Helper to format YYYY-MM-DDTHH:MM
        function toDateTimeLocal(d, hour) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hh = String(hour).padStart(2, '0');
            return `${year}-${month}-${day}T${hh}:00`;
        }

        // Today suggestions (hours that haven't passed yet)
        const currentHour = now.getHours();
        const todaySlots = [8, 10, 14, 16, 18, 19, 20].filter(h => h > currentHour + 1);
        todaySlots.slice(0, 3).forEach(h => {
            suggestions.push({
                label: isVi ? `Hôm nay ${h}:00` : `Today ${h}:00`,
                val: toDateTimeLocal(now, h)
            });
        });

        // Tomorrow suggestions
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowSlots = [8, 9, 14, 17, 18, 19];
        tomorrowSlots.slice(0, 4).forEach(h => {
            suggestions.push({
                label: isVi ? `Ngày mai ${h}:00` : `Tomorrow ${h}:00`,
                val: toDateTimeLocal(tomorrow, h)
            });
        });

        quickHintPills.innerHTML = suggestions.map(s => `
            <button type="button" class="hint-chip-btn" data-val="${s.val}">
                <i class="far fa-clock" style="color: var(--primary);"></i>
                <span>${s.label}</span>
            </button>
        `).join('');

        quickHintPills.querySelectorAll(".hint-chip-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const val = this.dataset.val;
                applyHintValue(val);
            });
        });
    }

    function applyHintValue(val) {
        if (!bookingDatesContainer) return;
        const inputs = bookingDatesContainer.querySelectorAll(".booking-date-field");
        let targetInput = null;
        for (let inp of inputs) {
            if (!inp.value) {
                targetInput = inp;
                break;
            }
        }
        if (!targetInput) {
            targetInput = inputs[inputs.length - 1];
        }

        if (targetInput) {
            targetInput.value = val;
            targetInput.style.borderColor = "var(--primary)";
            targetInput.style.boxShadow = "0 0 0 4px rgba(0,98,255,0.15)";
            setTimeout(() => {
                targetInput.style.borderColor = "";
                targetInput.style.boxShadow = "";
            }, 600);
        }
    }

    generateHints();

    // 3. Add Extra Slot Row (for multiple hours in day)
    const btnAddSlot = document.getElementById("btnAddSlot");
    if (btnAddSlot && bookingDatesContainer) {
        btnAddSlot.addEventListener("click", function() {
            const row = document.createElement("div");
            row.className = "booking-date-row";
            row.style.marginTop = "8px";
            row.innerHTML = `
                <input type="datetime-local" name="booking_dates[]" required 
                       min="<?php echo date('Y-m-d\TH:i'); ?>"
                       class="simple-form-input booking-date-field">
                <button type="button" class="btn-remove-date-row" title="${isVi ? 'Xóa khung giờ này' : 'Remove this slot'}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            row.querySelector(".btn-remove-date-row").addEventListener("click", function() {
                row.remove();
            });
            bookingDatesContainer.appendChild(row);
        });
    }

    // 4. Form Validation
    const bookingForm = document.getElementById("coachBookingForm");
    if (bookingForm) {
        bookingForm.addEventListener("submit", function(e) {
            const phoneInput = bookingForm.querySelector('input[name="user_phone"]');
            const addressInput = bookingForm.querySelector('input[name="user_address"]');
            const dateInputs = bookingForm.querySelectorAll('input[name="booking_dates[]"]');

            if (phoneInput && !phoneInput.value.trim()) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập số điện thoại liên hệ.' : 'Please enter your phone number.',
                    isVi ? 'Thiếu Số Điện Thoại' : 'Phone Required',
                    '📱'
                );
                phoneInput.focus();
                return false;
            }

            if (phoneInput && !/^[0-9]{10}$/.test(phoneInput.value.trim())) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Số điện thoại không hợp lệ (phải bao gồm 10 chữ số).' : 'Invalid phone number (must be 10 digits).',
                    isVi ? 'Số Điện Thoại Không Hợp Lệ' : 'Invalid Phone Number',
                    '⚠️'
                );
                phoneInput.focus();
                return false;
            }

            if (addressInput && !addressInput.value.trim()) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập địa chỉ liên hệ.' : 'Please enter your address.',
                    isVi ? 'Thiếu Địa Chỉ' : 'Address Required',
                    '🏠'
                );
                addressInput.focus();
                return false;
            }

            let hasDate = false;
            dateInputs.forEach(inp => {
                if (inp.value && inp.value.trim()) hasDate = true;
            });

            if (!hasDate) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng chọn ngày và giờ tập.' : 'Please select your workout date and time.',
                    isVi ? 'Chưa Chọn Giờ Tập' : 'Time Slot Required',
                    '📅'
                );
                if (dateInputs[0]) dateInputs[0].focus();
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

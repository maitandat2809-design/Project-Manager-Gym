<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

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
    $booking_date = $_POST['booking_date'];
    $user_phone = $_POST['user_phone'] ?? '';
    $user_address = $_POST['user_address'] ?? '';
    
    // Simple validation: date must be in the future, phone must be 10 digits
    if (!preg_match('/^[0-9]{10}$/', $user_phone)) {
        $error = __('phone_invalid');
    } elseif (strtotime($booking_date) < time()) {
        $error = __('date_future');
    } else {
        // Check for conflicts: Any booking for this coach within 1 hour of the requested time
        $requested_time = strtotime($booking_date);
        $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
        $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);
        
        $conflict_stmt = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
        $conflict_stmt->execute([$id, $start_buffer, $end_buffer]);
        
        if ($conflict_stmt->fetch()) {
            $error = __('time_conflict_msg');
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status, user_phone, user_address) VALUES (?, ?, ?, 'pending', ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $id, $booking_date, $user_phone, $user_address])) {
            
            // Save info back to user profile
            $stmt_u = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ? AND (phone IS NULL OR address IS NULL OR phone != ? OR address != ?)");
            $stmt_u->execute([$user_phone, $user_address, $_SESSION['user_id'], $user_phone, $user_address]);

            $message = __('booking_success');

            // --- Start PHPMailer Notification to Coach ---
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';
                $mail->Password   = 'hpst uazz wdsq sbnf';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Booking System');
                if ($coach['email']) {
                    $mail->addAddress($coach['email'], $coach['coach_name']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = "🆕 NEW BOOKING: " . $_SESSION['user_name'] . " booked you!";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $mail_body .= "<h2 style='color: #0062ff;'>New Training Request</h2>";
                    $mail_body .= "<p>A new training session has been booked with you.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                    $mail_body .= "<strong>Client:</strong> " . $_SESSION['user_name'] . "<br>";
                    $mail_body .= "<strong>Phone:</strong> " . htmlspecialchars($user_phone) . "<br>";
                    $mail_body .= "<strong>Address:</strong> " . htmlspecialchars($user_address) . "<br>";
                    $mail_body .= "<strong>Date/Time:</strong> " . date('l, F d, Y - H:i', strtotime($booking_date));
                    $mail_body .= "</div>";
                    $mail_body .= "<p>Please log in to your dashboard to manage this request.</p></div>";

                    $mail->Body = $mail_body;
                    $mail->send();
                }
            } catch (Exception $e) {
                error_log("Booking Mail to Coach Error: " . $e->getMessage());
            }

            // --- Start PHPMailer Notification to Managers ---
            try {
                $stmt_staff = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
                $stmt_staff->execute();
                $staff = $stmt_staff->fetchAll();
                
                if (count($staff) > 0) {
                    $mail2 = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail2->CharSet = 'UTF-8';
                    $mail2->isSMTP();
                    $mail2->Host       = 'smtp.gmail.com';
                    $mail2->SMTPAuth   = true;
                    $mail2->Username   = 'lamtuoi155@gmail.com';
                    $mail2->Password   = 'hpst uazz wdsq sbnf';
                    $mail2->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail2->Port       = 587;

                    $mail2->setFrom('lamtuoi155@gmail.com', 'GymX Booking System');
                    
                    $has_mgr = false;
                    foreach ($staff as $s) {
                        if ($s['email'] && $s['email'] !== $coach['email']) {
                            $mail2->addAddress($s['email'], $s['name']);
                            $has_mgr = true;
                        }
                    }
                    
                    if ($has_mgr) {
                        $mail2->isHTML(true);
                        $mail2->Subject = "URGENT: " . $_SESSION['user_name'] . " booked Coach " . $coach['coach_name'];
                        
                        $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                        $mail_body .= "<h2 style='color: #0062ff;'>New Booking Alert</h2>";
                        $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                        $mail_body .= "<strong>Coach:</strong> " . $coach['coach_name'] . "<br>";
                        $mail_body .= "<strong>Client:</strong> " . $_SESSION['user_name'] . "<br>";
                        $mail_body .= "<strong>Date/Time:</strong> " . date('l, F d, Y - H:i', strtotime($booking_date));
                        $mail_body .= "</div></div>";

                        $mail2->Body = $mail_body;
                        $mail2->send();
                    }
                }
            } catch (Exception $e) {
                error_log("Booking Mail to Manager Error: " . $e->getMessage());
            }

            // --- Start PHPMailer Notification to User ---
            try {
                $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt_u->execute([$_SESSION['user_id']]);
                $user_email = $stmt_u->fetchColumn();

                if ($user_email) {
                    $mail3 = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail3->CharSet = 'UTF-8';
                    $mail3->isSMTP();
                    $mail3->Host       = 'smtp.gmail.com';
                    $mail3->SMTPAuth   = true;
                    $mail3->Username   = 'lamtuoi155@gmail.com';
                    $mail3->Password   = 'hpst uazz wdsq sbnf';
                    $mail3->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail3->Port       = 587;

                    $mail3->setFrom('lamtuoi155@gmail.com', 'GymX Booking System');
                    $mail3->addAddress($user_email, $_SESSION['user_name']);
                    
                    $mail3->isHTML(true);
                    $mail3->Subject = "GymX: Your Booking Confirmation with Coach " . $coach['coach_name'];
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $mail_body .= "<h2 style='color: #0062ff;'>Booking Confirmation</h2>";
                    $mail_body .= "<p>Hello <strong>" . $_SESSION['user_name'] . "</strong>, your training session has been successfully booked!</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                    $mail_body .= "<strong>Coach:</strong> " . $coach['coach_name'] . "<br>";
                    $mail_body .= "<strong>Date/Time:</strong> " . date('l, F d, Y - H:i', strtotime($booking_date)) . "<br>";
                    $mail_body .= "<strong>Status:</strong> Pending (Waiting for Coach Approval)";
                    $mail_body .= "</div>";
                    $mail_body .= "<p>You will receive another notification once the coach confirms your session.</p></div>";

                    $mail3->Body = $mail_body;
                    $mail3->send();
                }
            } catch (Exception $e) {
                error_log("Booking Mail to User Error: " . $e->getMessage());
            }
            // --- End Notifications ---
            } else {
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

<section>
    <?php if($message): ?>
        <p style="color: #00C851; text-align: center; margin-bottom: 2rem; background: rgba(0,200,81,0.1); padding: 1rem; border-radius: 10px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if($error): ?>
        <p style="color: #ff4444; text-align: center; margin-bottom: 2rem; background: rgba(255,68,68,0.1); padding: 1rem; border-radius: 10px;"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="coach-detail-container">
        <?php 
        $display_img = $coach['avatar'] ?: ($coach['image'] ?: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=2070&auto=format&fit=crop');
        ?>
        <img src="<?php echo $display_img; ?>" class="coach-detail-img" alt="<?php echo $coach['coach_name']; ?>">
        
        <div class="coach-info">
            <h3 style="color: var(--primary); text-transform: uppercase; font-weight: 700;"><?php echo $coach['specialty']; ?></h3>
            <h1><?php echo __('coach_label') . ' ' . htmlspecialchars($coach['coach_name']); ?></h1>
            
            <div class="coach-meta">
                <span>💰 $<?php echo $coach['hourly_rate']; ?><?php echo __('hour_label'); ?></span>
                <span>📧 <?php echo $coach['email']; ?></span>
                <?php if (!empty($coach['room_name']) || !empty($coach['room_number'])): ?>
                <span>🏠
                    <?php if (!empty($coach['room_name'])): ?>
                        <strong><?php echo htmlspecialchars($coach['room_name']); ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($coach['room_number'])): ?>
                        &nbsp;<span style="background: var(--primary); color: #fff; padding: 2px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;"><?php echo htmlspecialchars($coach['room_number']); ?></span>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
                <span style="display: flex; align-items: center; gap: 5px;">
                    <div style="color: #ffbb33; display: flex; gap: 2px;">
                        <?php 
                        $rating = round($rating_data['avg_rating']);
                        for($i=1; $i<=5; $i++) echo $i <= $rating ? "★" : "☆";
                        ?>
                    </div>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">(<?php echo $rating_data['count'] . ' ' . __('reviews'); ?>)</span>
                </span>
            </div>

            <?php if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'manager')): ?>
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <?php if(!empty($coach['zalo'] ?? null)): ?>
                    <a href="<?php echo (strpos($coach['zalo'], 'http') === 0) ? $coach['zalo'] : 'https://zalo.me/'.$coach['zalo']; ?>" 
                       target="_blank" class="contact-btn zalo">
                        <img src="https://img.icons8.com/color/48/zalo.png" style="width: 24px;" alt="Zalo">
                        <?php echo __('contact_zalo'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if(!empty($coach['gmail'] ?? null)): ?>
                    <a href="mailto:<?php echo $coach['gmail']; ?>" class="contact-btn gmail">
                        <img src="https://img.icons8.com/color/48/gmail-new.png" style="width: 24px;" alt="Gmail">
                        <?php echo __('send_gmail'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-muted); margin-bottom: 2rem;">
                <?php echo $coach['bio']; ?>
            </p>

            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach'): ?>
            <div style="background: var(--bg-card); padding: 2rem; border-radius: 15px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1rem;"><?php echo __('book_session_title'); ?></h3>
                <?php if (!empty($coach['room_name']) || !empty($coach['room_number'])): ?>
                <div style="display: flex; align-items: center; gap: 0.7rem; margin-bottom: 1.2rem; padding: 0.8rem 1.2rem; background: rgba(0,98,255,0.06); border-radius: 12px; border: 1px solid rgba(0,98,255,0.15);">
                    <span style="font-size: 1.2rem;">🏠</span>
                    <span style="font-weight: 600; color: var(--text-main);"><?php echo __('training_room'); ?>:</span>
                    <?php if (!empty($coach['room_name'])): ?>
                        <span style="color: var(--text-muted);"><?php echo htmlspecialchars($coach['room_name']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($coach['room_number'])): ?>
                        <span style="background: var(--primary); color: #fff; padding: 3px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.5px;"><?php echo htmlspecialchars($coach['room_number']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <p style="margin-bottom: 1.5rem; color: var(--text-muted);"><?php echo __('book_session_subtitle'); ?></p>
                
                <form method="POST">
                    <div class="form-group">
                        <label style="color: var(--text-main); margin-bottom: 0.8rem; display: block; font-weight: 600;"><?php echo __('your_phone_label'); ?></label>
                        <input type="text" name="user_phone" required placeholder="090x xxx xxx" pattern="[0-9]{10}" title="<?php echo __('phone_invalid'); ?>"
                               value="<?php echo isset($curr_user['phone']) ? htmlspecialchars($curr_user['phone']) : ''; ?>"
                               style="width: 100%; padding: 1.1rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); margin-bottom: 1.5rem; outline: none; font-size: 1rem;">
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-main); margin-bottom: 0.8rem; display: block; font-weight: 600;"><?php echo __('your_address_label'); ?></label>
                        <input type="text" name="user_address" required placeholder="<?php echo __('address_placeholder'); ?>" 
                               value="<?php echo isset($curr_user['address']) ? htmlspecialchars($curr_user['address']) : ''; ?>"
                               style="width: 100%; padding: 1.1rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); margin-bottom: 1.5rem; outline: none; font-size: 1rem;">
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-main); margin-bottom: 0.8rem; display: block; font-weight: 600;"><?php echo __('select_date_time_label'); ?></label>
                        <input type="datetime-local" name="booking_date" required 
                               style="width: 100%; padding: 1.1rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); margin-bottom: 1.5rem; outline: none; font-size: 1rem;">
                    </div>
                    <button type="submit" name="book_now" class="cta-btn" style="width: 100%; border: none; cursor: pointer;"><?php echo __('send_booking_request'); ?></button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coach'): ?>
    <div style="max-width: 1000px; margin: 4rem auto 0; padding: 0 1rem;">
        <h2 style="margin-bottom: 2rem; border-bottom: 2px solid var(--primary); display: inline-block; padding-bottom: 5px;"><?php echo __('client_reviews'); ?></h2>
        
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 4rem; align-items: start;">
            <!-- Review List -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <?php if(empty($reviews)): ?>
                    <p style="color: var(--text-muted);"><?php echo __('no_reviews_yet'); ?></p>
                <?php else: ?>
                    <?php foreach($reviews as $rev): ?>
                        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 15px; border: 1px solid var(--border);">
                            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                                <img src="<?php echo $rev['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($rev['user_name']).'&background=0062ff&color=fff&size=50'; ?>" 
                                     style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border);">
                                <div>
                                    <h4 style="color: var(--text-main); font-weight: 700;"><?php echo $rev['user_name']; ?></h4>
                                    <div style="color: #f59e0b; font-size: 0.85rem;">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? "★" : "☆"; ?>
                                    </div>
                                </div>
                                <span style="margin-left: auto; font-size: 0.8rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                            </div>
                            <p style="color: var(--text-muted); line-height: 1.6; font-size: 0.95rem;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Write Review -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <div style="background: var(--bg-card); padding: 2rem; border-radius: 15px; border: 1px solid var(--border); position: sticky; top: 100px;">
                    <h3 style="margin-bottom: 1.5rem;"><?php echo __('leave_review'); ?></h3>
                    <form method="POST">
                        <div class="form-group">
                            <label style="color: var(--text-main); margin-bottom: 0.8rem; display: block; font-weight: 600;"><?php echo __('rating_label'); ?></label>
                            <select name="rating" required style="width: 100%; padding: 1rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); margin-bottom: 1.5rem; outline: none; font-size: 1rem;">
                                <option value="5">5 ★ - <?php echo __('stars_5'); ?></option>
                                <option value="4">4 ★ - <?php echo __('stars_4'); ?></option>
                                <option value="3">3 ★ - <?php echo __('stars_3'); ?></option>
                                <option value="2">2 ★ - <?php echo __('stars_2'); ?></option>
                                <option value="1">1 ★ - <?php echo __('stars_1'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="color: var(--text-main); margin-bottom: 0.8rem; display: block; font-weight: 600;"><?php echo __('comment_label'); ?></label>
                            <textarea name="comment" required placeholder="<?php echo __('comment_placeholder'); ?>" 
                                      style="width: 100%; padding: 1.2rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); margin-bottom: 1.5rem; outline: none; min-height: 120px; font-size: 1rem;"></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn-auth" style="width: 100%;"><?php echo __('post_review'); ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

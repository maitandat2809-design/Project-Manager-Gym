<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $nickname = $_POST['nickname'];
    $age = $_POST['age'];
    $phone = trim($_POST['phone'] ?? '');
    $address = $_POST['address'] ?? null;
    $avatar_path = null;

    if (!empty($phone)) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        $check_phone = $pdo->prepare("SELECT id FROM users WHERE (phone = ? OR phone = ?) AND id != ? AND phone IS NOT NULL AND phone != ''");
        $check_phone->execute([$phone, $clean_phone, $user_id]);
        if ($check_phone->fetch()) {
            $error = __('phone_exists_error');
        }
    }

    if (empty($error)) {
        // Handle Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = 'assets/img/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = $target_file;
            }
        }

        try {
            if ($avatar_path) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, nickname = ?, age = ?, phone = ?, address = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$name, $nickname, $age, $phone, $address, $avatar_path, $user_id]);
                
                // If user is a coach, also update the coach profile image
                if ($_SESSION['role'] === 'coach') {
                    $stmt_c = $pdo->prepare("UPDATE coaches SET image = ? WHERE user_id = ?");
                    $stmt_c->execute([$avatar_path, $user_id]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, nickname = ?, age = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $nickname, $age, $phone, $address, $user_id]);
            }
            $_SESSION['user_name'] = $name;
            $message = __('profile_updated_success');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $hashed_pw = $stmt->fetchColumn();

    if (!password_verify($current_password, $hashed_pw)) {
        $error = __('pw_incorrect');
    } elseif ($new_password !== $confirm_password) {
        $error = __('pw_mismatch');
    } elseif (strlen($new_password) < 6) {
        $error = __('pw_too_short');
    } else {
        $new_hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hashed_pw, $user_id]);
        $message = __('pw_updated_success');
    }
}

// Handle Product Review Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_product_review'])) {
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $rating, $comment]);
        $message = __('prod_review_success');
    } catch (Exception $e) {
        $error = "Review error: " . $e->getMessage();
    }
}

// Handle Booking Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $new_date = $_POST['new_date'];
    
    if (strtotime($new_date) < time()) {
        $error = __('booking_future_date');
    } else {
        try {
            // Find coach_id to check conflicts
            $c_stmt = $pdo->prepare("SELECT coach_id FROM bookings WHERE id = ?");
            $c_stmt->execute([$booking_id]);
            $coach_id = $c_stmt->fetchColumn();

            // Calculate 1-hour time window buffers
            $requested_time = strtotime($new_date);
            $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
            $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);

            // 1. Check coach slot conflict (within 1 hour)
            $conflict_stmt = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $conflict_stmt->execute([$coach_id, $booking_id, $start_buffer, $end_buffer]);

            // 2. Check user slot conflict (cannot book 2 coaches at the same time slot)
            $user_conflict_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $user_conflict_stmt->execute([$user_id, $booking_id, $start_buffer, $end_buffer]);
            
            if ($conflict_stmt->fetch()) {
                $error = __('timeslot_unavailable_err');
            } elseif ($user_conflict_stmt->fetch()) {
                $error = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi')
                    ? "⚠️ Bạn đã có lịch tập với HLV khác vào cùng khung giờ này (trùng trong vòng 60 phút). Không thể đăng ký 2 HLV cùng 1 khung giờ!"
                    : "⚠️ You already have another session scheduled at this time. Cannot book 2 coaches at the same time slot!";
            } else {
                // Update booking and reset coach_seen so they get a new notification
                $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, coach_seen = 0, status = 'pending' WHERE id = ? AND user_id = ?");
                $stmt->execute([$new_date, $booking_id, $user_id]);
            
            // Send Email to Coach
            $stmt_c = $pdo->prepare("
                SELECT b.booking_date, u.name as user_name, coach_u.email as coach_email, coach_u.name as coach_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN coaches c ON b.coach_id = c.id 
                JOIN users coach_u ON c.user_id = coach_u.id 
                WHERE b.id = ?
            ");
            $stmt_c->execute([$booking_id]);
            $info = $stmt_c->fetch();

            if ($info && $info['coach_email']) {
                require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Training System');
                $mail->addAddress($info['coach_email'], $info['coach_name']);

                // Also Notify Managers
                $stmt_staff = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
                $stmt_staff->execute();
                $staff = $stmt_staff->fetchAll();
                foreach ($staff as $s) {
                    if ($s['email'] && $s['email'] !== $info['coach_email']) {
                        $mail->addAddress($s['email'], $s['name']);
                    }
                }

                $mail->isHTML(true);
                $mail->Subject = "⚠️ UPDATED: " . $info['user_name'] . " changed booking with Coach " . $info['coach_name'];

                $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                $body .= "<h2 style='color: #0062ff;'>Booking Rescheduled</h2>";
                $body .= "<p>Student <strong>" . $info['user_name'] . "</strong> has updated their training schedule with Coach <strong>" . $info['coach_name'] . "</strong>.</p>";
                $body .= "<div style='background: #fff7ed; padding: 15px; border-radius: 12px; border-left: 4px solid #f97316; margin: 20px 0;'>";
                $body .= "<strong>New Date/Time:</strong> " . date('l, F d, Y - H:i', strtotime($new_date)) . "<br>";
                $body .= "<strong>Current Status:</strong> PENDING (Requires Approval)";
                $body .= "</div>";
                $body .= "<p>The system has been updated. Coach has been notified to re-approve the session.</p>";
                $body .= "</div>";

                $mail->Body = $body;
                $mail->send();
            }
            $message = __('booking_rescheduled_success');
            }
        } catch (Exception $e) {
            $error = "Update error: " . $e->getMessage();
        }
    }
}

// Handle Booking Cancellation by User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $reason = trim($_POST['cancel_reason'] ?? '');
    
    try {
        $stmt_check = $pdo->prepare("SELECT id, status, attendance, booking_date, class_name, user_id FROM bookings WHERE id = ?");
        $stmt_check->execute([$booking_id]);
        $b_check = $stmt_check->fetch();
        
        if ($b_check && $b_check['status'] !== 'cancelled') {
            if ($b_check['user_id'] != $user_id && !in_array($_SESSION['role'] ?? '', ['coach', 'manager', 'admin'])) {
                $error = "Unauthorized action.";
            } elseif (!empty($b_check['class_name'])) {
                $error = __('cannot_cancel_coach_class');
            } elseif (($b_check['attendance'] ?? '') === 'present' || ($b_check['attendance'] ?? '') === 'absent' || ($b_check['status'] ?? '') === 'completed') {
                $error = (($_SESSION['lang'] ?? 'en') === 'en') ? "Session has already been confirmed/attended by Coach and cannot be cancelled." : "Buổi tập đã được HLV xác nhận/điểm danh, không thể hủy buổi tập.";
            } elseif (strtotime($b_check['booking_date']) <= time()) {
                $error = __('cannot_cancel_past_session');
            } else {
                $stmt_up = $pdo->prepare("UPDATE bookings SET status = 'cancelled', coach_seen = 0, user_seen = 1 WHERE id = ?");
                $stmt_up->execute([$booking_id]);

                require_once __DIR__ . '/../includes/booking_mailer.php';
                sendBookingCancellationEmail($pdo, $booking_id, $reason);

                $message = __('session_cancelled_success');
            }
        }
    } catch (Exception $e) {
        $error = "Cancellation error: " . $e->getMessage();
    }
}
// Handle Coach Review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_coach_review'])) {
    $coach_id = $_POST['coach_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $stmt = $pdo->prepare("INSERT INTO coach_reviews (coach_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$coach_id, $user_id, $rating, $comment])) {
        $message = __('coach_review_success');
    } else {
        $error = __('review_error');
    }
}

// Handle Order Confirmation by Customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_confirm_order'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $stmt_o = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt_o->execute([$order_id, $user_id]);
    $order = $stmt_o->fetch();
    
    if ($order) {
        $stmt_upd = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt_upd->execute([$order_id]);
        $message = __('order_confirmed_msg');
        
        // Notify managers via email
        try {
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
            
            $stmt_mgrs = $pdo->query("SELECT email, name FROM users WHERE role = 'manager'");
            $managers = $stmt_mgrs->fetchAll();
            
            if (!empty($managers)) {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Order System');
                
                $has_mgr = false;
                foreach ($managers as $mgr) {
                    if (!empty($mgr['email'])) {
                        $mail->addAddress($mgr['email'], $mgr['name']);
                        $has_mgr = true;
                    }
                }
                
                if ($has_mgr) {
                    $mail->isHTML(true);
                    $mail->Subject = "✅ Customer Confirmed Order #" . $order_id . " Delivery Successful";
                    $m_body = "<div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $m_body .= "<h2 style='color: #10b981;'>Order #" . $order_id . " Confirmed Delivered</h2>";
                    $m_body .= "<p>Customer <strong>" . htmlspecialchars($_SESSION['user_name'] ?? '') . "</strong> has confirmed receiving order #" . $order_id . " ($" . number_format($order['total_price'], 2) . ").</p>";
                    $m_body .= "<p><a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=yearly_report' style='background: #0062ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;'>View Business Yearly Report</a></p>";
                    $m_body .= "</div>";
                    $mail->Body = $m_body;
                    $mail->send();
                }
            }
        } catch (Exception $mailEx) {
            error_log("Order Confirm Mail Error: " . $mailEx->getMessage());
        }
    }
}

// Handle Order Cancellation by Customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_cancel_order'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $stmt_o = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt_o->execute([$order_id, $user_id]);
    $order = $stmt_o->fetch();
    
    if ($order) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
            
            // Restore stock
            $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$order_id]);
            $o_items = $stmt_items->fetchAll();
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ?, sales_count = GREATEST(0, sales_count - ?) WHERE id = ?");
            foreach ($o_items as $item) {
                $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }
            $pdo->commit();
            $message = __('order_cancelled_msg');
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Cancellation error: " . $e->getMessage();
        }
    }
}

// Handle Order Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $pdo->beginTransaction();
        // Delete order items first (foreign key constraint)
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
        // Delete the order
        $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?")->execute([$order_id, $user_id]);
        $pdo->commit();
        $message = __('order_deleted');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Deletion error: " . $e->getMessage();
    }
}

// Get User Detail
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();


// Get bookings
$stmt = $pdo->prepare("SELECT b.*, c.id as coach_id, u.name as coach_name FROM bookings b JOIN coaches c ON b.coach_id = c.id JOIN users u ON c.user_id = u.id WHERE b.user_id = ? ORDER BY b.booking_date DESC");
$stmt->execute([$user_id]);
$all_bookings = $stmt->fetchAll();

$upcoming_bookings = [];
$past_bookings = [];
$now = time();
foreach ($all_bookings as $b) {
    if (strtotime($b['booking_date']) > $now) {
        $upcoming_bookings[] = $b;
    } else {
        $past_bookings[] = $b;
    }
}

// Get orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<section>
    <div class="section-title">
        <h2><?php echo __('your_account'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if($message): ?>
        <p style="color: #16a34a; text-align: center; margin-bottom: 2rem; background: #f0fdf4; padding: 1rem; border-radius: 15px; border: 1px solid #bbf7d0; font-weight: 600;"><?php echo $message; ?></p>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const msgText = <?php echo json_encode($message); ?>;
                let title = <?php echo json_encode(__('notification')); ?>;
                let icon = 'ℹ️';
                if (msgText.includes('nhận hàng thành công') || msgText.includes('receiving your order') || msgText.includes('xác nhận') || msgText.includes('confirmed')) {
                    title = <?php echo json_encode(__('order_received_success')); ?>;
                    icon = '🎉';
                } else if (msgText.includes('trải nghiệm các sản phẩm khác') || msgText.includes('explore and try other products') || msgText.includes('hủy') || msgText.includes('cancelled')) {
                    title = <?php echo json_encode(__('order_notification')); ?>;
                    icon = '🛍️';
                } else if (msgText.includes('mật khẩu') || msgText.includes('Password') || msgText.includes('password') || msgText.includes('pw_updated_success')) {
                    title = <?php echo json_encode(($_SESSION['lang'] ?? 'en') === 'vi' ? 'Đổi Mật Khẩu Thành Công' : 'Password Changed Successfully'); ?>;
                    icon = '🔑';
                }
                showGymXAlert(msgText, title, icon);
            });
        </script>
    <?php endif; ?>

    <?php if($error): ?>
        <div style="color: #991b1b; background: #fef2f2; padding: 1.25rem 1.5rem; border-radius: 16px; border: 1px solid #fca5a5; font-weight: 500; margin-bottom: 2rem; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12); line-height: 1.6;">
            <?php echo $error; ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showGymXAlert(<?php echo json_encode($error); ?>, <?php echo json_encode(__('error_notification')); ?>, '❌');
            });
        </script>
    <?php endif; ?>

    <!-- Profile Edit Section -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 4rem;">
        <form method="POST" enctype="multipart/form-data" id="profileEditForm" novalidate class="profile-form-grid">
            <div style="text-align: center;">
                <img src="<?php echo $user['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0062ff&color=fff&size=200'; ?>" 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 0.5rem;">
                
                <?php
                // Fetch active/pending membership subscription plan badge for user
                $stmt_profile_mem = $pdo->prepare("
                    SELECT mp.name, mp.name_vi, mp.badge, mp.badge_color, ms.status, ms.pt_sessions_total, ms.pt_sessions_used
                    FROM member_subscriptions ms
                    JOIN membership_plans mp ON ms.plan_id = mp.id
                    WHERE ms.user_id = ? AND ms.status IN ('active', 'pending') AND (ms.status = 'pending' OR ms.end_date >= CURDATE())
                    ORDER BY ms.id DESC LIMIT 1
                ");
                $stmt_profile_mem->execute([$user_id]);
                $profile_mem = $stmt_profile_mem->fetch();
                $pt_total = (int)($profile_mem['pt_sessions_total'] ?? 0);
                $pt_used  = (int)($profile_mem['pt_sessions_used'] ?? 0);
                $pt_rem   = max(0, $pt_total - $pt_used);
                ?>

                <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap;">
                    <?php if ($profile_mem): ?>
                        <span style="background: <?php echo !empty($profile_mem['badge_color']) ? $profile_mem['badge_color'] : '#2563eb'; ?>; color: #fff; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            <i class="fas fa-certificate"></i>
                            <?php echo htmlspecialchars(!empty($profile_mem['badge']) ? $profile_mem['badge'] : mb_strtoupper($profile_mem['name'])); ?>
                        </span>
                        <?php if ($pt_total > 0): ?>
                            <span style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1e40af; border: 1.5px solid #93c5fd; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 8px rgba(37,99,235,0.15);">
                                <i class="fas fa-dumbbell" style="color: #2563eb;"></i> 
                                <?php echo (($_SESSION['lang'] ?? 'en') === 'vi' ? "Còn {$pt_rem}/{$pt_total} buổi PT" : "{$pt_rem}/{$pt_total} PT Left"); ?>
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="background: #64748b; color: #fff; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-user-circle"></i>
                            <?php echo strtoupper($_SESSION['role'] ?? 'USER'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <input type="file" name="avatar" id="avatar" style="display: none;">
                <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
                    <label for="avatar" class="action-btn btn-add" style="cursor: pointer; font-size: 0.8rem; margin: 0;"><?php echo __('change_photo'); ?></label>
                    <?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
                        <a href="/Gym Manager/pages/membership.php" class="action-btn btn-add" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; font-weight: 700; font-size: 0.8rem; margin: 0; border: none; box-shadow: 0 4px 12px rgba(37,99,235,0.3);"><i class="fas fa-id-card"></i> <?php echo __('membership_nav'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label><?php echo __('full_name'); ?></label>
                    <input type="text" name="name" value="<?php echo $user['name']; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('nickname'); ?></label>
                    <input type="text" name="nickname" value="<?php echo $user['nickname']; ?>" placeholder="Gym Shark">
                </div>
                <div class="form-group">
                    <label><?php echo __('age'); ?></label>
                    <input type="number" name="age" value="<?php echo $user['age']; ?>" placeholder="25">
                </div>
                <div class="form-group">
                    <label><?php echo __('phone_number'); ?></label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="090x xxx xxx" pattern="[0-9]{10}">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label><?php echo __('address'); ?></label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="123 Main St...">
                </div>
                <div class="form-group">
                    <label><?php echo __('email_readonly'); ?></label>
                    <input type="email" value="<?php echo $user['email']; ?>" disabled style="opacity: 0.5;">
                </div>
                <div style="grid-column: span 2;">
                    <button type="submit" name="update_profile" class="cta-btn" style="width: 100%; border: none; cursor: pointer;"><?php echo __('save_changes'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Password Change Section -->
    <div style="background: var(--bg-card); padding: 2.5rem; border-radius: 25px; border: 1px solid var(--border); margin-bottom: 4rem; box-shadow: var(--shadow-premium);">
        <h3 style="margin-bottom: 2rem; color: var(--primary); font-weight: 800; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-shield-alt" style="color: var(--primary);"></i> <?php echo __('security_settings'); ?>
        </h3>
        <form method="POST" id="profilePasswordForm" novalidate class="password-form-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('current_password'); ?></label>
                <input type="password" name="current_password" required placeholder="••••••••" style="background: #f1f5f9; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('new_password'); ?></label>
                <input type="password" name="new_password" required placeholder="••••••••" style="background: #f1f5f9; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('confirm_new_password'); ?></label>
                <input type="password" name="confirm_password" required placeholder="••••••••" style="background: #f1f5f9; width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" name="update_password" class="cta-btn" style="border: none; cursor: pointer; white-space: nowrap; height: 48px; padding: 0 1.8rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; border-radius: 14px; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; border: 1px solid #c9a84c; box-shadow: 0 4px 14px rgba(15,23,42,0.25); transition: all 0.3s;">
                    <i class="fas fa-key"></i> <?php echo (($_SESSION['lang'] ?? 'en') === 'vi' ? 'Xác Nhận' : 'Confirm'); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="profile-layout-grid">
        <!-- Bookings Section -->
        <div>
            <!-- Upcoming Bookings Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: var(--primary);"><?php echo __('upcoming_bookings'); ?></h3>
                <?php if(!empty($upcoming_bookings)): ?>
                    <button onclick="document.getElementById('modal-all-upcoming').style.display='flex'"
                            style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; padding: 7px 14px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(37,99,235,0.25);">
                        <i class="fas fa-info-circle"></i> <?php echo __('full_details'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php if(empty($upcoming_bookings)): ?>
                <p style="color: var(--text-muted); margin-bottom: 2.5rem;"><?php echo __('no_upcoming_bookings'); ?></p>
            <?php else: ?>
                <!-- Single main page preview card for Upcoming Bookings -->
                <?php 
                $top_b = $upcoming_bookings[0];
                $is_top_cancelled = ($top_b['status'] === 'cancelled');
                ?>
                <div style="margin-bottom: 2.5rem;">
                    <div onclick="document.getElementById('modal-all-upcoming').style.display='flex'"
                         style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: var(--shadow-premium); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; cursor: pointer; <?php echo $is_top_cancelled ? 'opacity: 0.85; border-color: #fca5a5;' : ''; ?>">
                        <div>
                            <h4 style="color: var(--text-main); font-weight: 700; margin: 0 0 4px 0; font-size: 1rem;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($top_b['coach_name']); ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i><?php echo date('d/m/Y H:i', strtotime($top_b['booking_date'])); ?></p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if ($is_top_cancelled): ?>
                                <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; background: #fee2e2; color: #dc2626; font-weight: 800; text-transform: uppercase;">
                                    <i class="fas fa-ban"></i> <?php echo strtoupper(__('cancelled_status')); ?>
                                </span>
                            <?php else: ?>
                                <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; background: <?php echo $top_b['status'] == 'confirmed' ? '#dcfce7' : '#fef3c7'; ?>; color: <?php echo $top_b['status'] == 'confirmed' ? '#166534' : '#92400e'; ?>; font-weight: 800; text-transform: uppercase;">
                                    <?php echo strtoupper($top_b['status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ALL Upcoming Bookings Modal -->
                <div id="modal-all-upcoming" class="profile-modal-backdrop" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(6px); padding: 1rem;">
                    <div style="background: #fff; border-radius: 24px; width: 100%; max-width: 520px; max-height: 85vh; box-shadow: 0 25px 60px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="background: linear-gradient(135deg,#0f172a,#1e3a5f); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white; flex-shrink: 0;">
                            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800;"><i class="fas fa-calendar-check" style="color: #60a5fa; margin-right: 8px;"></i><?php echo __('upcoming_bookings'); ?> (<?php echo count($upcoming_bookings); ?>)</h4>
                            <button onclick="document.getElementById('modal-all-upcoming').style.display='none'" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">×</button>
                        </div>
                        <div style="padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
                            <?php foreach($upcoming_bookings as $booking): 
                                $is_b_cancelled = ($booking['status'] === 'cancelled');
                                $is_future_b = (strtotime($booking['booking_date']) > time());
                                $is_coach_class = !empty($booking['class_name']);
                                $is_b_attended = (in_array($booking['attendance'] ?? '', ['present', 'absent']) || $booking['status'] === 'completed');
                            ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1rem 1.2rem; display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="color: #0f172a; font-size: 0.98rem; display: block;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($booking['coach_name']); ?></strong>
                                            <span style="color: var(--text-muted); font-size: 0.82rem;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></span>
                                        </div>
                                        <?php if ($is_b_cancelled): ?>
                                            <span style="padding: 3px 10px; border-radius: 12px; font-size: 0.72rem; background: #fee2e2; color: #dc2626; font-weight: 800; text-transform: uppercase;">
                                                <i class="fas fa-ban"></i> <?php echo strtoupper(__('cancelled_status')); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="padding: 3px 10px; border-radius: 12px; font-size: 0.72rem; background: <?php echo $booking['status'] == 'confirmed' ? '#dcfce7' : '#fef3c7'; ?>; color: <?php echo $booking['status'] == 'confirmed' ? '#166534' : '#92400e'; ?>; font-weight: 800; text-transform: uppercase;">
                                                <?php echo strtoupper($booking['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($booking['coach_message'])): ?>
                                        <div style="background: #fff8e6; border: 1px solid #fde68a; padding: 8px 12px; border-radius: 10px; font-size: 0.82rem; color: #92400e;">
                                            <i class="fas fa-sticky-note" style="color: #d97706; margin-right: 4px;"></i> <strong><?php echo __('coach_notes_title'); ?>:</strong> <?php echo htmlspecialchars(getLocalizedCoachMsg($booking['coach_message'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$is_b_cancelled): ?>
                                        <div style="display: flex; gap: 8px; margin-top: 4px;">
                                            <?php if (!$is_b_attended): ?>
                                                <button onclick="document.getElementById('edit-booking-<?php echo $booking['id']; ?>').style.display='flex';"
                                                        style="flex: 1; background: #fff; border: 1px solid #cbd5e1; padding: 7px; border-radius: 8px; cursor: pointer; color: var(--text-main); font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                    <i class="fas fa-edit"></i> <?php echo __('edit'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($is_future_b && !$is_coach_class && !$is_b_attended): ?>
                                                <button onclick="document.getElementById('cancel-booking-<?php echo $booking['id']; ?>').style.display='flex';"
                                                        style="flex: 1; background: #fee2e2; border: 1px solid #fca5a5; padding: 7px; border-radius: 8px; cursor: pointer; color: #dc2626; font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                    <i class="fas fa-times-circle"></i> <?php echo __('cancel_session'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Edit Booking Modal -->
                                <div id="edit-booking-<?php echo $booking['id']; ?>" class="profile-modal-backdrop" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10001; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                                    <div style="background: white; padding: 2rem; border-radius: 25px; width: 90%; max-width: 440px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                                        <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: var(--text-main);"><?php echo __('reschedule_session'); ?></h3>
                                        <form method="POST">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <div class="form-group">
                                                <label><?php echo __('select_new_datetime'); ?></label>
                                                <input type="datetime-local" name="new_date" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['booking_date'])); ?>" required 
                                                       style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                                            </div>
                                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                                <button type="submit" name="update_booking" class="btn-auth" style="flex: 2; margin: 0;"><?php echo __('confirm_change'); ?></button>
                                                <button type="button" onclick="document.getElementById('edit-booking-<?php echo $booking['id']; ?>').style.display='none'" 
                                                         style="flex: 1; background: #f1f5f9; color: var(--text-muted); border: none; border-radius: 12px; font-weight: 700; cursor: pointer;"><?php echo __('cancel'); ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Cancel Booking Modal -->
                                <div id="cancel-booking-<?php echo $booking['id']; ?>" class="profile-modal-backdrop" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10001; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                                    <div style="background: white; padding: 2rem; border-radius: 25px; width: 90%; max-width: 440px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                                        <h3 style="margin-bottom: 1rem; font-weight: 800; color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> <?php echo __('cancel_session'); ?></h3>
                                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem;"><?php echo __('confirm_cancel_session'); ?></p>
                                        <form method="POST">
                                            <input type="hidden" name="user_cancel_booking" value="1">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                                <label style="font-weight: 600; color: var(--text-main);"><?php echo __('cancel_reason'); ?></label>
                                                <textarea name="cancel_reason" placeholder="<?php echo __('cancel_reason'); ?>" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; min-height: 80px; font-family: inherit; outline: none;"></textarea>
                                            </div>
                                            <div style="display: flex; gap: 1rem;">
                                                <button type="submit" class="btn-auth" style="flex: 2; margin: 0; background: #dc2626; border: none;"><?php echo __('cancel_session'); ?></button>
                                                <button type="button" onclick="document.getElementById('cancel-booking-<?php echo $booking['id']; ?>').style.display='none'" 
                                                        style="flex: 1; background: #f1f5f9; color: var(--text-muted); border: none; border-radius: 12px; font-weight: 700; cursor: pointer;"><?php echo __('cancel'); ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Workout History Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: var(--primary);"><?php echo __('workout_history'); ?></h3>
                <?php if(!empty($past_bookings)): ?>
                    <button onclick="document.getElementById('modal-all-workout').style.display='flex'"
                            style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; padding: 7px 14px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(37,99,235,0.25);">
                        <i class="fas fa-info-circle"></i> <?php echo __('full_details'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php if(empty($past_bookings)): ?>
                <p style="color: var(--text-muted);"><?php echo __('no_completed_workouts'); ?></p>
            <?php else: ?>
                <!-- Single main page preview card for Workout History -->
                <?php $top_w = $past_bookings[0]; ?>
                <div onclick="document.getElementById('modal-all-workout').style.display='flex'"
                     style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: var(--shadow-premium); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; cursor: pointer;">
                    <div>
                        <h4 style="color: var(--text-main); font-weight: 700; margin: 0 0 4px 0; font-size: 1rem;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($top_w['coach_name']); ?></h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;"><i class="far fa-calendar-check" style="margin-right: 4px;"></i><?php echo date('d/m/Y H:i', strtotime($top_w['booking_date'])); ?></p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; background: #e2e8f0; color: #475569; font-weight: 800; text-transform: uppercase;">
                            <?php echo __('completed_label'); ?>
                        </span>
                    </div>
                </div>

                <!-- ALL Workout History Modal -->
                <div id="modal-all-workout" class="profile-modal-backdrop" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(6px); padding: 1rem;">
                    <div style="background: #fff; border-radius: 24px; width: 100%; max-width: 520px; max-height: 85vh; box-shadow: 0 25px 60px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="background: linear-gradient(135deg,#0f172a,#1e3a5f); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white; flex-shrink: 0;">
                            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800;"><i class="fas fa-dumbbell" style="color: #60a5fa; margin-right: 8px;"></i><?php echo __('workout_history'); ?> (<?php echo count($past_bookings); ?>)</h4>
                            <button onclick="document.getElementById('modal-all-workout').style.display='none'" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">×</button>
                        </div>
                        <div style="padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 14px;">
                            <?php foreach($past_bookings as $booking): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1rem 1.2rem; display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="color: #0f172a; font-size: 0.98rem; display: block;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($booking['coach_name']); ?></strong>
                                            <span style="color: var(--text-muted); font-size: 0.82rem;"><i class="far fa-calendar-check" style="margin-right: 4px;"></i><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></span>
                                        </div>
                                        <span style="padding: 3px 10px; border-radius: 12px; font-size: 0.72rem; background: #e2e8f0; color: #475569; font-weight: 800; text-transform: uppercase;">
                                            <?php echo __('completed_label'); ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($booking['coach_message'])): ?>
                                        <div style="background: #fff8e6; border: 1px solid #fde68a; padding: 8px 12px; border-radius: 10px; font-size: 0.82rem; color: #92400e;">
                                            <i class="fas fa-sticky-note" style="color: #d97706; margin-right: 4px;"></i> <strong><?php echo __('coach_notes_title'); ?>:</strong> <?php echo htmlspecialchars(getLocalizedCoachMsg($booking['coach_message'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Coach Review Form -->
                                    <div style="background: #ffffff; padding: 10px; border-radius: 12px; border: 1px solid #cbd5e1;">
                                        <strong style="color: #334155; font-size: 0.8rem; display: block; margin-bottom: 6px; text-transform: uppercase;"><?php echo __('post_review'); ?></strong>
                                        <form action="/Gym Manager/dashboards/profile.php" method="POST" style="display: flex; flex-direction: column; gap: 6px; margin: 0;">
                                            <input type="hidden" name="coach_id" value="<?php echo $booking['coach_id']; ?>">
                                            <div style="display: flex; gap: 6px;">
                                                <select name="rating" required style="background: white; border: 1px solid #cbd5e1; padding: 6px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); outline: none;">
                                                    <option value="5">5 ★</option>
                                                    <option value="4">4 ★</option>
                                                    <option value="3">3 ★</option>
                                                    <option value="2">2 ★</option>
                                                    <option value="1">1 ★</option>
                                                </select>
                                                <input type="text" name="comment" placeholder="<?php echo __('rate_session_placeholder'); ?>" required style="flex: 1; background: white; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 8px; font-size: 0.8rem; outline: none;">
                                            </div>
                                            <button type="submit" name="submit_coach_review" class="btn-auth" style="margin: 0; padding: 6px 12px; font-size: 0.8rem; width: 100%; border-radius: 8px; font-weight: 700;"><?php echo __('post_review'); ?></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Orders Section -->
        <div>
            <!-- Order History Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: var(--primary);"><?php echo __('order_history'); ?></h3>
                <?php if(!empty($orders)): ?>
                    <button onclick="document.getElementById('modal-all-orders').style.display='flex'"
                            style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; padding: 7px 14px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(37,99,235,0.25);">
                        <i class="fas fa-info-circle"></i> <?php echo __('full_details'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php if(empty($orders)): ?>
                <p style="color: var(--text-muted);"><?php echo __('no_orders_yet'); ?></p>
            <?php else: ?>
                <!-- Single main page preview card for Order History -->
                <?php $top_o = $orders[0]; ?>
                <div onclick="document.getElementById('modal-all-orders').style.display='flex'"
                     style="background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: var(--shadow-premium); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; cursor: pointer;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h4 style="color: var(--text-main); margin: 0; font-weight: 700; font-size: 1rem;">Order #<?php echo $top_o['id']; ?></h4>
                            <?php if ($top_o['payment_method'] === 'momo' && !empty($top_o['momo_confirmed'])): ?>
                                <span style="background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; padding: 2px 8px; border-radius: 10px; font-weight: 800; font-size: 0.7rem;">
                                    💜 <?php echo (isset($current_lang) && $current_lang === 'vi') ? 'MoMo Đã TT' : 'MoMo Paid'; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($top_o['status'] === 'pending'): ?>
                                <span style="background: #f59e0b; color: #000; padding: 3px 8px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase;">
                                    🚚 <?php echo __('order_status_delivering'); ?>
                                </span>
                            <?php elseif ($top_o['status'] === 'completed'): ?>
                                <span style="background: #10b981; color: #fff; padding: 3px 8px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase;">
                                    ✔ <?php echo __('status_completed'); ?>
                                </span>
                            <?php else: ?>
                                <span style="background: #ef4444; color: #fff; padding: 3px 8px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase;">
                                    ✖ <?php echo __('status_cancelled'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 2px 0 0;"><i class="far fa-clock" style="margin-right: 4px;"></i><?php echo date('d/m/Y H:i', strtotime($top_o['created_at'])); ?></p>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="price" style="font-size: 1.1rem; font-weight: 800; color: var(--primary);">$<?php echo number_format($top_o['total_price'], 2); ?></span>
                    </div>
                </div>

                <!-- ALL Order History Modal -->
                <div id="modal-all-orders" class="profile-modal-backdrop" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(6px); padding: 1rem;">
                    <div style="background: #fff; border-radius: 24px; width: 100%; max-width: 560px; max-height: 85vh; box-shadow: 0 25px 60px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="background: linear-gradient(135deg,#0f172a,#1e3a5f); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white; flex-shrink: 0;">
                            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800;"><i class="fas fa-shopping-bag" style="color: #60a5fa; margin-right: 8px;"></i><?php echo __('order_history'); ?> (<?php echo count($orders); ?>)</h4>
                            <button onclick="document.getElementById('modal-all-orders').style.display='none'" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">×</button>
                        </div>
                        
                        <div style="padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach($orders as $order): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; padding: 1.2rem; display: flex; flex-direction: column; gap: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <h4 style="margin: 0 0 2px 0; font-size: 0.98rem; font-weight: 800; color: #0f172a;">Order #<?php echo $order['id']; ?></h4>
                                            <span style="color: var(--text-muted); font-size: 0.78rem;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 800; color: var(--primary); font-size: 1rem;">$<?php echo number_format($order['total_price'], 2); ?></span>
                                            <?php if ($order['payment_method'] === 'momo' && !empty($order['momo_confirmed'])): ?>
                                                <span style="background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; padding: 2px 7px; border-radius: 10px; font-weight: 800; font-size: 0.7rem;">
                                                    💜 <?php echo (isset($current_lang) && $current_lang === 'vi') ? 'MoMo Đã TT' : 'MoMo Paid'; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <span style="background: #f59e0b; color: #000; padding: 3px 8px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;">
                                                    🚚 <?php echo __('order_status_delivering'); ?>
                                                </span>
                                            <?php elseif ($order['status'] === 'completed'): ?>
                                                <span style="background: #10b981; color: #fff; padding: 3px 8px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;">
                                                    ✔ <?php echo __('status_completed'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #ef4444; color: #fff; padding: 3px 8px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;">
                                                    ✖ <?php echo __('status_cancelled'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Actions if pending -->
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <div style="display: flex; gap: 8px; background: #fff8e6; padding: 8px 10px; border-radius: 12px; border: 1px solid #fde68a;">
                                            <form method="POST" style="margin: 0; flex: 1;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('confirm_order_prompt'); ?>', '<?php echo __('confirm_order_success'); ?>', '✅');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action_confirm_order" value="1">
                                                <button type="submit" name="action_confirm_order" style="width: 100%; background: #10b981; color: white; border: none; padding: 6px 10px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; cursor: pointer;">
                                                    <i class="fas fa-check-circle"></i> <?php echo __('confirm_order_success'); ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="margin: 0; flex: 1;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('cancel_order_prompt'); ?>', '<?php echo __('cancel_order'); ?>', '⚠️');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action_cancel_order" value="1">
                                                <button type="submit" name="action_cancel_order" style="width: 100%; background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 6px 10px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; cursor: pointer;">
                                                    <i class="fas fa-times-circle"></i> <?php echo __('cancel_order'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; justify-content: flex-end;">
                                            <form method="POST" onsubmit="return confirmOrderCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn xóa đơn hàng này khỏi lịch sử?' : 'Are you sure you want to remove this order from your history?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Đơn Hàng' : 'Remove Order'; ?>', '🗑️');" style="margin: 0;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.78rem; font-weight: 700;">
                                                    <i class="fas fa-trash"></i> <?php echo __('remove'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Items in order -->
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <?php 
                                        $o_stmt = $pdo->prepare("SELECT oi.*, p.name, p.name_vi, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                        $o_stmt->execute([$order['id']]);
                                        $items = $o_stmt->fetchAll();
                                        foreach($items as $item): ?>
                                            <div style="display: flex; align-items: center; gap: 10px; background: #ffffff; padding: 8px 12px; border-radius: 12px; border: 1px solid #cbd5e1;">
                                                <img src="<?php echo $item['image']; ?>" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover;">
                                                <div style="flex-grow: 1;">
                                                    <p style="color: var(--text-main); font-weight: 700; margin: 0 0 2px 0; font-size: 0.85rem;">
                                                        <?php echo (isset($current_lang) && $current_lang === 'vi' && !empty($item['name_vi'])) ? htmlspecialchars($item['name_vi']) : htmlspecialchars($item['name']); ?>
                                                    </p>
                                                    <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0;"><?php echo __('quantity'); ?>: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
                                                </div>
                                                
                                                <?php 
                                                $r_check = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
                                                $r_check->execute([$user_id, $item['product_id']]);
                                                if (!$r_check->fetch()): ?>
                                                    <button onclick="document.getElementById('rev-form-<?php echo $item['id']; ?>').style.display='block'" 
                                                            style="background: none; border: 1px solid var(--primary); color: var(--primary); padding: 4px 8px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"><?php echo __('review_btn'); ?></button>
                                                <?php else: ?>
                                                    <span style="color: #00C851; font-size: 0.75rem; font-weight: 700;"><?php echo __('reviewed_label'); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <form id="rev-form-<?php echo $item['id']; ?>" method="POST" style="display: none; background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-top: 2px;">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <div style="display: flex; gap: 6px;">
                                                    <select name="rating" required style="background: white; color: var(--text-main); border: 1px solid #cbd5e1; padding: 6px; border-radius: 6px; font-size: 0.78rem; outline: none;">
                                                        <option value="5">5 ★</option>
                                                        <option value="4">4 ★</option>
                                                        <option value="3">3 ★</option>
                                                        <option value="2">2 ★</option>
                                                        <option value="1">1 ★</option>
                                                    </select>
                                                    <input type="text" name="comment" placeholder="<?php echo __('short_review_placeholder'); ?>" required style="flex-grow: 1; background: white; color: var(--text-main); border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 6px; font-size: 0.78rem; outline: none;">
                                                    <button type="submit" name="submit_product_review" class="btn-auth" style="padding: 4px 10px; font-size: 0.78rem; margin: 0; border-radius: 6px;"><?php echo __('post'); ?></button>
                                                </div>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const editForm = document.getElementById("profileEditForm");
    if (editForm) {
        editForm.addEventListener("submit", function(e) {
            const nameInput = editForm.querySelector('input[name="name"]');
            const phoneInput = editForm.querySelector('input[name="phone"]');
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'vi') === 'vi';

            if (nameInput && !nameInput.value.trim()) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập họ và tên.' : 'Please enter your full name.',
                    isVi ? 'Thiếu Thông Tin' : 'Name Required',
                    '👤'
                );
                nameInput.focus();
                return false;
            }

            if (phoneInput && phoneInput.value.trim() && !/^[0-9]{10}$/.test(phoneInput.value.trim())) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Số điện thoại không hợp lệ (phải bao gồm 10 chữ số).' : 'Invalid phone number (must be 10 digits).',
                    isVi ? 'Số Điện Thoại Không Hợp Lệ' : 'Invalid Phone Number',
                    '📱'
                );
                phoneInput.focus();
                return false;
            }
        });
    }

    const passForm = document.getElementById("profilePasswordForm");
    if (passForm) {
        passForm.addEventListener("submit", function(e) {
            const currInput = passForm.querySelector('input[name="current_password"]');
            const newInput = passForm.querySelector('input[name="new_password"]');
            const confInput = passForm.querySelector('input[name="confirm_password"]');
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'vi') === 'vi';

            if (currInput && !currInput.value) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập mật khẩu hiện tại.' : 'Please enter your current password.',
                    isVi ? 'Thiếu Mật Khẩu' : 'Password Required',
                    '🔒'
                );
                currInput.focus();
                return false;
            }

            if (newInput && !newInput.value) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Vui lòng nhập mật khẩu mới.' : 'Please enter your new password.',
                    isVi ? 'Thiếu Mật Khẩu Mới' : 'New Password Required',
                    '🔑'
                );
                newInput.focus();
                return false;
            }

            if (newInput && newInput.value.length < 6) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Mật khẩu mới phải có ít nhất 6 ký tự.' : 'New password must be at least 6 characters long.',
                    isVi ? 'Mật Khẩu Quá Ngắn' : 'Password Too Short',
                    '⚠️'
                );
                newInput.focus();
                return false;
            }

            if (confInput && newInput && confInput.value !== newInput.value) {
                e.preventDefault();
                showGymXAlert(
                    isVi ? 'Mật khẩu xác nhận không trùng khớp với mật khẩu mới.' : 'Confirmation password does not match new password.',
                    isVi ? 'Mật Khẩu Không Khớp' : 'Passwords Do Not Match',
                    '❌'
                );
                confInput.focus();
                return false;
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>



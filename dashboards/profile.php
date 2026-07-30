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
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $avatar_path = null;

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

            // Check for conflicts: Any booking for this coach within 1 hour of the requested time, except this same booking
            $requested_time = strtotime($new_date);
            $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
            $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);
            
            $conflict_stmt = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $conflict_stmt->execute([$coach_id, $booking_id, $start_buffer, $end_buffer]);
            
            if ($conflict_stmt->fetch()) {
                $error = __('timeslot_unavailable_err');
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

// Handle Product Review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_product_review'])) {
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$product_id, $user_id, $rating, $comment])) {
        $message = __('prod_review_success');
    } else {
        $error = __('review_error');
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

// Handle Body Metrics Log
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['log_metrics'])) {
    $recorded_date = $_POST['recorded_date'];
    $weight = (float)$_POST['weight'];
    $muscle_mass = $_POST['muscle_mass'] ? (float)$_POST['muscle_mass'] : null;
    $fat_mass = $_POST['fat_mass'] ? (float)$_POST['fat_mass'] : null;
    $height = $_POST['height'] ? (float)$_POST['height'] : null;
    $notes = $_POST['notes'] ?? '';
    
    $bmi = null;
    if ($height && $weight) {
        $bmi = round($weight / (($height / 100) * ($height / 100)), 2);
    }
    
    try {
        $stmt_m = $pdo->prepare("
            INSERT INTO body_metrics (user_id, recorded_date, weight, muscle_mass, fat_mass, bmi, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_m->execute([$user_id, $recorded_date, $weight, $muscle_mass, $fat_mass, $bmi, $notes]);
        $message = "Body metrics recorded successfully!";
    } catch (Exception $e) {
        $error = "Failed to record metrics: " . $e->getMessage();
    }
}

// Get User Detail
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get Body Metrics History
$stmt_metrics = $pdo->prepare("SELECT * FROM body_metrics WHERE user_id = ? ORDER BY recorded_date ASC");
$stmt_metrics->execute([$user_id]);
$metrics_history = $stmt_metrics->fetchAll();


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
    <?php endif; ?>

    <?php if($error): ?>
        <p style="color: #ef4444; text-align: center; margin-bottom: 2rem; background: #fee2e2; padding: 1rem; border-radius: 15px; border: 1px solid #fecaca; font-weight: 600;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- Profile Edit Section -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 4rem;">
        <form method="POST" enctype="multipart/form-data" class="profile-form-grid">
            <div style="text-align: center;">
                <img src="<?php echo $user['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0062ff&color=fff&size=200'; ?>" 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 1rem;">
                <input type="file" name="avatar" id="avatar" style="display: none;">
                <label for="avatar" class="action-btn btn-add" style="cursor: pointer; font-size: 0.8rem;"><?php echo __('change_photo'); ?></label>
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
        <h3 style="margin-bottom: 2rem; color: var(--primary); font-weight: 800;"><?php echo __('security_settings'); ?></h3>
        <form method="POST" class="password-form-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('current_password'); ?></label>
                <input type="password" name="current_password" required placeholder="••••••••" style="background: #f1f5f9;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('new_password'); ?></label>
                <input type="password" name="new_password" required placeholder="••••••••" style="background: #f1f5f9;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 600;"><?php echo __('confirm_new_password'); ?></label>
                <input type="password" name="confirm_password" required placeholder="••••••••" style="background: #f1f5f9;">
            </div>
            <button type="submit" name="update_password" class="btn-auth" style="margin-top: 0; padding: 1.1rem 2rem; width: auto; font-size: 0.95rem;"><?php echo __('update_password'); ?></button>
        </form>
    </div>

    <!-- Fitness & Body Metrics Tracker Section -->
    <div style="background: var(--bg-card); padding: 2.5rem; border-radius: 25px; border: 1px solid var(--border); margin-bottom: 4rem; box-shadow: var(--shadow-premium);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="color: var(--primary); font-weight: 800; margin: 0; font-family: 'Playfair Display', serif; font-size: 1.6rem;"><?php echo __('fitness_progress_tracker'); ?></h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; font-family: 'Outfit', sans-serif;">Track weight, muscle mass, fat mass, and BMI indicators</p>
            </div>
            <button onclick="document.getElementById('add-metrics-modal').style.display='flex'" class="action-btn btn-add" style="margin: 0; padding: 10px 20px; font-size: 0.85rem; font-family: 'Outfit', sans-serif;">
                <i class="fas fa-plus"></i> <?php echo __('add_metric_record'); ?>
            </button>
        </div>

        <!-- Metric Cards Grid -->
        <?php if (!empty($metrics_history)): 
            $latest = end($metrics_history); 
        ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; font-family: 'Outfit', sans-serif;">
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); text-align: center;">
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;"><?php echo __('metric_weight'); ?></span>
                    <h2 style="color: var(--primary); font-weight: 800; font-size: 2.2rem; margin-top: 5px;"><?php echo $latest['weight']; ?> <span style="font-size: 1rem; font-weight: 500;">kg</span></h2>
                </div>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); text-align: center;">
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;"><?php echo __('metric_muscle'); ?></span>
                    <h2 style="color: #10b981; font-weight: 800; font-size: 2.2rem; margin-top: 5px;"><?php echo $latest['muscle_mass'] ?: '--'; ?> <span style="font-size: 1rem; font-weight: 500;">kg</span></h2>
                </div>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); text-align: center;">
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;"><?php echo __('metric_fat'); ?></span>
                    <h2 style="color: #ef4444; font-weight: 800; font-size: 2.2rem; margin-top: 5px;"><?php echo $latest['fat_mass'] ?: '--'; ?> <span style="font-size: 1rem; font-weight: 500;">kg</span></h2>
                </div>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); text-align: center;">
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;"><?php echo __('metric_bmi'); ?></span>
                    <h2 style="color: #f59e0b; font-weight: 800; font-size: 2.2rem; margin-top: 5px;"><?php echo $latest['bmi'] ?: '--'; ?></h2>
                </div>
            </div>

            <!-- Chart Canvas -->
            <div style="position: relative; height: 320px; width: 100%; background: #ffffff; padding: 1rem; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 2rem;">
                <canvas id="metricsChart"></canvas>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 3rem 0; font-family: 'Outfit', sans-serif;"><?php echo __('no_metrics_data'); ?></p>
        <?php endif; ?>
    </div>
 
    <!-- Add Metrics Modal -->
    <div id="add-metrics-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px); font-family: 'Outfit', sans-serif;">
        <div style="background: white; padding: 2.5rem; border-radius: 25px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: var(--text-main); font-family: 'Playfair Display', serif;"><?php echo __('add_metric_record'); ?></h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('recorded_date'); ?></label>
                        <input type="date" name="recorded_date" value="<?php echo date('Y-m-d'); ?>" required style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('height_label'); ?> (cm)</label>
                        <input type="number" step="0.1" name="height" placeholder="175" style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('metric_weight'); ?> (kg)</label>
                        <input type="number" step="0.1" name="weight" placeholder="70" required style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('metric_muscle'); ?> (kg)</label>
                        <input type="number" step="0.1" name="muscle_mass" placeholder="32" style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('metric_fat'); ?> (kg)</label>
                        <input type="number" step="0.1" name="fat_mass" placeholder="12" style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo __('notes'); ?></label>
                    <input type="text" name="notes" placeholder="<?php echo __('notes_metric'); ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; width: 100%; border-radius: 12px; padding: 12px;">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="log_metrics" class="btn-auth" style="flex: 2; margin: 0;"><?php echo __('confirm'); ?></button>
                    <button type="button" onclick="document.getElementById('add-metrics-modal').style.display='none'" 
                             style="flex: 1; background: #f1f5f9; color: var(--text-muted); border: none; border-radius: 12px; font-weight: 700; cursor: pointer;"><?php echo __('cancel'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="profile-layout-grid">
        <!-- Bookings Section -->
        <div>


            <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><?php echo __('upcoming_bookings'); ?></h3>
            <?php if(empty($upcoming_bookings)): ?>
                <p style="color: var(--text-muted); margin-bottom: 2.5rem;"><?php echo __('no_upcoming_bookings'); ?></p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2.5rem;">
                    <?php foreach($upcoming_bookings as $booking): ?>
                        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow-premium);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h4 style="color: var(--text-main); font-weight: 700;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($booking['coach_name']); ?></h4>
                                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 5px 0;"><?php echo __('date'); ?>: <?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></p>
                                    <span style="display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 0.8rem; background: <?php echo $booking['status'] == 'confirmed' ? '#dcfce7' : '#fef3c7'; ?>; color: <?php echo $booking['status'] == 'confirmed' ? '#166534' : '#92400e'; ?>; font-weight: 800; text-transform: uppercase;">
                                        <?php echo strtoupper($booking['status']); ?>
                                    </span>
                                </div>
                                <button onclick="document.getElementById('edit-booking-<?php echo $booking['id']; ?>').style.display='flex'"
                                        style="background: #f1f5f9; border: none; padding: 8px 12px; border-radius: 10px; cursor: pointer; color: var(--text-main); font-weight: 600; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> <?php echo __('edit'); ?>
                                </button>
                            </div>

                            <!-- Edit Booking Modal -->
                            <div id="edit-booking-<?php echo $booking['id']; ?>" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                                <div style="background: white; padding: 2.5rem; border-radius: 25px; width: 90%; max-width: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><?php echo __('workout_history'); ?></h3>
            <?php if(empty($past_bookings)): ?>
                <p style="color: var(--text-muted);"><?php echo __('no_completed_workouts'); ?></p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($past_bookings as $booking): ?>
                        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="color: var(--text-main); font-weight: 700;"><?php echo __('coach_label'); ?>: <?php echo htmlspecialchars($booking['coach_name']); ?></h4>
                                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 5px 0;"><?php echo __('date'); ?>: <?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></p>
                                    <span style="display: inline-block; padding: 6px 14px; border-radius: 30px; font-size: 0.8rem; background: #e2e8f0; color: #475569; font-weight: 800; text-transform: uppercase;">
                                        <?php echo __('completed_label'); ?>
                                    </span>
                                </div>
                            </div>
                            <!-- Review Form -->
                            <form action="profile.php" method="POST" style="display: flex; gap: 10px; align-items: center; margin: 0;">
                                <input type="hidden" name="coach_id" value="<?php echo $booking['coach_id']; ?>">
                                <select name="rating" required style="background: white; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px; font-size: 0.85rem; outline: none; font-weight: 600; color: var(--text-main);">
                                    <option value="5">5 ★</option>
                                    <option value="4">4 ★</option>
                                    <option value="3">3 ★</option>
                                    <option value="2">2 ★</option>
                                    <option value="1">1 ★</option>
                                </select>
                                <input type="text" name="comment" placeholder="<?php echo __('rate_session_placeholder'); ?>" required style="flex-grow: 1; background: white; border: 1px solid #e2e8f0; padding: 8px 15px; border-radius: 10px; font-size: 0.85rem; outline: none;">
                                <button type="submit" name="submit_coach_review" class="btn-auth" style="margin: 0; padding: 8px 15px; font-size: 0.85rem; width: auto; border-radius: 10px;"><?php echo __('post_review'); ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Orders Section -->
        <div>
            <h3 style="margin-bottom: 2rem; color: var(--primary);"><?php echo __('order_history'); ?></h3>
            <?php if(empty($orders)): ?>
                <p style="color: var(--text-muted);"><?php echo __('no_orders_yet'); ?></p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($orders as $order): ?>
                        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                                <div>
                                    <h4 style="color: var(--text-main); margin-bottom: 5px; font-weight: 700;">Order #<?php echo $order['id']; ?></h4>
                                    <p style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <span class="price" style="font-size: 1rem; display: block; margin-bottom: 10px;">$<?php echo number_format($order['total_price'], 2); ?></span>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove this order from your history?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="delete_order" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem; font-weight: 700;">
                                            <i class="fas fa-trash"></i> <?php echo __('remove'); ?>
                                        </button>
                                    </form>
                                </div>
                              <!-- Order Items -->
                            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php 
                                $o_stmt = $pdo->prepare("SELECT oi.*, p.name, p.name_vi, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                $o_stmt->execute([$order['id']]);
                                $items = $o_stmt->fetchAll();
                                foreach($items as $item): ?>
                                    <div style="flex: 1 1 100%; display: flex; align-items: center; gap: 15px; background: #f8fafc; padding: 12px; border-radius: 15px; border: 1px solid var(--border);">
                                        <img src="<?php echo $item['image']; ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                                        <div style="flex-grow: 1;">
                                            <p style="color: var(--text-main); font-weight: 700;">
                                                <?php echo (isset($current_lang) && $current_lang === 'vi' && !empty($item['name_vi'])) ? htmlspecialchars($item['name_vi']) : htmlspecialchars($item['name']); ?>
                                                <?php if (!empty($item['variant'])): ?>
                                                    <span style="color: var(--primary); font-size: 0.85rem;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                                <?php endif; ?>
                                            </p>
                                            <p style="color: var(--text-muted); font-size: 0.8rem;"><?php echo __('quantity'); ?>: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        
                                        <!-- Product Review Button -->
                                        <?php 
                                        $r_check = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
                                        $r_check->execute([$user_id, $item['product_id']]);
                                        if (!$r_check->fetch()): ?>
                                            <button onclick="document.getElementById('rev-form-<?php echo $item['id']; ?>').style.display='block'" 
                                                    style="background: none; border: 1px solid var(--primary); color: var(--primary); padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 0.8rem;"><?php echo __('review_btn'); ?></button>
                                        <?php else: ?>
                                            <span style="color: #00C851; font-size: 0.8rem;"><?php echo __('reviewed_label'); ?></span>
                                        <?php endif; ?>
                                    </div>
 
                                    <!-- Review Form (Hidden by default) -->
                                    <form id="rev-form-<?php echo $item['id']; ?>" method="POST" style="display: none; width: 100%; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin-top: 5px;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                            <select name="rating" required style="background: #f1f5f9; color: var(--text-main); border: 2px solid transparent; padding: 8px; border-radius: 10px; font-size: 0.85rem; outline: none;">
                                                <option value="5">5 ★</option>
                                                <option value="4">4 ★</option>
                                                <option value="3">3 ★</option>
                                                <option value="2">2 ★</option>
                                                <option value="1">1 ★</option>
                                            </select>
                                            <input type="text" name="comment" placeholder="<?php echo __('short_review_placeholder'); ?>" required style="flex-grow: 1; background: #f1f5f9; color: var(--text-main); border: 2px solid transparent; padding: 8px 15px; border-radius: 10px; font-size: 0.85rem; outline: none;">
                                            <button type="submit" name="submit_product_review" class="btn-auth" style="padding: 5px 15px; font-size: 0.8rem;"><?php echo __('post'); ?></button>
                                        </div>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (!empty($metrics_history)): 
    $dates = [];
    $weights = [];
    $muscles = [];
    $fats = [];
    foreach ($metrics_history as $hist) {
        $dates[] = date('d/m', strtotime($hist['recorded_date']));
        $weights[] = (float)$hist['weight'];
        $muscles[] = $hist['muscle_mass'] ? (float)$hist['muscle_mass'] : null;
        $fats[] = $hist['fat_mass'] ? (float)$hist['fat_mass'] : null;
    }
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Cân nặng / Weight (kg)',
                        data: <?php echo json_encode($weights); ?>,
                        borderColor: '#1a2744',
                        backgroundColor: 'rgba(26, 39, 68, 0.03)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Lượng cơ / Muscle (kg)',
                        data: <?php echo json_encode($muscles); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.03)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Lượng mỡ / Fat (kg)',
                        data: <?php echo json_encode($fats); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.03)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Outfit',
                                size: 12
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.03)'
                        },
                        ticks: {
                            font: {
                                family: 'Outfit'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Outfit'
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

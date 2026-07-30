<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: /Gym Manager/index.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

$page = $_GET['page'] ?? 'dashboard';
$message = '';

// Handle Actions (Add/Update/Delete)
if ($page == 'products' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $desc = $_POST['description'] ?: 'Premium quality fitness equipment/supplement.';
    $category = $_POST['category'] ?? 'supplement';
    $has_weight = isset($_POST['has_weight_variants']) ? 1 : 0;
    
    // Handle Image Upload
    $image_path = 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?q=80&w=2070&auto=format&fit=crop'; // Default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/products/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('prod_') . '.' . $file_extension;
        $target_file = $upload_dir_fs . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = '/Gym Manager/assets/img/products/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, description, image, category, has_weight_variants) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $stock, $desc, $image_path, $category, $has_weight]);
    $message = "Product added successfully!";
}

if ($page == 'products' && isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $desc = $_POST['description'];
    $category = $_POST['category'] ?? 'supplement';
    $has_weight = isset($_POST['has_weight_variants']) ? 1 : 0;
    
    // Handle Image Update
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/products/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('prod_') . '.' . $file_extension;
        $target_file = $upload_dir_fs . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $new_image_path = '/Gym Manager/assets/img/products/' . $file_name;
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, image = ?, category = ?, has_weight_variants = ? WHERE id = ?");
            $stmt->execute([$name, $price, $stock, $desc, $new_image_path, $category, $has_weight, $id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category = ?, has_weight_variants = ? WHERE id = ?");
        $stmt->execute([$name, $price, $stock, $desc, $category, $has_weight, $id]);
    }
    $message = "Product updated successfully!";
}

if ($page == 'coaches' && isset($_POST['add_coach'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $specialty = $_POST['specialty'];
    $rate = $_POST['rate'];
    
    try {
        $zalo = $_POST['zalo'] ?? null;
        $gmail = $_POST['gmail'] ?? null;
        $room_name   = trim($_POST['room_name'] ?? '');
        $room_number = trim($_POST['room_number'] ?? '');

        // Check room duplicate by room number
        if ($room_number !== '') {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM coaches WHERE room_number = ?");
            $stmt_check->execute([$room_number]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception(__('room_already_assigned'));
            }
        }
        
        $pdo->beginTransaction();
        // 1. Create User
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'coach')");
        $stmt->execute([$name, $email, $password]);
        $user_id = $pdo->lastInsertId();
        
        // 2. Create Coach Entry
        $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio, zalo, gmail, room_name, room_number) VALUES (?, ?, ?, 'Professional fitness instructor', ?, ?, ?, ?)");
        $stmt->execute([$user_id, $specialty, $rate, $zalo, $gmail, $room_name ?: null, $room_number ?: null]);
        
        $pdo->commit();
        $message = "Coach added successfully! Default password: 123456";
    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
    }
}

if ($page == 'coaches' && isset($_POST['update_coach'])) {
    $coach_id = $_POST['coach_id'];
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $rate = $_POST['rate'];
    
    try {
        $zalo        = $_POST['zalo']        ?? null;
        $gmail       = $_POST['gmail']       ?? null;
        $room_name   = trim($_POST['room_name'] ?? '');
        $room_number = trim($_POST['room_number'] ?? '');
        
        // Check room duplicate by room number
        if ($room_number !== '') {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM coaches WHERE room_number = ? AND id != ?");
            $stmt_check->execute([$room_number, $coach_id]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception(__('room_already_assigned'));
            }
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT user_id FROM coaches WHERE id = ?");
        $stmt->execute([$coach_id]);
        $uid = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $uid]);
        
        $stmt = $pdo->prepare("UPDATE coaches SET specialty = ?, hourly_rate = ?, zalo = ?, gmail = ?, room_name = ?, room_number = ? WHERE id = ?");
        $stmt->execute([$specialty, $rate, $zalo, $gmail, $room_name ?: null, $room_number ?: null, $coach_id]);
        
        $pdo->commit();
        $message = "Coach updated successfully!";
    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
    }
}

if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
}

if (isset($_GET['delete_coach'])) {
    $coach_id = $_GET['delete_coach'];
    // Find user_id first
    $stmt = $pdo->prepare("SELECT user_id FROM coaches WHERE id = ?");
    $stmt->execute([$coach_id]);
    $uid = $stmt->fetchColumn();
    
    // Delete user will cascade to coach entry if FK set correctly
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $message = "Coach deleted successfully!";
}

if ($page == 'users' && isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    try {
        $pdo->beginTransaction();
        
        // Update user role
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $uid]);
        
        // Management of coaches table entry
        if ($new_role == 'coach') {
            $stmt = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
            $stmt->execute([$uid]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio) VALUES (?, 'Fitness', 20.00, 'New coach instructor')");
                $stmt->execute([$uid]);
            }
        }
        
        $pdo->commit();
        $message = "User role updated successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

if ($page == 'users' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check if email exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $message = "Error: Email already exists!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        $message = "User created successfully!";
    }
}

if ($page == 'users' && isset($_POST['update_user_details'])) {
    $uid = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $email, $role, $uid]);
    $message = "User updated successfully!";
}

if (isset($_GET['delete_user'])) {
    $uid = $_GET['delete_user'];
    if ($uid == $_SESSION['user_id']) {
        $message = "Error: You cannot delete yourself!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $message = "User deleted successfully!";
    }
}

// Booking Actions (Add / Update / Delete / Change Status)
if ($page == 'bookings' && isset($_POST['add_booking'])) {
    $user_id = $_POST['user_id'];
    $coach_id = $_POST['coach_id'];
    $booking_date = $_POST['booking_date'];
    $status = $_POST['status'] ?? 'pending';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status, coach_seen, user_seen) VALUES (?, ?, ?, ?, 0, 0)");
        $stmt->execute([$user_id, $coach_id, $booking_date, $status]);
        $message = "Booking created successfully!";
        
        // Optional: PHPMailer to both User and Coach
        try {
            $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt_u->execute([$user_id]);
            $usr = $stmt_u->fetch();

            $stmt_c = $pdo->prepare("SELECT u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $stmt_c->execute([$coach_id]);
            $cch = $stmt_c->fetch();

            if ($usr && $usr['email']) {
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

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                $mail->addAddress($usr['email'], $usr['name']);
                if ($cch && $cch['email']) {
                    $mail->addCC($cch['email'], $cch['name']);
                }

                $mail->isHTML(true);
                $mail->Subject = "🆕 New GymX Booking Scheduled by Admin";
                
                $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                $mail_body .= "<h2 style='color: #0062ff;'>New Appointment</h2>";
                $mail_body .= "<p>An administrator has scheduled a new training session for you.</p>";
                $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($usr['name']) . "<br>";
                $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($cch['name']) . "<br>";
                $mail_body .= "<strong>Time:</strong> " . date('l, d/m/Y - H:i', strtotime($booking_date)) . "<br>";
                $mail_body .= "<strong>Status:</strong> " . strtoupper($status) . "<br>";
                $mail_body .= "</div>";
                $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                
                $mail->Body = $mail_body;
                $mail->send();
            }
        } catch (Exception $mailEx) {
            error_log("Mail error during booking insert: " . $mailEx->getMessage());
        }
    } catch (Exception $e) {
        $message = "Error creating booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $user_id = $_POST['user_id'];
    $coach_id = $_POST['coach_id'];
    $booking_date = $_POST['booking_date'];
    $status = $_POST['status'];
    $coach_message = $_POST['coach_message'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET user_id = ?, coach_id = ?, booking_date = ?, status = ?, coach_message = ?, coach_seen = 0, user_seen = 0 WHERE id = ?");
        $stmt->execute([$user_id, $coach_id, $booking_date, $status, $coach_message, $booking_id]);
        $message = "Booking updated successfully!";
        
        // Optional: PHPMailer rescheduling notice
        try {
            $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt_u->execute([$user_id]);
            $usr = $stmt_u->fetch();

            $stmt_c = $pdo->prepare("SELECT u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $stmt_c->execute([$coach_id]);
            $cch = $stmt_c->fetch();

            if ($usr && $usr['email']) {
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

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                $mail->addAddress($usr['email'], $usr['name']);
                if ($cch && $cch['email']) {
                    $mail->addCC($cch['email'], $cch['name']);
                }

                $mail->isHTML(true);
                $mail->Subject = "🔄 GymX Booking Updated";
                
                $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                $mail_body .= "<h2 style='color: #f59e0b;'>Booking Rescheduled</h2>";
                $mail_body .= "<p>An administrator has updated your training session details.</p>";
                $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($usr['name']) . "<br>";
                $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($cch['name']) . "<br>";
                $mail_body .= "<strong>New Time:</strong> " . date('l, d/m/Y - H:i', strtotime($booking_date)) . "<br>";
                $mail_body .= "<strong>Status:</strong> " . strtoupper($status) . "<br>";
                if ($coach_message) {
                    $mail_body .= "<strong>Notes:</strong> " . htmlspecialchars($coach_message) . "<br>";
                }
                $mail_body .= "</div>";
                $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                
                $mail->Body = $mail_body;
                $mail->send();
            }
        } catch (Exception $mailEx) {
            error_log("Mail error during booking update: " . $mailEx->getMessage());
        }
    } catch (Exception $e) {
        $message = "Error updating booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_GET['delete_booking'])) {
    $booking_id = $_GET['delete_booking'];
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $message = "Booking deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_GET['change_status']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    $new_status = $_GET['change_status'];
    
    if (in_array($new_status, ['pending', 'confirmed', 'cancelled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ?, coach_seen = 0, user_seen = 0 WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            $message = "Booking status updated to " . strtoupper($new_status) . " successfully!";
            
            // Send status change email
            try {
                // Fetch details
                $stmt_b = $pdo->prepare("SELECT b.booking_date, u.name as user_name, u.email as user_email, c_u.name as coach_name, c_u.email as coach_email 
                                         FROM bookings b 
                                         JOIN users u ON b.user_id = u.id 
                                         JOIN coaches c ON b.coach_id = c.id 
                                         JOIN users c_u ON c.user_id = c_u.id 
                                         WHERE b.id = ?");
                $stmt_b->execute([$booking_id]);
                $b_info = $stmt_b->fetch();
                
                if ($b_info && $b_info['user_email']) {
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

                    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                    $mail->addAddress($b_info['user_email'], $b_info['user_name']);
                    if ($b_info['coach_email']) {
                        $mail->addCC($b_info['coach_email'], $b_info['coach_name']);
                    }

                    $mail->isHTML(true);
                    $color = ($new_status == 'confirmed') ? '#10b981' : (($new_status == 'cancelled') ? '#ef4444' : '#64748b');
                    $mail->Subject = "🔔 GymX Booking Status Update";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $mail_body .= "<h2 style='color: " . $color . ";'>Booking Status Updated</h2>";
                    $mail_body .= "<p>An administrator has updated the status of your training session.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                    $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($b_info['user_name']) . "<br>";
                    $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($b_info['coach_name']) . "<br>";
                    $mail_body .= "<strong>Time:</strong> " . date('l, d/m/Y - H:i', strtotime($b_info['booking_date'])) . "<br>";
                    $mail_body .= "<strong>New Status:</strong> <span style='color: " . $color . "; font-weight: bold;'>" . strtoupper($new_status) . "</span><br>";
                    $mail_body .= "</div>";
                    $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                    
                    $mail->Body = $mail_body;
                    $mail->send();
                }
            } catch (Exception $mailEx) {
                error_log("Mail error during status change: " . $mailEx->getMessage());
            }
        } catch (Exception $e) {
            $message = "Error updating booking status: " . $e->getMessage();
        }
    }
}
?>

<section class="admin-section">
    <div class="admin-header">
        <h1>Manager Dashboard</h1>
        <div class="auth-btns">
            <span style="color: var(--text-muted); margin-right: 1rem;">Hello Manager, <strong><?php echo $_SESSION['user_name']; ?></strong></span>
        </div>
    </div>

    <div class="admin-grid">
        <div class="admin-sidebar">
            <ul>
                <li><a href="/Gym Manager/dashboards/manager.php?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>"><?php echo __("overview"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=products" class="<?php echo $page == 'products' ? 'active' : ''; ?>"><?php echo __("product_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=coaches" class="<?php echo $page == 'coaches' ? 'active' : ''; ?>"><?php echo __("coach_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=bookings" class="<?php echo $page == 'bookings' ? 'active' : ''; ?>"><?php echo __("booking_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=yearly_report" class="<?php echo $page == 'yearly_report' ? 'active' : ''; ?>"><?php echo __("yearly_report"); ?></a></li>
            </ul>
        </div>

        <div class="admin-content" style="min-width: 0;">
            <?php if($message): ?>
                <p style="color: <?php echo (stripos($message, 'Lỗi') !== false || stripos($message, 'Error') !== false) ? '#ff4444' : '#00C851'; ?>; margin-bottom: 1rem; font-weight: 600;"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <h2><?php echo __("system_overview"); ?></h2>
                <div class="grid" style="margin-top: 2rem;">
                    <div style="background: white; padding: 2.5rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: var(--primary); text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;"><?php echo __("total_users"); ?></h3>
                        <p style="font-size: 3rem; font-weight: 800; color: var(--text-main);">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>
                        </p>
                    </div>
                    <div style="background: white; padding: 2.5rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: var(--primary); text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;">Total Orders</h3>
                        <p style="font-size: 3rem; font-weight: 800; color: var(--text-main);">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); ?>
                        </p>
                    </div>
                </div>

            <?php elseif ($page == 'products'): 
                $edit_prod = null;
                if (isset($_GET['edit_product'])) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt->execute([$_GET['edit_product']]);
                    $edit_prod = $stmt->fetch();
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo __("product_management"); ?></h2>
                </div>
                
                <?php if ($edit_prod): ?>
                <!-- Edit Product Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="product_id" value="<?php echo $edit_prod['id']; ?>">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_prod['name']); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Price ($)</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $edit_prod['price']; ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("stock"); ?></label>
                            <input type="number" name="stock" value="<?php echo $edit_prod['stock']; ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Category</label>
                            <select name="category" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="supplement" <?php echo ($edit_prod['category'] ?? '') == 'supplement' ? 'selected' : ''; ?>>Supplement</option>
                                <option value="accessory" <?php echo ($edit_prod['category'] ?? '') == 'accessory' ? 'selected' : ''; ?>>Accessory</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="has_weight_variants" value="1" id="edit_has_weight" style="width: 20px; height: 20px;" <?php echo !empty($edit_prod['has_weight_variants']) ? 'checked' : ''; ?>>
                        <label for="edit_has_weight" style="font-weight: 600; cursor: pointer;">Enable Weight Options (Grams, KG)</label>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Product Image</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <?php if ($edit_prod['image']): ?>
                                <img src="<?php echo $edit_prod['image']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;">
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: #f1f5f9; border-radius: 10px; width: 100%;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"><?php echo htmlspecialchars($edit_prod['description']); ?></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_product" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo __("update_product"); ?></button>
                        <a href="/Gym Manager/dashboards/manager.php?page=products" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Product Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Product Name</label>
                            <input type="text" name="name" required placeholder="e.g. Whey Protein">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Price ($)</label>
                            <input type="number" step="0.01" name="price" required placeholder="0.00">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("stock"); ?></label>
                            <input type="number" name="stock" required placeholder="10">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Category</label>
                            <select name="category" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="supplement">Supplement</option>
                                <option value="accessory">Accessory</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="has_weight_variants" value="1" id="add_has_weight" style="width: 20px; height: 20px;">
                        <label for="add_has_weight" style="font-weight: 600; cursor: pointer;">Enable Weight Options (Grams, KG)</label>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Product Image</label>
                        <input type="file" name="image" accept="image/*" required style="padding: 0.8rem; background: #f1f5f9; border-radius: 10px; width: 100%;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" placeholder="Describe the product..." style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"></textarea>
                    </div>
                    <button type="submit" name="add_product" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo __("add_product"); ?></button>
                </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th><?php echo __("price"); ?></th>
                                <th><?php echo __("stock"); ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prods = $pdo->query("SELECT * FROM products")->fetchAll();
                            foreach($prods as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><img src="<?php echo $p['image']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"></td>
                                <td><?php echo $p['name']; ?></td>
                                <td>$<?php echo $p['price']; ?></td>
                                <td><?php echo $p['stock']; ?></td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/manager.php?page=products&edit_product=<?php echo $p['id']; ?>" class="action-btn btn-edit">Edit</a>
                                        <a href="/Gym Manager/dashboards/manager.php?page=products&delete_product=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Confirm delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'coaches'): 
                $edit_coach = null;
                if (isset($_GET['edit_coach'])) {
                    $stmt = $pdo->prepare("SELECT c.*, u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                    $stmt->execute([$_GET['edit_coach']]);
                    $edit_coach = $stmt->fetch();
                }
            ?>
                <h2><?php echo __("coach_management"); ?></h2>
                
                <?php if ($edit_coach): ?>
                <!-- Edit Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="coach_id" value="<?php echo $edit_coach['id']; ?>">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" value="<?php echo $edit_coach['name']; ?>" required style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Discipline</label>
                            <select name="specialty" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="Gym" <?php echo (strtolower($edit_coach['specialty']) == 'gym') ? 'selected' : ''; ?>>Gym Fitness</option>
                                <option value="Yoga" <?php echo (strtolower($edit_coach['specialty']) == 'yoga') ? 'selected' : ''; ?>>Yoga</option>
                                <option value="Muay Thai" <?php echo (strtolower($edit_coach['specialty']) == 'muay thai') ? 'selected' : ''; ?>>Muay Thai</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Rate/h ($)</label>
                            <input type="number" step="0.01" name="rate" value="<?php echo $edit_coach['hourly_rate']; ?>" required style="background: #f1f5f9;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Zalo Number/Link</label>
                            <input type="text" name="zalo" value="<?php echo $edit_coach['zalo']; ?>" placeholder="09xxxxxxx or link" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Gmail Address</label>
                            <input type="email" name="gmail" value="<?php echo $edit_coach['gmail']; ?>" placeholder="coach@gmail.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Tên Phòng</label>
                            <select name="room_name" id="edit_room_name" onchange="updateManagerRoomOptions('edit_room_name', 'edit_room_number')" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600; height: 54px;">
                                <option value="Gym">Gym</option>
                                <option value="Muay">Muay</option>
                                <option value="Yoga">Yoga</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Số Phòng</label>
                            <select name="room_number" id="edit_room_number" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600; height: 54px;">
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="submit" name="update_coach" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Update Coach Details</button>
                        <a href="/Gym Manager/dashboards/manager.php?page=coaches" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="e.g. John Doe" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Email (Account)</label>
                            <input type="email" name="email" required placeholder="john@gymx.com" style="background: #f1f5f9;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Discipline</label>
                            <select name="specialty" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="Gym">Gym Fitness</option>
                                <option value="Yoga">Yoga</option>
                                <option value="Muay Thai">Muay Thai</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Rate/h ($)</label>
                            <input type="number" step="0.01" name="rate" required placeholder="25.00" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Zalo Number</label>
                            <input type="text" name="zalo" placeholder="09xxxxxxxx" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Contact Gmail</label>
                            <input type="email" name="gmail" placeholder="coach@gmail.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Tên Phòng</label>
                            <select name="room_name" id="add_room_name" onchange="updateManagerRoomOptions('add_room_name', 'add_room_number')" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600; height: 54px;">
                                <option value="Gym">Gym</option>
                                <option value="Muay">Muay</option>
                                <option value="Yoga">Yoga</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Số Phòng</label>
                            <select name="room_number" id="add_room_number" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600; height: 54px;">
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_coach" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2.8rem;">Add Coach</button>
                </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th><?php echo __("email"); ?></th>
                                <th>Specialty</th>
                                <th>Tên Phòng</th>
                                <th>Số Phòng</th>
                                <th>Rate/h</th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $coaches = $pdo->query("SELECT c.*, u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id")->fetchAll();
                            foreach($coaches as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['specialty'] ?? ''); ?></td>
                                <td><?php echo !empty($c['room_name']) ? htmlspecialchars($c['room_name']) : '<span style="color:#aaa;">—</span>'; ?></td>
                                <td><?php echo !empty($c['room_number']) ? '<strong style="color:var(--primary);">' . htmlspecialchars($c['room_number']) . '</strong>' : '<span style="color:#aaa;">—</span>'; ?></td>
                                <td>$<?php echo htmlspecialchars($c['hourly_rate'] ?? '0'); ?></td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/manager.php?page=coaches&edit_coach=<?php echo $c['id']; ?>" class="action-btn btn-edit" style="font-size: 0.8rem; padding: 5px 10px; display: inline-block;">Edit</a>
                                        <a href="/Gym Manager/dashboards/manager.php?page=coaches&delete_coach=<?php echo $c['id']; ?>" class="action-btn btn-delete" style="font-size: 0.8rem; padding: 5px 10px; display: inline-block;" onclick="return confirm('Confirm delete this coach?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <script>
                function updateManagerRoomOptions(roomNameId, roomNumberId) {
                    const nameSelect = document.getElementById(roomNameId);
                    if (!nameSelect) return;
                    const roomNameVal = nameSelect.value;
                    const numberSelect = document.getElementById(roomNumberId);
                    if (!numberSelect) return;
                    
                    numberSelect.innerHTML = '';
                    
                    const nameLower = roomNameVal.toLowerCase();
                    let rooms = [];
                    if (nameLower.includes('gym')) {
                        rooms = ['Room 1', 'Room 2', 'Room 3'];
                    } else if (nameLower.includes('muay')) {
                        rooms = ['Room 4', 'Room 5', 'Room 6', 'Room 7'];
                    } else if (nameLower.includes('yoga')) {
                        rooms = ['Room 8', 'Room 9', 'Room 10'];
                    } else {
                        rooms = ['Room 1', 'Room 2', 'Room 3', 'Room 4', 'Room 5', 'Room 6', 'Room 7', 'Room 8', 'Room 9', 'Room 10'];
                    }
                    
                    rooms.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r;
                        opt.textContent = r;
                        numberSelect.appendChild(opt);
                    });
                }
                
                // Initialize for Add form if present
                if (document.getElementById('add_room_name')) {
                    updateManagerRoomOptions('add_room_name', 'add_room_number');
                }
                // Initialize for Edit form if present
                if (document.getElementById('edit_room_name')) {
                    const savedRoomName = "<?php echo isset($edit_coach) ? htmlspecialchars($edit_coach['room_name'] ?? 'Gym') : 'Gym'; ?>";
                    const savedRoomNumber = "<?php echo isset($edit_coach) ? htmlspecialchars($edit_coach['room_number'] ?? '') : ''; ?>";
                    
                    let cleanName = 'Gym';
                    if (savedRoomName.toLowerCase().includes('gym')) cleanName = 'Gym';
                    else if (savedRoomName.toLowerCase().includes('muay')) cleanName = 'Muay';
                    else if (savedRoomName.toLowerCase().includes('yoga')) cleanName = 'Yoga';
                    
                    document.getElementById('edit_room_name').value = cleanName;
                    updateManagerRoomOptions('edit_room_name', 'edit_room_number');
                    if (savedRoomNumber) {
                        document.getElementById('edit_room_number').value = savedRoomNumber;
                    }
                }
                </script>

            <?php elseif ($page == 'users'): 
                if ($_SESSION['role'] !== 'admin') {
                    echo "<script>window.location.href='manager.php';</script>";
                    exit();
                }

                $edit_u = null;
                if (isset($_GET['edit_user'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_GET['edit_user']]);
                    $edit_u = $stmt->fetch();
                }
            ?>
                <h2>User Permissions Management</h2>

                <!-- Add New User Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Add New User</h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="User Name" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("email"); ?></label>
                            <input type="email" name="email" required placeholder="email@example.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Password</label>
                            <input type="password" name="password" required placeholder="******" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("role"); ?></label>
                            <select name="role" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="user">User</option>
                                <option value="coach"><?php echo __("coach"); ?></option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Add User</button>
                    </div>
                </form>

                <?php if ($edit_u): ?>
                    <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 2px solid var(--primary);">
                        <input type="hidden" name="user_id" value="<?php echo $edit_u['id']; ?>">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_u['name']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_u['email']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">System Role</label>
                                <select name="role" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                    <option value="user" <?php echo $edit_u['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="coach" <?php echo $edit_u['role'] == 'coach' ? 'selected' : ''; ?>><?php echo __("coach"); ?></option>
                                    <option value="manager" <?php echo $edit_u['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $edit_u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="update_user_details" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">Update User</button>
                            <a href="/Gym Manager/dashboards/manager.php?page=users" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th><?php echo __("email"); ?></th>
                                <th><?php echo __("status"); ?></th>
                                <th><?php echo __("role"); ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
                            foreach($all_users as $u): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--primary); font-size: 0.8rem;">
                                            <?php echo strtoupper($u['name'][0]); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 5px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">
                                        <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%;"></span> Active
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $u['role'] == 'admin' ? '#f0fdf4' : ($u['role'] == 'manager' ? '#fff7ed' : ($u['role'] == 'coach' ? '#eff6ff' : '#f8fafc')); 
                                    ?>; color: <?php 
                                        echo $u['role'] == 'admin' ? '#22c55e' : ($u['role'] == 'manager' ? '#f97316' : ($u['role'] == 'coach' ? '#3b82f6' : '#64748b')); 
                                    ?>; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                        <?php echo strtoupper($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/manager.php?page=users&edit_user=<?php echo $u['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 8px 12px;">Edit</a>
                                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="/Gym Manager/dashboards/manager.php?page=users&delete_user=<?php echo $u['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 8px 12px;" onclick="return confirm('WARNING: Are you sure you want to delete this user? This action cannot be undone.')">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'bookings'): 
                // Fetch stats
                $stat_total = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
                $stat_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
                $stat_confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
                $stat_cancelled = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

                // Filters & Search logic
                $search = $_GET['search'] ?? '';
                $status_filter = $_GET['status_filter'] ?? '';
                
                $query_str = "SELECT b.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, 
                                     c_u.name as coach_name, c.specialty as coach_specialty, c.hourly_rate as coach_rate,
                                     c.room_name as coach_room_name, c.room_number as coach_room_number
                              FROM bookings b 
                              JOIN users u ON b.user_id = u.id 
                              JOIN coaches c ON b.coach_id = c.id 
                              JOIN users c_u ON c.user_id = c_u.id";
                
                $params = [];
                $conditions = [];
                
                if ($search !== '') {
                    $conditions[] = "(u.name LIKE ? OR c_u.name LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($status_filter !== '' && in_array($status_filter, ['pending', 'confirmed', 'cancelled'])) {
                    $conditions[] = "b.status = ?";
                    $params[] = $status_filter;
                }
                
                if (!empty($conditions)) {
                    $query_str .= " WHERE " . implode(" AND ", $conditions);
                }
                
                $query_str .= " ORDER BY b.booking_date DESC";
                
                $stmt = $pdo->prepare($query_str);
                $stmt->execute($params);
                $bookings = $stmt->fetchAll();

                // Fetch all users for dropdown (only 'user' role or any, let's allow booking for any user)
                $users_list = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name ASC")->fetchAll();
                if (empty($users_list)) {
                    // Fallback to all users if none have 'user' role
                    $users_list = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
                }
                
                // Fetch all coaches for dropdown
                $coaches_list = $pdo->query("SELECT c.id, u.name as coach_name, c.specialty, c.room_name, c.room_number FROM coaches c JOIN users u ON c.user_id = u.id ORDER BY u.name ASC")->fetchAll();

                // Check if we are editing a booking
                $edit_booking = null;
                if (isset($_GET['edit_booking'])) {
                    $stmt_eb = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                    $stmt_eb->execute([$_GET['edit_booking']]);
                    $edit_booking = $stmt_eb->fetch();
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("booking_management"); ?></h2>
                </div>

                <!-- Statistics Cards -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;"><?php echo __("total_bookings"); ?></h4>
                        <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_total; ?></p>
                    </div>
                    <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5;">
                        <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase;">Pending</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_pending; ?></p>
                    </div>
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7;">
                        <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase;">Confirmed</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_confirmed; ?></p>
                    </div>
                    <div style="background: #fafafa; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: #4b5563; font-size: 0.8rem; text-transform: uppercase;">Cancelled</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #4b5563; margin-top: 5px;"><?php echo $stat_cancelled; ?></p>
                    </div>
                </div>

                <?php if ($edit_booking): ?>
                <!-- Edit Reschedule Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="booking_id" value="<?php echo $edit_booking['id']; ?>">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i> Edit & Reschedule Booking #<?php echo $edit_booking['id']; ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("client"); ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($users_list as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $edit_booking['user_id'] == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("coach"); ?></label>
                            <select name="coach_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($coaches_list as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $edit_booking['coach_id'] == $c['id'] ? 'selected' : ''; ?>>Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)<?php echo $c['room_number'] ? ' - ' . htmlspecialchars($c['room_number']) : ''; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Booking Date</label>
                            <input type="datetime-local" name="booking_date" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_booking['booking_date'])); ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("status"); ?></label>
                            <select name="status" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="pending" <?php echo $edit_booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $edit_booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $edit_booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Notes / Coach's Message</label>
                            <input type="text" name="coach_message" value="<?php echo htmlspecialchars($edit_booking['coach_message'] ?? ''); ?>" placeholder="Enter cancellation reason or trainer advice..." style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_booking" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">Save Changes</button>
                        <a href="/Gym Manager/dashboards/manager.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Booking Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-plus" style="color: var(--primary);"></i> Create Direct Booking
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("client"); ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected>-- Select Client --</option>
                                <?php foreach($users_list as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("coach"); ?></label>
                            <select name="coach_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected>-- Select Coach --</option>
                                <?php foreach($coaches_list as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)<?php echo $c['room_number'] ? ' - ' . htmlspecialchars($c['room_number']) : ''; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Date Time</label>
                            <input type="datetime-local" name="booking_date" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("status"); ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="pending">Pending</option>
                                <option value="confirmed" selected>Confirmed</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_booking" class="btn-auth" style="margin-top: 1.5rem; width: auto; padding: 1.1rem 2.5rem;">Create Booking</button>
                </form>
                <?php endif; ?>

                <!-- Search & Filters -->
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem; background: #f8fafc; padding: 1.2rem; border-radius: 20px; border: 1px solid var(--border);">
                    <input type="hidden" name="page" value="bookings">
                    <div style="flex: 2; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search client or coach name..." style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-size: 0.95rem; outline: none;">
                    </div>
                    <div style="flex: 1;">
                        <select name="status_filter" style="width: 100%; padding: 1rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-weight: 600; outline: none; font-size: 0.95rem;">
                            <option value="">-- All Statuses --</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-auth" style="margin-top: 0; width: auto; padding: 1rem 2rem; font-size: 0.95rem;">Search</button>
                    <?php if ($search !== '' || $status_filter !== ''): ?>
                        <a href="/Gym Manager/dashboards/manager.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); padding: 1rem 1.5rem; border-radius: 15px; text-decoration: none; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center;">Reset Filter</a>
                    <?php endif; ?>
                </form>

                <!-- Bookings Table -->
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 20px; border: 1px solid var(--border); margin-top: 2rem;">
                        <i class="far fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">No bookings found matching filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 1.2rem; border-radius: 15px 0 0 15px;"><?php echo __("client"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("coach"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("training_room"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("date_time"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("notes"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("status"); ?></th>
                                    <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;"><?php echo __("actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo $b['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($b['user_name']).'&background=0062ff&color=fff&size=40'; ?>" 
                                                 style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
                                            <div>
                                                <strong style="color: var(--text-main); font-weight: 700;"><?php echo htmlspecialchars($b['user_name']); ?></strong>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                                <?php 
                                                $phone_to_show = !empty($b['user_phone']) ? $b['user_phone'] : '';
                                                if (!empty($phone_to_show)): ?>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;"><i class="fas fa-phone-alt" style="font-size: 0.75rem; color: var(--primary);"></i> <?php echo htmlspecialchars($phone_to_show); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($b['class_name'])): ?>
                                                    <div style="margin-top: 4px;">
                                                        <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.2px;">
                                                            <i class="fas fa-box" style="margin-right: 3px;"></i> <?php echo htmlspecialchars($b['class_name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong style="color: var(--text-main);">Coach <?php echo htmlspecialchars($b['coach_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($b['coach_specialty']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($b['coach_room_number'])): ?>
                                            <span style="background: var(--primary); color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;"><?php echo htmlspecialchars($b['coach_room_number']); ?></span>
                                            <?php if (!empty($b['coach_room_name'])): ?>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px;"><?php echo htmlspecialchars($b['coach_room_name']); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #aaa; font-size: 0.85rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <i class="far fa-calendar-alt" style="color: var(--primary); margin-right: 5px;"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                            <?php echo date('l', strtotime($b['booking_date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($b['coach_message']): ?>
                                            <span style="font-size: 0.85rem; color: var(--text-muted); display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($b['coach_message']); ?>">
                                                <?php echo htmlspecialchars($b['coach_message']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $b['status']; ?>" style="display: inline-block;">
                                            <?php echo strtoupper($b['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell-flex">
                                            <?php if ($b['status'] == 'pending'): ?>
                                                <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.75rem; padding: 6px 10px;" title="Approve booking">
                                                    <i class="fas fa-check"></i> Confirm
                                                </a>
                                                <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px;" title="Cancel booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php elseif ($b['status'] == 'confirmed'): ?>
                                                <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px;" title="Cancel booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php elseif ($b['status'] == 'cancelled'): ?>
                                                <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.75rem; padding: 6px 10px;" title="Re-confirm booking">
                                                    <i class="fas fa-redo"></i> Re-confirm
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="/Gym Manager/dashboards/manager.php?page=bookings&edit_booking=<?php echo $b['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 6px 10px;" title="Reschedule">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            
                                            <a href="/Gym Manager/dashboards/manager.php?page=bookings&delete_booking=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px; background: #fff1f2; color: #ef4444;" title="Delete permanently" onclick="return confirm('WARNING: Are you sure you want to PERMANENTLY DELETE this booking?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php elseif ($page == 'yearly_report'): 
                $selected_year = $_GET['year'] ?? date('Y');
                
                // Fetch bookings for the year
                $stmt_b = $pdo->prepare("SELECT b.*, u.name as user_name, u.email as user_email, b.user_phone, c_u.name as coach_name 
                                         FROM bookings b 
                                         JOIN users u ON b.user_id = u.id 
                                         JOIN coaches c ON b.coach_id = c.id 
                                         JOIN users c_u ON c.user_id = c_u.id 
                                         WHERE YEAR(b.booking_date) = ?
                                         ORDER BY b.booking_date DESC");
                $stmt_b->execute([$selected_year]);
                $yearly_bookings = $stmt_b->fetchAll();

                // Fetch orders for the year
                $stmt_o = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                         FROM orders o 
                                         JOIN users u ON o.user_id = u.id 
                                         WHERE YEAR(o.created_at) = ?
                                         ORDER BY o.created_at DESC");
                $stmt_o->execute([$selected_year]);
                $yearly_orders = $stmt_o->fetchAll();

                // Fetch monthly revenue for the chart
                $stmt_chart = $pdo->prepare("SELECT MONTH(o.created_at) as month, SUM(o.total_price) as revenue 
                                             FROM orders o
                                             WHERE YEAR(o.created_at) = ? AND o.status = 'completed'
                                             AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)
                                             GROUP BY MONTH(o.created_at)");
                $stmt_chart->execute([$selected_year]);
                $chart_data_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

                $monthly_revenue = array_fill(1, 12, 0);
                foreach($chart_data_raw as $row) {
                    $monthly_revenue[(int)$row['month']] = (float)$row['revenue'];
                }
            ?>
                <?php $total_yearly_revenue = array_sum($monthly_revenue); ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2>Yearly Report - <?php echo htmlspecialchars($selected_year); ?></h2>
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-top: 5px;">
                            Total Revenue: <strong style="color: #10b981; font-size: 1.5rem;">$<?php echo number_format($total_yearly_revenue, 2); ?></strong>
                        </p>
                    </div>
                    <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="page" value="yearly_report">
                        <label style="font-weight: 600;"><?php echo __("select_year"); ?></label>
                        <select name="year" onchange="this.form.submit()" style="padding: 0.8rem; border-radius: 10px; border: 1px solid var(--border); outline: none; font-weight: 600;">
                            <?php 
                            $current_year = date('Y');
                            for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <!-- Chart Section -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <div style="background: white; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 3rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                        <?php echo __("monthly_revenue_chart"); ?>
                    </h3>
                    <div style="height: 350px; width: 100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const canvas = document.getElementById('revenueChart');
                    if(canvas) {
                        const ctx = canvas.getContext('2d');
                        
                        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(0, 98, 255, 0.4)');
                        gradient.addColorStop(1, 'rgba(0, 98, 255, 0.0)');

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                datasets: [{
                                    label: 'Revenue',
                                    data: <?php echo json_encode(array_values($monthly_revenue)); ?>,
                                    backgroundColor: gradient,
                                    borderColor: '#0062ff',
                                    borderWidth: 3,
                                    pointBackgroundColor: '#ffffff',
                                    pointBorderColor: '#0062ff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        titleFont: { size: 13 },
                                        bodyFont: { size: 14, weight: 'bold' },
                                        padding: 12,
                                        displayColors: false,
                                        callbacks: {
                                            label: function(context) {
                                                return '$' + context.parsed.y.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: '#f1f5f9', drawBorder: false },
                                        ticks: {
                                            callback: function(value) { return '$' + value; },
                                            font: { family: 'Inter, sans-serif', size: 12 },
                                            color: '#64748b'
                                        }
                                    },
                                    x: {
                                        grid: { display: false, drawBorder: false },
                                        ticks: {
                                            font: { family: 'Inter, sans-serif', size: 12 },
                                            color: '#64748b'
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>

                                <?php 
                $bookings_by_coach = [];
                foreach ($yearly_bookings as $b) {
                    $c_name = $b['coach_name'];
                    if (!isset($bookings_by_coach[$c_name])) {
                        $bookings_by_coach[$c_name] = [];
                    }
                    $bookings_by_coach[$c_name][] = $b;
                }
                ?>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                    <i class="fas fa-users-cog" style="color: var(--primary);"></i> Coach Performance Summary (<?php echo htmlspecialchars($selected_year); ?>)
                </h3>
                
                <?php if (empty($bookings_by_coach)): ?>
                    <p style="color: var(--text-muted); margin-bottom: 3rem;"><?php echo __("no_appointments"); ?></p>
                <?php else: ?>
                    <div style="margin-bottom: 3rem;">
                    <?php foreach ($bookings_by_coach as $c_name => $c_bookings): ?>
                    <div style="background: white; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                        <div style="background: #f8fafc; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                            <h4 style="margin: 0; color: var(--primary); font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-user-tie"></i> Coach <?php echo htmlspecialchars($c_name); ?>
                            </h4>
                            <span style="background: var(--primary); color: white; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                                Total Bookings: <?php echo count($c_bookings); ?>
                            </span>
                        </div>
                        
                        <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                            <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("date_time"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("client_name"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("email"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("phone"); ?></th>
                                        <th style="padding: 1.2rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #e2e8f0;"><?php echo __("status"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($c_bookings as $b): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td style="padding: 1.2rem; font-weight: 600; border-bottom: 1px solid #f1f5f9;"><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                        <td style="padding: 1.2rem; border-bottom: 1px solid #f1f5f9;"><strong><?php echo htmlspecialchars($b['user_name']); ?></strong></td>
                                        <td style="padding: 1.2rem; word-break: break-all; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars($b['user_email']); ?></td>
                                        <td style="padding: 1.2rem; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?></td>
                                        <td style="padding: 1.2rem; text-align: center; border-bottom: 1px solid #f1f5f9;">
                                            <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo strtoupper($b['status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Products Purchased in <?php echo htmlspecialchars($selected_year); ?>
                </h3>
                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 1.2rem; border-radius: 15px 0 0 15px; white-space: nowrap; text-align: left;"><?php echo __("date"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("order_id"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("client_name"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("email"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("phone"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("total_price"); ?></th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;"><?php echo __("items"); ?></th>
                                <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center; white-space: nowrap;"><?php echo __("status"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($yearly_orders as $o): 
                                $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                $stmt_items->execute([$o['id']]);
                                $items = $stmt_items->fetchAll();
                                if (empty($items)) continue;
                                $items_str = array_map(function($i) { return $i['name'] . (!empty($i['variant']) ? ' (' . $i['variant'] . ')' : '') . ' x' . $i['quantity']; }, $items);
                            ?>
                            <tr style="box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px; font-weight: 600;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                <td style="padding: 1.2rem; background: white;"><strong>#<?php echo $o['id']; ?></strong></td>
                                <td style="padding: 1.2rem; background: white;"><strong><?php echo htmlspecialchars($o['user_name']); ?></strong></td>
                                <td style="padding: 1.2rem; background: white; word-break: break-all;"><?php echo htmlspecialchars($o['user_email']); ?></td>
                                <td style="padding: 1.2rem; background: white;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></td>
                                <td style="padding: 1.2rem; background: white; color: var(--primary); font-weight: 700;">$<?php echo number_format($o['total_price'], 2); ?></td>
                                <td style="padding: 1.2rem; background: white; max-width: 250px; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                    <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 5px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                        <?php echo strtoupper($o['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($yearly_orders)): ?>
                            <tr><td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo __("no_orders"); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

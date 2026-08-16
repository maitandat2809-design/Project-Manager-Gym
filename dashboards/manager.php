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
if ($page == 'memberships') {
    header("Location: /Gym Manager/dashboards/manager.php?page=member_subscriptions");
    exit();
}
$message = '';
$current_lang = $_SESSION['lang'] ?? 'vi';

// Handle Member Subscriptions Actions (Add / Update / Delete)
if ($page == 'member_subscriptions' && isset($_POST['add_subscription'])) {
    $user_id        = intval($_POST['user_id'] ?? 0);
    $plan_id        = intval($_POST['plan_id'] ?? 0);
    $start_date     = $_POST['start_date'] ?? date('Y-m-d');
    $end_date       = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $price_paid     = floatval($_POST['price_paid'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $status         = $_POST['status'] ?? 'active';
    $note           = trim($_POST['note'] ?? '');

    if ($user_id <= 0 || $plan_id <= 0) {
        $message = "Error: Please select a valid user and membership plan.";
    } else {
        try {
            if (isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] == '1') {
                $stmt_del_old = $pdo->prepare("DELETE FROM member_subscriptions WHERE user_id = ?");
                $stmt_del_old->execute([$user_id]);
            }

            $stmt_ins = $pdo->prepare("
                INSERT INTO member_subscriptions 
                (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, note, admin_seen) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt_ins->execute([$user_id, $plan_id, $start_date, $end_date, $price_paid, $payment_method, $status, $note]);
            $message = "Membership subscription created successfully!";
        } catch (Exception $e) {
            $message = "Error creating membership subscription: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && isset($_POST['update_subscription'])) {
    $sub_id         = intval($_POST['sub_id'] ?? 0);
    $plan_id        = intval($_POST['plan_id'] ?? 0);
    $start_date     = $_POST['start_date'];
    $end_date       = $_POST['end_date'];
    $price_paid     = floatval($_POST['price_paid'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $status         = $_POST['status'] ?? 'active';
    $note           = trim($_POST['note'] ?? '');

    if ($sub_id <= 0 || $plan_id <= 0) {
        $message = "Error: Invalid subscription or plan.";
    } else {
        try {
            $stmt_upd = $pdo->prepare("
                UPDATE member_subscriptions 
                SET plan_id = ?, start_date = ?, end_date = ?, price_paid = ?, payment_method = ?, status = ?, note = ?, admin_seen = 1 
                WHERE id = ?
            ");
            $stmt_upd->execute([$plan_id, $start_date, $end_date, $price_paid, $payment_method, $status, $note, $sub_id]);
            $message = "Membership subscription updated successfully!";
        } catch (Exception $e) {
            $message = "Error updating subscription: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && (isset($_GET['approve_sub']) || isset($_POST['do_approve_sub']))) {
    $sub_id = intval($_POST['sub_id'] ?? $_GET['approve_sub']);
    try {
        $stmt_app = $pdo->prepare("UPDATE member_subscriptions SET status = 'active', admin_seen = 1 WHERE id = ?");
        $stmt_app->execute([$sub_id]);
        $message = "🎉 Đã xác nhận kích hoạt thẻ thành viên #" . $sub_id . " thành công!";
    } catch (Exception $e) {
        $message = "Error confirming membership: " . $e->getMessage();
    }
}

if ($page == 'member_subscriptions' && (isset($_GET['reject_sub']) || isset($_POST['do_reject_sub']))) {
    $sub_id = intval($_POST['sub_id'] ?? $_GET['reject_sub']);
    $reason = trim($_POST['reject_reason'] ?? '');
    try {
        $stmt_rej = $pdo->prepare("UPDATE member_subscriptions SET status = 'cancelled', admin_seen = 1 WHERE id = ?");
        $stmt_rej->execute([$sub_id]);
        $message = "🔴 Đã từ chối / hủy yêu cầu đăng ký thẻ thành viên #" . $sub_id . "!" . ($reason ? " (Lý do: " . htmlspecialchars($reason) . ")" : "");
    } catch (Exception $e) {
        $message = "Error cancelling membership: " . $e->getMessage();
    }
}

if ($page == 'member_subscriptions' && isset($_GET['delete_sub'])) {
    $sub_id = intval($_GET['delete_sub']);
    try {
        $stmt_del = $pdo->prepare("DELETE FROM member_subscriptions WHERE id = ?");
        $stmt_del->execute([$sub_id]);
        $message = "Membership subscription deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting subscription: " . $e->getMessage();
    }
}

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

if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
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

// Order Management Actions for Manager
if ($page == 'orders' && isset($_GET['change_order_status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = $_GET['change_order_status'];
    
    if (in_array($new_status, ['pending', 'completed', 'cancelled'])) {
        try {
            $stmt_o = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt_o->execute([$order_id]);
            $old_status = $stmt_o->fetchColumn();
            
            if ($old_status) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_seen = 1 WHERE id = ?");
                $stmt->execute([$new_status, $order_id]);
                
                // If changing to cancelled from pending/completed, restore stock
                if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $o_items = $stmt_items->fetchAll();
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ?, sales_count = GREATEST(0, sales_count - ?) WHERE id = ?");
                    foreach ($o_items as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                    }
                }
                
                // If un-cancelling (cancelled -> completed/pending), deduct stock
                if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $o_items = $stmt_items->fetchAll();
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?), sales_count = sales_count + ? WHERE id = ?");
                    foreach ($o_items as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                    }
                }
                
                $pdo->commit();
                $message = "Order #$order_id status updated to " . strtoupper($new_status) . " successfully!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error updating order status: " . $e->getMessage();
        }
    }
}

if ($page == 'orders' && isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
        $pdo->commit();
        $message = "Order #$order_id deleted successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "Error deleting order: " . $e->getMessage();
    }
}

// Equipment Management Actions for Manager
if ($page == 'equipment' && isset($_POST['add_equipment'])) {
    $name = trim($_POST['name'] ?? '');
    $discipline = $_POST['discipline'] ?? 'gym';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $image_path = '/Gym Manager/assets/img/equipment/treadmills.png';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/equipment/';
        if (!is_dir($upload_dir_fs)) {
            mkdir($upload_dir_fs, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = 'eq_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir_fs . $file_name)) {
            $image_path = '/Gym Manager/assets/img/equipment/' . $file_name;
        }
    } elseif (!empty($_POST['custom_image_url'])) {
        $image_path = trim($_POST['custom_image_url']);
    }

    if (empty($name) || empty($description)) {
        $message = "Error: Equipment name and description are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO equipment (name, discipline, description, status, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $discipline, $description, $status, $image_path]);
            $message = "Equipment added successfully!";
        } catch (Exception $e) {
            $message = "Error adding equipment: " . $e->getMessage();
        }
    }
}

if ($page == 'equipment' && isset($_POST['update_equipment'])) {
    $eq_id = intval($_POST['equipment_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $discipline = $_POST['discipline'] ?? 'gym';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($eq_id <= 0 || empty($name) || empty($description)) {
        $message = "Error: Invalid equipment details.";
    } else {
        try {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_fs = __DIR__ . '/../assets/img/equipment/';
                if (!is_dir($upload_dir_fs)) {
                    mkdir($upload_dir_fs, 0777, true);
                }
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'eq_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir_fs . $file_name)) {
                    $new_image_path = '/Gym Manager/assets/img/equipment/' . $file_name;
                    $stmt = $pdo->prepare("UPDATE equipment SET name = ?, discipline = ?, description = ?, status = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $discipline, $description, $status, $new_image_path, $eq_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE equipment SET name = ?, discipline = ?, description = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $discipline, $description, $status, $eq_id]);
                }
            } elseif (!empty($_POST['custom_image_url'])) {
                $new_image_path = trim($_POST['custom_image_url']);
                $stmt = $pdo->prepare("UPDATE equipment SET name = ?, discipline = ?, description = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $discipline, $description, $status, $new_image_path, $eq_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE equipment SET name = ?, discipline = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $discipline, $description, $status, $eq_id]);
            }
            $message = "Equipment updated successfully!";
        } catch (Exception $e) {
            $message = "Error updating equipment: " . $e->getMessage();
        }
    }
}

if ($page == 'equipment' && isset($_GET['delete_equipment'])) {
    $eq_id = intval($_GET['delete_equipment']);
    try {
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->execute([$eq_id]);
        $message = "Equipment deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting equipment: " . $e->getMessage();
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
                <li><a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="<?php echo $page == 'member_subscriptions' ? 'active' : ''; ?>"><?php echo $current_lang === 'vi' ? 'Quản Lý Thẻ Thành Viên' : 'Member Subscriptions'; ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=products" class="<?php echo $page == 'products' ? 'active' : ''; ?>"><?php echo __("product_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=orders" class="<?php echo $page == 'orders' ? 'active' : ''; ?>"><?php echo __("manage_orders"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=bookings" class="<?php echo $page == 'bookings' ? 'active' : ''; ?>"><?php echo __("booking_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=equipment" class="<?php echo $page == 'equipment' ? 'active' : ''; ?>">Equipment Management</a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=yearly_report" class="<?php echo $page == 'yearly_report' ? 'active' : ''; ?>"><?php echo __("yearly_report"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=monthly_report" class="<?php echo $page == 'monthly_report' ? 'active' : ''; ?>"><?php echo __("monthly_report"); ?></a></li>
            </ul>
        </div>

        <div class="admin-content" style="min-width: 0;">
            <?php if($message): ?>
                <p style="color: <?php echo (stripos($message, 'Lỗi') !== false || stripos($message, 'Error') !== false) ? '#ff4444' : '#00C851'; ?>; margin-bottom: 1rem; font-weight: 600;"><?php echo $message; ?></p>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const msgText = <?php echo json_encode($message); ?>;
                        const lower = msgText.toLowerCase();
                        let title = <?php echo json_encode(__('notification')); ?>;
                        let icon = 'ℹ️';
                        if (lower.includes('lỗi') || lower.includes('error')) {
                            title = <?php echo json_encode(__('error_notification')); ?>;
                            icon = '❌';
                        } else if (lower.includes('completed') || lower.includes('thành công') || lower.includes('nhận hàng')) {
                            title = <?php echo json_encode(__('order_confirmed_success')); ?>;
                            icon = '✅';
                        } else if (lower.includes('cancelled') || lower.includes('hủy')) {
                            title = <?php echo json_encode(__('order_cancelled_success')); ?>;
                            icon = '🗑️';
                        }
                        showGymXAlert(msgText, title, icon);
                    });
                </script>
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
                                <img src="<?php echo get_image_url($edit_prod['image']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;">
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
                                <td><img src="<?php echo get_image_url($p['image']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"></td>
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

            <?php elseif ($page == 'orders'): 
                $stat_ord_total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                $stat_ord_pending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
                $stat_ord_completed = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
                $stat_ord_cancelled = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();

                $ord_search = trim($_GET['search'] ?? '');
                $ord_status = $_GET['status_filter'] ?? '';

                $ord_query = "SELECT o.*, u.name as user_name, u.email as user_email 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id";
                $ord_params = [];
                $ord_conds = [];

                if ($ord_search !== '') {
                    $ord_conds[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id = ? OR o.phone LIKE ?)";
                    $ord_params[] = "%$ord_search%";
                    $ord_params[] = "%$ord_search%";
                    $ord_params[] = intval($ord_search);
                    $ord_params[] = "%$ord_search%";
                }

                if ($ord_status !== '' && in_array($ord_status, ['pending', 'completed', 'cancelled'])) {
                    $ord_conds[] = "o.status = ?";
                    $ord_params[] = $ord_status;
                }

                if (!empty($ord_conds)) {
                    $ord_query .= " WHERE " . implode(" AND ", $ord_conds);
                }

                $ord_query .= " ORDER BY o.created_at DESC";
                $stmt_ord = $pdo->prepare($ord_query);
                $stmt_ord->execute($ord_params);
                $mgr_orders = $stmt_ord->fetchAll();
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("manage_orders"); ?></h2>
                </div>

                <!-- Orders Statistics -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Total Orders</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_ord_total; ?></p>
                    </div>
                    <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5;">
                        <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Pending (2-3 Days)</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_ord_pending; ?></p>
                    </div>
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7;">
                        <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Completed</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_ord_completed; ?></p>
                    </div>
                    <div style="background: #fef2f2; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #fee2e2;">
                        <h4 style="color: #991b1b; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Cancelled</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #dc2626; margin-top: 5px;"><?php echo $stat_ord_cancelled; ?></p>
                    </div>
                </div>

                <!-- Search & Filter Bar -->
                <div style="background: white; padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid var(--border); box-shadow: var(--shadow-premium);">
                    <form method="GET" action="/Gym Manager/dashboards/manager.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin: 0;">
                        <input type="hidden" name="page" value="orders">
                        <input type="text" name="search" placeholder="Search by customer, phone, or Order ID..." value="<?php echo htmlspecialchars($ord_search); ?>" style="flex: 1; min-width: 250px; padding: 0.8rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); outline: none; background: #f8fafc;">
                        <select name="status_filter" style="padding: 0.8rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); outline: none; background: #f8fafc; font-weight: 600;">
                            <option value="">-- All Statuses --</option>
                            <option value="pending" <?php echo $ord_status === 'pending' ? 'selected' : ''; ?>>Pending (Delivering 2-3 days)</option>
                            <option value="completed" <?php echo $ord_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $ord_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn-auth" style="padding: 0.8rem 1.8rem; font-size: 0.9rem; margin: 0; width: auto; border-radius: 12px;">Filter</button>
                        <?php if ($ord_search !== '' || $ord_status !== ''): ?>
                            <a href="/Gym Manager/dashboards/manager.php?page=orders" style="color: var(--text-muted); font-size: 0.9rem; text-decoration: underline; font-weight: 600;">Reset Filters</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive" style="background: white; border-radius: 25px; padding: 1.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 1.2rem; border-radius: 15px 0 0 15px; text-align: left;">Order ID</th>
                                <th style="padding: 1.2rem; text-align: left;">Date</th>
                                <th style="padding: 1.2rem; text-align: left;">Customer Info</th>
                                <th style="padding: 1.2rem; text-align: left;">Address / Phone</th>
                                <th style="padding: 1.2rem; text-align: left;">Total & Method</th>
                                <th style="padding: 1.2rem; text-align: center;">Status</th>
                                <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mgr_orders)): ?>
                                <tr><td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted); font-weight: 600;">No orders found matching criteria.</td></tr>
                            <?php else: ?>
                                <?php foreach ($mgr_orders as $o): 
                                    $stmt_o_items = $pdo->prepare("SELECT oi.*, p.name, p.name_vi FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $stmt_o_items->execute([$o['id']]);
                                    $o_items = $stmt_o_items->fetchAll();
                                ?>
                                    <tr style="box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: background 0.2s;">
                                        <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px; font-weight: 800; color: var(--primary);">
                                            #<?php echo $o['id']; ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white;">
                                            <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($o['user_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($o['user_email']); ?></div>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; font-size: 0.85rem;">
                                            <div><strong>📞 <?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></strong></div>
                                            <div style="color: var(--text-muted); margin-top: 3px; max-width: 200px; white-space: normal; line-height: 1.3;">📍 <?php echo htmlspecialchars($o['address'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td style="padding: 1.2rem; background: white;">
                                            <div style="font-size: 1.1rem; font-weight: 800; color: #10b981;">$<?php echo number_format($o['total_price'], 2); ?></div>
                                            <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 10px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">
                                                <?php echo strtoupper($o['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; text-align: center;">
                                            <?php if ($o['status'] === 'pending'): ?>
                                                <span style="background: #ffedd5; color: #c2410c; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.75rem; display: inline-block;">
                                                    🚚 Pending (2-3 days)
                                                </span>
                                            <?php elseif ($o['status'] === 'completed'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.75rem; display: inline-block;">
                                                    ✔ Completed
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.75rem; display: inline-block;">
                                                    ✖ Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                            <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                                <?php if ($o['status'] === 'pending'): ?>
                                                    <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=completed&order_id=<?php echo $o['id']; ?>" class="action-btn btn-add" style="font-size: 0.75rem; padding: 6px 12px;" onclick="return confirmLinkCustom(event, this, 'Xác nhận đơn hàng #<?php echo $o['id']; ?> đã giao hàng thành công?', 'Xác Nhận Đơn Hàng', '✅');">
                                                        <i class="fas fa-check"></i> Complete
                                                    </a>
                                                    <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=cancelled&order_id=<?php echo $o['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 12px;" onclick="return confirmLinkCustom(event, this, 'Hủy đơn hàng #<?php echo $o['id']; ?> và tự động hoàn trả hàng vào kho?', 'Hủy Đơn Hàng', '⚠️');">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </a>
                                                <?php elseif ($o['status'] === 'cancelled'): ?>
                                                    <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=pending&order_id=<?php echo $o['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 6px 12px;" onclick="return confirmLinkCustom(event, this, 'Khôi phục đơn hàng #<?php echo $o['id']; ?> về trạng thái Đang xử lý?', 'Khôi Phục Đơn Hàng', '🔄');">
                                                        <i class="fas fa-redo"></i> Restore
                                                    </a>
                                                <?php endif; ?>
                                                <a href="/Gym Manager/dashboards/manager.php?page=orders&delete_order=<?php echo $o['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px;" onclick="return confirmLinkCustom(event, this, 'Xóa vĩnh viễn dữ liệu đơn hàng #<?php echo $o['id']; ?>?', 'Xóa Đơn Hàng', '🗑️');" title="Delete record">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                <!-- html2canvas & jsPDF for Image and PDF Export -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2>Yearly Report - <?php echo htmlspecialchars($selected_year); ?></h2>
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-top: 5px;">
                            Total Revenue: <strong style="color: #10b981; font-size: 1.5rem;">$<?php echo number_format($total_yearly_revenue, 2); ?></strong>
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="downloadYearlyReportPDF('<?php echo $selected_year; ?>', event)" 
                                style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 0.8rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(16,185,129,0.3); transition: transform 0.2s;">
                            <i class="fas fa-file-pdf"></i> <?php echo $current_lang === 'vi' ? 'Tải Báo Cáo Năm (PDF)' : 'Download Yearly Report (PDF)'; ?>
                        </button>

                        <button type="button" onclick="downloadYearlyReportImage('<?php echo $selected_year; ?>')" 
                                style="background: #f1f5f9; color: var(--text-main); border: 1px solid var(--border); padding: 0.8rem 1.1rem; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="fas fa-file-image"></i> PNG
                        </button>

                        <form method="GET" style="display: flex; align-items: center; gap: 10px; margin: 0;">
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
                </div>

                <div id="yearly-report-export-area" style="background: white; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 2rem;">

                    <!-- Chart Section -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <div style="background: #fafafa; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 3rem;">
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
                            
                            <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date_time"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($c_bookings as $b): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                            <td style="padding: 0.75rem 1rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($b['user_name']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; word-break: break-all; max-width: 200px;"><?php echo htmlspecialchars($b['user_email']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; white-space: nowrap;"><?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem 1rem; text-align: center;">
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
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; border-radius: 12px; border: 1px solid var(--border);">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("order_id"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("total_price"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; min-width: 140px;"><?php echo __("items"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;" class="invoice-export-btn"><?php echo $current_lang === 'vi' ? 'Hóa Đơn' : 'Invoice'; ?></th>
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
                                <tr style="border-bottom: 1px solid #f1f5f9; background: white; transition: background 0.2s;">
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 800; color: var(--primary); white-space: nowrap;">#<?php echo $o['id']; ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($o['user_name']); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #475569; word-break: break-all; max-width: 160px; font-size: 0.8rem;"><?php echo htmlspecialchars($o['user_email']); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #475569; white-space: nowrap; font-size: 0.8rem;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #10b981; font-weight: 800; white-space: nowrap;">$<?php echo number_format($o['total_price'], 2); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #334155; font-size: 0.8rem; line-height: 1.35; max-width: 220px; word-wrap: break-word;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                            <?php echo strtoupper($o['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <button type="button" class="invoice-export-btn" onclick="openInvoiceModal(<?php echo $o['id']; ?>)" 
                                                style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); transition: all 0.2s;">
                                            <i class="fas fa-file-invoice" style="color: #60a5fa;"></i> <?php echo $current_lang === 'vi' ? 'Xuất Hóa Đơn' : 'Export Invoice'; ?>
                                        </button>

                                        <!-- Invoice Modal for Order #<?php echo $o['id']; ?> -->
                                        <div id="invoice-modal-<?php echo $o['id']; ?>" onclick="if(event.target===this) closeInvoiceModal(<?php echo $o['id']; ?>)" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 1.5rem; overflow-y: auto;">
                                            <div style="background: white; border-radius: 24px; width: 100%; max-width: 720px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; font-family: 'Outfit', sans-serif;">
                                                <!-- Action Control Bar -->
                                                <div style="background: #0f172a; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại' : 'Back'; ?>
                                                        </button>
                                                        <span style="font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                                            <i class="fas fa-receipt" style="color: #f59e0b;"></i> <?php echo $current_lang === 'vi' ? 'Hóa Đơn #' : 'Invoice #'; ?><?php echo $o['id']; ?>
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <button type="button" onclick="downloadInvoicePDF(<?php echo $o['id']; ?>)" 
                                                                style="background: #ef4444; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'png')" 
                                                                style="background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> PNG
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'jpg')" 
                                                                style="background: #3b82f6; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> JPG
                                                        </button>
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;" title="Close">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Printable/Exportable Invoice Card -->
                                                <div style="padding: 2rem; background: #f8fafc;">
                                                    <div id="invoice-card-<?php echo $o['id']; ?>" style="background: #ffffff; padding: 2.5rem; border-radius: 16px; border: 1px solid #e2e8f0; color: #0f172a; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;">
                                                        <!-- Header -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 1.5rem; border-bottom: 2px solid #0062ff;">
                                                            <div>
                                                                <h2 style="margin: 0; color: #0062ff; font-weight: 900; font-size: 1.6rem; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
                                                                    <i class="fas fa-bolt" style="color: #f59e0b;"></i> GYM MANAGER
                                                                </h2>
                                                                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                                                                    <?php echo $current_lang === 'vi' ? 'HÓA ĐƠN THANH TOÁN CHÍNH THỨC' : 'OFFICIAL SALES INVOICE'; ?>
                                                                </p>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8;">
                                                                    123 Fitness Ave, District 1, HCMC | Hotline: 1900 8888
                                                                </p>
                                                            </div>
                                                            <div style="text-align: right;">
                                                                <span style="background: #eff6ff; color: #0062ff; font-weight: 800; font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; border: 1px solid #bfdbfe; display: inline-block;">
                                                                    INV-#<?php echo sprintf('%05d', $o['id']); ?>
                                                                </span>
                                                                <p style="margin: 6px 0 0; font-size: 0.8rem; color: #64748b;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Ngày:' : 'Date:'; ?></strong> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Customer Info -->
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; background: #f8fafc; padding: 1.2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Billed To'; ?></h4>
                                                                <p style="margin: 0; font-weight: 800; font-size: 1rem; color: #0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['user_email']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Payment Status'; ?></h4>
                                                                <span style="background: #dcfce7; color: #166534; font-weight: 800; font-size: 0.8rem; padding: 4px 10px; border-radius: 12px; display: inline-block; margin-bottom: 6px;">
                                                                    ✔ <?php echo strtoupper($o['status']); ?>
                                                                </span>
                                                                <p style="margin: 0; font-size: 0.85rem; color: #475569;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?></strong> Gym Online Store
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Item Table -->
                                                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.88rem;">
                                                            <thead>
                                                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; background: #f1f5f9;">
                                                                    <th style="padding: 10px; border-radius: 8px 0 0 8px; color: #475569; font-weight: 700;">#</th>
                                                                    <th style="padding: 10px; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Sản Phẩm' : 'Product Item'; ?></th>
                                                                    <th style="padding: 10px; text-align: center; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'SL' : 'Qty'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Đơn Giá' : 'Unit Price'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; border-radius: 0 8px 8px 0; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Thành Tiền' : 'Total'; ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $idx = 1;
                                                                foreach ($items as $item): 
                                                                    $item_total = $item['price'] * $item['quantity'];
                                                                ?>
                                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                    <td style="padding: 10px; font-weight: 600; color: #64748b;"><?php echo $idx++; ?></td>
                                                                    <td style="padding: 10px; font-weight: 700; color: #0f172a;">
                                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 400;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td style="padding: 10px; text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                                                    <td style="padding: 10px; text-align: right; color: #475569;">$<?php echo number_format($item['price'], 2); ?></td>
                                                                    <td style="padding: 10px; text-align: right; font-weight: 700; color: #0062ff;">$<?php echo number_format($item_total, 2); ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                        <!-- Summary -->
                                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: white; padding: 1.2rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                                                            <div>
                                                                <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'TỔNG CỘNG THANH TOÁN' : 'GRAND TOTAL'; ?></span>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Đã bao gồm thuế & phí' : 'Taxes & fees included'; ?></p>
                                                            </div>
                                                            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;">
                                                                $<?php echo number_format($o['total_price'], 2); ?>
                                                            </h2>
                                                        </div>

                                                        <!-- Footer Stamp -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                                                            <div>
                                                                <div style="display: inline-flex; align-items: center; gap: 6px; border: 2px solid #16a34a; color: #16a34a; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; transform: rotate(-2deg);">
                                                                    <i class="fas fa-check-circle"></i> GYM MANAGER VERIFIED
                                                                </div>
                                                                <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                                                                    Thank you for your business!
                                                                </p>
                                                            </div>
                                                            <div style="text-align: center;">
                                                                <p style="margin: 0; font-size: 0.8rem; font-weight: 700; color: #475569;"><?php echo $current_lang === 'vi' ? 'Xác Nhận Quản Lý' : 'Authorized Signature'; ?></p>
                                                                <div style="height: 30px;"></div>
                                                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8;">(Signed Digitally)</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Back Button -->
                                                    <div style="margin-top: 1.5rem; text-align: center;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: #475569; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(71,85,105,0.25); transition: transform 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại danh sách' : 'Back to List'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($yearly_orders)): ?>
                                <tr><td colspan="9" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo __("no_orders"); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'monthly_report'): 
                $selected_month = intval($_GET['month'] ?? date('n'));
                $selected_year = intval($_GET['year'] ?? date('Y'));
                
                $month_name_vi = [1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'];
                $month_name_en = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
                $display_month_title = ($current_lang === 'vi' ? $month_name_vi[$selected_month] : $month_name_en[$selected_month]) . " " . $selected_year;

                $days_in_month = date('t', strtotime("$selected_year-$selected_month-01"));

                // Fetch bookings for the month
                $stmt_mb = $pdo->prepare("SELECT b.*, u.name as user_name, u.email as user_email, b.user_phone, c_u.name as coach_name 
                                          FROM bookings b 
                                          JOIN users u ON b.user_id = u.id 
                                          JOIN coaches c ON b.coach_id = c.id 
                                          JOIN users c_u ON c.user_id = c_u.id 
                                          WHERE MONTH(b.booking_date) = ? AND YEAR(b.booking_date) = ?
                                          ORDER BY b.booking_date DESC");
                $stmt_mb->execute([$selected_month, $selected_year]);
                $monthly_bookings = $stmt_mb->fetchAll();

                $monthly_bookings_by_coach = [];
                foreach ($monthly_bookings as $mb) {
                    $monthly_bookings_by_coach[$mb['coach_name']][] = $mb;
                }

                // Fetch orders for the month
                $stmt_mo = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                          FROM orders o 
                                          JOIN users u ON o.user_id = u.id 
                                          WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
                                          ORDER BY o.created_at DESC");
                $stmt_mo->execute([$selected_month, $selected_year]);
                $monthly_orders = $stmt_mo->fetchAll();

                // Fetch daily revenue for chart
                $stmt_daily = $pdo->prepare("SELECT DAY(o.created_at) as day_num, SUM(o.total_price) as daily_revenue 
                                              FROM orders o
                                              WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ? AND o.status = 'completed'
                                              AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)
                                              GROUP BY DAY(o.created_at)");
                $stmt_daily->execute([$selected_month, $selected_year]);
                $daily_data_raw = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

                $daily_revenue = array_fill(1, $days_in_month, 0);
                foreach($daily_data_raw as $row) {
                    $daily_revenue[(int)$row['day_num']] = (float)$row['daily_revenue'];
                }

                $total_monthly_revenue = array_sum($daily_revenue);
                $completed_orders_count = count(array_filter($monthly_orders, function($o) { return $o['status'] === 'completed'; }));
            ?>
                <!-- html2canvas & jsPDF for Image and PDF Export -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2><?php echo $current_lang === 'vi' ? 'Báo Cáo Tháng' : 'Monthly Report'; ?> - <?php echo htmlspecialchars($display_month_title); ?></h2>
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-top: 5px;">
                            <?php echo $current_lang === 'vi' ? 'Tổng Doanh Thu Tháng:' : 'Total Monthly Revenue:'; ?> <strong style="color: #10b981; font-size: 1.5rem;">$<?php echo number_format($total_monthly_revenue, 2); ?></strong>
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="downloadMonthlyReportPDF('<?php echo $selected_month; ?>', '<?php echo $selected_year; ?>', event)" 
                                style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 0.8rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(16,185,129,0.3); transition: transform 0.2s;">
                            <i class="fas fa-file-pdf"></i> <?php echo $current_lang === 'vi' ? 'Tải Báo Cáo Tháng (PDF)' : 'Download Monthly Report (PDF)'; ?>
                        </button>

                        <button type="button" onclick="downloadMonthlyReportImage('<?php echo $selected_month; ?>', '<?php echo $selected_year; ?>')" 
                                style="background: #f1f5f9; color: var(--text-main); border: 1px solid var(--border); padding: 0.8rem 1.1rem; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="fas fa-file-image"></i> PNG
                        </button>

                        <form method="GET" style="display: flex; align-items: center; gap: 8px; margin: 0; flex-wrap: wrap;">
                            <input type="hidden" name="page" value="monthly_report">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Tháng:' : 'Month:'; ?></label>
                            <select name="month" style="padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border); outline: none; font-weight: 600;">
                                <?php 
                                for ($m = 1; $m <= 12; $m++) {
                                    $m_label = $current_lang === 'vi' ? 'Tháng ' . $m : date('F', mktime(0,0,0,$m,1));
                                    echo "<option value='$m' " . ($selected_month == $m ? 'selected' : '') . ">$m_label</option>";
                                }
                                ?>
                            </select>

                            <label style="font-weight: 600;"><?php echo __("select_year"); ?></label>
                            <select name="year" style="padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border); outline: none; font-weight: 600;">
                                <?php 
                                $curr_yr = date('Y');
                                for ($y = $curr_yr; $y >= $curr_yr - 5; $y--) {
                                    echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.2rem; border-radius: 10px; font-weight: 700; cursor: pointer;">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </form>
                    </div>
                </div>

                <div id="monthly-report-export-area" style="background: white; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 2rem;">
                    
                    <!-- Top Summary Stats Grid -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
                        <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 1.5rem; border-radius: 16px; text-align: left; box-shadow: 0 10px 25px rgba(15,23,42,0.15);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'DOANH THU THÁNG' : 'MONTHLY REVENUE'; ?></span>
                                <i class="fas fa-wallet" style="color: #10b981; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;">$<?php echo number_format($total_monthly_revenue, 2); ?></h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $display_month_title; ?></p>
                        </div>

                        <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 1.5rem; border-radius: 16px; text-align: left; box-shadow: 0 10px 25px rgba(15,23,42,0.15);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'ĐƠN HÀNG THÀNH CÔNG' : 'COMPLETED ORDERS'; ?></span>
                                <i class="fas fa-shopping-bag" style="color: #60a5fa; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #60a5fa;"><?php echo $completed_orders_count; ?> / <?php echo count($monthly_orders); ?></h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Tổng đơn hàng sản phẩm' : 'Total store product orders'; ?></p>
                        </div>

                        <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 1.5rem; border-radius: 16px; text-align: left; box-shadow: 0 10px 25px rgba(15,23,42,0.15);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'BUỔI TẬP HLV (PT)' : 'COACH BOOKINGS'; ?></span>
                                <i class="fas fa-dumbbell" style="color: #f59e0b; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #f59e0b;"><?php echo count($monthly_bookings); ?></h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Tổng lịch hẹn huấn luyện' : 'Total training sessions'; ?></p>
                        </div>
                    </div>

                    <!-- Daily Chart Section -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <div style="background: #fafafa; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 3rem;">
                        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-chart-area" style="color: var(--primary);"></i>
                            <?php echo $current_lang === 'vi' ? 'Biểu Đồ Doanh Thu Theo Ngày' : 'Daily Revenue Growth Chart'; ?> (<?php echo $display_month_title; ?>)
                        </h3>
                        <div style="height: 350px; width: 100%;">
                            <canvas id="monthlyDailyChart"></canvas>
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('monthlyDailyChart');
                        if(canvas) {
                            const ctx = canvas.getContext('2d');
                            
                            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
                            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

                            const daysLabels = [];
                            for(let d = 1; d <= <?php echo $days_in_month; ?>; d++) {
                                daysLabels.push('Day ' + d);
                            }

                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: daysLabels,
                                    datasets: [{
                                        label: 'Daily Revenue ($)',
                                        data: <?php echo json_encode(array_values($daily_revenue)); ?>,
                                        backgroundColor: gradient,
                                        borderColor: '#10b981',
                                        borderWidth: 3,
                                        pointBackgroundColor: '#ffffff',
                                        pointBorderColor: '#10b981',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        fill: true,
                                        tension: 0.35
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: '#f1f5f9' },
                                            ticks: {
                                                callback: function(value) { return '$' + value; },
                                                font: { family: "'Outfit', sans-serif", weight: '600' }
                                            }
                                        },
                                        x: {
                                            grid: { display: false },
                                            ticks: { font: { family: "'Outfit', sans-serif" } }
                                        }
                                    }
                                }
                            });
                        }
                    });
                    </script>

                    <!-- Coach Performance Summary for Month -->
                    <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                        <i class="fas fa-users-cog" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Tổng Quan HLV Trong Tháng' : 'Coach Performance Summary'; ?> (<?php echo $display_month_title; ?>)
                    </h3>
                    
                    <?php if (empty($monthly_bookings_by_coach)): ?>
                        <p style="color: var(--text-muted); margin-bottom: 3rem;"><?php echo $current_lang === 'vi' ? 'Không có lịch đặt tập nào trong tháng này.' : 'No training bookings recorded for this month.'; ?></p>
                    <?php else: ?>
                        <div style="margin-bottom: 3rem;">
                        <?php foreach ($monthly_bookings_by_coach as $c_name => $c_bookings): ?>
                        <div style="background: white; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <div style="background: #f8fafc; padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                                <h4 style="margin: 0; color: var(--primary); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-user-tie"></i> Coach <?php echo htmlspecialchars($c_name); ?>
                                </h4>
                                <span style="background: var(--primary); color: white; padding: 4px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                                    Bookings: <?php echo count($c_bookings); ?>
                                </span>
                            </div>
                            
                            <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date_time"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($c_bookings as $b): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                            <td style="padding: 0.75rem 1rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($b['user_name']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; word-break: break-all; max-width: 200px;"><?php echo htmlspecialchars($b['user_email']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; white-space: nowrap;"><?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem 1rem; text-align: center;">
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

                    <!-- Products Purchased in Month Table -->
                    <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shopping-cart"></i> <?php echo $current_lang === 'vi' ? 'Sản Phẩm Đã Bán Trong Tháng' : 'Products Purchased in Month'; ?> (<?php echo $display_month_title; ?>)
                    </h3>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; border-radius: 12px; border: 1px solid var(--border);">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("order_id"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("total_price"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; min-width: 140px;"><?php echo __("items"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;" class="invoice-export-btn"><?php echo $current_lang === 'vi' ? 'Hóa Đơn' : 'Invoice'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($monthly_orders as $o): 
                                    $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $stmt_items->execute([$o['id']]);
                                    $items = $stmt_items->fetchAll();
                                    if (empty($items)) continue;
                                    $items_str = array_map(function($i) { return $i['name'] . (!empty($i['variant']) ? ' (' . $i['variant'] . ')' : '') . ' x' . $i['quantity']; }, $items);
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: white; transition: background 0.2s;">
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 800; color: var(--primary); white-space: nowrap;">#<?php echo $o['id']; ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($o['user_name']); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #475569; word-break: break-all; max-width: 160px; font-size: 0.8rem;"><?php echo htmlspecialchars($o['user_email']); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #475569; white-space: nowrap; font-size: 0.8rem;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #10b981; font-weight: 800; white-space: nowrap;">$<?php echo number_format($o['total_price'], 2); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; color: #334155; font-size: 0.8rem; line-height: 1.35; max-width: 220px; word-wrap: break-word;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                            <?php echo strtoupper($o['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <button type="button" class="invoice-export-btn" onclick="openInvoiceModal(<?php echo $o['id']; ?>)" 
                                                style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); transition: all 0.2s;">
                                            <i class="fas fa-file-invoice" style="color: #60a5fa;"></i> <?php echo $current_lang === 'vi' ? 'Xuất Hóa Đơn' : 'Export Invoice'; ?>
                                        </button>

                                        <!-- Invoice Modal for Order #<?php echo $o['id']; ?> -->
                                        <div id="invoice-modal-<?php echo $o['id']; ?>" onclick="if(event.target===this) closeInvoiceModal(<?php echo $o['id']; ?>)" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 1.5rem; overflow-y: auto;">
                                            <div style="background: white; border-radius: 24px; width: 100%; max-width: 720px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; font-family: 'Outfit', sans-serif;">
                                                <!-- Action Control Bar -->
                                                <div style="background: #0f172a; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại' : 'Back'; ?>
                                                        </button>
                                                        <span style="font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                                            <i class="fas fa-receipt" style="color: #f59e0b;"></i> <?php echo $current_lang === 'vi' ? 'Hóa Đơn #' : 'Invoice #'; ?><?php echo $o['id']; ?>
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <button type="button" onclick="downloadInvoicePDF(<?php echo $o['id']; ?>)" 
                                                                style="background: #ef4444; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'png')" 
                                                                style="background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> PNG
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'jpg')" 
                                                                style="background: #3b82f6; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> JPG
                                                        </button>
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;" title="Close">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Printable/Exportable Invoice Card -->
                                                <div style="padding: 2rem; background: #f8fafc;">
                                                    <div id="invoice-card-<?php echo $o['id']; ?>" style="background: #ffffff; padding: 2.5rem; border-radius: 16px; border: 1px solid #e2e8f0; color: #0f172a; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;">
                                                        <!-- Header -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 1.5rem; border-bottom: 2px solid #0062ff;">
                                                            <div>
                                                                <h2 style="margin: 0; color: #0062ff; font-weight: 900; font-size: 1.6rem; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
                                                                    <i class="fas fa-bolt" style="color: #f59e0b;"></i> GYM MANAGER
                                                                </h2>
                                                                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                                                                    <?php echo $current_lang === 'vi' ? 'HÓA ĐƠN THANH TOÁN CHÍNH THỨC' : 'OFFICIAL SALES INVOICE'; ?>
                                                                </p>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8;">
                                                                    123 Fitness Ave, District 1, HCMC | Hotline: 1900 8888
                                                                </p>
                                                            </div>
                                                            <div style="text-align: right;">
                                                                <span style="background: #eff6ff; color: #0062ff; font-weight: 800; font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; border: 1px solid #bfdbfe; display: inline-block;">
                                                                    INV-#<?php echo sprintf('%05d', $o['id']); ?>
                                                                </span>
                                                                <p style="margin: 6px 0 0; font-size: 0.8rem; color: #64748b;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Ngày:' : 'Date:'; ?></strong> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Customer Info -->
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; background: #f8fafc; padding: 1.2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Billed To'; ?></h4>
                                                                <p style="margin: 0; font-weight: 800; font-size: 1rem; color: #0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['user_email']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Payment Status'; ?></h4>
                                                                <span style="background: #dcfce7; color: #166534; font-weight: 800; font-size: 0.8rem; padding: 4px 10px; border-radius: 12px; display: inline-block; margin-bottom: 6px;">
                                                                    ✔ <?php echo strtoupper($o['status']); ?>
                                                                </span>
                                                                <p style="margin: 0; font-size: 0.85rem; color: #475569;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?></strong> Gym Online Store
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Item Table -->
                                                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.88rem;">
                                                            <thead>
                                                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; background: #f1f5f9;">
                                                                    <th style="padding: 10px; border-radius: 8px 0 0 8px; color: #475569; font-weight: 700;">#</th>
                                                                    <th style="padding: 10px; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Sản Phẩm' : 'Product Item'; ?></th>
                                                                    <th style="padding: 10px; text-align: center; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'SL' : 'Qty'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Đơn Giá' : 'Unit Price'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; border-radius: 0 8px 8px 0; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Thành Tiền' : 'Total'; ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $idx = 1;
                                                                foreach ($items as $item): 
                                                                    $item_total = $item['price'] * $item['quantity'];
                                                                ?>
                                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                    <td style="padding: 10px; font-weight: 600; color: #64748b;"><?php echo $idx++; ?></td>
                                                                    <td style="padding: 10px; font-weight: 700; color: #0f172a;">
                                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 400;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td style="padding: 10px; text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                                                    <td style="padding: 10px; text-align: right; color: #475569;">$<?php echo number_format($item['price'], 2); ?></td>
                                                                    <td style="padding: 10px; text-align: right; font-weight: 700; color: #0062ff;">$<?php echo number_format($item_total, 2); ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                        <!-- Summary -->
                                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: white; padding: 1.2rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                                                            <div>
                                                                <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'TỔNG CỘNG THANH TOÁN' : 'GRAND TOTAL'; ?></span>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Đã bao gồm thuế & phí' : 'Taxes & fees included'; ?></p>
                                                            </div>
                                                            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;">
                                                                $<?php echo number_format($o['total_price'], 2); ?>
                                                            </h2>
                                                        </div>

                                                        <!-- Footer Stamp -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                                                            <div>
                                                                <div style="display: inline-flex; align-items: center; gap: 6px; border: 2px solid #16a34a; color: #16a34a; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; transform: rotate(-2deg);">
                                                                    <i class="fas fa-check-circle"></i> GYM MANAGER VERIFIED
                                                                </div>
                                                                <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                                                                    Thank you for your business!
                                                                </p>
                                                            </div>
                                                            <div style="text-align: center;">
                                                                <p style="margin: 0; font-size: 0.8rem; font-weight: 700; color: #475569;"><?php echo $current_lang === 'vi' ? 'Xác Nhận Quản Lý' : 'Authorized Signature'; ?></p>
                                                                <div style="height: 30px;"></div>
                                                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8;">(Signed Digitally)</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Back Button -->
                                                    <div style="margin-top: 1.5rem; text-align: center;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: #475569; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(71,85,105,0.25); transition: transform 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại danh sách' : 'Back to List'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($monthly_orders)): ?>
                                <tr><td colspan="9" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo $current_lang === 'vi' ? 'Không có đơn hàng nào trong tháng này.' : 'No orders found for this month.'; ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                function openInvoiceModal(id) {
                    const modal = document.getElementById('invoice-modal-' + id);
                    if (modal) modal.style.display = 'flex';
                }
                function closeInvoiceModal(id) {
                    const modal = document.getElementById('invoice-modal-' + id);
                    if (modal) modal.style.display = 'none';
                }
                function downloadInvoicePDF(id) {
                    const card = document.getElementById('invoice-card-' + id);
                    if (!card) return;
                    
                    html2canvas(card, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff'
                    }).then(canvas => {
                        const { jsPDF } = window.jspdf;
                        const imgData = canvas.toDataURL('image/jpeg', 0.98);
                        const imgWidth = 210;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        const pdf = new jsPDF('p', 'mm', [imgWidth, imgHeight]);
                        pdf.addImage(imgData, 'JPEG', 0, 0, imgWidth, imgHeight);
                        pdf.save(`Invoice_#${id}_GymManager.pdf`);
                    }).catch(err => {
                        console.error("PDF Invoice export error:", err);
                        alert("Could not export invoice PDF.");
                    });
                }
                function downloadInvoiceImage(id, format) {
                    const card = document.getElementById('invoice-card-' + id);
                    if (!card) return;
                    
                    html2canvas(card, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff'
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `Invoice_#${id}_GymManager.${format}`;
                        link.href = canvas.toDataURL(`image/${format === 'jpg' ? 'jpeg' : 'png'}`, 0.98);
                        link.click();
                    }).catch(err => {
                        console.error("Canvas export error:", err);
                        alert("Could not export invoice image.");
                    });
                }
                function captureElementFull(element) {
                    return new Promise((resolve, reject) => {
                        const clone = element.cloneNode(true);
                        
                        clone.style.width = '1450px';
                        clone.style.maxWidth = 'none';
                        clone.style.position = 'absolute';
                        clone.style.left = '-9999px';
                        clone.style.top = '0';
                        clone.style.zIndex = '-9999';
                        clone.style.background = '#ffffff';

                        // Copy canvas content (Chart.js)
                        const origCanvases = element.querySelectorAll('canvas');
                        const cloneCanvases = clone.querySelectorAll('canvas');
                        origCanvases.forEach((origCanvas, i) => {
                            if (cloneCanvases[i]) {
                                const destCtx = cloneCanvases[i].getContext('2d');
                                cloneCanvases[i].width = origCanvas.width;
                                cloneCanvases[i].height = origCanvas.height;
                                destCtx.drawImage(origCanvas, 0, 0);
                            }
                        });

                        // Make all table containers 100% visible and unconstrained
                        const scrollables = clone.querySelectorAll('.table-responsive, table, div');
                        scrollables.forEach(el => {
                            el.style.overflow = 'visible';
                            el.style.overflowX = 'visible';
                            el.style.maxWidth = 'none';
                        });

                        // Hide action buttons in clone for clean report
                        const actionBtns = clone.querySelectorAll('.invoice-export-btn, button');
                        actionBtns.forEach(btn => btn.style.display = 'none');

                        document.body.appendChild(clone);

                        setTimeout(() => {
                            html2canvas(clone, {
                                scale: 2,
                                useCORS: true,
                                backgroundColor: '#ffffff',
                                width: 1450,
                                windowWidth: 1450
                            }).then(canvas => {
                                document.body.removeChild(clone);
                                resolve(canvas);
                            }).catch(err => {
                                if (document.body.contains(clone)) document.body.removeChild(clone);
                                reject(err);
                            });
                        }, 100);
                    });
                }

                function downloadYearlyReportPDF(year, evt) {
                    const reportArea = document.getElementById('yearly-report-export-area');
                    if (!reportArea) return;
                    
                    let btn = evt ? evt.currentTarget : null;
                    let originalText = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                        btn.disabled = true;
                    }
                    
                    captureElementFull(reportArea).then(canvas => {
                        const { jsPDF } = window.jspdf;
                        const imgData = canvas.toDataURL('image/jpeg', 0.95);
                        
                        const pdf = new jsPDF({
                            orientation: 'landscape',
                            unit: 'mm',
                            format: 'a4'
                        });

                        const imgWidth = 297;
                        const pageHeight = 210;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        let heightLeft = imgHeight;
                        let position = 0;

                        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;

                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pageHeight;
                        }

                        pdf.save(`GymManager_Yearly_Report_${year}.pdf`);

                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    }).catch(err => {
                        console.error("Report PDF export error:", err);
                        alert("Could not export report PDF.");
                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    });
                }
                function downloadYearlyReportImage(year) {
                    const reportArea = document.getElementById('yearly-report-export-area');
                    if (!reportArea) return;
                    
                    captureElementFull(reportArea).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `GymManager_Yearly_Report_${year}.png`;
                        link.href = canvas.toDataURL('image/png', 0.98);
                        link.click();
                    }).catch(err => {
                        console.error("Report export error:", err);
                        alert("Could not export report image.");
                    });
                }
                function downloadMonthlyReportPDF(month, year, evt) {
                    const reportArea = document.getElementById('monthly-report-export-area');
                    if (!reportArea) return;
                    
                    let btn = evt ? evt.currentTarget : null;
                    let originalText = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                        btn.disabled = true;
                    }
                    
                    captureElementFull(reportArea).then(canvas => {
                        const { jsPDF } = window.jspdf;
                        const imgData = canvas.toDataURL('image/jpeg', 0.95);
                        
                        const pdf = new jsPDF({
                            orientation: 'landscape',
                            unit: 'mm',
                            format: 'a4'
                        });

                        const imgWidth = 297;
                        const pageHeight = 210;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        let heightLeft = imgHeight;
                        let position = 0;

                        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;

                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pageHeight;
                        }

                        pdf.save(`GymManager_Monthly_Report_${month}_${year}.pdf`);

                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    }).catch(err => {
                        console.error("Report PDF export error:", err);
                        alert("Could not export report PDF.");
                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    });
                }
                function downloadMonthlyReportImage(month, year) {
                    const reportArea = document.getElementById('monthly-report-export-area');
                    if (!reportArea) return;
                    
                    captureElementFull(reportArea).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `GymManager_Monthly_Report_${month}_${year}.png`;
                        link.href = canvas.toDataURL('image/png', 0.98);
                        link.click();
                    }).catch(err => {
                        console.error("Report export error:", err);
                        alert("Could not export report image.");
                    });
                }
                </script>

            <?php elseif ($page == 'member_subscriptions'): 
                $edit_sub = null;
                if (isset($_GET['edit_sub'])) {
                    $stmt_es = $pdo->prepare("SELECT ms.*, u.name as user_name, u.email as user_email FROM member_subscriptions ms JOIN users u ON ms.user_id = u.id WHERE ms.id = ?");
                    $stmt_es->execute([$_GET['edit_sub']]);
                    $edit_sub = $stmt_es->fetch();
                }

                $all_users = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
                $all_plans = $pdo->query("SELECT id, name, name_vi, price, duration_days, badge FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
                $all_subs  = $pdo->query("
                    SELECT ms.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, mp.name as plan_name, mp.name_vi as plan_name_vi, mp.badge, mp.badge_color 
                    FROM member_subscriptions ms 
                    JOIN users u ON ms.user_id = u.id 
                    JOIN membership_plans mp ON ms.plan_id = mp.id 
                    ORDER BY ms.id DESC
                ")->fetchAll();
                $confirm_sub = null;
                $confirm_action = $_GET['confirm_action'] ?? null;
                if (isset($_GET['sub_id']) && in_array($confirm_action, ['approve', 'reject'])) {
                    $stmt_cs = $pdo->prepare("
                        SELECT ms.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, u.phone as user_phone, 
                               mp.name as plan_name, mp.name_vi as plan_name_vi, mp.price as plan_price, mp.duration_days 
                        FROM member_subscriptions ms 
                        JOIN users u ON ms.user_id = u.id 
                        JOIN membership_plans mp ON ms.plan_id = mp.id 
                        WHERE ms.id = ?
                    ");
                    $stmt_cs->execute([$_GET['sub_id']]);
                    $confirm_sub = $stmt_cs->fetch();
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo $current_lang === 'vi' ? 'Quản Lý Thẻ Thành Viên Khách Hàng' : 'Manage Customer Memberships'; ?></h2>
                </div>

                <?php if ($confirm_sub && $confirm_action === 'approve'): ?>
                <!-- TRANG XÁC NHẬN KÍCH HOẠT THẺ -->
                <div style="background: white; border-radius: 25px; padding: 2.5rem; margin: 2rem 0; border: 2px solid #16a34a; box-shadow: 0 15px 40px rgba(22,163,74,0.15); animation: slideUp 0.3s ease-out;">
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom: 1.5rem;">
                        <div style="width: 52px; height: 52px; background: #dcfce7; color: #16a34a; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 12px rgba(22,163,74,0.2);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; color:#16a34a; font-size:1.4rem; font-weight:800;">
                                <?php echo $current_lang === 'vi' ? 'TRANG XÁC NHẬN KÍCH HOẠT THẺ THÀNH VIÊN #' . $confirm_sub['id'] : 'CONFIRM MEMBERSHIP ACTIVATION #' . $confirm_sub['id']; ?>
                            </h3>
                            <p style="margin: 4px 0 0; color: var(--text-muted); font-size: 0.9rem;">Vui lòng kiểm tra thông tin hội viên trước khi xác nhận kích hoạt thẻ.</p>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-radius: 18px; padding: 1.5rem; margin-bottom: 1.8rem; border: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <h4 style="color: var(--text-main); margin: 0 0 10px; font-size: 1rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">👤 Thông tin Hội viên:</h4>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Họ và tên:</strong> <?php echo htmlspecialchars($confirm_sub['user_name']); ?></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Email:</strong> <?php echo htmlspecialchars($confirm_sub['user_email']); ?></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>SĐT:</strong> <?php echo htmlspecialchars($confirm_sub['user_phone'] ?? 'Chưa cập nhật'); ?></p>
                        </div>
                        <div>
                            <h4 style="color: var(--text-main); margin: 0 0 10px; font-size: 1rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">💳 Thông tin Thẻ & Gói đăng ký:</h4>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Gói tập:</strong> <span style="color:#2563eb;font-weight:700;"><?php echo htmlspecialchars(($current_lang === 'vi' && !empty($confirm_sub['plan_name_vi'])) ? $confirm_sub['plan_name_vi'] : $confirm_sub['plan_name']); ?></span></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Số tiền:</strong> <span style="color:#ef4444;font-weight:800;"><?php echo number_format($confirm_sub['price_paid'], 0, ',', '.'); ?>đ</span></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Thời hạn thẻ:</strong> <?php echo date('d/m/Y', strtotime($confirm_sub['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($confirm_sub['end_date'])); ?> (<?php echo $confirm_sub['duration_days']; ?> ngày)</p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Phương thức thanh toán:</strong> <?php echo strtoupper($confirm_sub['payment_method']); ?></p>
                            <?php if (!empty($confirm_sub['note'])): ?>
                                <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Ghi chú:</strong> <em><?php echo htmlspecialchars($confirm_sub['note']); ?></em></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="sub_id" value="<?php echo $confirm_sub['id']; ?>">
                        <button type="submit" name="do_approve_sub" class="btn-auth" style="margin: 0; width: auto; padding: 1.1rem 2.2rem; background: linear-gradient(135deg, #16a34a, #15803d); box-shadow: 0 8px 20px rgba(22,163,74,0.3);">
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                            <?php echo $current_lang === 'vi' ? 'XÁC NHẬN KÍCH HOẠT THẺ THÀNH VIÊN' : 'CONFIRM & ACTIVATE MEMBERSHIP'; ?>
                        </button>
                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.8rem; height: auto; text-decoration: none; font-weight: 700;">
                            <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Quay Lại Danh Sách
                        </a>
                    </form>
                </div>

                <?php elseif ($confirm_sub && $confirm_action === 'reject'): ?>
                <!-- TRANG XÁC NHẬN TỪ CHỐI THẺ -->
                <div style="background: white; border-radius: 25px; padding: 2.5rem; margin: 2rem 0; border: 2px solid #dc2626; box-shadow: 0 15px 40px rgba(220,38,38,0.15); animation: slideUp 0.3s ease-out;">
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom: 1.5rem;">
                        <div style="width: 52px; height: 52px; background: #fee2e2; color: #dc2626; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 12px rgba(220,38,38,0.2);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; color:#dc2626; font-size:1.4rem; font-weight:800;">
                                <?php echo $current_lang === 'vi' ? 'TRANG XÁC NHẬN TỪ CHỐI / HỦY ĐĂNG KÝ THẺ #' . $confirm_sub['id'] : 'CONFIRM MEMBERSHIP REJECTION #' . $confirm_sub['id']; ?>
                            </h3>
                            <p style="margin: 4px 0 0; color: var(--text-muted); font-size: 0.9rem;">Bạn đang thực hiện từ chối yêu cầu đăng ký thẻ thành viên này.</p>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-radius: 18px; padding: 1.5rem; margin-bottom: 1.8rem; border: 1px solid #e2e8f0; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <h4 style="color: var(--text-main); margin: 0 0 10px; font-size: 1rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">👤 Thông tin Hội viên:</h4>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Họ và tên:</strong> <?php echo htmlspecialchars($confirm_sub['user_name']); ?></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Email:</strong> <?php echo htmlspecialchars($confirm_sub['user_email']); ?></p>
                        </div>
                        <div>
                            <h4 style="color: var(--text-main); margin: 0 0 10px; font-size: 1rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">💳 Gói Đăng Ký:</h4>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Gói tập:</strong> <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($confirm_sub['plan_name_vi'])) ? $confirm_sub['plan_name_vi'] : $confirm_sub['plan_name']); ?></p>
                            <p style="margin: 6px 0; font-size: 0.92rem;"><strong>Số tiền:</strong> <?php echo number_format($confirm_sub['price_paid'], 0, ',', '.'); ?>đ</p>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="sub_id" value="<?php echo $confirm_sub['id']; ?>">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 8px;">
                                📝 Lý do từ chối / hủy (Ghi chú hệ thống):
                            </label>
                            <textarea name="reject_reason" rows="2" placeholder="Ví dụ: Chưa nhận được thanh toán tại quầy, thông tin không chính xác..." style="width:100%; padding: 1rem; border-radius: 12px; border: 1.5px solid #e2e8f0; outline: none; font-size: 0.9rem; font-family: inherit;"></textarea>
                        </div>

                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <button type="submit" name="do_reject_sub" class="btn-auth" style="margin: 0; width: auto; padding: 1.1rem 2.2rem; background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 8px 20px rgba(220,38,38,0.3);">
                                <i class="fas fa-ban" style="margin-right: 8px;"></i>
                                <?php echo $current_lang === 'vi' ? 'XÁC NHẬN TỪ CHỐI ĐĂNG KÝ' : 'CONFIRM REJECTION'; ?>
                            </button>
                            <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.8rem; height: auto; text-decoration: none; font-weight: 700;">
                                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Quay Lại Danh Sách
                            </a>
                        </div>
                    </form>
                </div>

                <?php elseif ($edit_sub): ?>
                <!-- Edit Subscription Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="sub_id" value="<?php echo $edit_sub['id']; ?>">
                    <h3 style="margin-bottom:1.5rem;color:var(--primary);font-size:1.2rem;font-weight:800;">
                        ✏️ <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa Thẻ Thành Viên #' . $edit_sub['id'] : 'Edit Membership #' . $edit_sub['id']; ?> 
                        (<?php echo htmlspecialchars($edit_sub['user_name']); ?>)
                    </h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Gói Thành Viên' : 'Membership Plan'; ?></label>
                            <select name="plan_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $edit_sub['plan_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> 
                                        (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ - <?php echo $p['duration_days']; ?> ngày)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" value="<?php echo $edit_sub['start_date']; ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" value="<?php echo $edit_sub['end_date']; ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán (VNĐ)' : 'Price Paid (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price_paid" value="<?php echo $edit_sub['price_paid']; ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Phương Thức Thanh Toán' : 'Payment Method'; ?></label>
                            <select name="payment_method" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="cod" <?php echo $edit_sub['payment_method'] == 'cod' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Thanh toán tại quầy (COD)' : 'Pay at counter (COD)'; ?></option>
                                <option value="cash" <?php echo $edit_sub['payment_method'] == 'cash' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Tiền mặt' : 'Cash'; ?></option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái Thẻ' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" <?php echo $edit_sub['status'] == 'active' ? 'selected' : ''; ?>>🟢 <?php echo $current_lang === 'vi' ? 'Đang hoạt động (Kích hoạt ngay)' : 'Active (Instant activation)'; ?></option>
                                <option value="pending" <?php echo $edit_sub['status'] == 'pending' ? 'selected' : ''; ?>>🟡 <?php echo $current_lang === 'vi' ? 'Chờ thanh toán (Chờ xác nhận)' : 'Pending (Awaiting payment)'; ?></option>
                                <option value="expired" <?php echo $edit_sub['status'] == 'expired' ? 'selected' : ''; ?>>🔴 <?php echo $current_lang === 'vi' ? 'Đã hết hạn' : 'Expired'; ?></option>
                                <option value="cancelled" <?php echo $edit_sub['status'] == 'cancelled' ? 'selected' : ''; ?>>⚪ <?php echo $current_lang === 'vi' ? 'Đã hủy' : 'Cancelled'; ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ghi Chú Admin/Manager' : 'Notes'; ?></label>
                        <input type="text" name="note" value="<?php echo htmlspecialchars($edit_sub['note'] ?? ''); ?>" placeholder="<?php echo $current_lang === 'vi' ? 'Ghi chú thêm...' : 'Additional notes...'; ?>">
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_subscription" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo $current_lang === 'vi' ? 'Lưu Thay Đổi' : 'Save Changes'; ?></button>
                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; text-decoration: none; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Hủy Bỏ' : 'Cancel'; ?></a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Subscription Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom:1.5rem;color:var(--primary);font-size:1.2rem;font-weight:800;">
                        ➕ <?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên Cho Khách Hàng' : 'Create Membership for Customer'; ?>
                    </h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Chọn Tài Khoản Khách Hàng' : 'Select Customer Account'; ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value=""><?php echo $current_lang === 'vi' ? '-- Chọn tài khoản --' : '-- Select Account --'; ?></option>
                                <?php foreach($all_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Chọn Gói Thành Viên' : 'Select Membership Plan'; ?></label>
                            <select name="plan_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value=""><?php echo $current_lang === 'vi' ? '-- Chọn gói --' : '-- Select Plan --'; ?></option>
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> 
                                        (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ - <?php echo $p['duration_days']; ?> ngày)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán' : 'Price Paid'; ?></label>
                            <input type="number" step="1000" name="price_paid" placeholder="500000" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active">🟢 <?php echo $current_lang === 'vi' ? 'Đang hoạt động (Kích hoạt ngay)' : 'Active (Instant activation)'; ?></option>
                                <option value="pending">🟡 <?php echo $current_lang === 'vi' ? 'Chờ thanh toán (Chờ xác nhận)' : 'Pending (Awaiting payment)'; ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="overwrite_existing" value="1" id="add_overwrite" checked style="width: 20px; height: 20px;">
                        <label for="add_overwrite" style="font-weight: 600; cursor: pointer; color: #ef4444;">
                            <?php echo $current_lang === 'vi' ? 'Xóa/Thay thế thẻ thành viên cũ của khách hàng nếu đã có' : 'Overwrite previous membership if user already has one'; ?>
                        </label>
                    </div>

                    <button type="submit" name="add_subscription" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">
                        <i class="fas fa-plus-circle" style="margin-right:8px;"></i>
                        <?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên' : 'Create Membership'; ?>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Subscriptions List Table -->
                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Customer'; ?></th>
                                <th><?php echo $current_lang === 'vi' ? 'Gói Đăng Ký' : 'Plan'; ?></th>
                                <th><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></th>
                                <th><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></th>
                                <th><?php echo $current_lang === 'vi' ? 'Số Tiền' : 'Amount'; ?></th>
                                <th><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?></th>
                                <th style="text-align: center;"><?php echo $current_lang === 'vi' ? 'Hành Động' : 'Actions'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_subs as $s): 
                                $p_name = ($current_lang === 'vi' && !empty($s['plan_name_vi'])) ? $s['plan_name_vi'] : $s['plan_name'];
                                $b_bg   = !empty($s['badge_color']) ? $s['badge_color'] : '#2563eb';
                                $b_txt  = !empty($s['badge']) ? $s['badge'] : mb_strtoupper($p_name);
                            ?>
                            <tr style="box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px; font-weight: 600;">#<?php echo $s['id']; ?></td>
                                <td style="padding: 1.2rem; background: white;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <img src="<?php echo $s['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($s['user_name']).'&background=0062ff&color=fff&size=40'; ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['user_name']); ?></strong><br>
                                            <span style="font-size:0.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($s['user_email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1.2rem; background: white;">
                                    <strong><?php echo htmlspecialchars($p_name); ?></strong><br>
                                    <span style="background:<?php echo $b_bg; ?>;color:#fff;font-size:0.65rem;font-weight:800;padding:2px 8px;border-radius:6px;text-transform:uppercase;">
                                        <?php echo htmlspecialchars($b_txt); ?>
                                    </span>
                                </td>
                                <td style="padding: 1.2rem; background: white; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($s['start_date'])); ?></td>
                                <td style="padding: 1.2rem; background: white; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($s['end_date'])); ?></td>
                                <td style="padding: 1.2rem; background: white; color: var(--primary); font-weight: 700; white-space: nowrap;"><?php echo number_format($s['price_paid'], 0, ',', '.'); ?>đ</td>
                                <td style="padding: 1.2rem; background: white;">
                                    <?php
                                    $st_map = [
                                        'active' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'label' => '🟢 ACTIVE'],
                                        'pending' => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => '🟡 PENDING'],
                                        'expired' => ['bg' => '#fee2e2', 'color' => '#ef4444', 'label' => '🔴 EXPIRED'],
                                        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => '⚪ CANCELLED'],
                                    ];
                                    $st = $st_map[$s['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => strtoupper($s['status'])];
                                    ?>
                                    <span style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;padding:5px 12px;border-radius:20px;font-weight:800;font-size:0.72rem;display:inline-block;">
                                        <?php echo $st['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center; white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <?php if ($s['status'] === 'pending'): ?>
                                            <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=approve&sub_id=<?php echo $s['id']; ?>" class="action-btn" style="background:#dcfce7;color:#16a34a;border:1px solid #86efac;" title="Trang Xác Nhận Kích Hoạt">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&confirm_action=reject&sub_id=<?php echo $s['id']; ?>" class="action-btn" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;" title="Trang Từ Chối / Hủy Thẻ">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&edit_sub=<?php echo $s['id']; ?>" class="action-btn btn-edit" title="Sửa Thẻ">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&delete_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" onclick="return confirm('<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn XÓA thẻ thành viên này của khách hàng?' : 'Are you sure you want to DELETE this membership?'; ?>');" title="Xóa Thẻ">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_subs)): ?>
                            <tr><td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo $current_lang === 'vi' ? 'Chưa có thẻ thành viên nào.' : 'No membership subscriptions found.'; ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'equipment'): 
                $edit_eq = null;
                if (isset($_GET['edit_equipment'])) {
                    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
                    $stmt->execute([$_GET['edit_equipment']]);
                    $edit_eq = $stmt->fetch();
                }

                $all_equipment = $pdo->query("SELECT * FROM equipment ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                $total_eq = count($all_equipment);
                $active_eq = count(array_filter($all_equipment, function($e) { return $e['status'] === 'active'; }));
                $maint_eq = count(array_filter($all_equipment, function($e) { return $e['status'] === 'maintenance'; }));
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2>Gym Equipment Management</h2>
                </div>

                <!-- Stats summary cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div style="background: white; padding: 1.5rem; border-radius: 18px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); text-align: center;">
                        <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.5rem;">Total Equipment</h4>
                        <span style="font-size: 2.2rem; font-weight: 800; color: var(--primary);"><?php echo $total_eq; ?></span>
                    </div>
                    <div style="background: white; padding: 1.5rem; border-radius: 18px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); text-align: center;">
                        <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.5rem;">Active Status</h4>
                        <span style="font-size: 2.2rem; font-weight: 800; color: #16a34a;"><?php echo $active_eq; ?></span>
                    </div>
                    <div style="background: white; padding: 1.5rem; border-radius: 18px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); text-align: center;">
                        <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 0.5rem;">Under Maintenance</h4>
                        <span style="font-size: 2.2rem; font-weight: 800; color: #d97706;"><?php echo $maint_eq; ?></span>
                    </div>
                </div>

                <?php if ($edit_eq): ?>
                <!-- Edit Equipment Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin-bottom: 2.5rem; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="equipment_id" value="<?php echo $edit_eq['id']; ?>">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><i class="fas fa-edit"></i> Edit Equipment Details</h3>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Equipment Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_eq['name']); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Discipline</label>
                            <select name="discipline" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="gym" <?php echo $edit_eq['discipline'] == 'gym' ? 'selected' : ''; ?>>🏋️‍♂️ Gym Fitness</option>
                                <option value="muay" <?php echo $edit_eq['discipline'] == 'muay' ? 'selected' : ''; ?>>🥊 Muay Thai / Boxing</option>
                                <option value="yoga" <?php echo $edit_eq['discipline'] == 'yoga' ? 'selected' : ''; ?>>🧘‍♀️ Yoga & Pilates</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Status</label>
                            <select name="status" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" <?php echo $edit_eq['status'] == 'active' ? 'selected' : ''; ?>>🟢 Active</option>
                                <option value="maintenance" <?php echo $edit_eq['status'] == 'maintenance' ? 'selected' : ''; ?>>🟡 Maintenance</option>
                                <option value="retired" <?php echo $edit_eq['status'] == 'retired' ? 'selected' : ''; ?>>🔴 Retired</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Equipment Image</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <?php if ($edit_eq['image']): ?>
                                <img src="<?php echo htmlspecialchars($edit_eq['image']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border);">
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: #f1f5f9; border-radius: 10px; flex: 1;">
                        </div>
                        <input type="text" name="custom_image_url" placeholder="Or paste image URL here..." value="<?php echo htmlspecialchars($edit_eq['image']); ?>" style="margin-top: 10px;">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"><?php echo htmlspecialchars($edit_eq['description']); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_equipment" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">
                            <i class="fas fa-save" style="margin-right: 8px;"></i>
                            Update Equipment
                        </button>
                        <a href="/Gym Manager/dashboards/manager.php?page=equipment" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; text-decoration: none; font-weight: 700; line-height: 2;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>

                <!-- Add Equipment Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin-bottom: 2.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary);"><i class="fas fa-plus-circle"></i> Add New Equipment</h3>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Equipment Name</label>
                            <input type="text" name="name" required placeholder="e.g. Treadmill Pro 5000">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Discipline</label>
                            <select name="discipline" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="gym">🏋️‍♂️ Gym Fitness</option>
                                <option value="muay">🥊 Muay Thai / Boxing</option>
                                <option value="yoga">🧘‍♀️ Yoga & Pilates</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Status</label>
                            <select name="status" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active">🟢 Active</option>
                                <option value="maintenance">🟡 Maintenance</option>
                                <option value="retired">🔴 Retired</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Equipment Image</label>
                        <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: #f1f5f9; border-radius: 10px; width: 100%;">
                        <input type="text" name="custom_image_url" placeholder="Or paste absolute image URL here..." style="margin-top: 10px;">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" required placeholder="Enter equipment specifications, features, or notes..." style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"></textarea>
                    </div>

                    <button type="submit" name="add_equipment" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">
                        <i class="fas fa-plus" style="margin-right: 8px;"></i>
                        Add Equipment
                    </button>
                </form>
                <?php endif; ?>

                <!-- Equipment Table -->
                <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Equipment Name</th>
                                <th>Discipline</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_equipment as $eq): 
                                $disc_badge = [
                                    'gym' => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'label' => '🏋️‍♂️ GYM'],
                                    'muay' => ['bg' => '#fef2f2', 'color' => '#b91c1c', 'label' => '🥊 MUAY THAI'],
                                    'yoga' => ['bg' => '#f0fdf4', 'color' => '#15803d', 'label' => '🧘‍♀️ YOGA']
                                ][$eq['discipline']] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => strtoupper($eq['discipline'])];

                                $st_badge = [
                                    'active' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'label' => '🟢 ACTIVE'],
                                    'maintenance' => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => '🟡 MAINTENANCE'],
                                    'retired' => ['bg' => '#fee2e2', 'color' => '#ef4444', 'label' => '🔴 RETIRED']
                                ][$eq['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => strtoupper($eq['status'])];
                            ?>
                            <tr style="box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px; font-weight: 600;">#<?php echo $eq['id']; ?></td>
                                <td style="padding: 1.2rem; background: white;">
                                    <img src="<?php echo htmlspecialchars($eq['image'] ?: '/Gym Manager/assets/img/equipment/treadmills.png'); ?>" style="width: 55px; height: 55px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border);">
                                </td>
                                <td style="padding: 1.2rem; background: white; font-weight: 700; color: var(--text-main);">
                                    <?php echo htmlspecialchars($eq['name']); ?>
                                </td>
                                <td style="padding: 1.2rem; background: white;">
                                    <span style="background:<?php echo $disc_badge['bg']; ?>; color:<?php echo $disc_badge['color']; ?>; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 0.72rem;">
                                        <?php echo $disc_badge['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 1.2rem; background: white; color: var(--text-muted); font-size: 0.9rem; max-width: 300px;">
                                    <?php echo htmlspecialchars($eq['description']); ?>
                                </td>
                                <td style="padding: 1.2rem; background: white;">
                                    <span style="background:<?php echo $st_badge['bg']; ?>; color:<?php echo $st_badge['color']; ?>; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.72rem;">
                                        <?php echo $st_badge['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center; white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="/Gym Manager/dashboards/manager.php?page=equipment&edit_equipment=<?php echo $eq['id']; ?>" class="action-btn btn-edit" title="Edit Equipment">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/Gym Manager/dashboards/manager.php?page=equipment&delete_equipment=<?php echo $eq['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to DELETE this equipment?');" title="Delete Equipment">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_equipment)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-muted);">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

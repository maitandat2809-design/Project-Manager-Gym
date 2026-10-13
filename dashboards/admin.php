<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /Gym Manager/index.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
// Administrators in this project are restricted to account administration.
// Operational, commerce, booking, membership and reporting functions belong
// to the Manager role and must not be reachable through a direct URL either.
$allowed_admin_pages = ['dashboard', 'users', 'coaches', 'managers'];
if (!in_array($page, $allowed_admin_pages, true)) {
    header("Location: /Gym Manager/dashboards/admin.php?page=dashboard");
    exit();
}

include_once __DIR__ . '/../includes/header.php';
$message = '';

// Handle Actions (Add/Update/Delete)
if ($page == 'products' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    if ($price >= 1000) {
        $price = round($price / 25000, 2);
    }
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

    $name_vi = $_POST['name_vi'] ?? '';
    $desc_vi = $_POST['description_vi'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO products (name, name_vi, price, stock, description, description_vi, image, category, has_weight_variants) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $name_vi, $price, $stock, $desc, $desc_vi, $image_path, $category, $has_weight]);
    $message = "Product added successfully!";
}

if ($page == 'products' && isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    if ($price >= 1000) {
        $price = round($price / 25000, 2);
    }
    $stock = $_POST['stock'];
    $desc = $_POST['description'];
    $category = $_POST['category'] ?? 'supplement';
    $has_weight = isset($_POST['has_weight_variants']) ? 1 : 0;
    
        $name_vi = $_POST['name_vi'] ?? '';
        $desc_vi = $_POST['description_vi'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir_fs = __DIR__ . '/../assets/img/products/';
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('prod_') . '.' . $file_extension;
            $target_file = $upload_dir_fs . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $new_image_path = '/Gym Manager/assets/img/products/' . $file_name;
                $stmt = $pdo->prepare("UPDATE products SET name=?, name_vi=?, price=?, stock=?, description=?, description_vi=?, image=?, category=?, has_weight_variants=? WHERE id=?");
                $stmt->execute([$name, $name_vi, $price, $stock, $desc, $desc_vi, $new_image_path, $category, $has_weight, $id]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, name_vi=?, price=?, stock=?, description=?, description_vi=?, category=?, has_weight_variants=? WHERE id=?");
            $stmt->execute([$name, $name_vi, $price, $stock, $desc, $desc_vi, $category, $has_weight, $id]);
        }
    $message = "Product updated successfully!";
}

if ($page == 'coaches' && isset($_POST['add_coach'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $raw_password = !empty($_POST['password']) ? $_POST['password'] : '123456';
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    $specialty = $_POST['specialty'];
    $rate = floatval($_POST['rate']);
    if ($rate >= 1000) {
        $rate = round($rate / 25000, 2);
    }
    $zalo = trim($_POST['zalo'] ?? '');
    
    // Check Email Duplicate
    $check_email = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $check_email->execute([$email]);
    
    // Check Phone / Zalo Duplicate
    $check_zalo = null;
    if ($zalo !== '') {
        $check_zalo_stmt = $pdo->prepare("SELECT c.id FROM coaches c JOIN users u ON c.user_id = u.id WHERE (c.zalo = ? AND c.zalo != '') OR (u.phone = ? AND u.phone != '')");
        $check_zalo_stmt->execute([$zalo, $zalo]);
        $check_zalo = $check_zalo_stmt->fetch();
    }

    if ($check_email->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã tồn tại trong hệ thống!" : "⚠️ Error: Email '$email' already exists!";
    } elseif ($check_zalo) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại/Zalo '$zalo' đã tồn tại trong hệ thống!" : "⚠️ Error: Phone/Zalo number '$zalo' already exists!";
    } else {
        try {
            $pdo->beginTransaction();
            // 1. Create User
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'coach')");
            $stmt->execute([$name, $email, $password]);
            $user_id = $pdo->lastInsertId();
            
            // 2. Create Coach Entry
            $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio, zalo) VALUES (?, ?, ?, 'Professional fitness instructor', ?)");
            $stmt->execute([$user_id, $specialty, $rate, $zalo]);
            
            $pdo->commit();
            $message = ($current_lang === 'vi') ? "Thêm HLV thành công!" : "Coach added successfully!";
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error: " . $e->getMessage();
        }
    }
}

if ($page == 'coaches' && isset($_POST['update_coach'])) {
    $coach_id = $_POST['coach_id'];
    $name = trim($_POST['name']);
    $specialty = $_POST['specialty'];
    $rate = floatval($_POST['rate']);
    if ($rate >= 1000) {
        $rate = round($rate / 25000, 2);
    }
    $zalo = trim($_POST['zalo'] ?? '');
    $coach_status = $_POST['status'] ?? 'active';
    $coach_status = in_array($coach_status, ['active', 'inactive'], true) ? $coach_status : 'active';
    
    $stmt = $pdo->prepare("SELECT user_id FROM coaches WHERE id = ?");
    $stmt->execute([$coach_id]);
    $uid = $stmt->fetchColumn();

    // Check Phone / Zalo Duplicate for other accounts
    $check_zalo = null;
    if ($zalo !== '') {
        $check_zalo_stmt = $pdo->prepare("SELECT c.id FROM coaches c JOIN users u ON c.user_id = u.id WHERE ((c.zalo = ? AND c.zalo != '') OR (u.phone = ? AND u.phone != '')) AND c.id != ? AND u.id != ?");
        $check_zalo_stmt->execute([$zalo, $zalo, $coach_id, $uid]);
        $check_zalo = $check_zalo_stmt->fetch();
    }

    if ($check_zalo) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại/Zalo '$zalo' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Phone/Zalo number '$zalo' is already used by another account!";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET name = ?, status = ? WHERE id = ? AND role = 'coach'");
            $stmt->execute([$name, $coach_status, $uid]);
            
            $stmt = $pdo->prepare("UPDATE coaches SET specialty = ?, hourly_rate = ?, zalo = ? WHERE id = ?");
            $stmt->execute([$specialty, $rate, $zalo, $coach_id]);
            
            $pdo->commit();
            $message = ($current_lang === 'vi') ? "Cập nhật thông tin HLV thành công!" : "Coach updated successfully!";
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
}

if (isset($_GET['delete_coach'])) {
    $coach_id = $_GET['delete_coach'];
    $stmt = $pdo->prepare("SELECT user_id FROM coaches WHERE id = ?");
    $stmt->execute([$coach_id]);
    $uid = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $message = "Coach deleted successfully!";
}

if ($page == 'users' && isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $uid]);
        
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
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    // The User Management screen may create standard user accounts only.
    $role = 'user';
    $phone = trim($_POST['phone'] ?? '');
    $is_valid_gmail = filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[^@\s]+@gmail\.com$/i', $email);
    $is_valid_phone = preg_match('/^[0-9]{10}$/', $phone);

    // Check email duplicate
    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $check_e->execute([$email]);

    // Check phone duplicate
    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != ''");
        $check_p_stmt->execute([$phone]);
        $check_p = $check_p_stmt->fetch();
    }

    if (!$is_valid_gmail) {
        $message = ($current_lang === 'vi') ? '⚠️ Email phải có định dạng @gmail.com.' : '⚠️ Email must use a @gmail.com address.';
    } elseif (!$is_valid_phone) {
        $message = ($current_lang === 'vi') ? '⚠️ Số điện thoại phải gồm đúng 10 chữ số.' : '⚠️ Phone number must contain exactly 10 digits.';
    } elseif ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã tồn tại trong hệ thống!" : "⚠️ Error: Email '$email' is already registered!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã tồn tại trong hệ thống!" : "⚠️ Error: Phone number '$phone' is already registered!";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role" . ($phone !== '' ? ", phone" : "") . ") VALUES (?, ?, ?, ?" . ($phone !== '' ? ", ?" : "") . ")");
            $params = [$name, $email, $password, $role];
            if ($phone !== '') $params[] = $phone;
            $stmt->execute($params);
            $user_id = $pdo->lastInsertId();

            if ($role === 'coach') {
                $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio) VALUES (?, 'Gym', 20.00, 'Professional fitness instructor')");
                $stmt->execute([$user_id]);
            }
            $pdo->commit();
            $message = ($current_lang === 'vi') ? "Tạo người dùng thành công!" : "User created successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error: " . $e->getMessage();
        }
    }
}

if ($page == 'users' && isset($_POST['update_user_details'])) {
    $uid = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    // Role changes are handled only in the dedicated Coach and Manager areas.
    $role = 'user';
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    $is_valid_gmail = filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[^@\s]+@gmail\.com$/i', $email);
    $is_valid_phone = preg_match('/^[0-9]{10}$/', $phone);

    // Check Email duplicate for another user
    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
    $check_e->execute([$email, $uid]);

    // Check Phone duplicate for another user
    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != '' AND id != ?");
        $check_p_stmt->execute([$phone, $uid]);
        $check_p = $check_p_stmt->fetch();
    }

    if (!$is_valid_gmail) {
        $message = ($current_lang === 'vi') ? '⚠️ Email phải có định dạng @gmail.com.' : '⚠️ Email must use a @gmail.com address.';
    } elseif (!$is_valid_phone) {
        $message = ($current_lang === 'vi') ? '⚠️ Số điện thoại phải gồm đúng 10 chữ số.' : '⚠️ Phone number must contain exactly 10 digits.';
    } elseif ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Email '$email' is already registered to another account!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Phone number '$phone' is already registered to another account!";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?" . ($phone !== '' ? ", phone = ?" : "") . " WHERE id = ? AND role = 'user'");
            $params = [$name, $email, $role, $status];
            if ($phone !== '') $params[] = $phone;
            $params[] = $uid;
            $stmt->execute($params);

            if ($role === 'coach') {
                $check_c = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
                $check_c->execute([$uid]);
                if (!$check_c->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio) VALUES (?, 'Gym', 20.00, 'Professional fitness instructor')");
                    $stmt->execute([$uid]);
                }
            }
            $pdo->commit();
            $message = ($current_lang === 'vi') ? "Cập nhật thông tin người dùng thành công!" : "User updated successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['delete_user'])) {
    $uid = $_GET['delete_user'];
    if ($uid == $_SESSION['user_id']) {
        $message = "Error: You cannot delete yourself!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('user', 'coach', 'manager')");
        $stmt->execute([$uid]);
        $message = "User deleted successfully!";
    }
}

if ($page == 'managers' && isset($_POST['add_manager'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = !empty($_POST['password']) ? $_POST['password'] : '123456';
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $phone = trim($_POST['phone'] ?? '');
    
    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $check_e->execute([$email]);

    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != ''");
        $check_p_stmt->execute([$phone]);
        $check_p = $check_p_stmt->fetch();
    }

    if ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã tồn tại trong hệ thống!" : "⚠️ Error: Email '$email' is already registered!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã tồn tại trong hệ thống!" : "⚠️ Error: Phone number '$phone' is already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role" . ($phone !== '' ? ", phone" : "") . ") VALUES (?, ?, ?, 'manager'" . ($phone !== '' ? ", ?" : "") . ")");
        $params = [$name, $email, $password];
        if ($phone !== '') $params[] = $phone;
        $stmt->execute($params);
        $message = ($current_lang === 'vi') ? "Tạo tài khoản Quản lý thành công!" : "Manager account created successfully!";
    }
}

if ($page == 'managers' && isset($_POST['update_manager'])) {
    $uid = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');

    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
    $check_e->execute([$email, $uid]);

    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != '' AND id != ?");
        $check_p_stmt->execute([$phone, $uid]);
        $check_p = $check_p_stmt->fetch();
    }

    if ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Email '$email' is already registered to another account!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Phone number '$phone' is already registered to another account!";
    } else {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?" . ($phone !== '' ? ", phone = ?" : "") . " WHERE id = ? AND role = 'manager'");
            $params = [$name, $email, $password];
            if ($phone !== '') $params[] = $phone;
            $params[] = $uid;
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?" . ($phone !== '' ? ", phone = ?" : "") . " WHERE id = ? AND role = 'manager'");
            $params = [$name, $email];
            if ($phone !== '') $params[] = $phone;
            $params[] = $uid;
            $stmt->execute($params);
        }
        $message = ($current_lang === 'vi') ? "Cập nhật tài khoản Quản lý thành công!" : "Manager updated successfully!";
    }
}

if (isset($_GET['delete_manager'])) {
    $uid = $_GET['delete_manager'];
    if ($uid == $_SESSION['user_id']) {
        $message = "Error: You cannot delete yourself!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'manager'");
        $stmt->execute([$uid]);
        $message = "Manager account deleted successfully!";
    }
}

// Handle Member Subscriptions Actions (Add / Update / Delete / Approve / Reject)
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
                $stmt_arch_old = $pdo->prepare("UPDATE member_subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'");
                $stmt_arch_old->execute([$user_id]);
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
    $sub_id = intval($_POST['sub_id'] ?? $_GET['approve_sub'] ?? 0);
    if ($sub_id > 0) {
        try {
            $stmt_app = $pdo->prepare("
                UPDATE member_subscriptions ms 
                JOIN membership_plans mp ON ms.plan_id = mp.id 
                SET ms.status = 'active', 
                    ms.admin_seen = 1,
                    ms.pt_sessions_total = CASE 
                        WHEN ms.pt_sessions_total > 0 THEN ms.pt_sessions_total 
                        ELSE (mp.pt_sessions + COALESCE(ms.bonus_pt, 0)) 
                    END 
                WHERE ms.id = ?
            ");
            $stmt_app->execute([$sub_id]);

            $message = ($current_lang === 'en')
                ? "🎉 Membership card #" . $sub_id . " activated successfully!"
                : "🎉 Đã xác nhận kích hoạt thẻ thành viên #" . $sub_id . " thành công!";
        } catch (Exception $e) {
            $message = ($current_lang === 'en')
                ? "Error activating membership card: " . $e->getMessage()
                : "Lỗi khi kích hoạt thẻ: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && (isset($_GET['reject_sub']) || isset($_POST['do_reject_sub']))) {
    $sub_id = intval($_POST['sub_id'] ?? $_GET['reject_sub']);
    $reason = trim($_POST['reject_reason'] ?? '');
    try {
        $stmt_rej = $pdo->prepare("UPDATE member_subscriptions SET status = 'cancelled', admin_seen = 1 WHERE id = ?");
        $stmt_rej->execute([$sub_id]);
        $message = ($current_lang === 'en')
            ? "🔴 Rejected / cancelled membership card subscription #" . $sub_id . "!" . ($reason ? " (Reason: " . htmlspecialchars($reason) . ")" : "")
            : "🔴 Đã từ chối / hủy yêu cầu đăng ký thẻ thành viên #" . $sub_id . "!" . ($reason ? " (Lý do: " . htmlspecialchars($reason) . ")" : "");
    } catch (Exception $e) {
        $message = ($current_lang === 'en')
            ? "Error cancelling membership: " . $e->getMessage()
            : "Lỗi khi hủy thẻ thành viên: " . $e->getMessage();
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

// Booking Actions (Add / Update / Delete / Change Status)
if ($page == 'bookings' && isset($_POST['add_booking'])) {
    $user_id = $_POST['user_id'];
    $coach_id = $_POST['coach_id'];
    $booking_date = $_POST['booking_date'];
    $status = $_POST['status'] ?? 'pending';
    
    try {
        $requested_time = strtotime($booking_date);
        $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
        $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);

        // 1. Coach slot conflict
        $stmt_c_chk = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
        $stmt_c_chk->execute([$coach_id, $start_buffer, $end_buffer]);
        
        // 2. User slot conflict
        $stmt_u_chk = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
        $stmt_u_chk->execute([$user_id, $start_buffer, $end_buffer]);

        if ($stmt_c_chk->fetch()) {
            $message = ($current_lang === 'vi')
                ? "⚠️ Lỗi: HLV này đã có lịch dạy vào khung giờ này (trùng trong vòng 60 phút). Vui lòng chọn thời gian hoặc HLV khác!"
                : "⚠️ Error: This coach is already booked for another session at this time (overlapping 60min slot).";
        } elseif ($stmt_u_chk->fetch()) {
            $message = ($current_lang === 'vi')
                ? "⚠️ Lỗi: Học viên đã có lịch tập với HLV khác vào cùng khung giờ này (trùng trong vòng 60 phút). Không thể đăng ký 2 HLV cùng 1 khung giờ!"
                : "⚠️ Error: The client already has a session scheduled with another coach at this time slot.";
        } else {
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
        $requested_time = strtotime($booking_date);
        $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
        $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);

        $has_conflict = false;
        if ($status !== 'cancelled') {
            // 1. Coach slot conflict
            $stmt_c_chk = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $stmt_c_chk->execute([$coach_id, $booking_id, $start_buffer, $end_buffer]);
            
            // 2. User slot conflict
            $stmt_u_chk = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $stmt_u_chk->execute([$user_id, $booking_id, $start_buffer, $end_buffer]);

            if ($stmt_c_chk->fetch()) {
                $has_conflict = true;
                $message = ($current_lang === 'vi')
                    ? "⚠️ Lỗi: HLV này đã có lịch dạy vào khung giờ này (trùng trong vòng 60 phút). Vui lòng chọn thời gian hoặc HLV khác!"
                    : "⚠️ Error: This coach is already booked for another session at this time (overlapping 60min slot).";
            } elseif ($stmt_u_chk->fetch()) {
                $has_conflict = true;
                $message = ($current_lang === 'vi')
                    ? "⚠️ Lỗi: Học viên đã có lịch tập với HLV khác vào cùng khung giờ này (trùng trong vòng 60 phút). Không thể đăng ký 2 HLV cùng 1 khung giờ!"
                    : "⚠️ Error: The client already has a session scheduled with another coach at this time slot.";
            }
        }

        if (!$has_conflict) {
            $stmt = $pdo->prepare("UPDATE bookings SET user_id = ?, coach_id = ?, booking_date = ?, status = ?, coach_message = ?, coach_seen = 0, user_seen = 0 WHERE id = ?");
            $stmt->execute([$user_id, $coach_id, $booking_date, $status, $coach_message, $booking_id]);
            $message = "Booking updated successfully!";
        }
        
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
        <h1>Admin Dashboard</h1>
        <div class="auth-btns">
            <span style="color: var(--text-muted); margin-right: 1rem;">Hello Admin, <strong><?php echo $_SESSION['user_name']; ?></strong></span>
        </div>
    </div>

    <div class="admin-grid">
        <div class="admin-sidebar">
            <ul>
                <li><a href="/Gym Manager/dashboards/admin.php?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>"><?php echo __("overview"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/admin.php?page=users" class="<?php echo $page == 'users' ? 'active' : ''; ?>"><?php echo __("user_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/admin.php?page=coaches" class="<?php echo $page == 'coaches' ? 'active' : ''; ?>"><?php echo __("coach_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/admin.php?page=managers" class="<?php echo $page == 'managers' ? 'active' : ''; ?>">👔 <?php echo __("manager_management"); ?></a></li>
            </ul>
        </div>

        <div class="admin-content" style="min-width: 0;">
            <?php if($message): 
                $is_err_msg = (strpos($message, 'Error') !== false || strpos($message, 'Lỗi') !== false || strpos($message, '⚠️') !== false);
            ?>
                <div style="padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 10px; <?php echo $is_err_msg ? 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' : 'background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;'; ?>">
                    <i class="<?php echo $is_err_msg ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'; ?>" style="font-size: 1.2rem;"></i>
                    <span><?php echo htmlspecialchars(str_replace(['Error: ', '⚠️ '], '', $message)); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <h2><?php echo __("system_overview"); ?></h2>
                <div class="grid" style="margin-top: 2rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div style="background: white; padding: 2rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: var(--primary); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 0.5rem;">User Accounts</h3>
                        <p style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(); ?>
                        </p>
                    </div>
                    <div style="background: white; padding: 2rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: #3b82f6; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 0.5rem;">Coach Accounts</h3>
                        <p style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coach'")->fetchColumn(); ?>
                        </p>
                    </div>
                    <div style="background: white; padding: 2rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: #f97316; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 0.5rem;">Manager Accounts</h3>
                        <p style="font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager'")->fetchColumn(); ?>
                        </p>
                    </div>
                </div>

                <div style="margin-top: 2rem; padding: 1.35rem; background: white; border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--shadow-premium);">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                        <div>
                            <h3 style="margin:0; color:var(--text-main); font-size:1rem;">Account Directory</h3>
                            <p style="margin:.25rem 0 0; color:var(--text-muted); font-size:.85rem;">Choose an account type to manage.</p>
                        </div>
                    </div>
                    <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
                        <a href="/Gym Manager/dashboards/admin.php?page=dashboard" class="action-btn" style="background:var(--primary); color:white; border-radius:14px; padding:.85rem 1.45rem; text-decoration:none; font-weight:800;">All</a>
                        <a href="/Gym Manager/dashboards/admin.php?page=users" class="action-btn" style="background:#f1f5f9; color:var(--text-main); border-radius:14px; padding:.85rem 1.45rem; text-decoration:none; font-weight:800;">User</a>
                        <a href="/Gym Manager/dashboards/admin.php?page=coaches" class="action-btn" style="background:#f1f5f9; color:var(--text-main); border-radius:14px; padding:.85rem 1.45rem; text-decoration:none; font-weight:800;">Coach</a>
                        <a href="/Gym Manager/dashboards/admin.php?page=managers" class="action-btn" style="background:#f1f5f9; color:var(--text-main); border-radius:14px; padding:.85rem 1.45rem; text-decoration:none; font-weight:800;">Manager</a>
                    </div>
                </div>

            <?php elseif ($page == 'products'): 
                $edit_prod = null;
                if (isset($_GET['edit_product'])) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt->execute([$_GET['edit_product']]);
                    $edit_prod = $stmt->fetch();
                }

                // Calculate Product Stock Metrics for Real-time Monitoring
                $prods_all = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
                $total_products_cnt = count($prods_all);
                $total_stock_units = 0;
                $total_sales_cnt = 0;
                $low_stock_cnt = 0;

                foreach ($prods_all as $pr_item) {
                    $stk_val = (int)($pr_item['stock'] ?? 0);
                    $total_stock_units += $stk_val;
                    $total_sales_cnt += (int)($pr_item['sales_count'] ?? 0);
                    if ($stk_val <= 5) {
                        $low_stock_cnt++;
                    }
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo __("product_management"); ?></h2>
                </div>

                <!-- Product Stock Metrics Dashboard -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin: 1.5rem 0;">
                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Tổng sản phẩm' : 'Total Products'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;"><?php echo $total_products_cnt; ?></div>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Tổng tồn kho' : 'Total Warehouse Stock'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #16a34a;"><?php echo number_format($total_stock_units); ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'món' : 'units'; ?></span></div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Đã bán (Khách mua)' : 'Total Items Sold'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #d97706;"><?php echo number_format($total_sales_cnt); ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'sản phẩm' : 'sold'; ?></span></div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Cảnh báo tồn kho' : 'Low Stock Alert'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #dc2626;"><?php echo $low_stock_cnt; ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'mặt hàng (≤5)' : 'items (≤5)'; ?></span></div>
                        </div>
                    </div>
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
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá Bán (VNĐ)' : 'Price (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price" value="<?php echo (int)round($edit_prod['price'] * 25000); ?>" required>
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
                        <label style="font-weight: 600;">Name (English)</label>
                        <textarea name="description" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"><?php echo htmlspecialchars($edit_prod['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--gold);">🇻🇳 Vietnamese Name</label>
                        <input type="text" name="name_vi" value="<?php echo htmlspecialchars($edit_prod['name_vi'] ?? ''); ?>" placeholder="e.g. Sữa Whey Protein" style="background: #fffbea; border: 1px solid rgba(201,168,76,0.3);">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--gold);">🇻🇳 Vietnamese Description</label>
                        <textarea name="description_vi" style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #fffbea; border: 1px solid rgba(201,168,76,0.3); outline: none; min-height: 80px;" placeholder="Product description in Vietnamese..."><?php echo htmlspecialchars($edit_prod['description_vi'] ?? ''); ?></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_product" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo __("update_product"); ?></button>
                        <a href="/Gym Manager/dashboards/admin.php?page=products" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; text-decoration: none; font-weight: 700;">Cancel</a>
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
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá Bán (VNĐ)' : 'Price (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price" required placeholder="<?php echo ($current_lang === 'vi') ? 'Ví dụ: 250000' : 'e.g. 250000'; ?>">
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
                        <label style="font-weight: 600;">Description (English)</label>
                        <textarea name="description" placeholder="Describe the product..." style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--gold);">🇻🇳 Vietnamese Name</label>
                        <input type="text" name="name_vi" placeholder="e.g. Sữa Whey Protein" style="background: #fffbea; border: 1px solid rgba(201,168,76,0.3);">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--gold);">🇻🇳 Vietnamese Description</label>
                        <textarea name="description_vi" placeholder="Product description in Vietnamese..." style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #fffbea; border: 1px solid rgba(201,168,76,0.3); outline: none; min-height: 80px;"></textarea>
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
                                <th>Category</th>
                                <th><?php echo __("price"); ?></th>
                                <th><?php echo ($current_lang === 'vi') ? 'Số Lượng Tồn Kho' : 'Stock Quantity'; ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($prods_all as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><img src="<?php echo get_image_url($p['image']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <?php if ($current_lang === 'vi' && !empty($p['name_vi'])): ?>
                                        <div style="font-size: 0.78rem; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($p['name_vi']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-size: 0.8rem; padding: 4px 10px; background: #e2e8f0; border-radius: 20px; font-weight: 600;"><?php echo ucfirst($p['category'] ?? 'supplement'); ?></span></td>
                                <td><strong style="color: #7e22ce;"><?php echo number_format((int)round($p['price'] * 25000), 0, ',', '.'); ?>đ</strong></td>
                                <td>
                                    <?php 
                                    $stk = (int)$p['stock'];
                                    $sales = (int)($p['sales_count'] ?? 0);
                                    if ($stk > 10): ?>
                                        <span style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-check-circle"></i> <?php echo ($current_lang === 'vi') ? "Còn tồn: {$stk}" : "In Stock: {$stk}"; ?>
                                        </span>
                                    <?php elseif ($stk > 0): ?>
                                        <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde047; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-exclamation-circle"></i> <?php echo ($current_lang === 'vi') ? "Sắp hết: Chỉ còn {$stk}" : "Low Stock: {$stk}"; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-times-circle"></i> <?php echo ($current_lang === 'vi') ? "HẾT HÀNG (0)" : "OUT OF STOCK (0)"; ?>
                                        </span>
                                    <?php endif; ?>
                                    <div style="font-size: 0.78rem; color: #475569; font-weight: 700; margin-top: 5px;">
                                        🛒 <?php echo ($current_lang === 'vi') ? "Đã bán: {$sales} SP" : "Sold: {$sales} units"; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/admin.php?page=products&edit_product=<?php echo $p['id']; ?>" class="action-btn btn-edit">Edit</a>
                                        <a href="/Gym Manager/dashboards/admin.php?page=products&delete_product=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn xóa sản phẩm này?' : 'Confirm delete this product?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Sản Phẩm' : 'Delete Product'; ?>', '🗑️');">Delete</a>
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
                    $stmt = $pdo->prepare("SELECT c.*, u.name, u.email, u.status AS account_status FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                    $stmt->execute([$_GET['edit_coach']]);
                    $edit_coach = $stmt->fetch();
                }
            ?>
                <h2><?php echo __("coach_management"); ?></h2>
                
                <?php if ($edit_coach): ?>
                <!-- Edit Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="coach_id" value="<?php echo $edit_coach['id']; ?>">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_coach['name']); ?>" required style="background: #f1f5f9;">
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
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá/giờ (VNĐ)' : 'Rate/h (VNĐ)'; ?></label>
                            <input type="number" step="10000" name="rate" value="<?php echo (int)round(($edit_coach['hourly_rate'] < 100 ? $edit_coach['hourly_rate'] * 25000 : $edit_coach['hourly_rate'])); ?>" required style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Zalo Number/Link</label>
                            <input type="text" name="zalo" value="<?php echo htmlspecialchars($edit_coach['zalo'] ?? ''); ?>" placeholder="09xxxxxxx or link" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Account Status</label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 700; color: <?php echo ($edit_coach['account_status'] ?? 'active') === 'active' ? '#15803d' : '#b91c1c'; ?>;">
                                <option value="active" <?php echo ($edit_coach['account_status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_coach['account_status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="submit" name="update_coach" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Update Coach Details</button>
                        <a href="/Gym Manager/dashboards/admin.php?page=coaches" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="e.g. John Doe" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Email (Account)</label>
                            <input type="email" name="email" required placeholder="john@gymx.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Password</label>
                            <input type="password" name="password" required placeholder="******" style="background: #f1f5f9;">
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
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá/giờ (VNĐ)' : 'Rate/h (VNĐ)'; ?></label>
                            <input type="number" step="10000" name="rate" required placeholder="500000" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Zalo Number</label>
                            <input type="text" name="zalo" placeholder="09xxxxxxxx" style="background: #f1f5f9;">
                        </div>
                    </div>
                    <button type="submit" name="add_coach" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2.8rem;">Add Coach</button>
                </form>
                <?php endif; ?>

                <?php $coach_search = trim($_GET['coach_search'] ?? ''); ?>
                <form method="GET" action="/Gym Manager/dashboards/admin.php" style="display: flex; gap: 0.75rem; align-items: center; margin: 1.5rem 0 1rem; flex-wrap: wrap;">
                    <input type="hidden" name="page" value="coaches">
                    <div style="position: relative; flex: 1; min-width: 260px; max-width: 460px;">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                        <input type="search" name="coach_search" value="<?php echo htmlspecialchars($coach_search); ?>" placeholder="Search coach by name, email or specialty..." style="padding: 0.95rem 1rem 0.95rem 2.8rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; width: 100%;">
                    </div>
                    <button type="submit" class="action-btn btn-edit" style="padding: 0.9rem 1.2rem;">Search</button>
                    <?php if ($coach_search !== ''): ?>
                        <a href="/Gym Manager/dashboards/admin.php?page=coaches" class="action-btn" style="padding: 0.9rem 1.2rem; background: #f1f5f9; color: #475569; text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th><?php echo __("email"); ?></th>
                                <th>Specialty</th>
                                <th>Rate/h</th>
                                <th>Status</th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $coach_sql = "SELECT c.*, u.name, u.email, u.status AS account_status FROM coaches c JOIN users u ON c.user_id = u.id";
                            $coach_params = [];
                            if ($coach_search !== '') {
                                $coach_sql .= " WHERE u.name LIKE ? OR u.email LIKE ? OR c.specialty LIKE ?";
                                $coach_term = '%' . $coach_search . '%';
                                $coach_params = [$coach_term, $coach_term, $coach_term];
                            }
                            $coach_sql .= " ORDER BY u.name ASC";
                            $coach_stmt = $pdo->prepare($coach_sql);
                            $coach_stmt->execute($coach_params);
                            $coaches = $coach_stmt->fetchAll();
                            foreach($coaches as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['specialty'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $hr_vnd = floatval($c['hourly_rate'] ?? 0);
                                    if ($hr_vnd < 100) $hr_vnd *= 25000;
                                    echo number_format((int)round($hr_vnd), 0, ',', '.') . 'đ/h';
                                    ?>
                                </td>
                                <td>
                                    <?php $is_coach_active = ($c['account_status'] ?? 'active') === 'active'; ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;font-size:0.78rem;font-weight:800;background:<?php echo $is_coach_active ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $is_coach_active ? '#15803d' : '#b91c1c'; ?>;">
                                        <i class="fas <?php echo $is_coach_active ? 'fa-check-circle' : 'fa-ban'; ?>"></i> <?php echo $is_coach_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/admin.php?page=coaches&edit_coach=<?php echo $c['id']; ?>" class="action-btn btn-edit" style="font-size: 0.8rem; padding: 5px 10px; display: inline-block;">Edit</a>
                                        <a href="/Gym Manager/dashboards/admin.php?page=coaches&delete_coach=<?php echo $c['id']; ?>" class="action-btn btn-delete" style="font-size: 0.8rem; padding: 5px 10px; display: inline-block;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xác nhận xóa tài khoản HLV này?' : 'Confirm delete this coach?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa HLV' : 'Delete Coach'; ?>', '🗑️');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'managers'): 
                $edit_mgr = null;
                if (isset($_GET['edit_manager'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'manager'");
                    $stmt->execute([$_GET['edit_manager']]);
                    $edit_mgr = $stmt->fetch();
                }
            ?>
                <h2>👔 <?php echo __("manager_management"); ?></h2>

                <?php if ($edit_mgr): ?>
                <!-- Edit Manager Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="user_id" value="<?php echo $edit_mgr['id']; ?>">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_mgr['name']); ?>" required style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_mgr['email']); ?>" required style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">New Password (leave empty to keep current)</label>
                            <input type="password" name="password" placeholder="******" style="background: #f1f5f9;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="submit" name="update_manager" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Update Manager</button>
                        <a href="/Gym Manager/dashboards/admin.php?page=managers" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Manager Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Add New Manager</h3>
                    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) auto; gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="Manager Name" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Email</label>
                            <input type="email" name="email" required placeholder="manager@gym.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Password (Default: 123456)</label>
                            <input type="password" name="password" placeholder="123456" style="background: #f1f5f9;">
                        </div>
                        <button type="submit" name="add_manager" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Add Manager</button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $managers = $pdo->query("SELECT * FROM users WHERE role = 'manager' ORDER BY created_at DESC")->fetchAll();
                            foreach($managers as $m): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; background: #fff7ed; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #f97316; font-size: 0.8rem;">
                                            <?php echo strtoupper($m['name'][0]); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($m['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($m['email']); ?></td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 5px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">
                                        <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%;"></span> Active Manager
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($m['created_at'])); ?></td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/admin.php?page=managers&edit_manager=<?php echo $m['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 8px 12px;">Edit</a>
                                        <a href="/Gym Manager/dashboards/admin.php?page=managers&delete_manager=<?php echo $m['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 8px 12px;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xác nhận xóa tài khoản Quản lý này?' : 'Confirm delete this manager account?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Quản Lý' : 'Delete Manager'; ?>', '🗑️');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'users'): 
                if ($_SESSION['role'] !== 'admin') {
                    echo "<script>window.location.href='admin.php';</script>";
                    exit();
                }

                $edit_u = null;
                if (isset($_GET['edit_user'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
                    $stmt->execute([$_GET['edit_user']]);
                    $edit_u = $stmt->fetch();
                }
            ?>
                <h2>User Permissions Management</h2>

                <!-- Add New User Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Add New User</h3>
                    <input type="hidden" name="role" value="user">
                    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) auto; gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="User Name" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("email"); ?></label>
                            <input type="email" name="email" required placeholder="name@gmail.com" pattern="[A-Za-z0-9._%+-]+@gmail\.com" title="Use a valid @gmail.com address" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Password</label>
                            <input type="password" name="password" required placeholder="******" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Phone Number</label>
                            <input type="tel" name="phone" required inputmode="numeric" minlength="10" maxlength="10" pattern="[0-9]{10}" placeholder="e.g. 0900000000" title="Enter exactly 10 digits" style="background: #f1f5f9;">
                        </div>
                        <button type="submit" name="add_user" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Add User</button>
                    </div>
                </form>

                <?php if ($edit_u): ?>
                    <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 2px solid var(--primary);">
                        <input type="hidden" name="user_id" value="<?php echo $edit_u['id']; ?>">
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_u['name']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_u['email']); ?>" required pattern="[A-Za-z0-9._%+-]+@gmail\.com" title="Use a valid @gmail.com address">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($edit_u['phone'] ?? ''); ?>" required inputmode="numeric" minlength="10" maxlength="10" pattern="[0-9]{10}" title="Enter exactly 10 digits">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Status</label>
                                <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                    <option value="active" <?php echo ($edit_u['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($edit_u['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="update_user_details" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">Update User</button>
                            <a href="/Gym Manager/dashboards/admin.php?page=users" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>

                <?php $user_search = trim($_GET['user_search'] ?? ''); ?>
                <form method="GET" action="/Gym Manager/dashboards/admin.php" style="display: flex; gap: 0.75rem; align-items: center; margin: 1.5rem 0 1rem; flex-wrap: wrap;">
                    <input type="hidden" name="page" value="users">
                    <div style="position: relative; flex: 1; min-width: 260px; max-width: 460px;">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                        <input type="search" name="user_search" value="<?php echo htmlspecialchars($user_search); ?>" placeholder="Search user by name, email or phone..." style="padding: 0.95rem 1rem 0.95rem 2.8rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; width: 100%;">
                    </div>
                    <button type="submit" class="action-btn btn-edit" style="padding: 0.9rem 1.2rem;">Search</button>
                    <?php if ($user_search !== ''): ?>
                        <a href="/Gym Manager/dashboards/admin.php?page=users" class="action-btn" style="padding: 0.9rem 1.2rem; background: #f1f5f9; color: #475569; text-decoration: none;">Clear</a>
                    <?php endif; ?>
                </form>
                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th><?php echo __("email"); ?></th>
                                <th>Phone</th>
                                <th><?php echo __("status"); ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $user_sql = "SELECT * FROM users WHERE role = 'user'";
                            $user_params = [];
                            if ($user_search !== '') {
                                $user_sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                                $user_term = '%' . $user_search . '%';
                                $user_params = [$user_term, $user_term, $user_term];
                            }
                            $user_sql .= " ORDER BY created_at DESC";
                            $user_stmt = $pdo->prepare($user_sql);
                            $user_stmt->execute($user_params);
                            $all_users = $user_stmt->fetchAll();
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
                                <td><?php echo htmlspecialchars($u['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php $user_is_active = ($u['status'] ?? 'active') === 'active'; ?>
                                    <span style="display: flex; align-items: center; gap: 5px; color: <?php echo $user_is_active ? '#22c55e' : '#64748b'; ?>; font-size: 0.85rem; font-weight: 600;">
                                        <span style="width: 8px; height: 8px; background: <?php echo $user_is_active ? '#22c55e' : '#94a3b8'; ?>; border-radius: 50%;"></span> <?php echo $user_is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/admin.php?page=users&edit_user=<?php echo $u['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 8px 12px;">Edit</a>
                                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="/Gym Manager/dashboards/admin.php?page=users&delete_user=<?php echo $u['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 8px 12px;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn xóa người dùng này? Thao tác không thể hoàn tác.' : 'WARNING: Are you sure you want to delete this user? This action cannot be undone.'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Người Dùng' : 'Delete User'; ?>', '⚠️');">Delete</a>
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
                                     c_u.name as coach_name, c.specialty as coach_specialty, c.hourly_rate as coach_rate
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
                $coaches_list = $pdo->query("SELECT c.id, u.name as coach_name, c.specialty FROM coaches c JOIN users u ON c.user_id = u.id ORDER BY u.name ASC")->fetchAll();

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
                                    <option value="<?php echo $c['id']; ?>" <?php echo $edit_booking['coach_id'] == $c['id'] ? 'selected' : ''; ?>>Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)</option>
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
                        <a href="/Gym Manager/dashboards/admin.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
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
                                    <option value="<?php echo $c['id']; ?>">Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)</option>
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
                        <a href="/Gym Manager/dashboards/admin.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); padding: 1rem 1.5rem; border-radius: 15px; text-decoration: none; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center;">Reset Filter</a>
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
                                                <a href="/Gym Manager/dashboards/admin.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.75rem; padding: 6px 10px;" title="Approve booking">
                                                    <i class="fas fa-check"></i> Confirm
                                                </a>
                                                <a href="/Gym Manager/dashboards/admin.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px;" title="Cancel booking" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn hủy buổi tập này?' : 'Are you sure you want to cancel this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel Booking'; ?>', '❌');">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php elseif ($b['status'] == 'confirmed'): ?>
                                                <a href="/Gym Manager/dashboards/admin.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px;" title="Cancel booking" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn hủy buổi tập này?' : 'Are you sure you want to cancel this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel Booking'; ?>', '❌');">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php elseif ($b['status'] == 'cancelled'): ?>
                                                <a href="/Gym Manager/dashboards/admin.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.75rem; padding: 6px 10px;" title="Re-confirm booking">
                                                    <i class="fas fa-redo"></i> Re-confirm
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="/Gym Manager/dashboards/admin.php?page=bookings&edit_booking=<?php echo $b['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 6px 10px;" title="Reschedule">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            
                                            <a href="/Gym Manager/dashboards/admin.php?page=bookings&delete_booking=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 6px 10px; background: #fff1f2; color: #ef4444;" title="Delete permanently" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn XÓA VĨNH VIỄN buổi tập này?' : 'WARNING: Are you sure you want to PERMANENTLY DELETE this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete Permanently'; ?>', '🗑️');">
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
                    $monthly_revenue[(int)$row['month']] = (int)round((float)$row['revenue'] * 25000);
                }
            ?>
                <?php $total_yearly_revenue = array_sum($monthly_revenue); ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2>Yearly Report - <?php echo htmlspecialchars($selected_year); ?></h2>
                        <p style="font-size: 1.1rem; color: var(--text-muted); margin-top: 5px;">
                            Total Revenue: <strong style="color: #10b981; font-size: 1.5rem;"><?php echo number_format($total_yearly_revenue, 0, ',', '.'); ?>đ</strong>
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
                                                return context.parsed.y.toLocaleString('vi-VN') + 'đ';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: '#f1f5f9', drawBorder: false },
                                        ticks: {
                                            callback: function(value) { return value.toLocaleString('vi-VN') + 'đ'; },
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
                                <td style="padding: 1.2rem; background: white; color: var(--primary); font-weight: 700;"><?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ</td>
                                <td style="padding: 1.2rem; background: white; max-width: 250px; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                    <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 5px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                        <?php echo strtoupper($o['status']); ?>
                                    </span>
                                    <span class="status-badge" style="background: #eff6ff; color: #1d4ed8; padding: 5px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem; display: block; margin-top: 4px;">
                                        <?php echo strtoupper($o['payment_method'] ?? 'COD'); ?>
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

            <?php elseif ($page == 'member_subscriptions'): 
                // Fetch stats (Identical to Booking Management 4-card structure)
                $stat_total   = $pdo->query("SELECT COUNT(*) FROM member_subscriptions")->fetchColumn();
                $stat_pending = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status = 'pending'")->fetchColumn();
                $stat_active  = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status = 'active'")->fetchColumn();
                $stat_expired = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status IN ('expired', 'cancelled')")->fetchColumn();

                // Filters & Search logic
                $search = trim($_GET['search'] ?? '');
                $status_filter = trim($_GET['status_filter'] ?? '');
                
                $query_str = "SELECT ms.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.avatar as user_avatar, 
                                     mp.name as plan_name, mp.name_vi as plan_name_vi, mp.badge, mp.badge_color, mp.price as plan_default_price, mp.duration_days as plan_duration_days
                              FROM member_subscriptions ms 
                              JOIN users u ON ms.user_id = u.id 
                              JOIN membership_plans mp ON ms.plan_id = mp.id";
                
                $params = [];
                $conditions = [];
                
                if ($search !== '') {
                    $conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR mp.name LIKE ? OR mp.name_vi LIKE ? OR ms.id LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($status_filter !== '') {
                    if ($status_filter === 'expired_all') {
                        $conditions[] = "ms.status IN ('expired', 'cancelled')";
                    } elseif (in_array($status_filter, ['pending', 'active', 'expired', 'cancelled'])) {
                        $conditions[] = "ms.status = ?";
                        $params[] = $status_filter;
                    }
                }
                
                if (!empty($conditions)) {
                    $query_str .= " WHERE " . implode(" AND ", $conditions);
                }
                
                $query_str .= " ORDER BY ms.id DESC";
                
                $stmt = $pdo->prepare($query_str);
                $stmt->execute($params);
                $all_subs = $stmt->fetchAll();

                // Fetch all users for dropdown
                $all_users = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
                
                // Fetch all active plans for dropdown
                $all_plans = $pdo->query("SELECT id, name, name_vi, price, duration_days, badge FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

                // Check if editing a subscription
                $edit_sub = null;
                if (isset($_GET['edit_sub'])) {
                    $stmt_es = $pdo->prepare("SELECT ms.*, u.name as user_name, u.email as user_email FROM member_subscriptions ms JOIN users u ON ms.user_id = u.id WHERE ms.id = ?");
                    $stmt_es->execute([$_GET['edit_sub']]);
                    $edit_sub = $stmt_es->fetch();
                }
            ?>
                <!-- Section Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("membership_management"); ?></h2>
                </div>

                <!-- Statistics Cards (Exact Booking Management Grid Style) -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Tổng Thẻ Thành Viên' : 'Total Memberships'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_total; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=pending" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(234,88,12,0.12)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Chờ Xác Nhận' : 'Pending'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_pending; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=active" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(22,163,74,0.12)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_active; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=expired_all" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #fafafa; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #4b5563; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Hết Hạn / Đã Hủy' : 'Expired / Cancelled'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #4b5563; margin-top: 5px;"><?php echo $stat_expired; ?></p>
                        </div>
                    </a>
                </div>

                <!-- Interactive Status Tabs Bar (Trình bày hiện rõ trong Tab) -->
                <div class="membership-tabs-nav" style="display: flex; gap: 0.6rem; margin-bottom: 1.8rem; flex-wrap: wrap; background: #ffffff; padding: 0.6rem 0.8rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo empty($status_filter) ? 'background: #1a2744; color: white; box-shadow: 0 4px 12px rgba(26,39,68,0.2);' : 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;'; ?>">
                        <i class="fas fa-layer-group"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Tất Cả Thẻ' : 'All Cards'; ?></span>
                        <span style="background: <?php echo empty($status_filter) ? 'rgba(255,255,255,0.25)' : '#e2e8f0'; ?>; color: <?php echo empty($status_filter) ? '#fff' : '#475569'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_total; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=pending<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo $status_filter === 'pending' ? 'background: #ea580c; color: white; box-shadow: 0 4px 12px rgba(234,88,12,0.25);' : 'background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;'; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Chờ Duyệt' : 'Pending'; ?></span>
                        <span style="background: <?php echo $status_filter === 'pending' ? 'rgba(255,255,255,0.25)' : '#fed7aa'; ?>; color: <?php echo $status_filter === 'pending' ? '#fff' : '#9a3412'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_pending; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=active<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo $status_filter === 'active' ? 'background: #16a34a; color: white; box-shadow: 0 4px 12px rgba(22,163,74,0.25);' : 'background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;'; ?>">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?></span>
                        <span style="background: <?php echo $status_filter === 'active' ? 'rgba(255,255,255,0.25)' : '#bbf7d0'; ?>; color: <?php echo $status_filter === 'active' ? '#fff' : '#14532d'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_active; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&status_filter=expired_all<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? 'background: #ef4444; color: white; box-shadow: 0 4px 12px rgba(239,68,68,0.25);' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2;'; ?>">
                        <i class="fas fa-history"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Hết Hạn / Đã Hủy' : 'Expired / Cancelled'; ?></span>
                        <span style="background: <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? 'rgba(255,255,255,0.25)' : '#fecaca'; ?>; color: <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? '#fff' : '#991b1b'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_expired; ?></span>
                    </a>
                </div>

                <?php if ($edit_sub): ?>
                <!-- Edit Subscription Form (Exact Booking Management Style) -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="sub_id" value="<?php echo $edit_sub['id']; ?>">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-edit" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa Thẻ Thành Viên #' . $edit_sub['id'] : 'Edit Membership #' . $edit_sub['id']; ?> (<?php echo htmlspecialchars($edit_sub['user_name']); ?>)
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Gói Thành Viên' : 'Membership Plan'; ?></label>
                            <select name="plan_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $edit_sub['plan_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ - <?php echo $p['duration_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" value="<?php echo $edit_sub['start_date']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" value="<?php echo $edit_sub['end_date']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán (VNĐ)' : 'Price Paid (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price_paid" value="<?php echo $edit_sub['price_paid']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Phương Thức' : 'Payment Method'; ?></label>
                            <select name="payment_method" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="cod" <?php echo $edit_sub['payment_method'] == 'cod' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Tại quầy (COD)' : 'At counter (COD)'; ?></option>
                                <option value="momo" <?php echo $edit_sub['payment_method'] == 'momo' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Ví MoMo' : 'MoMo Wallet'; ?></option>
                                <option value="cash" <?php echo $edit_sub['payment_method'] == 'cash' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Tiền mặt' : 'Cash'; ?></option>
                                <option value="bank_transfer" <?php echo $edit_sub['payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Chuyển khoản' : 'Bank Transfer'; ?></option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái Thẻ' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" <?php echo $edit_sub['status'] == 'active' ? 'selected' : ''; ?>>🟢 <?php echo $current_lang === 'vi' ? 'Active (Đang hoạt động)' : 'Active'; ?></option>
                                <option value="pending" <?php echo $edit_sub['status'] == 'pending' ? 'selected' : ''; ?>>🟡 <?php echo $current_lang === 'vi' ? 'Pending (Chờ duyệt)' : 'Pending'; ?></option>
                                <option value="expired" <?php echo $edit_sub['status'] == 'expired' ? 'selected' : ''; ?>>🔴 <?php echo $current_lang === 'vi' ? 'Expired (Đã hết hạn)' : 'Expired'; ?></option>
                                <option value="cancelled" <?php echo $edit_sub['status'] == 'cancelled' ? 'selected' : ''; ?>>⚪ <?php echo $current_lang === 'vi' ? 'Cancelled (Đã hủy)' : 'Cancelled'; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ghi Chú Quản Trị' : 'Admin Notes'; ?></label>
                        <input type="text" name="note" value="<?php echo htmlspecialchars($edit_sub['note'] ?? ''); ?>" placeholder="<?php echo $current_lang === 'vi' ? 'Nhập ghi chú thêm...' : 'Additional notes...'; ?>" style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_subscription" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo $current_lang === 'vi' ? 'Lưu Thay Đổi' : 'Save Changes'; ?></button>
                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Hủy Bỏ' : 'Cancel'; ?></a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Create Direct Membership Form (Exact Booking Management Style) -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên Trực Tiếp' : 'Create Direct Membership'; ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Học Viên' : 'Client'; ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected><?php echo $current_lang === 'vi' ? '-- Chọn Học Viên --' : '-- Select Client --'; ?></option>
                                <?php foreach($all_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Gói Thành Viên' : 'Membership Plan'; ?></label>
                            <select name="plan_id" id="create_sub_plan_id_admin" required onchange="handlePlanSelectAdmin(this)" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected><?php echo $current_lang === 'vi' ? '-- Chọn Gói --' : '-- Select Plan --'; ?></option>
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-days="<?php echo $p['duration_days']; ?>">
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" id="create_sub_start_date_admin" value="<?php echo date('Y-m-d'); ?>" required onchange="calcEndDateAdmin()" style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" selected>🟢 <?php echo $current_lang === 'vi' ? 'Active (Kích hoạt ngay)' : 'Active (Immediate)'; ?></option>
                                <option value="pending">🟡 <?php echo $current_lang === 'vi' ? 'Pending (Chờ xác nhận)' : 'Pending (Requires Confirmation)'; ?></option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 1.5rem; margin-top: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" id="create_sub_end_date_admin" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán' : 'Price Paid'; ?></label>
                            <input type="number" step="1000" name="price_paid" id="create_sub_price_paid_admin" placeholder="899000" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px; height: 55px;">
                            <input type="checkbox" name="overwrite_existing" value="1" id="add_overwrite_admin" checked style="width: 20px; height: 20px; accent-color: var(--primary);">
                            <label for="add_overwrite_admin" style="font-weight: 600; cursor: pointer; color: #64748b; font-size: 0.9rem;">
                                <?php echo $current_lang === 'vi' ? 'Thay thế thẻ cũ của khách hàng nếu đang có thẻ hoạt động' : 'Overwrite previous active membership if user already has one'; ?>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="add_subscription" class="btn-auth" style="margin-top: 1.5rem; width: auto; padding: 1.1rem 2.5rem;"><?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên' : 'Create Membership'; ?></button>
                </form>
                <?php endif; ?>

                <!-- Search & Filters (Exact Booking Management Style) -->
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem; background: #f8fafc; padding: 1.2rem; border-radius: 20px; border: 1px solid var(--border);">
                    <input type="hidden" name="page" value="member_subscriptions">
                    <div style="flex: 2; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $current_lang === 'vi' ? 'Tìm theo tên hội viên, email, số điện thoại, mã thẻ, gói tập...' : 'Search member name, email, phone, card ID, plan...'; ?>" style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-size: 0.95rem; outline: none;">
                    </div>
                    <div style="flex: 1;">
                        <select name="status_filter" style="width: 100%; padding: 1rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-weight: 600; outline: none; font-size: 0.95rem;">
                            <option value=""><?php echo $current_lang === 'vi' ? '-- Tất Cả Trạng Thái --' : '-- All Statuses --'; ?></option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending (<?php echo $current_lang === 'vi' ? 'Chờ Duyệt' : 'Pending'; ?>)</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active (<?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?>)</option>
                            <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired (<?php echo $current_lang === 'vi' ? 'Hết Hạn' : 'Expired'; ?>)</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled (<?php echo $current_lang === 'vi' ? 'Đã Hủy' : 'Cancelled'; ?>)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-auth" style="margin-top: 0; width: auto; padding: 1rem 2rem; font-size: 0.95rem;"><?php echo __("search"); ?></button>
                    <?php if ($search !== '' || $status_filter !== ''): ?>
                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); padding: 1rem 1.5rem; border-radius: 15px; text-decoration: none; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center;"><?php echo $current_lang === 'vi' ? 'Đặt Lại Bộ Lọc' : 'Reset Filter'; ?></a>
                    <?php endif; ?>
                </form>

                <!-- Subscriptions Table (Exact Booking Management Table Structure) -->
                <?php if (empty($all_subs)): ?>
                    <div style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 20px; border: 1px solid var(--border); margin-top: 2rem;">
                        <i class="far fa-id-card" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Không tìm thấy thẻ thành viên nào phù hợp bộ lọc.' : 'No membership subscriptions found matching filters.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 1.2rem; border-radius: 15px 0 0 15px;"><?php echo $current_lang === 'vi' ? 'HỘI VIÊN' : 'CLIENT'; ?></th>
                                    <th style="padding: 1.2rem;"><?php echo $current_lang === 'vi' ? 'GÓI THÀNH VIÊN' : 'MEMBERSHIP PLAN'; ?></th>
                                    <th style="padding: 1.2rem;"><?php echo $current_lang === 'vi' ? 'THỜI HẠN & HIỆU LỰC' : 'VALIDITY & DATES'; ?></th>
                                    <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;">DETAILS & ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_subs as $s): 
                                    $p_name = ($current_lang === 'vi' && !empty($s['plan_name_vi'])) ? $s['plan_name_vi'] : $s['plan_name'];
                                    $b_bg   = !empty($s['badge_color']) ? $s['badge_color'] : '#2563eb';
                                    $b_txt  = !empty($s['badge']) ? $s['badge'] : mb_strtoupper($p_name);
                                    $end_ts = strtotime($s['end_date']);
                                    $today_ts = strtotime(date('Y-m-d'));
                                    $days_left = ceil(($end_ts - $today_ts) / 86400);
                                ?>
                                <tr>
                                    <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo $s['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($s['user_name']).'&background=0062ff&color=fff&size=40'; ?>" 
                                                 style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
                                            <div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <strong style="color: var(--text-main); font-weight: 700;"><?php echo htmlspecialchars($s['user_name']); ?></strong>
                                                    <span style="background: #f1f5f9; color: #475569; font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 6px;">#<?php echo $s['id']; ?></span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($s['user_email']); ?></div>
                                                <?php if (!empty($s['user_phone'])): ?>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                                        <i class="fas fa-phone-alt" style="font-size: 0.75rem; color: var(--primary);"></i> <?php echo htmlspecialchars($s['user_phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div>
                                            <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($p_name); ?></strong>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 4px; align-items: center;">
                                                <span style="background: <?php echo $b_bg; ?>; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 7px; border-radius: 6px; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($b_txt); ?>
                                                </span>
                                                <?php if (!empty($s['is_renewal'])): ?>
                                                    <span style="background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 7px; border-radius: 6px; display: inline-flex; align-items: center; gap: 3px;">
                                                        <i class="fas fa-crown"></i> <?php echo $current_lang === 'vi' ? 'GIA HẠN' : 'RENEWAL'; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span style="font-size: 0.82rem; font-weight: 800; color: #ef4444; margin-left: 2px;">
                                                    <?php echo number_format($s['price_paid'], 0, ',', '.'); ?>đ
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <i class="far fa-calendar-alt" style="color: var(--primary); margin-right: 5px;"></i>
                                            <?php echo date('d/m/Y', strtotime($s['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($s['end_date'])); ?>
                                        </div>
                                        <div style="display: flex; gap: 6px; align-items: center; margin-top: 4px;">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <?php if ($days_left > 0): ?>
                                                    <span style="font-size: 0.75rem; color: #16a34a; font-weight: 700; background: #dcfce7; padding: 2px 8px; border-radius: 10px;">
                                                        ⚡ <?php echo $current_lang === 'vi' ? "Còn $days_left ngày" : "$days_left days left"; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; background: #fee2e2; padding: 2px 8px; border-radius: 10px;">
                                                        <?php echo $current_lang === 'vi' ? 'Hôm nay hết hạn' : 'Expires today'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php elseif ($s['status'] === 'pending'): ?>
                                                <span style="font-size: 0.75rem; color: #d97706; font-weight: 700; background: #fef3c7; padding: 2px 8px; border-radius: 10px;">
                                                    ⏳ <?php echo $current_lang === 'vi' ? 'Chờ kích hoạt' : 'Pending'; ?>
                                                </span>
                                            <?php elseif ($s['status'] === 'expired'): ?>
                                                <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; background: #fee2e2; padding: 2px 8px; border-radius: 10px;">
                                                    <?php echo $current_lang === 'vi' ? 'Đã hết hạn' : 'Expired'; ?>
                                                </span>
                                            <?php elseif ($s['status'] === 'cancelled'): ?>
                                                <span style="font-size: 0.75rem; color: #64748b; font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 10px;">
                                                    <?php echo $current_lang === 'vi' ? 'Đã hủy' : 'Cancelled'; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['bonus_days']) && $s['bonus_days'] > 0): ?>
                                                <span style="font-size: 0.75rem; color: #15803d; font-weight: 700;">+<?php echo $s['bonus_days']; ?>d free</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                        <button type="button" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 7px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; background: linear-gradient(135deg, #1a2744, #2563eb); color: white; border: none;" onclick="showSubDetailsModal(<?php echo $s['id']; ?>)">
                                            <i class="fas fa-eye"></i> <?php echo $current_lang === 'vi' ? 'Xem Chi Tiết' : 'Details'; ?>
                                        </button>

                                        <!-- Hidden Modal Content for Subscription #<?php echo $s['id']; ?> -->
                                        <div id="sub-modal-content-<?php echo $s['id']; ?>" style="display:none;">
                                            <!-- Membership Header & Status Badge -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1.2rem; border-bottom: 1.5px solid #e2e8f0;">
                                                <div>
                                                    <h3 style="margin: 0; color: var(--primary); font-weight: 800; font-size: 1.2rem;">
                                                        <?php echo $current_lang === 'vi' ? 'Thẻ Thành Viên #' : 'Membership Card #'; ?><?php echo $s['id']; ?>
                                                    </h3>
                                                    <span style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                                                        📅 <?php echo date('d/m/Y H:i', strtotime($s['created_at'] ?? $s['start_date'])); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <?php
                                                    $st_styles = [
                                                        'active' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'border' => '#86efac', 'label' => '🟢 ACTIVE'],
                                                        'pending' => ['bg' => '#fef3c7', 'color' => '#d97706', 'border' => '#fde68a', 'label' => '🟡 PENDING'],
                                                        'expired' => ['bg' => '#fee2e2', 'color' => '#ef4444', 'border' => '#fca5a5', 'label' => '🔴 EXPIRED'],
                                                        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'border' => '#cbd5e1', 'label' => '⚪ CANCELLED'],
                                                    ];
                                                    $badge_curr = $st_styles[$s['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'border' => '#cbd5e1', 'label' => strtoupper($s['status'])];
                                                    ?>
                                                    <span style="display: inline-block; padding: 6px 14px; font-weight: 800; font-size: 0.8rem; border-radius: 20px; background: <?php echo $badge_curr['bg']; ?>; color: <?php echo $badge_curr['color']; ?>; border: 1px solid <?php echo $badge_curr['border']; ?>;">
                                                        <?php echo $badge_curr['label']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Member & Plan Info Cards -->
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem; background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; text-align: left;">
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        👤 <?php echo $current_lang === 'vi' ? 'Hội Viên' : 'Member Info'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($s['user_name']); ?></div>
                                                    <div style="font-size: 0.82rem; color: #475569;"><?php echo htmlspecialchars($s['user_email']); ?></div>
                                                    <?php if (!empty($s['user_phone'])): ?>
                                                        <div style="font-size: 0.82rem; color: #334155; margin-top: 2px;">📞 <?php echo htmlspecialchars($s['user_phone']); ?></div>
                                                    <?php endif; ?>
                                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">User ID: #<?php echo $s['user_id']; ?></div>
                                                </div>
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        💳 <?php echo $current_lang === 'vi' ? 'Gói Tập & Chi Phí' : 'Plan & Payment'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($p_name); ?></div>
                                                    <div style="font-size: 0.9rem; color: #ef4444; font-weight: 800;"><?php echo number_format($s['price_paid'], 0, ',', '.'); ?>đ</div>
                                                    <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;">
                                                        <?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?> <strong><?php echo strtoupper($s['payment_method']); ?></strong>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: #334155; margin-top: 2px;">
                                                        📅 <?php echo date('d/m/Y', strtotime($s['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($s['end_date'])); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!empty($s['is_renewal'])): ?>
                                            <!-- Loyalty & Renewal Privileges Card -->
                                            <div style="background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 16px; padding: 1.2rem; margin-bottom: 1.2rem; text-align: left; font-size: 0.88rem; color: #92400e;">
                                                <div style="font-weight: 800; display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                                    <i class="fas fa-crown" style="color: #d97706;"></i> <?php echo $current_lang === 'vi' ? 'ĐẶC QUYỀN GIA HẠN / HỘI VIÊN THÂN THIẾT' : 'RENEWAL & LOYALTY PRIVILEGES'; ?>
                                                </div>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                                    <?php if (!empty($s['discount_amount']) && $s['discount_amount'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Giảm giá tri ân:' : 'Discount:'; ?> <strong>-<?php echo number_format($s['discount_amount'], 0, ',', '.'); ?>đ</strong></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($s['bonus_days']) && $s['bonus_days'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Tặng thêm:' : 'Bonus:'; ?> <strong>+<?php echo $s['bonus_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày tập free' : 'free days'; ?></strong></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($s['bonus_pt']) && $s['bonus_pt'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Tặng kèm:' : 'Bonus:'; ?> <strong>+<?php echo $s['bonus_pt']; ?> buổi PT 1:1</strong></div>
                                                    <?php endif; ?>
                                                    <div>• <?php echo $current_lang === 'vi' ? 'Quà tặng:' : 'Gift:'; ?> <strong>Bình GymX Shaker VIP Pro + 2 Guest Pass</strong></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <!-- Notes Card -->
                                            <div style="background: #f1f5f9; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-sticky-note" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Ghi Chú Hệ Thống' : 'System Notes'; ?>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #334155; font-weight: 600; line-height: 1.4;">
                                                    <?php echo !empty($s['note']) ? htmlspecialchars($s['note']) : '<span style="color: #94a3b8; font-style: italic; font-weight: 500;">' . ($current_lang === 'vi' ? 'Chưa có ghi chú' : 'None') . '</span>'; ?>
                                                </div>
                                            </div>

                                            <!-- Action Controls inside Modal (Exact Booking Management Action Style) -->
                                            <div style="background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-cogs" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Thao Tác Quản Lý Thẻ Thành Viên' : 'Membership Management Actions'; ?>
                                                </div>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                                    <?php if ($s['status'] === 'pending'): ?>
                                                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&approve_sub=<?php echo $s['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xác nhận KÍCH HOẠT thẻ thành viên này cho hội viên?' : 'Confirm activation of this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Kích Hoạt Thẻ' : 'Activate Membership'; ?>', '⚡');">
                                                            <i class="fas fa-check-circle"></i> <?php echo $current_lang === 'vi' ? 'Xác Nhận Kích Hoạt' : 'Confirm & Activate'; ?>
                                                        </a>
                                                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&reject_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn TỪ CHỐI yêu cầu đăng ký thẻ này?' : 'Are you sure you want to reject this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Từ Chối Thẻ' : 'Reject Membership'; ?>', '❌');">
                                                            <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Từ Chối' : 'Reject'; ?>
                                                        </a>
                                                    <?php elseif ($s['status'] === 'active'): ?>
                                                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&reject_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn HỦY thẻ thành viên này?' : 'Are you sure you want to cancel this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Thẻ' : 'Cancel Membership'; ?>', '❌');">
                                                            <i class="fas fa-ban"></i> <?php echo $current_lang === 'vi' ? 'Hủy Thẻ' : 'Cancel'; ?>
                                                        </a>
                                                    <?php elseif (in_array($s['status'], ['expired', 'cancelled'])): ?>
                                                        <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&approve_sub=<?php echo $s['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Kích hoạt lại thẻ thành viên này?' : 'Re-activate this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Kích Hoạt Lại' : 'Re-activate'; ?>', '⚡');">
                                                            <i class="fas fa-redo"></i> <?php echo $current_lang === 'vi' ? 'Kích Hoạt Lại' : 'Re-activate'; ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&edit_sub=<?php echo $s['id']; ?>" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;">
                                                        <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa' : 'Edit'; ?>
                                                    </a>

                                                    <a href="/Gym Manager/dashboards/admin.php?page=member_subscriptions&delete_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; background: #fff1f2; color: #ef4444; text-decoration: none; margin-left: auto;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn XÓA VĨNH VIỄN thẻ thành viên này?' : 'WARNING: Are you sure you want to PERMANENTLY DELETE this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete Permanently'; ?>', '🗑️');">
                                                        <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete'; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Membership Details Modal Shell (Exact Booking Details Modal Shell Style) -->
                    <div id="subDetailsModalShell" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
                        <div style="background: #ffffff; width: 100%; max-width: 660px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden; animation: euModalPop 0.3s ease forwards;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.6rem; background: linear-gradient(135deg, #1a2744, #2563eb); color: white;">
                                <div style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-id-card" style="color: var(--gold);"></i>
                                    <span><?php echo $current_lang === 'vi' ? 'Chi Tiết & Quản Lý Thẻ Thành Viên' : 'Membership Details & Actions'; ?></span>
                                </div>
                                <button type="button" onclick="closeSubDetailsModal()" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                    &times;
                                </button>
                            </div>
                            <div id="subDetailsModalBody" style="padding: 1.6rem; max-height: 80vh; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <script>
                    function showSubDetailsModal(subId) {
                        const src = document.getElementById('sub-modal-content-' + subId);
                        const body = document.getElementById('subDetailsModalBody');
                        const shell = document.getElementById('subDetailsModalShell');
                        if (src && body && shell) {
                            body.innerHTML = src.innerHTML;
                            shell.style.display = 'flex';
                        }
                    }
                    function closeSubDetailsModal() {
                        const shell = document.getElementById('subDetailsModalShell');
                        if (shell) shell.style.display = 'none';
                    }
                    window.addEventListener('click', function(e) {
                        const shell = document.getElementById('subDetailsModalShell');
                        if (e.target === shell) {
                            closeSubDetailsModal();
                        }
                    });

                    function handlePlanSelectAdmin(selectEl) {
                        const opt = selectEl.options[selectEl.selectedIndex];
                        if (opt) {
                            const price = opt.getAttribute('data-price');
                            const days = parseInt(opt.getAttribute('data-days')) || 30;
                            if (price) {
                                document.getElementById('create_sub_price_paid_admin').value = price;
                            }
                            calcEndDateAdmin(days);
                        }
                    }

                    function calcEndDateAdmin(customDays) {
                        const startInput = document.getElementById('create_sub_start_date_admin');
                        const endInput = document.getElementById('create_sub_end_date_admin');
                        const planSelect = document.getElementById('create_sub_plan_id_admin');
                        if (!startInput || !endInput) return;
                        
                        let days = customDays;
                        if (!days && planSelect) {
                            const opt = planSelect.options[planSelect.selectedIndex];
                            if (opt) days = parseInt(opt.getAttribute('data-days')) || 30;
                        }
                        if (!days) days = 30;
                        
                        const startDate = new Date(startInput.value || new Date());
                        startDate.setDate(startDate.getDate() + days);
                        endInput.value = startDate.toISOString().split('T')[0];
                    }

                    // Auto open modal if sub_id or confirm_action is passed in URL
                    document.addEventListener('DOMContentLoaded', function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const subId = urlParams.get('sub_id');
                        if (subId) {
                            showSubDetailsModal(subId);
                        }
                    });
                    </script>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

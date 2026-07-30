<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Safe translate function for internal keys
function translate_key($key, $lang = 'vi') {
    $lang_file = "languages/{$lang}.php";
    if (file_exists($lang_file)) {
        $translations = include $lang_file;
        if (isset($translations[$key])) {
            return $translations[$key];
        }
    }
    return $key;
}

$lang = $_SESSION['lang'] ?? 'vi';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => translate_key('fs_modal_error', $lang)
    ]);
    exit();
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$package_id = trim($_POST['package_id'] ?? '');
$coach_id = trim($_POST['coach_id'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$note = trim($_POST['note'] ?? '');

if (empty($name) || empty($phone) || empty($email) || empty($package_id)) {
    echo json_encode([
        'success' => false,
        'message' => translate_key('fs_modal_fill_all', $lang)
    ]);
    exit();
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => ($lang === 'vi' ? 'Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 số.' : 'Invalid phone number! Please enter exactly 10 digits.')
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => ($lang === 'vi' ? 'Địa chỉ Email không hợp lệ!' : 'Invalid Email address!')
    ]);
    exit();
}

try {
    // 1. Find or create user
    $user_id = null;
    $temp_created = false;
    
    $stmt_u = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_u->execute([$email]);
    $existing_user = $stmt_u->fetch();
    
    if ($existing_user) {
        $user_id = $existing_user['id'];
        
        // Update phone if it's empty
        $stmt_update_phone = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ? AND (phone IS NULL OR phone = '')");
        $stmt_update_phone->execute([$phone, $user_id]);
    } else {
        // Create a new guest user
        $temp_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $stmt_insert = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'user', ?)");
        $stmt_insert->execute([$name, $email, $temp_pass, $phone]);
        $user_id = $pdo->lastInsertId();
        $temp_created = true;
    }
    
    // 2. Define package info
    $package_name = '';
    $specialty = 'Gym';
    
    switch ($package_id) {
        case 'gym':
            $package_name = translate_key('fs_gym_title', $lang);
            $specialty = 'Gym';
            break;
        case 'pt':
            $package_name = translate_key('fs_pt_title', $lang);
            $specialty = 'Gym';
            break;
        case 'fatloss':
            $package_name = translate_key('fs_fatloss_title', $lang);
            $specialty = 'Gym';
            break;
        case 'yoga':
            $package_name = translate_key('fs_yoga_title', $lang);
            $specialty = 'Yoga';
            break;
        case 'bodypump':
            $package_name = translate_key('fs_bodypump_title', $lang);
            $specialty = 'Gym';
            break;
        case 'kickboxing':
            $package_name = translate_key('fs_kickboxing_title', $lang);
            $specialty = 'Muay';
            break;
        default:
            $package_name = 'Custom Featured Service';
            $specialty = 'Gym';
            break;
    }
    
    // 3. Find coach and specialty room number
    $assigned_coach_id = null;
    $room_number = 'TBD';
    
    if (!empty($coach_id)) {
        $assigned_coach_id = (int)$coach_id;
    } else {
        // Find coach by specialty matching
        $stmt_c = $pdo->prepare("SELECT c.id FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.specialty LIKE ? LIMIT 1");
        $stmt_c->execute(["%{$specialty}%"]);
        $assigned_coach_id = $stmt_c->fetchColumn();
        
        if (!$assigned_coach_id) {
            // Fallback to first coach
            $assigned_coach_id = $pdo->query("SELECT id FROM coaches LIMIT 1")->fetchColumn();
        }
    }
    
    // Look up coach details for room number
    if ($assigned_coach_id) {
        $stmt_c_info = $pdo->prepare("SELECT room_number, specialty FROM coaches WHERE id = ?");
        $stmt_c_info->execute([$assigned_coach_id]);
        $c_info = $stmt_c_info->fetch();
        if ($c_info) {
            $room_number = $c_info['room_number'] ?: 'TBD';
            // Overwrite specialty from coach specialty if possible
            if ($c_info['specialty']) {
                if (stripos($c_info['specialty'], 'Yoga') !== false) {
                    $specialty = 'Yoga';
                } elseif (stripos($c_info['specialty'], 'Muay') !== false) {
                    $specialty = 'Muay';
                } else {
                    $specialty = 'Gym';
                }
            }
        }
    }
    
    // 4. Booking Date
    $booking_date = !empty($start_date) ? date('Y-m-d H:i:s', strtotime($start_date)) : date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
    
    // 5. Training Plan Note
    $training_plan = "Registered via homepage Featured Services.\nPackage: {$package_name}\nNote: {$note}";
    
    // 6. Insert booking record
    $stmt_book = $pdo->prepare("
        INSERT INTO bookings (user_id, coach_id, booking_date, status, class_name, specialty, room_number, training_plan, user_phone) 
        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)
    ");
    $stmt_book->execute([
        $user_id,
        $assigned_coach_id,
        $booking_date,
        $package_name,
        $specialty,
        $room_number,
        $training_plan,
        $phone
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => translate_key('fs_modal_success_desc', $lang),
        'temp_created' => $temp_created
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => translate_key('fs_modal_error', $lang) . ' ' . $e->getMessage()
    ]);
}

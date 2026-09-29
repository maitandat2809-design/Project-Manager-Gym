<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: /Gym Manager/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
$stmt->execute([$user_id]);
$coach = $stmt->fetch();

if (!$coach) {
    include_once __DIR__ . '/../includes/header.php';
    echo "<section><h1>Coach profile not set up!</h1></section>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$coach_id = $coach['id'];
$tab = $_GET['tab'] ?? 'schedule';

// Handle status updates and details submission
if (isset($_POST['confirm_booking'])) {
    $booking_id = $_POST['booking_id'];
    $coach_phone = $_POST['coach_phone'];
    $coach_address = $_POST['coach_address'];

    if (!preg_match('/^[0-9]{10}$/', $coach_phone)) {
        header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&error=" . urlencode("Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 số."));
        exit();
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', coach_phone = ?, coach_address = ? WHERE id = ? AND coach_id = ?");
    
    if ($stmt->execute([$coach_phone, $coach_address, $booking_id, $coach_id])) {
        // Send Email to Managers
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $stmt_b = $pdo->prepare("SELECT b.*, u.name as user_name, u.email as user_email, c_u.name as coach_name 
                                     FROM bookings b 
                                     LEFT JOIN users u ON b.user_id = u.id 
                                     JOIN coaches c ON b.coach_id = c.id 
                                     JOIN users c_u ON c.user_id = c_u.id 
                                     WHERE b.id = ?");
            $stmt_b->execute([$booking_id]);
            $b_info = $stmt_b->fetch();

            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lamtuoi155@gmail.com';     
            $mail->Password   = 'hpst uazz wdsq sbnf';   
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('lamtuoi155@gmail.com', 'GymX System');

            $stmt_mgr = $pdo->query("SELECT email, name FROM users WHERE role = 'manager'");
            $managers = $stmt_mgr->fetchAll();
            foreach ($managers as $mgr) {
                if ($mgr['email']) $mail->addAddress($mgr['email'], $mgr['name']);
            }

            $mail->isHTML(true);
            $mail->Subject = "⚠️ Action Required: New Booking Confirmed by Coach";
            
            $html = "<div style='font-family: Arial; padding: 20px; border: 1px solid #ccc; border-radius: 10px;'>";
            $html .= "<h2 style='color: #0062ff;'>Booking Information Details</h2>";
            $html .= "<p>A session has been fully confirmed and details have been provided.</p>";
            $html .= "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Client Name:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$b_info['user_name']}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Client Phone:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$b_info['user_phone']}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Client Address:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$b_info['user_address']}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Coach Name:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$b_info['coach_name']}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Coach Phone:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$coach_phone}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Coach Address:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>{$coach_address}</td></tr>";
            $html .= "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Date/Time:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>" . date('d/m/Y H:i', strtotime($b_info['booking_date'])) . "</td></tr>";
            $html .= "</table></div>";

            $mail->Body = $html;
            $mail->send();
        } catch (Exception $e) {
            error_log("Mail Error for Manager Notif: " . $mail->ErrorInfo);
        }
        
        header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&success=Booking Confirmed");
        exit();
    }
}

// Handle Create New Class
if (isset($_POST['add_class'])) {
    $class_name = $_POST['class_name'];
    $specialty = $_POST['specialty'];
    $room_number = $_POST['room_number'];
    $training_plan = $_POST['training_plan'];
    $booking_date = $_POST['booking_date'];

    $stmt = $pdo->prepare("INSERT INTO bookings (coach_id, booking_date, status, class_name, specialty, room_number, training_plan) VALUES (?, ?, 'confirmed', ?, ?, ?, ?)");
    $stmt->execute([$coach_id, $booking_date, $class_name, $specialty, $room_number, $training_plan]);
    
    header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&success=" . urlencode("Đã tạo lớp học mới thành công"));
    exit();
}

// Handle Class Details & Schedule Date/Time Update
if (isset($_POST['update_class_details'])) {
    $booking_id = $_POST['booking_id'];
    $class_name = $_POST['class_name'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $training_plan = $_POST['training_plan'] ?? '';
    $attendance = $_POST['attendance'] ?? 'pending';
    $coach_message = $_POST['coach_message'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';

    if (!empty($booking_date)) {
        // Check for time conflict with coach's other bookings
        $chk_stmt = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND booking_date = ? AND id != ? AND status != 'cancelled'");
        $chk_stmt->execute([$coach_id, $booking_date, $booking_id]);
        if ($chk_stmt->fetch()) {
            header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&error=" . urlencode("Khung giờ dạy này bị trùng với một lịch học khác của bạn!"));
            exit();
        }

        $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, class_name = ?, specialty = ?, room_number = ?, training_plan = ?, attendance = ?, coach_message = ? WHERE id = ? AND coach_id = ?");
        $stmt->execute([$booking_date, $class_name, $specialty, $room_number, $training_plan, $attendance, $coach_message, $booking_id, $coach_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET class_name = ?, specialty = ?, room_number = ?, training_plan = ?, attendance = ?, coach_message = ? WHERE id = ? AND coach_id = ?");
        $stmt->execute([$class_name, $specialty, $room_number, $training_plan, $attendance, $coach_message, $booking_id, $coach_id]);
    }

    header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&success=" . urlencode("Cập nhật thông tin và lịch dạy thành công"));
    exit();
}

// Handle Cancel/Delete Class
if (isset($_POST['delete_class'])) {
    $booking_id = (int)$_POST['booking_id'];

    $stmt_chk = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ? AND coach_id = ?");
    $stmt_chk->execute([$booking_id, $coach_id]);
    $bk_info = $stmt_chk->fetch();

    if ($bk_info) {
        if (empty($bk_info['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND coach_id = ?");
            $stmt->execute([$booking_id, $coach_id]);
            header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&success=" . urlencode("Đã xóa lớp học khỏi lịch dạy"));
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND coach_id = ?");
            $stmt->execute([$booking_id, $coach_id]);
            header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=schedule&success=" . urlencode("Đã hủy buổi dạy thành công"));
        }
    }
    exit();
}

// Handle Bio Update

if (isset($_POST['update_bio'])) {
    $specialty = $_POST['specialty'];
    $bio = $_POST['bio'];
    $rate = $_POST['hourly_rate'];
    $stmt = $pdo->prepare("UPDATE coaches SET specialty = ?, bio = ?, hourly_rate = ? WHERE id = ?");
    $stmt->execute([$specialty, $bio, $rate, $coach_id]);
    header("Location: /Gym Manager/dashboards/coach_dashboard.php?tab=profile&success=Profile Updated");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

$coach_details = $pdo->prepare("SELECT * FROM coaches WHERE id = ?");
$coach_details->execute([$coach_id]);
$c_info = $coach_details->fetch();

?>

<style>
.coach-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--border);
    padding-bottom: 1rem;
}
.coach-tab {
    padding: 0.8rem 1.5rem;
    background: #f1f5f9;
    color: var(--text-muted);
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
}
.coach-tab:hover {
    background: #e2e8f0;
}
.coach-tab.active {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 98, 255, 0.3);
}
</style>

<section>
    <div class="admin-header">
        <div>
            <h1>Coach Workspace</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Manage your teaching schedule, profile, and reports.</p>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="/Gym Manager/notifications/coach_notifications.php" class="btn-card" style="background: var(--grad-primary); color: white; border: none;">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <span style="color: var(--text-muted); border-left: 1px solid var(--border); padding-left: 1rem;">PT: <strong><?php echo $_SESSION['user_name']; ?></strong></span>
        </div>
    </div>

    <div class="admin-content" style="min-width: 0;">
        <div class="coach-tabs">
            <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=schedule" class="coach-tab <?php echo $tab == 'schedule' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Schedule
            </a>
            <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=calendar" class="coach-tab <?php echo $tab == 'calendar' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> <?php echo __('calendar_view_tab'); ?>
            </a>
            <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=report" class="coach-tab <?php echo $tab == 'report' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Yearly Report
            </a>
            <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=profile" class="coach-tab <?php echo $tab == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user-edit"></i> Profile
            </a>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <p style="color: #ff4444; background: rgba(255,68,68,0.1); padding: 1rem; border-radius: 10px; margin-bottom: 1rem;"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <?php if(isset($_GET['success'])): ?>
            <p style="color: #00C851; background: rgba(0,200,81,0.1); padding: 1rem; border-radius: 10px; margin-bottom: 1rem;"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <?php if ($tab == 'schedule'): 
            // Get bookings for this coach
            $stmt = $pdo->prepare("SELECT b.*, u.name as client_name FROM bookings b LEFT JOIN users u ON b.user_id = u.id WHERE b.coach_id = ? ORDER BY b.booking_date DESC");
            $stmt->execute([$coach_id]);
            $bookings = $stmt->fetchAll();
        ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>Your Teaching Schedule</h2>
                <button type="button" onclick="openAddClassModal()" class="btn-auth" style="margin: 0; padding: 10px 20px; border-radius: 10px; width: auto; font-size: 1rem;"><i class="fas fa-plus-circle" style="margin-right: 8px;"></i> <?php echo __('create_new_class_btn'); ?></button>
            </div>
            <?php if(empty($bookings)): ?>
                <p style="color: var(--text-muted); margin-top: 1.5rem;">You have no booking requests yet.</p>
            <?php else: ?>
                <div class="table-responsive" style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($b['client_name']); ?></strong>
                                    <?php 
                                    $phone_to_show = !empty($b['user_phone']) ? $b['user_phone'] : '';
                                    if (!empty($phone_to_show)): ?>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;"><i class="fas fa-phone-alt" style="font-size: 0.75rem; color: var(--primary);"></i> <?php echo htmlspecialchars($phone_to_show); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($b['class_name'])): ?>
                                        <div style="margin-top: 4px;">
                                            <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.2px;">
                                                <i class="fas fa-box" style="margin-right: 3px;"></i> <?php echo htmlspecialchars($b['class_name']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($b['coach_message'])): ?>
                                        <div style="margin-top: 6px; font-size: 0.8rem; background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 6px 10px; border-radius: 8px;">
                                            <i class="fas fa-edit" style="margin-right: 4px; color: #d97706;"></i> <strong><?php echo __('coach_notes_title'); ?>:</strong> <?php echo htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $b['status']; ?>">
                                        <?php echo strtoupper($b['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <?php if($b['status'] == 'pending'): ?>
                                            <form method="POST" style="display: flex; flex-direction: column; gap: 5px; background: #f8fafc; padding: 10px; border-radius: 10px; border: 1px solid var(--border);">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <input type="text" name="coach_phone" required placeholder="Your Phone (10 digits)" pattern="[0-9]{10}" title="<?php echo __('phone_validation_msg'); ?>" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc; font-size: 0.8rem;">
                                                <input type="text" name="coach_address" required placeholder="Training Location/Address" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc; font-size: 0.8rem;">
                                                <button type="submit" name="confirm_booking" class="action-btn btn-add" style="font-size: 0.8rem; padding: 5px 10px; cursor: pointer; border: none; width: 100%; justify-content: center;">Confirm</button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" onclick='openClassModal(<?php echo json_encode([
                                                "id" => $b["id"],
                                                "booking_date" => date("Y-m-d\TH:i", strtotime($b["booking_date"])),
                                                "class_name" => $b["class_name"] ?? "",
                                                "specialty" => $b["specialty"] ?? "",
                                                "room_number" => $b["room_number"] ?? "",
                                                "training_plan" => $b["training_plan"] ?? "",
                                                "attendance" => $b["attendance"] ?? "pending",
                                                "coach_message" => $b["coach_message"] ?? ""
                                            ]); ?>)' class="action-btn" style="font-size: 0.85rem; padding: 8px 12px; cursor: pointer; border: none; width: 100%; justify-content: center; background: var(--primary); color: white; border-radius: 8px;">
                                                <i class="fas fa-edit" style="margin-right: 5px;"></i> <?php echo __('update_class_title'); ?>
                                            </button>

                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Class Details Modal -->
            <div id="classModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                <div style="background: white; padding: 2.5rem; border-radius: 20px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                    <button type="button" onclick="closeClassModal()" style="position: absolute; right: 20px; top: 20px; border: none; background: #f1f5f9; color: var(--text-muted); width: 35px; height: 35px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
                    
                    <h3 style="margin-top: 0; color: var(--text-main); font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;"><?php echo __('update_class_title'); ?></h3>
                    
                    <form method="POST">
                        <input type="hidden" name="booking_id" id="modal_booking_id">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><i class="far fa-clock" style="margin-right: 5px; color: var(--primary);"></i> <?php echo __('date'); ?> / Thời Gian Dạy</label>
                            <input type="datetime-local" name="booking_date" id="modal_booking_date" required style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('class_name_label'); ?></label>
                            <input type="text" name="class_name" id="modal_class_name" placeholder="<?php echo __('class_name_placeholder'); ?>" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('specialty'); ?></label>
                            <select name="specialty" id="modal_specialty" onchange="updateRoomOptions('modal_specialty', 'modal_room_number')" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                                <option value="Gym">Gym</option>
                                <option value="Muay">Muay</option>
                                <option value="Yoga">Yoga</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('room_number'); ?></label>
                            <select name="room_number" id="modal_room_number" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('training_plan_label'); ?></label>
                            <textarea name="training_plan" id="modal_training_plan" placeholder="<?php echo __('training_plan_placeholder'); ?>" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; height: 80px; outline: none; background: #f8fafc; font-family: inherit; resize: vertical;"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('coach_improvement_notes'); ?></label>
                            <textarea name="coach_message" id="modal_coach_message" placeholder="<?php echo __('coach_improvement_notes_placeholder'); ?>" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; height: 80px; outline: none; background: #f8fafc; font-family: inherit; resize: vertical;"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('attendance_label'); ?></label>
                            <select name="attendance" id="modal_attendance" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                                <option value="pending"><?php echo __('attendance_pending'); ?> (Pending)</option>
                                <option value="present"><?php echo __('attendance_present'); ?> (Present)</option>
                                <option value="absent"><?php echo __('attendance_absent'); ?> (Absent)</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" name="update_class_details" class="btn-auth" style="flex: 1; padding: 12px; font-size: 1rem; border-radius: 10px;"><i class="fas fa-save" style="margin-right: 5px;"></i> <?php echo __('save_class_btn'); ?></button>
                            <button type="submit" name="delete_class" onclick="return confirmOrderCustom(event, this.form, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn hủy / xóa buổi dạy này không?' : 'Are you sure you want to cancel / delete this class?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Lịch Dạy' : 'Cancel Class'; ?>', '❌');" class="btn-auth" style="background: #ef4444; flex: 1; padding: 12px; font-size: 1rem; border-radius: 10px; color: white;"><i class="fas fa-trash-alt" style="margin-right: 5px;"></i> Hủy Lịch Dạy</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Class Modal -->
            <div id="addClassModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                <div style="background: white; padding: 2.5rem; border-radius: 20px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                    <button type="button" onclick="closeAddClassModal()" style="position: absolute; right: 20px; top: 20px; border: none; background: #f1f5f9; color: var(--text-muted); width: 35px; height: 35px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
                    
                    <h3 style="margin-top: 0; color: var(--text-main); font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;"><?php echo __('create_class_title'); ?></h3>
                    
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('class_name_label'); ?></label>
                            <input type="text" name="class_name" placeholder="<?php echo __('class_name_placeholder'); ?>" required style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('date'); ?></label>
                            <input type="datetime-local" name="booking_date" required style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('specialty'); ?></label>
                            <select name="specialty" id="add_specialty" onchange="updateRoomOptions('add_specialty', 'add_room_number')" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                                <option value="Gym">Gym</option>
                                <option value="Muay">Muay</option>
                                <option value="Yoga">Yoga</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('room_number'); ?></label>
                            <select name="room_number" id="add_room_number" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600;">
                                <!-- Will be populated dynamically -->
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="font-weight: 600; color: var(--text-main);"><?php echo __('training_plan_label'); ?></label>
                            <textarea name="training_plan" placeholder="<?php echo __('training_plan_placeholder'); ?>" style="width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid #cbd5e1; height: 80px; outline: none; background: #f8fafc; font-family: inherit; resize: vertical;"></textarea>
                        </div>

                        <button type="submit" name="add_class" class="btn-auth" style="width: 100%; padding: 12px; font-size: 1.1rem; border-radius: 10px;"><?php echo __('create_class_btn'); ?></button>
                    </form>
                </div>
            </div>

            <script>
            function updateRoomOptions(specId, roomId) {
                const specVal = document.getElementById(specId).value;
                const roomSelect = document.getElementById(roomId);
                roomSelect.innerHTML = '';
                
                const specLower = specVal.toLowerCase();
                let rooms = [];
                if (specLower.includes('gym')) {
                    rooms = ['Room 1', 'Room 2', 'Room 3'];
                } else if (specLower.includes('muay')) {
                    rooms = ['Room 4', 'Room 5', 'Room 6', 'Room 7'];
                } else if (specLower.includes('yoga')) {
                    rooms = ['Room 8', 'Room 9', 'Room 10'];
                } else {
                    rooms = ['Room 1', 'Room 2', 'Room 3', 'Room 4', 'Room 5', 'Room 6', 'Room 7', 'Room 8', 'Room 9', 'Room 10'];
                }
                
                rooms.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r;
                    opt.textContent = r;
                    roomSelect.appendChild(opt);
                });
            }

            function openClassModal(data) {
                document.getElementById('modal_booking_id').value = data.id;
                document.getElementById('modal_booking_date').value = data.booking_date || '';
                document.getElementById('modal_class_name').value = data.class_name;
                
                // set specialty select value
                const specSelect = document.getElementById('modal_specialty');
                let savedSpec = data.specialty || 'Gym';
                // capitalize to match option values
                if (savedSpec.toLowerCase() === 'gym') savedSpec = 'Gym';
                else if (savedSpec.toLowerCase() === 'muay') savedSpec = 'Muay';
                else if (savedSpec.toLowerCase() === 'yoga') savedSpec = 'Yoga';
                specSelect.value = savedSpec;
                
                // update room options based on specialty
                updateRoomOptions('modal_specialty', 'modal_room_number');
                
                // set room select value
                const roomSelect = document.getElementById('modal_room_number');
                roomSelect.value = data.room_number;
                
                document.getElementById('modal_training_plan').value = data.training_plan;
                document.getElementById('modal_coach_message').value = data.coach_message || '';
                document.getElementById('modal_attendance').value = data.attendance;
                document.getElementById('classModal').style.display = 'flex';
            }
            function closeClassModal() {
                document.getElementById('classModal').style.display = 'none';
            }
            function openAddClassModal() {
                updateRoomOptions('add_specialty', 'add_room_number');
                document.getElementById('addClassModal').style.display = 'flex';
            }
            function closeAddClassModal() {
                document.getElementById('addClassModal').style.display = 'none';
            }
            </script>




        <?php elseif ($tab == 'report'): 
            $selected_year = $_GET['year'] ?? date('Y');
            
            $stmt_rep = $pdo->prepare("SELECT b.*, u.name as client_name, u.email as client_email, b.user_phone as client_phone
                                       FROM bookings b 
                                       LEFT JOIN users u ON b.user_id = u.id 
                                       WHERE b.coach_id = ? AND YEAR(b.booking_date) = ?
                                       ORDER BY b.booking_date DESC");
            $stmt_rep->execute([$coach_id, $selected_year]);
            $yearly_bookings = $stmt_rep->fetchAll();
            $total_bookings = count($yearly_bookings);
        ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h2>Yearly Report</h2>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Total Bookings in <?php echo $selected_year; ?>: <strong style="color: var(--primary); font-size: 1.2rem;"><?php echo $total_bookings; ?></strong></p>
                </div>
                <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="tab" value="report">
                    <label style="font-weight: 600;">Select Year:</label>
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

            <?php if(empty($yearly_bookings)): ?>
                <p style="color: var(--text-muted);">No bookings found for <?php echo $selected_year; ?>.</p>
            <?php else: ?>
                <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 1.2rem; border-radius: 15px 0 0 15px; white-space: nowrap; text-align: left;">Date & Time</th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;">Client Name</th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;">Email</th>
                                <th style="padding: 1.2rem; white-space: nowrap; text-align: left;">Phone</th>
                                <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; white-space: nowrap; text-align: left;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($yearly_bookings as $bk): ?>
                            <tr style="background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: transform 0.2s;">
                                <td style="padding: 1.2rem; border-radius: 15px 0 0 15px; border: 1px solid #f1f5f9; border-right: none;">
                                    <?php echo date('d/m/Y H:i', strtotime($bk['booking_date'])); ?>
                                </td>
                                <td style="padding: 1.2rem; border: 1px solid #f1f5f9; border-right: none; border-left: none; font-weight: 600;">
                                    <?php echo htmlspecialchars($bk['client_name']); ?>
                                    <?php if (!empty($bk['class_name'])): ?>
                                        <div style="margin-top: 4px;">
                                            <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.2px;">
                                                <i class="fas fa-box" style="margin-right: 3px;"></i> <?php echo htmlspecialchars($bk['class_name']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1.2rem; border: 1px solid #f1f5f9; border-right: none; border-left: none; color: #64748b; word-break: break-all;">
                                    <?php echo htmlspecialchars($bk['client_email']); ?>
                                </td>
                                <td style="padding: 1.2rem; border: 1px solid #f1f5f9; border-right: none; border-left: none;">
                                    <?php echo htmlspecialchars($bk['client_phone'] ?: 'N/A'); ?>
                                </td>
                                <td style="padding: 1.2rem; border-radius: 0 15px 15px 0; border: 1px solid #f1f5f9; border-left: none;">
                                    <span class="status-badge status-<?php echo $bk['status']; ?>" style="font-size: 0.75rem; padding: 6px 12px;">
                                        <?php echo strtoupper($bk['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php elseif ($tab == 'profile'): ?>
            <h2 style="font-weight: 800; color: var(--text-main);">Update Personal Information</h2>
            <form method="POST" style="margin-top: 2rem; max-width: 650px; background: white; padding: 2.5rem; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                <div class="form-group">
                    <label style="font-weight: 600;">Specialty</label>
                    <input type="text" name="specialty" value="<?php echo $c_info['specialty']; ?>" required style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600;">Hourly Rate ($)</label>
                    <input type="number" step="0.01" name="hourly_rate" value="<?php echo $c_info['hourly_rate']; ?>" required style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600;">Introduction (Bio)</label>
                    <textarea name="bio" style="width: 100%; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; color: var(--text-main); padding: 1.2rem; min-height: 180px; outline: none; transition: var(--transition); font-size: 1rem;"><?php echo $c_info['bio']; ?></textarea>
                </div>
                <button type="submit" name="update_bio" class="btn-auth" style="margin-top: 1rem;">Save Profile Changes</button>
            </form>

        <?php elseif ($tab == 'calendar'): 
            // Weekly roster logic
            $week_offset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;
            $monday = new DateTime("Monday this week");
            if ($week_offset !== 0) {
                $monday->modify("$week_offset week");
            }
            $sunday = clone $monday;
            $sunday->modify('+6 days');

            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $date = clone $monday;
                $date->modify("+$i days");
                $days[] = $date;
            }

            // Fetch all coaches
            $stmt_c = $pdo->query("SELECT c.id, u.name, COALESCE(c.zalo, u.email) as contact FROM coaches c JOIN users u ON c.user_id = u.id");
            $coaches = $stmt_c->fetchAll();

            // Fetch all bookings for this week
            $start_date_str = $monday->format('Y-m-d 00:00:00');
            $end_date_str = $sunday->format('Y-m-d 23:59:59');

            $stmt_b = $pdo->prepare("
                SELECT b.*, u.name as client_name, COALESCE(b.user_phone, u.phone) as client_phone 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                WHERE b.booking_date >= ? AND b.booking_date <= ? AND b.status != 'cancelled'
            ");
            $stmt_b->execute([$start_date_str, $end_date_str]);
            $bookings = $stmt_b->fetchAll();

            // Group bookings by coach and date
            $roster = [];
            foreach ($bookings as $b) {
                $date = date('Y-m-d', strtotime($b['booking_date']));
                $time = date('H:i', strtotime($b['booking_date']));
                $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
                
                $client_name = !empty($b['client_name']) ? htmlspecialchars($b['client_name']) : (!empty($b['class_name']) ? htmlspecialchars($b['class_name']) : __('open_class'));
                $client_phone = !empty($b['client_phone']) ? htmlspecialchars($b['client_phone']) : '';
                
                $att_color = $b['attendance'] == 'present' ? 'color: #16a34a;' : ($b['attendance'] == 'absent' ? 'color: #dc2626;' : 'color: #f59e0b;');
                $att_text = $b['attendance'] == 'present' ? __('present_label') : ($b['attendance'] == 'absent' ? __('absent_label') : __('pending'));

                $box_html = "<div style='background: #ffffff; padding: 8px 10px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 6px; text-align: left; box-shadow: 0 2px 4px rgba(0,0,0,0.03);'>";
                $box_html .= "<div><strong style='color: var(--primary); font-size: 0.85rem;'><i class='far fa-clock'></i> {$time} - {$end_time}</strong></div>";
                $box_html .= "<div style='font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-top: 4px;'><i class='fas fa-user' style='color: #64748b; width: 14px;'></i> {$client_name}</div>";
                if ($client_phone) {
                    $box_html .= "<div style='font-size: 0.75rem; color: #475569; margin-top: 2px;'><i class='fas fa-phone-alt' style='color: #2563eb; width: 14px;'></i> {$client_phone}</div>";
                }
                if (!empty($b['class_name'])) {
                    $box_html .= "<div style='font-size: 0.75rem; color: #1d4ed8; font-weight: 600; margin-top: 2px;'><i class='fas fa-chalkboard-teacher' style='width: 14px;'></i> " . htmlspecialchars($b['class_name']) . "</div>";
                }
                if (!empty($b['room_number'])) {
                    $box_html .= "<div style='font-size: 0.75rem; color: #475569; margin-top: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> " . htmlspecialchars($b['room_number']) . "</div>";
                }
                $box_html .= "<div style='font-size: 0.72rem; {$att_color} font-weight: 700; margin-top: 3px;'><i class='fas fa-user-check' style='width: 14px;'></i> {$att_text}</div>";
                if (!empty($b['coach_message'])) {
                    $box_html .= "<div style='font-size: 0.72rem; background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 4px 6px; border-radius: 6px; margin-top: 5px;'><i class='fas fa-edit' style='color: #d97706; margin-right: 3px;'></i> <strong>" . __('coach_notes_title') . ":</strong> " . htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])) . "</div>";
                }
                $box_html .= "</div>";
                
                $roster[$b['coach_id']][$date][] = $box_html;
            }

            $day_names = ['THỨ 2', 'THỨ 3', 'THỨ 4', 'THỨ 5', 'THỨ 6', 'THỨ 7', 'CHỦ NHẬT'];
        ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 2rem; text-transform: uppercase; font-weight: 900;">STAFF WEEKLY ROSTER</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=calendar&week_offset=<?php echo $week_offset - 1; ?>" class="btn-auth" style="padding: 10px 15px; margin: 0; width: auto;"><i class="fas fa-chevron-left"></i> Tuần trước</a>
                    <span style="padding: 10px 15px; background: white; border-radius: 10px; font-weight: bold; border: 1px solid var(--border);">
                        <?php echo $monday->format('d/m/Y') . ' - ' . $sunday->format('d/m/Y'); ?>
                    </span>
                    <a href="/Gym Manager/dashboards/coach_dashboard.php?tab=calendar&week_offset=<?php echo $week_offset + 1; ?>" class="btn-auth" style="padding: 10px 15px; margin: 0; width: auto;">Tuần sau <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            
            <div style="background: white; padding: 1rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-family: Arial, sans-serif;">
                    <thead>
                        <tr>
                            <th style="border: 2px solid #000; padding: 12px; background: #ffeaa7; font-weight: 800; font-size: 1.1rem; color: #000;" rowspan="2">TÊN NHÂN VIÊN</th>
                            <th style="border: 2px solid #000; padding: 12px; background: #ffeaa7; font-weight: 800; font-size: 1.1rem; color: #000;" rowspan="2">SỐ ĐIỆN THOẠI</th>
                            <?php foreach($day_names as $idx => $dname): ?>
                                <th style="border: 2px solid #000; padding: 10px; background: #ffeaa7; font-weight: 800; font-size: 1.1rem; color: #000;"><?php echo $dname; ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach($days as $d): ?>
                                <th style="border: 2px solid #000; padding: 8px; background: #ffeaa7; font-weight: 800; font-size: 1rem; color: #000;"><?php echo $d->format('d-M'); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($coaches as $idx => $c): ?>
                            <tr>
                                <td style="border: 2px solid #000; padding: 12px; text-align: left; font-weight: 800; background: <?php echo ($idx % 2 == 0) ? '#fff' : '#fdf6e3'; ?>; color: #000; white-space: nowrap;"><?php echo htmlspecialchars($c['name']); ?></td>
                                <td style="border: 2px solid #000; padding: 12px; font-weight: 800; background: <?php echo ($idx % 2 == 0) ? '#fff' : '#fdf6e3'; ?>; color: #000;"><?php echo htmlspecialchars($c['contact']); ?></td>
                                <?php foreach($days as $d): 
                                    $d_str = $d->format('Y-m-d');
                                    $has_booking = isset($roster[$c['id']][$d_str]);
                                    if ($has_booking) {
                                        $times = implode("<br>", $roster[$c['id']][$d_str]);
                                        echo "<td style='border: 2px solid #000; padding: 10px; background: #fff; color: #000; font-weight: 600;'>{$times}</td>";
                                    } else {
                                        $bg = ($d->format('N') % 2 == 0) ? '#d5ffd5' : '#ffd5d5'; 
                                        echo "<td style='border: 2px solid #000; padding: 10px; background: {$bg}; color: #000; font-weight: 800;'>OFF</td>";
                                    }
                                endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /Gym Manager/dashboards/admin.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_schedule'])) {
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['manager', 'admin'])) {
        $booking_id = (int)$_POST['booking_id'];
        $new_coach_id = (int)$_POST['coach_id'];
        $new_booking_date = $_POST['booking_date'];
        $new_status = $_POST['status'];
        $new_attendance = $_POST['attendance'] ?? 'pending';
        $new_coach_message = trim($_POST['coach_message'] ?? '');
        $week_offset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;
        
        try {
            // Server-side check: Do not allow editing completed or past sessions!
            $stmt_chk_b = $pdo->prepare("SELECT booking_date, status, attendance FROM bookings WHERE id = ?");
            $stmt_chk_b->execute([$booking_id]);
            $existing_b = $stmt_chk_b->fetch();
            if ($existing_b) {
                $e_start = strtotime($existing_b['booking_date']);
                $e_end = $e_start + 3600;
                $e_is_attended = ($existing_b['attendance'] === 'present' || $existing_b['status'] === 'completed');
                $e_is_past = (time() > $e_end);

                if (($e_is_attended || $e_is_past) && $existing_b['status'] !== 'cancelled') {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_past';</script>";
                    exit;
                }
            }

            $stmt_upd = $pdo->prepare("
                UPDATE bookings 
                SET coach_id = ?, 
                    booking_date = ?, 
                    status = ?, 
                    attendance = ?,
                    coach_message = ?, 
                    coach_seen = 0, 
                    user_seen = 0 
                WHERE id = ?
            ");
            $stmt_upd->execute([
                $new_coach_id,
                $new_booking_date,
                $new_status,
                $new_attendance,
                $new_coach_message,
                $booking_id
            ]);
            
            // --- Send Email Notification via PHPMailer to User & Coach ---
            try {
                // Fetch student info
                $stmt_usr = $pdo->prepare("SELECT u.name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
                $stmt_usr->execute([$booking_id]);
                $student_info = $stmt_usr->fetch();

                // Fetch new coach info
                $stmt_cch = $pdo->prepare("SELECT u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                $stmt_cch->execute([$new_coach_id]);
                $coach_info = $stmt_cch->fetch();

                if ($student_info && !empty($student_info['email'])) {
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

                    $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');
                    $mail->addAddress($student_info['email'], $student_info['name']);

                    if ($coach_info && !empty($coach_info['email'])) {
                        $mail->addCC($coach_info['email'], $coach_info['name']);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "⚡ [Gym Manager] Substitute Coach Assignment / Phân Công HLV Dạy Thế";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff;'>";
                    $mail_body .= "<h2 style='color: #1c2340; border-bottom: 2px solid #b08d57; padding-bottom: 10px; margin-top: 0;'>⚡ Substitute Coach Assigned / Phân Công HLV Dạy Thế</h2>";
                    $mail_body .= "<p style='color: #475569; font-size: 15px;'>Notice: The Manager has assigned a substitute coach for your training session / Quản lý đã xếp HLV thế cho buổi tập của bạn.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 18px; border-left: 4px solid #b08d57; border-radius: 8px; margin: 20px 0;'>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Client / Học viên:</strong> " . htmlspecialchars($student_info['name']) . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Assigned Coach / HLV Phụ Trách Dạy:</strong> <span style='color: #16a34a; font-weight: bold;'>" . htmlspecialchars($coach_info['name'] ?? 'N/A') . "</span></p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Date & Time / Thời gian tập:</strong> <span style='color: #2563eb; font-weight: bold;'>" . date('l, d/m/Y - H:i', strtotime($new_booking_date)) . "</span></p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Status / Trạng thái:</strong> <span style='color: #16a34a; font-weight: bold;'>" . strtoupper($new_status) . "</span></p>";
                    if (!empty($new_coach_message)) {
                        $mail_body .= "<p style='margin: 6px 0;'><strong>Manager Note / Ghi chú Quản lý:</strong> " . htmlspecialchars($new_coach_message) . "</p>";
                    }
                    $mail_body .= "</div>";
                    $mail_body .= "<p style='color: #64748b; font-size: 13px;'>Please log in to Gym Manager system for more details / Vui lòng đăng nhập hệ thống Gym Manager để xem chi tiết.</p>";
                    $mail_body .= "</div>";
                    
                    $mail->Body = $mail_body;
                    $mail->send();
                }
            } catch (Exception $mailEx) {
                error_log("Calendar mail error: " . $mailEx->getMessage());
            }

            echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=updated';</script>";
            exit;
        } catch (Exception $e) {
            error_log("Calendar update error: " . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user_cancel_booking'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $booking_id = (int)$_POST['booking_id'];
        $reason = trim($_POST['cancel_reason'] ?? '');
        $week_offset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;
        
        try {
            $stmt_check = $pdo->prepare("SELECT id, status, attendance, booking_date, class_name, user_id FROM bookings WHERE id = ?");
            $stmt_check->execute([$booking_id]);
            $b_check = $stmt_check->fetch();
            
            if ($b_check && $b_check['status'] !== 'cancelled') {
                if ($b_check['user_id'] != $user_id && !in_array($_SESSION['role'] ?? '', ['coach', 'manager'])) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_unauthorized';</script>";
                    exit;
                }

                if (!empty($b_check['class_name'])) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_coach_class';</script>";
                    exit;
                }

                if (($b_check['attendance'] ?? '') === 'present' || ($b_check['attendance'] ?? '') === 'absent' || ($b_check['status'] ?? '') === 'completed') {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_already_attended';</script>";
                    exit;
                }

                if (strtotime($b_check['booking_date']) <= time()) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_past';</script>";
                    exit;
                }

                $stmt_upd = $pdo->prepare("UPDATE bookings SET status = 'cancelled', coach_seen = 0, user_seen = 1 WHERE id = ?");
                $stmt_upd->execute([$booking_id]);

                require_once __DIR__ . '/../includes/booking_mailer.php';
                sendBookingCancellationEmail($pdo, $booking_id, $reason);

                echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=cancelled';</script>";
                exit;
            }
        } catch (Exception $e) {
            error_log("Calendar user cancellation error: " . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user_reschedule_booking'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $booking_id = (int)$_POST['booking_id'];
        $new_date = $_POST['new_booking_date'];
        $reason = trim($_POST['reschedule_reason'] ?? '');
        $week_offset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;
        
        try {
            $stmt_check = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                WHERE b.id = ?
            ");
            $stmt_check->execute([$booking_id]);
            $b_check = $stmt_check->fetch();
            
            if ($b_check) {
                if ($b_check['user_id'] != $user_id && !in_array($_SESSION['role'] ?? '', ['coach', 'manager', 'admin'])) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_unauthorized';</script>";
                    exit;
                }

                if (!empty($b_check['class_name'])) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_coach_class';</script>";
                    exit;
                }

                if (($b_check['attendance'] ?? '') === 'present' || ($b_check['attendance'] ?? '') === 'absent' || ($b_check['status'] ?? '') === 'completed') {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_already_attended';</script>";
                    exit;
                }

                if (strtotime($b_check['booking_date']) <= time()) {
                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=err_past';</script>";
                    exit;
                }

                $old_date_formatted = date('d/m/Y H:i', strtotime($b_check['booking_date']));
                $new_date_formatted = date('d/m/Y H:i', strtotime($new_date));

                $stmt_upd = $pdo->prepare("UPDATE bookings SET booking_date = ?, coach_message = ?, coach_seen = 0, user_seen = 1 WHERE id = ?");
                $stmt_upd->execute([$new_date, $reason, $booking_id]);

                // Send email to User, Coach, Manager
                try {
                    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                    $stmt_c = $pdo->prepare("SELECT u.name as coach_name, u.email as coach_email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                    $stmt_c->execute([$b_check['coach_id']]);
                    $coach_info = $stmt_c->fetch();

                    $stmt_m = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
                    $stmt_m->execute();
                    $managers = $stmt_m->fetchAll();

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lamtuoi155@gmail.com';     
                    $mail->Password   = 'hpst uazz wdsq sbnf';   
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');
                    
                    if (!empty($b_check['client_email'])) {
                        $mail->addAddress($b_check['client_email'], $b_check['client_name']);
                    }
                    if ($coach_info && !empty($coach_info['coach_email'])) {
                        $mail->addCC($coach_info['coach_email'], $coach_info['coach_name']);
                    }
                    foreach ($managers as $mgr) {
                        if (!empty($mgr['email'])) {
                            $mail->addCC($mgr['email'], $mgr['name']);
                        }
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "📅 [Gym Manager] Workout Rescheduled / Đã Đổi Lịch Tập - Booking #" . $booking_id;

                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff;'>";
                    $mail_body .= "<h2 style='color: #1a2744; border-bottom: 2px solid #c9a84c; padding-bottom: 10px; margin-top: 0;'>📅 Schedule Rescheduled / Đã Đổi Lịch Tập</h2>";
                    $mail_body .= "<p style='color: #475569; font-size: 15px;'>Notice: The training session date/time has been updated / Buổi tập đã được cập nhật lịch mới.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 18px; border-left: 4px solid #c9a84c; border-radius: 8px; margin: 20px 0;'>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Booking ID:</strong> #" . $booking_id . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Client / Học viên:</strong> " . htmlspecialchars($b_check['client_name']) . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Coach / HLV Phụ Trách:</strong> " . htmlspecialchars($coach_info['coach_name'] ?? 'N/A') . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Previous Time / Thời gian cũ:</strong> <span style='color: #94a3b8; text-decoration: line-through;'>" . $old_date_formatted . "</span></p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>NEW Time / Thời gian MỚI:</strong> <span style='color: #16a34a; font-weight: bold; font-size: 1.05rem;'>" . $new_date_formatted . "</span></p>";
                    if (!empty($reason)) {
                        $mail_body .= "<p style='margin: 6px 0;'><strong>Note / Lý do đổi lịch:</strong> " . htmlspecialchars($reason) . "</p>";
                    }
                    $mail_body .= "</div>";
                    $mail_body .= "<p style='color: #64748b; font-size: 13px;'>This email was automatically sent to Client, Coach, and Manager.</p>";
                    $mail_body .= "</div>";

                    $mail->Body = $mail_body;
                    $mail->send();
                } catch (Exception $mailEx) {
                    error_log("Calendar reschedule mail error: " . $mailEx->getMessage());
                }

                echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=rescheduled';</script>";
                exit;
            }
        } catch (Exception $e) {
            error_log("Calendar user reschedule error: " . $e->getMessage());
        }
    }
}

// ─── Smart Coach Attendance Handler ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_coach_attendance'])) {
    // Only Coach, Manager, Admin can mark attendance
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
        $booking_id    = (int)$_POST['booking_id'];
        $att_value     = $_POST['attendance_value']; // 'present' or 'absent'
        $session_num   = isset($_POST['session_num']) ? (int)$_POST['session_num'] : 0;
        $week_offset   = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;

        try {
            // 1. Fetch booking & client info
            $stmt_bk = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, u.phone as client_phone
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.id = ?
            ");
            $stmt_bk->execute([$booking_id]);
            $bk = $stmt_bk->fetch();

            if (!$bk) {
                echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}';</script>";
                exit;
            }

            $client_user_id = (int)$bk['user_id'];

            // 2. Check if client has an ACTIVE membership + PT session quota
            $has_membership  = false;
            $sub_id          = null;
            $pt_total        = 0;
            $pt_used         = 0;
            $pt_remaining    = 0;
            $plan_name       = '';

            if ($client_user_id > 0) {
                $stmt_mem = $pdo->prepare("
                    SELECT ms.id, ms.pt_sessions_total, ms.pt_sessions_used, mp.name as plan_name, mp.pt_sessions as plan_pt_sessions
                    FROM member_subscriptions ms
                    JOIN membership_plans mp ON ms.plan_id = mp.id
                    WHERE ms.user_id = ? AND ms.status = 'active' AND ms.end_date >= CURDATE()
                    ORDER BY ms.created_at DESC
                    LIMIT 1
                ");
                $stmt_mem->execute([$client_user_id]);
                $mem_row = $stmt_mem->fetch();

                if ($mem_row) {
                    $has_membership = true;
                    $sub_id         = (int)$mem_row['id'];
                    $plan_name      = $mem_row['plan_name'];

                    // Init pt_sessions_total from plan if not yet set
                    if ((int)$mem_row['pt_sessions_total'] === 0 && (int)$mem_row['plan_pt_sessions'] > 0) {
                        $pdo->prepare("UPDATE member_subscriptions SET pt_sessions_total = ? WHERE id = ?")
                            ->execute([$mem_row['plan_pt_sessions'], $sub_id]);
                        $pt_total = (int)$mem_row['plan_pt_sessions'];
                    } else {
                        $pt_total = (int)$mem_row['pt_sessions_total'];
                    }
                    $pt_used      = (int)$mem_row['pt_sessions_used'];
                    $pt_remaining = max(0, $pt_total - $pt_used);
                }
            }

            // 3. Apply logic based on membership status
            if ($att_value === 'present') {
                // ── Always allow marking present ──────────────────────────────
                $pdo->prepare("UPDATE bookings SET attendance = 'present', status = 'confirmed', coach_seen = 0, user_seen = 0 WHERE id = ?")
                    ->execute([$booking_id]);

                // Increment or update PT sessions used for membership clients
                if ($has_membership && $sub_id && $pt_total > 0) {
                    if ($session_num > 0) {
                        $pt_used = min($session_num, $pt_total);
                        $pdo->prepare("UPDATE member_subscriptions SET pt_sessions_used = ? WHERE id = ?")
                            ->execute([$pt_used, $sub_id]);
                    } else {
                        $pdo->prepare("UPDATE member_subscriptions SET pt_sessions_used = LEAST(pt_sessions_used + 1, pt_sessions_total) WHERE id = ?")
                            ->execute([$sub_id]);
                        $pt_used = min($pt_used + 1, $pt_total);
                    }
                    $pt_remaining = max(0, $pt_total - $pt_used);

                    // Check if package is now expired (0 sessions remaining)
                    if ($pt_remaining === 0 || $pt_used >= $pt_total) {
                        $pdo->prepare("UPDATE member_subscriptions SET status = 'expired' WHERE id = ?")->execute([$sub_id]);
                        require_once __DIR__ . '/../includes/booking_mailer.php';
                        sendMembershipExpirationEmail($pdo, $sub_id);
                    }
                }

                // Send attendance confirmation email
                try {
                    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
                    $stmt_ci = $pdo->prepare("SELECT u.name as coach_name, u.email as coach_email FROM bookings b LEFT JOIN coaches c ON b.coach_id = c.id LEFT JOIN users u ON c.user_id = u.id WHERE b.id = ?");
                    $stmt_ci->execute([$booking_id]);
                    $coach_row = $stmt_ci->fetch();

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8'; $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
                    $mail->Username = 'lamtuoi155@gmail.com'; $mail->Password = 'hpst uazz wdsq sbnf';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
                    $mail->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');
                    if (!empty($bk['client_email'])) $mail->addAddress($bk['client_email'], $bk['client_name']);
                    if (!empty($coach_row['coach_email'])) $mail->addCC($coach_row['coach_email'], $coach_row['coach_name']);
                    $mail->isHTML(true);
                    $mail->Subject = "✅ [Gym Manager] Attendance Confirmed / Xác Nhận Có Mặt - Booking #" . $booking_id;
                    $pt_session_block = '';
                    if ($has_membership && $pt_total > 0) {
                        $pt_session_block = "<div style='background:#eff6ff;padding:12px 16px;border-radius:10px;border:1px solid #bfdbfe;margin-top:10px;'>
                            <p style='margin:4px 0;font-weight:700;color:#1d4ed8;'>💳 Membership: " . htmlspecialchars($plan_name) . "</p>
                            <p style='margin:4px 0;'>PT Sessions Used: <strong>" . $pt_used . " / " . $pt_total . "</strong></p>
                            <p style='margin:4px 0;'>Remaining: <strong style='color:" . ($pt_remaining > 0 ? '#16a34a' : '#dc2626') . ";'>" . $pt_remaining . " sessions left</strong></p>
                        </div>";
                    }
                    $mail->Body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;'>
                        <h2 style='color:#16a34a;border-bottom:2px solid #16a34a;padding-bottom:10px;'>✅ Attendance Confirmed / Xác Nhận Có Mặt</h2>
                        <p style='color:#475569;'>Client <strong>" . htmlspecialchars($bk['client_name']) . "</strong> has been marked <strong style='color:#16a34a;'>PRESENT</strong> for their session.</p>
                        <div style='background:#f0fdf4;padding:16px;border-left:4px solid #16a34a;border-radius:8px;margin:16px 0;'>
                            <p style='margin:5px 0;'><strong>Booking ID:</strong> #" . $booking_id . "</p>
                            <p style='margin:5px 0;'><strong>Date & Time:</strong> " . date('l, d/m/Y - H:i', strtotime($bk['booking_date'])) . "</p>
                            <p style='margin:5px 0;'><strong>Attendance:</strong> <span style='color:#16a34a;font-weight:bold;'>PRESENT ✅</span></p>
                        </div>
                        {$pt_session_block}
                        <p style='color:#64748b;font-size:13px;margin-top:16px;'>This is an automated notification from Gym Manager System.</p>
                    </div>";
                    $mail->send();
                } catch (Exception $mailEx) {
                    error_log("Attendance present email error: " . $mailEx->getMessage());
                }

                echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=attended';</script>";
                exit;

            } elseif ($att_value === 'absent') {
                if ($has_membership) {
                    // ── Membership client: mark absent, KEEP booking, CONSUME 1 PT session ─
                    $pdo->prepare("UPDATE bookings SET attendance = 'absent', coach_seen = 0, user_seen = 0 WHERE id = ?")
                        ->execute([$booking_id]);

                    // Absent still counts as 1 used PT session (client missed their slot)
                    if ($sub_id && $pt_total > 0) {
                        if ($session_num > 0) {
                            $pt_used = min($session_num, $pt_total);
                            $pdo->prepare("UPDATE member_subscriptions SET pt_sessions_used = ? WHERE id = ?")
                                ->execute([$pt_used, $sub_id]);
                        } else {
                            $pdo->prepare("UPDATE member_subscriptions SET pt_sessions_used = LEAST(pt_sessions_used + 1, pt_sessions_total) WHERE id = ?")
                                ->execute([$sub_id]);
                            $pt_used = min($pt_used + 1, $pt_total);
                        }
                        $pt_remaining = max(0, $pt_total - $pt_used);

                        // Check if package is now expired (0 sessions remaining)
                        if ($pt_remaining === 0 || $pt_used >= $pt_total) {
                            $pdo->prepare("UPDATE member_subscriptions SET status = 'expired' WHERE id = ?")->execute([$sub_id]);
                            require_once __DIR__ . '/../includes/booking_mailer.php';
                            sendMembershipExpirationEmail($pdo, $sub_id);
                        }
                    }

                    // Send absent notification email with PT session remaining count
                    try {
                        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
                        $stmt_ci2 = $pdo->prepare("SELECT u.name as coach_name, u.email as coach_email FROM bookings b LEFT JOIN coaches c ON b.coach_id = c.id LEFT JOIN users u ON c.user_id = u.id WHERE b.id = ?");
                        $stmt_ci2->execute([$booking_id]);
                        $coach_row2 = $stmt_ci2->fetch();

                        $mail2 = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail2->CharSet = 'UTF-8'; $mail2->isSMTP();
                        $mail2->Host = 'smtp.gmail.com'; $mail2->SMTPAuth = true;
                        $mail2->Username = 'lamtuoi155@gmail.com'; $mail2->Password = 'hpst uazz wdsq sbnf';
                        $mail2->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; $mail2->Port = 587;
                        $mail2->setFrom('lamtuoi155@gmail.com', 'Gym Manager System');
                        if (!empty($bk['client_email'])) $mail2->addAddress($bk['client_email'], $bk['client_name']);
                        if (!empty($coach_row2['coach_email'])) $mail2->addCC($coach_row2['coach_email'], $coach_row2['coach_name']);
                        $mail2->isHTML(true);
                        $mail2->Subject = "⚠️ [Gym Manager] Absent – PT Session Deducted / Vắng Mặt – Trừ 1 Buổi - Booking #" . $booking_id;

                        $pt_block_absent = ($pt_total > 0) ? "
                            <div style='background:#fef9c3;padding:12px 16px;border-radius:10px;border:1px solid #fde047;margin-top:10px;'>
                                <p style='margin:4px 0;font-weight:700;color:#854d0e;'>💳 Membership: " . htmlspecialchars($plan_name) . "</p>
                                <p style='margin:4px 0;'>PT Sessions Used: <strong>" . $pt_used . " / " . $pt_total . "</strong> — 1 buổi vừa bị trừ do vắng mặt.</p>
                                <p style='margin:4px 0;'>Remaining: <strong style='color:" . ($pt_remaining > 0 ? '#16a34a' : '#dc2626') . ";'>" . $pt_remaining . " sessions left</strong></p>
                            </div>" : "";

                        $mail2->Body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:25px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;'>
                            <h2 style='color:#d97706;border-bottom:2px solid #d97706;padding-bottom:10px;'>⚠️ Absent Noted – 1 PT Session Deducted / Vắng Mặt – Trừ 1 Buổi</h2>
                            <p style='color:#475569;'>Client <strong>" . htmlspecialchars($bk['client_name']) . "</strong> was marked <strong style='color:#d97706;'>ABSENT</strong>. Because this client did not attend, <strong>1 PT session has been deducted</strong> from their membership quota.</p>
                            <div style='background:#fffbeb;padding:16px;border-left:4px solid #d97706;border-radius:8px;margin:16px 0;'>
                                <p style='margin:5px 0;'><strong>Booking ID:</strong> #" . $booking_id . "</p>
                                <p style='margin:5px 0;'><strong>Date & Time:</strong> " . date('l, d/m/Y - H:i', strtotime($bk['booking_date'])) . "</p>
                                <p style='margin:5px 0;'><strong>Attendance:</strong> <span style='color:#d97706;font-weight:bold;'>ABSENT ⚠️</span></p>
                                <p style='margin:5px 0;'><strong>Note:</strong> Session is recorded (not cancelled) but the slot has been counted.</p>
                            </div>
                            {$pt_block_absent}
                            <p style='color:#64748b;font-size:13px;margin-top:16px;'>This is an automated notification from Gym Manager System.</p>
                        </div>";
                        $mail2->send();
                    } catch (Exception $mailEx) {
                        error_log("Attendance absent (member) email error: " . $mailEx->getMessage());
                    }

                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=absent_member&rem={$pt_remaining}&tot={$pt_total}';</script>";
                    exit;

                } else {
                    // ── Regular client (no membership): auto-CANCEL the booking ─
                    $pdo->prepare("UPDATE bookings SET attendance = 'absent', status = 'cancelled', coach_seen = 0, user_seen = 0 WHERE id = ?")
                        ->execute([$booking_id]);

                    // Send auto-cancellation email
                    require_once __DIR__ . '/../includes/booking_mailer.php';
                    sendBookingCancellationEmail($pdo, $booking_id, 'Auto-cancelled: Client absent without active membership / Tự động hủy: Khách hàng vắng mặt, không có gói thành viên.');

                    echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=absent_cancelled';</script>";
                    exit;
                }
            }

        } catch (Exception $e) {
            error_log("Coach attendance error: " . $e->getMessage());
        }
    }
}

// Legacy fallback (keep for compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_mark_attended'])) {
    if (isset($_SESSION['user_id'])) {
        $booking_id = (int)$_POST['booking_id'];
        $week_offset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;
        try {
            $pdo->prepare("UPDATE bookings SET attendance = 'present', status = 'confirmed', coach_seen = 0, user_seen = 0 WHERE id = ?")
                ->execute([$booking_id]);
            echo "<script>window.location.href='/Gym Manager/features/calendar.php?week_offset={$week_offset}&msg=attended';</script>";
            exit;
        } catch (Exception $e) { error_log("Mark attended error: " . $e->getMessage()); }
    }
}
?>

<div style="padding-top: 120px; background: var(--bg-cream); min-height: 100vh;">
<section id="calendar" style="padding: 4rem 2rem; max-width: 1440px; margin: 0 auto;">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'attended'): ?>
        <div style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);">
            <i class="fas fa-check-circle" style="font-size: 1.3rem;"></i>
            <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Đã xác nhận tham gia buổi tập thành công! Lịch tập đã được ghi nhận "Đã tham gia".' : 'Session attendance confirmed successfully! Schedule status updated to "Attended".'; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);">
            <i class="fas fa-check-circle" style="font-size: 1.3rem;"></i>
            <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Đã cập nhật lịch tập và thay đổi HLV thành công!' : 'Schedule and substitute coach updated successfully!'; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);">
            <i class="fas fa-check-circle" style="font-size: 1.3rem; color: #16a34a;"></i>
            <?php echo __('session_cancelled_success'); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rescheduled'): ?>
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);">
            <i class="fas fa-check-circle" style="font-size: 1.3rem; color: #16a34a;"></i>
            <?php echo __('reschedule_success_msg'); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'err_coach_class'): ?>
        <div style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.3rem;"></i>
            <?php echo __('cannot_cancel_coach_class'); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'err_past'): ?>
        <div style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.3rem;"></i>
            <?php echo __('cannot_cancel_past_session'); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'absent_member'): 
        $rem_sessions = isset($_GET['rem']) ? (int)$_GET['rem'] : null;
        $tot_sessions = isset($_GET['tot']) ? (int)$_GET['tot'] : null;
        $isVi = (($_SESSION['lang'] ?? 'en') === 'vi');
    ?>
        <div style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(245,158,11,0.15);">
            <i class="fas fa-user-times" style="font-size: 1.3rem; flex-shrink:0;"></i>
            <div>
                <?php echo $isVi
                    ? '⚠️ Đã ghi nhận vắng mặt. Khách hàng có gói thành viên — buổi tập được <strong>GIỮ NGUYÊN</strong>, không bị hủy. <strong>1 buổi PT đã bị trừ.</strong> Email đã gửi.'
                    : '⚠️ Absent noted. Client has active membership — session is <strong>KEPT</strong>, not cancelled. <strong>1 PT session deducted.</strong> Email sent.'; ?>
                <?php if ($rem_sessions !== null && $tot_sessions > 0): ?>
                    &nbsp;|&nbsp;
                    <span style="background: <?php echo $rem_sessions > 0 ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $rem_sessions > 0 ? '#15803d' : '#dc2626'; ?>; padding: 2px 10px; border-radius: 20px; font-size: 0.88rem;">
                        <?php echo $isVi ? "Còn lại: {$rem_sessions} / {$tot_sessions} buổi PT" : "Remaining: {$rem_sessions} / {$tot_sessions} PT sessions"; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'absent_cancelled'): ?>
        <div style="background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(220,38,38,0.15);">
            <i class="fas fa-ban" style="font-size: 1.3rem;"></i>
            <?php echo (($_SESSION['lang'] ?? 'en') === 'vi') ? '🚫 Khách hàng vắng mặt & không có gói thành viên — Buổi tập đã bị HỦY TỰ ĐỘNG. Email thông báo đã được gửi.' : '🚫 Client absent without membership — Session has been AUTO-CANCELLED. Email notification sent.'; ?>
        </div>
    <?php endif; ?>
<style>
    /* ────────────────────────────────────────────────────────────────
       CALENDAR — MAISON EUROPÉENNE  ·  Nouvelle Architecture
       Coach-first panel layout · clarity · luxury restraint
    ──────────────────────────────────────────────────────────────── */
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --cal-navy:      #1c2340;
        --cal-gold:      #b08d57;
        --cal-gold-lt:   #e0cab98a;
        --cal-cream:     #f7f4ef;
        --cal-paper:     #fdfcfa;
        --cal-border:    #e8e2d8;
        --cal-border-dk: #c9bfb0;
        --cal-text:      #1c2340;
        --cal-muted:     #6b6862;
        --cal-shadow:    rgba(28, 35, 64, 0.06);
    }

    div[style*="background: var(--bg-cream)"] { background: #f5f2ec !important; }

    #calendar {
        padding: 3rem 1.5rem 5rem;
        max-width: 1380px;
        margin: 0 auto;
        font-family: 'Inter', sans-serif;
        color: var(--cal-text);
    }

    /* ── Page title ── */
    .calendar-title-wrapper { text-align: center; margin-bottom: 3.5rem; }
    .calendar-title-wrapper h2 {
        font-family: 'DM Serif Display', serif;
        font-weight: 400; font-size: 3.4rem;
        color: var(--cal-navy); letter-spacing: -0.5px; line-height: 1.15; margin-bottom: 0;
    }
    .calendar-title-wrapper h2 em { font-style: italic; color: var(--cal-gold); }
    .calendar-title-wrapper .underline {
        display: flex; align-items: center; justify-content: center;
        gap: 12px; margin: 1.2rem auto 1rem;
    }
    .calendar-title-wrapper .underline::before,
    .calendar-title-wrapper .underline::after {
        content: ''; display: block; width: 60px; height: 1px;
        background: var(--cal-gold); opacity: 0.6;
    }
    .calendar-title-wrapper .underline-diamond { color: var(--cal-gold); font-size: 0.55rem; opacity: 0.85; letter-spacing: 4px; }
    .calendar-title-wrapper p { color: var(--cal-muted); font-size: 0.9rem; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 500; }

    /* ── Crowd Tracker ── */
    .crowd-tracker-card {
        background: var(--cal-paper); border: 1px solid var(--cal-border);
        padding: 2.2rem 2.5rem; margin: 0 auto 2.5rem;
        display: grid; grid-template-columns: 1fr 2.5fr; gap: 2.5rem;
        box-shadow: 0 4px 40px var(--cal-shadow); position: relative; overflow: hidden;
    }
    .crowd-tracker-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
    }
    @media (max-width: 800px) { .crowd-tracker-card { grid-template-columns: 1fr; gap: 1.5rem; padding: 1.5rem; } }
    .live-occupancy-box {
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        border-right: 1px solid var(--cal-border); padding-right: 2.5rem; text-align: center;
    }
    @media (max-width: 800px) { .live-occupancy-box { border-right: none; padding-right: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--cal-border); } }
    .live-circle {
        position: relative; width: 130px; height: 130px; border-radius: 50%;
        background: var(--cal-cream); border: 1.5px solid var(--cal-gold);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        margin-bottom: 1.2rem; box-shadow: 0 0 0 8px rgba(176,141,87,0.06), 0 8px 30px rgba(28,35,64,0.06);
    }
    .live-percent { font-family: 'DM Serif Display', serif; font-size: 2.4rem; font-weight: 700; color: var(--cal-navy); line-height: 1; margin-bottom: 4px; }
    .live-percent-lbl { font-size: 0.6rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--cal-muted); }
    .live-pulse { position: absolute; top: 14px; right: 14px; width: 8px; height: 8px; border-radius: 50%; animation: pulseLive 2s infinite; }
    @keyframes pulseLive {
        0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70%  { transform: scale(1);    box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }
    .live-lbl-status { font-family: 'DM Serif Display', serif; font-size: 1.15rem; font-weight: 600; color: var(--cal-navy); margin: 5px 0 4px; }
    .live-lbl-desc { font-size: 0.75rem; color: var(--cal-muted); letter-spacing: 0.05em; text-transform: uppercase; }
    .heatmap-box { display: flex; flex-direction: column; justify-content: space-between; }
    .heatmap-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--cal-border); }
    .heatmap-title-row strong { font-family: 'DM Serif Display', serif; font-size: 1.4rem; color: var(--cal-navy); font-weight: 400; }
    .heatmap-legend { display: flex; gap: 14px; font-size: 0.68rem; font-weight: 500; color: var(--cal-muted); text-transform: uppercase; letter-spacing: 0.8px; }
    .legend-item { display: flex; align-items: center; gap: 5px; }
    .legend-box { width: 12px; height: 12px; }
    .heatmap-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
    .heatmap-col { display: flex; flex-direction: column; gap: 6px; text-align: center; }
    .heatmap-day-label { font-size: 0.65rem; font-weight: 700; color: var(--cal-navy); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 1px; }
    .heatmap-slot { height: 38px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.62rem; font-weight: 600; transition: all 0.22s; cursor: pointer; position: relative; border: 1px solid rgba(0,0,0,0.05); letter-spacing: 0.5px; }
    .heatmap-slot::after { content: attr(data-tooltip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%) translateY(-6px); background: var(--cal-navy); color: white; padding: 6px 10px; font-size: 0.66rem; white-space: nowrap; opacity: 0; pointer-events: none; transition: all 0.2s; z-index: 100; box-shadow: 0 4px 14px rgba(0,0,0,0.15); }
    .heatmap-slot:hover::after { opacity: 1; transform: translateX(-50%) translateY(-3px); }
    .heatmap-slot:hover { transform: translateY(-2px); box-shadow: 0 6px 16px var(--cal-shadow); }
    .load-low  { background: #f7f4ef; color: #9a9389; border-color: var(--cal-border); }
    .load-med  { background: #ede5d8; color: #8a6c3b; border-color: #d9c9af; }
    .load-high { background: var(--cal-navy); color: var(--cal-gold); border-color: var(--cal-navy); }

    /* ── Roster wrapper ── */
    .roster-section {
        background: var(--cal-paper); border: 1px solid var(--cal-border);
        box-shadow: 0 4px 40px var(--cal-shadow); margin-bottom: 2.5rem;
        position: relative;
    }
    .roster-section::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
    }
    .roster-section-header {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 1rem;
        padding: 1.8rem 2.2rem 1.5rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .roster-section-header h2 {
        font-family: 'DM Serif Display', serif; font-size: 2rem; font-weight: 400;
        color: var(--cal-navy); margin: 0; letter-spacing: -0.3px;
    }

    /* ── Week navigation ── */
    .week-nav-group { display: flex; align-items: center; gap: 0; }
    .btn-nav-week {
        font-family: 'Inter', sans-serif; font-weight: 600; font-size: 0.8rem;
        letter-spacing: 1px; text-transform: uppercase; color: var(--cal-navy);
        border: 1px solid var(--cal-border-dk); background: transparent;
        padding: 9px 18px; cursor: pointer; transition: all 0.22s;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-nav-week:hover { background: var(--cal-navy); color: var(--cal-gold); border-color: var(--cal-navy); }
    .week-indicator {
        font-family: 'Inter', sans-serif; font-weight: 600; font-size: 0.88rem;
        padding: 9px 20px; background: var(--cal-cream);
        border-top: 1px solid var(--cal-border-dk); border-bottom: 1px solid var(--cal-border-dk);
        color: var(--cal-navy); letter-spacing: 0.03em; white-space: nowrap;
    }

    /* ── Filter strip ── */
    .filter-strip {
        display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
        background: var(--cal-cream); padding: 1rem 2.2rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .filter-strip strong {
        font-family: 'DM Serif Display', serif; font-style: italic;
        font-size: 1.15rem; color: var(--cal-navy); font-weight: 400; white-space: nowrap;
    }
    .filter-input-luxury,
    .filter-select-luxury {
        font-family: 'Inter', sans-serif; font-size: 0.88rem; padding: 8px 13px;
        border: 1px solid var(--cal-border-dk); background: var(--cal-paper);
        color: var(--cal-text); outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        border-radius: 4px;
    }
    .filter-input-luxury { width: 200px; }
    .filter-select-luxury { width: 165px; }
    .filter-input-luxury:focus, .filter-select-luxury:focus {
        border-color: var(--cal-gold); box-shadow: 0 0 0 3px rgba(176,141,87,0.12);
    }

    /* ── Day header bar ── */
    .week-day-header {
        display: grid;
        grid-template-columns: 240px repeat(7, 1fr);
        border-bottom: 1px solid var(--cal-border);
        background: var(--cal-cream);
    }
    .week-day-header .wdh-label {
        padding: 12px 2.2rem; font-size: 0.75rem; font-weight: 700;
        letter-spacing: 0.8px; text-transform: uppercase; color: var(--cal-muted);
        border-right: 1px solid var(--cal-border); display: flex; align-items: center;
    }
    .week-day-header .wdh-day {
        padding: 10px 8px; text-align: center; border-right: 1px solid var(--cal-border);
        display: flex; flex-direction: column; align-items: center; gap: 2px;
    }
    .week-day-header .wdh-day:last-child { border-right: none; }
    .wdh-dayname {
        font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;
        text-transform: uppercase; color: var(--cal-navy);
    }
    .wdh-date {
        font-family: 'DM Serif Display', serif; font-size: 1.4rem;
        font-weight: 400; color: var(--cal-navy); line-height: 1;
    }
    .wdh-month { font-size: 0.65rem; color: var(--cal-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .wdh-day.today .wdh-date { color: var(--cal-gold); }
    .wdh-day.today .wdh-dayname { color: var(--cal-gold); }
    .wdh-day.today { background: rgba(176,141,87,0.06); }

    /* ── Coach row ── */
    .coach-row {
        display: grid;
        grid-template-columns: 240px repeat(7, 1fr);
        border-bottom: 1px solid var(--cal-border);
        transition: background 0.15s;
    }
    .coach-row:last-child { border-bottom: none; }
    .coach-row:hover { background: rgba(176,141,87,0.018); }

    .coach-info-cell {
        padding: 1.2rem 2.2rem; border-right: 1px solid var(--cal-border);
        display: flex; flex-direction: column; justify-content: flex-start;
        gap: 8px; background: var(--cal-paper);
    }
    .coach-name {
        font-family: 'DM Serif Display', serif; font-size: 1.25rem;
        font-weight: 400; color: var(--cal-navy); line-height: 1.2;
    }
    .btn-book-roster {
        font-family: 'Inter', sans-serif; font-weight: 700; font-size: 0.72rem;
        text-transform: uppercase; letter-spacing: 1px; color: var(--cal-navy);
        border: 1px solid var(--cal-navy); background: transparent;
        padding: 6px 12px; cursor: pointer; transition: all 0.22s;
        text-decoration: none; display: inline-block; align-self: flex-start;
    }
    .btn-book-roster:hover { background: var(--cal-navy); color: var(--cal-gold); }

    /* ── Day cell ── */
    .coach-day-cell {
        padding: 10px 8px; border-right: 1px solid var(--cal-border);
        vertical-align: top; min-height: 80px;
        display: flex; flex-direction: column; gap: 6px;
    }
    .coach-day-cell:last-child { border-right: none; }

    /* ── Slot available / off ── */
    .slot-available {
        display: flex; align-items: center; justify-content: center;
        height: 100%; min-height: 60px;
        color: var(--cal-gold); font-size: 0.7rem; font-weight: 700;
        letter-spacing: 0.08em; text-transform: uppercase;
        opacity: 0.7;
    }
    .slot-available::before { content: "◆ "; font-size: 0.45rem; vertical-align: middle; margin-right: 2px; }
    .slot-off {
        display: flex; align-items: center; justify-content: center;
        height: 100%; min-height: 60px;
        color: #bbb4ab; font-size: 0.7rem; font-weight: 500; letter-spacing: 0.05em;
    }

    /* ── Booking mini-card ── */
    .booking-card {
        background: var(--cal-paper); border: 1px solid var(--cal-border);
        border-radius: 3px; padding: 8px 10px 8px 12px;
        position: relative; transition: all 0.2s; font-size: 0.8rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .booking-card::before {
        content: ''; position: absolute; top: 0; left: 0;
        width: 3px; height: 100%; border-radius: 3px 0 0 3px;
    }
    .booking-card.staff::before    { background: var(--cal-navy); }
    .booking-card.personal::before { background: var(--cal-gold); }
    .booking-card:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(28,35,64,0.08); border-color: var(--cal-gold); }
    .booking-card:last-child { margin-bottom: 0; }

    .booking-time {
        font-weight: 700; font-size: 0.82rem; color: var(--cal-navy);
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 5px; padding-bottom: 5px;
        border-bottom: 1px solid var(--cal-border);
    }
    .booking-time i { color: var(--cal-gold); font-size: 0.7rem; margin-right: 3px; }

    .class-info-block {
        font-size: 0.74rem; background: var(--cal-cream); padding: 6px 8px;
        margin-top: 5px; border-left: 2px solid var(--cal-gold);
        border: 1px solid var(--cal-border); border-left: 2px solid var(--cal-gold);
    }
    .class-info-block strong { color: var(--cal-navy); display: block; margin-bottom: 3px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

    /* ── My roster (user personal) ── */
    .my-roster-section {
        background: var(--cal-paper); border: 1px solid var(--cal-border);
        box-shadow: 0 4px 40px var(--cal-shadow); margin-bottom: 2rem;
        position: relative;
    }
    .my-roster-section::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--cal-gold) 0%, var(--cal-navy) 50%, var(--cal-gold) 100%);
    }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
        .week-day-header, .coach-row { grid-template-columns: 180px repeat(7, 1fr); }
        .coach-info-cell { padding: 1rem 1rem; }
        .wdh-label { padding: 12px 1rem; }
    }
    @media (max-width: 860px) {
        .week-day-header, .coach-row { display: block; }
        .coach-day-cell { border-right: none; border-bottom: 1px solid var(--cal-border); }
        .coach-info-cell { border-right: none; border-bottom: 1px solid var(--cal-border); }
        .week-day-header { display: none; }
    }
</style>

    <div class="calendar-title-wrapper">
        <h2><?php echo __('calendar_title'); ?></h2>
        <div class="underline"><span class="underline-diamond">◆ ◆ ◆</span></div>
        <p><?php echo __('calendar_subtitle'); ?></p>
    </div>

    <!-- Live Crowd & Capacity Heatmap Panel -->
    <?php
    try {
        // Calculate Live occupancy
        $current_hour = (int)date('H');
        $current_dow = (int)date('w') + 1; // 1 = Sunday, ..., 7 = Saturday
        $today_str = date('Y-m-d');
        
        $stmt_crowd = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookings 
            WHERE DATE(booking_date) = ? 
              AND HOUR(booking_date) = ? 
              AND status != 'cancelled'
        ");
        $stmt_crowd->execute([$today_str, $current_hour]);
        $active_bookings_now = (int)$stmt_crowd->fetchColumn();
        
        // Fetch 30-day statistics for Heatmap
        $stmt_heatmap = $pdo->prepare("
            SELECT DAYOFWEEK(booking_date) as dow, HOUR(booking_date) as hr, COUNT(*) as cnt
            FROM bookings
            WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != 'cancelled'
            GROUP BY dow, hr
        ");
        $stmt_heatmap->execute();
        $heatmap_raw = $stmt_heatmap->fetchAll();
        
        $load_grid = [];
        for ($dow = 1; $dow <= 7; $dow++) {
            $load_grid[$dow] = ['morning' => 0, 'midday' => 0, 'evening' => 0];
        }
        foreach ($heatmap_raw as $row) {
            $dow = (int)$row['dow'];
            $hr = (int)$row['hr'];
            $cnt = (int)$row['cnt'];
            
            if ($hr >= 6 && $hr < 11) {
                $load_grid[$dow]['morning'] += $cnt;
            } elseif ($hr >= 11 && $hr < 16) {
                $load_grid[$dow]['midday'] += $cnt;
            } elseif ($hr >= 16 && $hr < 22) {
                $load_grid[$dow]['evening'] += $cnt;
            }
        }
        
        // Normalize live occupancy percentage based on current period load
        $current_period = 'morning';
        if ($current_hour >= 11 && $current_hour < 16) {
            $current_period = 'midday';
        } elseif ($current_hour >= 16 && $current_hour < 22) {
            $current_period = 'evening';
        }
        
        $period_load = isset($load_grid[$current_dow][$current_period]) ? $load_grid[$current_dow][$current_period] : 0;
        $live_capacity_percent = min(98, max(5, ($period_load * 12) + ($active_bookings_now * 15) + rand(3, 8)));
        
        // Determine status labels
        if ($live_capacity_percent < 30) {
            $live_status_text = __('crowd_peaceful');
            $pulse_color = '#16a34a';
        } elseif ($live_capacity_percent < 70) {
            $live_status_text = __('crowd_moderate');
            $pulse_color = '#d97706';
        } else {
            $live_status_text = __('crowd_busy');
            $pulse_color = '#dc2626';
        }
        
        // Days map for displaying DOW
        $dow_labels = [
            2 => __('day_monday'),
            3 => __('day_tuesday'),
            4 => __('day_wednesday'),
            5 => __('day_thursday'),
            6 => __('day_friday'),
            7 => __('day_saturday'),
            1 => __('day_sunday')
        ];
        
        // Helper to output CSS class & label based on load size
        function getLoadClass($val) {
            if ($val == 0) return 'load-low';
            if ($val < 3) return 'load-med';
            return 'load-high';
        }
        
        function getLoadText($val) {
            if ($val == 0) return 'Low / Vắng';
            if ($val < 3) return 'Normal / Vừa';
            return 'Peak / Đông';
        }
        
    } catch (Exception $e) {
        // Fallback default variables on error
        $live_capacity_percent = 15;
        $live_status_text = __('crowd_peaceful');
        $pulse_color = '#16a34a';
    }
    ?>
    <div class="crowd-tracker-card">
        <div class="live-occupancy-box">
            <div class="live-circle">
                <div class="live-pulse" style="background: <?php echo $pulse_color; ?>; box-shadow: 0 0 0 0 <?php echo $pulse_color; ?>80;"></div>
                <span class="live-percent"><?php echo $live_capacity_percent; ?>%</span>
                <span class="live-percent-lbl"><?php echo __('status'); ?></span>
            </div>
            <h4 class="live-lbl-status"><?php echo __('crowd_status_live'); ?></h4>
            <p class="live-lbl-desc"><?php echo $live_status_text; ?></p>
        </div>
        
        <div class="heatmap-box">
            <div class="heatmap-title-row">
                <strong><?php echo __('weekly_load_forecast'); ?></strong>
                <div class="heatmap-legend">
                    <div class="legend-item"><span class="legend-box load-low"></span><span>Low</span></div>
                    <div class="legend-item"><span class="legend-box load-med"></span><span>Active</span></div>
                    <div class="legend-item"><span class="legend-box load-high"></span><span>Peak</span></div>
                </div>
            </div>
            
            <div class="heatmap-grid">
                <?php foreach ([2, 3, 4, 5, 6, 7, 1] as $d_idx): ?>
                    <div class="heatmap-col">
                        <span class="heatmap-day-label"><?php echo substr($dow_labels[$d_idx], 0, 3); ?></span>
                        
                        <?php 
                        $m_load = $load_grid[$d_idx]['morning'];
                        $md_load = $load_grid[$d_idx]['midday'];
                        $e_load = $load_grid[$d_idx]['evening'];
                        ?>
                        
                        <!-- Morning (06:00 - 11:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($m_load); ?>" 
                             data-tooltip="Morning (06:00-11:00): <?php echo getLoadText($m_load); ?>">
                            <span>AM</span>
                        </div>
                        
                        <!-- Midday (11:00 - 16:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($md_load); ?>" 
                             data-tooltip="Midday (11:00-16:00): <?php echo getLoadText($md_load); ?>">
                            <span>NOON</span>
                        </div>
                        
                        <!-- Evening (16:00 - 22:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($e_load); ?>" 
                             data-tooltip="Evening (16:00-22:00): <?php echo getLoadText($e_load); ?>">
                            <span>PM</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    
    <?php
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

    $stmt_c = $pdo->query("SELECT c.id, u.name, COALESCE(c.zalo, u.email) as contact FROM coaches c JOIN users u ON c.user_id = u.id");
    $coaches = $stmt_c->fetchAll();

    $start_date_str = $monday->format('Y-m-d 00:00:00');
    $end_date_str = $sunday->format('Y-m-d 23:59:59');

    $stmt_b = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
               c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
               c.room_number as coach_room_number,
               mem.mem_plan_name, mem.pt_sessions_total, mem.pt_sessions_used
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        JOIN coaches c ON b.coach_id = c.id
        JOIN users c_u ON c.user_id = c_u.id
        LEFT JOIN (
            SELECT ms.user_id, mp.name as mem_plan_name, 
                   COALESCE(NULLIF(ms.pt_sessions_total, 0), mp.pt_sessions, 0) as pt_sessions_total, 
                   ms.pt_sessions_used
            FROM member_subscriptions ms
            JOIN membership_plans mp ON ms.plan_id = mp.id
            WHERE ms.status = 'active' AND ms.end_date >= CURDATE()
        ) mem ON mem.user_id = b.user_id
        WHERE b.booking_date >= ? AND b.booking_date <= ?
    ");
    $stmt_b->execute([$start_date_str, $end_date_str]);
    $bookings = $stmt_b->fetchAll();

    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['manager', 'admin'])): 
        $stmt_cancelled = $pdo->prepare("
            SELECT b.*, u.name as client_name, u.phone as client_phone, u.email as client_email,
                   c_u.name as orig_coach_name, c.id as orig_coach_id
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN coaches c ON b.coach_id = c.id
            LEFT JOIN users c_u ON c.user_id = c_u.id
            WHERE b.status = 'cancelled' AND b.booking_date >= NOW() - INTERVAL 2 DAY
            ORDER BY b.booking_date ASC
        ");
        $stmt_cancelled->execute();
        $cancelled_list = $stmt_cancelled->fetchAll();
        if (!empty($cancelled_list)):
    ?>
    <div style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border: 2px solid #ef4444; border-radius: 20px; padding: 1.8rem; margin-bottom: 2.5rem; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem; border-bottom: 1px dashed #fca5a5; padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 6px 15px rgba(239,68,68,0.35);">
                    🚨
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800; color: #991b1b;">
                        <?php echo __('emergency_cancelled_title'); ?>
                    </h3>
                    <p style="margin: 3px 0 0; font-size: 0.85rem; color: #7f1d1d; font-weight: 600;">
                        <?php echo __('emergency_cancelled_subtitle'); ?>
                    </p>
                </div>
            </div>
            <span style="background: #ef4444; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; box-shadow: 0 4px 10px rgba(239,68,68,0.3);">
                <?php echo count($cancelled_list); ?> <?php echo (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Buổi Cần Đổi HLV' : 'Pending Substitute'); ?>
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.2rem;">
            <?php foreach ($cancelled_list as $cb): 
                $cb_date = date('d/m/Y - H:i', strtotime($cb['booking_date']));
                $cb_client = htmlspecialchars($cb['client_name'] ?? __('open_class'));
                $cb_coach = htmlspecialchars($cb['orig_coach_name'] ?? 'N/A');
                $cb_reason_text = !empty($cb['cancel_reason']) ? $cb['cancel_reason'] : ((($_SESSION['lang'] ?? 'vi') === 'vi') ? 'Hủy đột xuất / Cần HLV dạy thế' : 'Emergency cancel / Substitute needed');
                $cb_reason = htmlspecialchars($cb_reason_text);
                $cb_client_phone = htmlspecialchars($cb['client_phone'] ?: 'N/A');
            ?>
                <div style="background: white; border-radius: 16px; padding: 1.25rem; border: 1.5px solid #fca5a5; box-shadow: 0 4px 12px rgba(239,68,68,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 800; font-size: 0.88rem; color: #991b1b;">
                                <i class="far fa-clock"></i> <?php echo $cb_date; ?>
                            </span>
                            <span style="font-size: 0.75rem; background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 10px; font-weight: 700;">#<?php echo $cb['id']; ?></span>
                        </div>
                        <div style="font-size: 0.9rem; color: #1e293b; margin-bottom: 4px;">
                            <strong><?php echo __('client'); ?>:</strong> <?php echo $cb_client; ?> 
                            <span style="color: #64748b; font-size: 0.8rem;">(<?php echo $cb_client_phone; ?>)</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569; margin-bottom: 6px;">
                            <strong><?php echo __('orig_coach'); ?>:</strong> <?php echo $cb_coach; ?>
                        </div>
                        <div style="background: #fff5f5; padding: 8px 10px; border-radius: 8px; font-size: 0.8rem; color: #b91c1c; margin-bottom: 12px; border-left: 3px solid #ef4444;">
                            <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> <strong><?php echo __('cancel_reason'); ?>:</strong> <?php echo $cb_reason; ?>
                        </div>
                    </div>
                    <button type="button"
                            class="btn-edit-schedule-card"
                            data-id="<?php echo $cb['id']; ?>"
                            data-coach-id="<?php echo $cb['coach_id']; ?>"
                            data-date="<?php echo date('Y-m-d\TH:i', strtotime($cb['booking_date'])); ?>"
                            data-status="confirmed"
                            data-attendance="pending"
                            data-msg="<?php echo htmlspecialchars(getLocalizedCoachMsg('Phân công HLV dạy thế cho buổi tập bị hủy đột xuất')); ?>"
                            data-client="<?php echo addslashes($cb_client); ?>"
                            onclick="openScheduleEditModal(this)"
                            style="background: linear-gradient(135deg, #0062ff, #004ed8); color: white; border: none; padding: 10px 16px; border-radius: 12px; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,98,255,0.3); transition: transform 0.2s;">
                        <i class="fas fa-user-ninja"></i> <?php echo __('substitute_coach_btn'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; endif; ?>

    <?php
    $roster = [];
    foreach ($bookings as $b) {
        $date = date('Y-m-d', strtotime($b['booking_date']));
        $time = date('H:i', strtotime($b['booking_date']));
        $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
        
        $b_mem_plan  = $b['mem_plan_name'] ?? null;
        $b_pt_total  = (int)($b['pt_sessions_total'] ?? 0);
        $b_pt_used   = (int)($b['pt_sessions_used'] ?? 0);
        $b_pt_rem    = max(0, $b_pt_total - $b_pt_used);

        $client_membership_box = "";
        $client_card_badge = "";
        $is_vi_lang = (($_SESSION['lang'] ?? 'en') === 'vi');
        if (!empty($b_mem_plan)) {
            $p_name_safe = htmlspecialchars($b_mem_plan);
            $is_expired_quota = ($b_pt_rem === 0 || $b_pt_used >= $b_pt_total);
            
            if ($is_expired_quota) {
                $client_membership_box = "
                <div style='background: #fef2f2; border: 1.5px solid #fca5a5; padding: 12px 14px; border-radius: 12px; margin-top: 8px;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;'>
                        <div>
                            <span style='font-size: 0.72rem; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;'>
                                <i class='fas fa-exclamation-triangle' style='color: #dc2626;'></i> " . ($is_vi_lang ? 'Gói PT (Đã Hết Hạn)' : 'PT Package (Expired)') . "
                            </span>
                            <strong style='font-size: 0.92rem; color: #991b1b; display: block; margin-top: 2px;'>{$p_name_safe}</strong>
                        </div>
                        <div style='background: #dc2626; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; box-shadow: 0 2px 6px rgba(220,38,38,0.25); display: flex; align-items: center; gap: 5px;'>
                            <i class='fas fa-times-circle'></i> " . ($is_vi_lang ? "Đã dùng {$b_pt_used}/{$b_pt_total} buổi" : "Used {$b_pt_used}/{$b_pt_total}") . "
                        </div>
                    </div>
                    <div style='margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;'>
                        <a href='/Gym Manager/pages/membership.php' style='background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none;'>🔄 " . ($is_vi_lang ? 'Gia Hạn Gói' : 'Renew Plan') . "</a>
                        <a href='/Gym Manager/pages/membership.php' style='background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none;'>✨ " . ($is_vi_lang ? 'Chọn Gói Khác' : 'Choose New') . "</a>
                    </div>
                </div>";
            } else {
                $client_membership_box = "
                <div style='background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1.5px solid #93c5fd; padding: 10px 14px; border-radius: 12px; margin-top: 8px;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;'>
                        <div>
                            <span style='font-size: 0.72rem; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;'>
                                <i class='fas fa-id-card' style='color: #2563eb;'></i> " . ($is_vi_lang ? 'Gói Thành Viên (Active)' : 'Active Membership Plan') . "
                            </span>
                            <strong style='font-size: 0.92rem; color: #1e3a8a; display: block; margin-top: 2px;'>{$p_name_safe}</strong>
                        </div>
                        <div style='background: #ffffff; border: 1.5px solid #2563eb; color: #15803d; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; box-shadow: 0 2px 6px rgba(37,99,235,0.15); display: flex; align-items: center; gap: 5px;'>
                            <i class='fas fa-dumbbell' style='color: #2563eb;'></i> " . ($is_vi_lang ? "Còn {$b_pt_rem} / {$b_pt_total} buổi PT" : "{$b_pt_rem} / {$b_pt_total} PT left") . "
                        </div>
                    </div>
                    <div style='font-size: 0.75rem; color: #1d4ed8; margin-top: 6px; line-height: 1.3;'>
                        ℹ️ <strong>" . ($is_vi_lang ? 'Quy tắc điểm danh:' : 'Attendance Rule:') . "</strong> " . ($is_vi_lang ? 'Có mặt / Vắng mặt đều trừ 1 buổi PT.' : 'Both Present & Absent deduct 1 PT session.') . "
                    </div>
                </div>";
            }

            $client_card_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: " . ($is_expired_quota ? "#fee2e2" : "#dbeafe") . "; color: " . ($is_expired_quota ? "#dc2626" : "#1e40af") . "; border: 1px solid " . ($is_expired_quota ? "#fca5a5" : "#93c5fd") . "; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 800; margin: 4px 0;'><i class='fas fa-id-card'></i> {$p_name_safe} (" . ($is_expired_quota ? "Hết Hạn" : "{$b_pt_rem}/{$b_pt_total} PT") . ")</div>";
        } else {
            $client_membership_box = "
            <div style='background: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px 12px; border-radius: 10px; margin-top: 8px; font-size: 0.78rem; color: #64748b; font-weight: 600;'>
                👤 <strong>" . ($is_vi_lang ? 'Khách Thường (Không gói TV):' : 'Standard Client (No Membership):') . "</strong> " . ($is_vi_lang ? 'Nếu điểm danh Vắng mặt, hệ thống sẽ TỰ ĐỘNG HỦY buổi tập.' : 'If marked Absent, session will be AUTO-CANCELLED.') . "
            </div>";

            $client_card_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; margin: 4px 0;'><i class='fas fa-user'></i> " . ($is_vi_lang ? 'Khách thường' : 'Standard') . "</div>";
        }

        $session_check_buttons_html = "";
        if ($b_pt_total > 0 && isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
            $session_check_buttons_html .= "
            <div style='margin-top: 6px; background: #ffffff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 10px 12px;'>
                <div style='font-size: 0.78rem; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;'>
                    <span><i class='fas fa-list-check' style='color: #2563eb;'></i> ATTENDANCE PROGRESS ({$b_pt_total} SESSIONS):</span>
                    <span style='background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-size: 0.72rem; font-weight: 800;'>{$b_pt_used} / {$b_pt_total} SESSIONS</span>
                </div>
                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(105px, 1fr)); gap: 6px;'>";

            for ($s = 1; $s <= $b_pt_total; $s++) {
                $is_done = ($s <= $b_pt_used);
                $is_next = ($s == $b_pt_used + 1);

                if ($is_done) {
                    $session_check_buttons_html .= "
                    <div style='background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 8px 6px; text-align: center;'>
                        <div style='font-weight: 800; color: #16a34a; font-size: 0.78rem;'><i class='fas fa-check-circle'></i> Session {$s}</div>
                        <div style='font-size: 0.68rem; color: #15803d; font-weight: 700; margin-top: 2px;'>✓ Used</div>
                    </div>";
                } elseif ($is_next) {
                    $session_check_buttons_html .= "
                    <div style='background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 8px; padding: 8px 6px; text-align: center; box-shadow: 0 2px 6px rgba(59,130,246,0.15);'>
                        <div style='font-weight: 800; color: #1d4ed8; font-size: 0.78rem;'>📍 Session {$s}</div>
                        <div style='font-size: 0.68rem; color: #2563eb; font-weight: 700; margin-top: 2px;'>" . __('current_session') . "</div>
                    </div>";
                } else {
                    $session_check_buttons_html .= "
                    <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 6px; text-align: center;'>
                        <div style='font-weight: 800; color: #64748b; font-size: 0.78rem;'>Session {$s}</div>
                        <div style='font-size: 0.68rem; color: #94a3b8; font-weight: 600; margin-top: 2px;'>" . __('upcoming_status') . "</div>
                    </div>";
                }
            }

            $session_check_buttons_html .= "
                </div>
            </div>";
        }

        $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
        $client_email = htmlspecialchars($b['client_email'] ?? '');
        $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
        $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
        $coach_email = htmlspecialchars($b['coach_email'] ?? '');
        $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
        
        $start_ts = strtotime($b['booking_date']);
        $end_ts = $start_ts + 3600;
        $now = time();
        
        $is_ongoing = ($now >= $start_ts && $now <= $end_ts && $b['status'] !== 'cancelled' && $b['attendance'] !== 'absent');
        $is_past = ($now > $end_ts);
        $is_attended = ($b['attendance'] === 'present' || $b['status'] === 'completed' || ($is_past && $b['status'] !== 'cancelled' && $b['attendance'] !== 'absent'));
        $is_absent = ($b['attendance'] === 'absent');
        $is_b_cancelled = ($b['status'] === 'cancelled');

        if ($is_b_cancelled) {
            $staff_card_style = "background: #fef2f2; border: 1px solid #fca5a5; opacity: 0.85;";
            $staff_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; color: #dc2626; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='fas fa-ban'></i> " . __('cancelled_status') . "</div>";
        } elseif ($is_absent) {
            $staff_card_style = "background: #fff5f5; border: 1px solid #fecaca; border-left: 4px solid #dc2626;";
            $staff_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; color: #dc2626; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='fas fa-user-times'></i> " . __('absent_label') . "</div>";
        } elseif ($is_ongoing) {
            $staff_card_style = "background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 2px solid #f59e0b; border-left: 5px solid #d97706; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);";
            $staff_badge = "<div style='display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px; margin: 4px 0; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);'><i class='fas fa-running'></i> " . __('in_progress_label') . "</div>";
        } elseif ($is_attended) {
            $staff_card_style = "background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.12);";
            $staff_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #16a34a, #15803d); color: #ffffff; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px; margin: 4px 0; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);'><i class='fas fa-check-circle'></i> " . __('attended_label') . "</div>";
        } else {
            $staff_card_style = "background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #2563eb;";
            $staff_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1d4ed8; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='far fa-calendar-check'></i> " . ($is_vi_lang ? 'Đã Đặt Lịch' : 'Scheduled') . "</div>";
        }

        $class_info = "";
        if (!empty($b['class_name'])) {
            $class_info = "
            <div class='class-info-block' style='margin-top: 4px;'>
                <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
            </div>";
        }
        
        $coach_note_info = "";
        if (!empty($b['coach_message'])) {
            $coach_note_info = "
            <div style='background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 6px 10px; border-radius: 8px; font-size: 0.78rem; margin-top: 6px;'>
                <i class='fas fa-edit' style='color: #d97706; margin-right: 4px;'></i> <strong>" . __('coach_notes_title') . ":</strong> " . htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])) . "
            </div>";
        }

        $manager_edit_btn = "";
        if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['manager', 'admin'])) {
            $b_id = (int)$b['id'];
            $c_id = (int)$b['coach_id'];
            $b_date = date('Y-m-d\TH:i', strtotime($b['booking_date']));
            $b_status = htmlspecialchars($b['status'] ?? 'confirmed');
            $b_attendance = htmlspecialchars($b['attendance'] ?? 'pending');
            $b_class = htmlspecialchars($b['class_name'] ?? '');
            $b_spec = htmlspecialchars($b['specialty'] ?? '');
            $b_room = htmlspecialchars($b['room_number'] ?? '');
            $b_plan = htmlspecialchars($b['training_plan'] ?? '');
            $b_msg = htmlspecialchars($b['coach_message'] ?? '');
            $b_client = htmlspecialchars($client_name);

            if (!$is_attended && !$is_past) {
                $manager_edit_btn = "
                <button type='button' 
                        class='btn-edit-schedule-card' 
                        data-id='{$b_id}' 
                        data-coach-id='{$c_id}' 
                        data-date='{$b_date}' 
                        data-status='{$b_status}' 
                        data-attendance='{$b_attendance}'
                        data-class='{$b_class}' 
                        data-spec='{$b_spec}' 
                        data-room='{$b_room}' 
                        data-plan='{$b_plan}' 
                        data-msg='{$b_msg}' 
                        data-client='{$b_client}' 
                        onclick='openScheduleEditModal(this)' 
                        title='" . __('edit') . "'
                        style='background: #1c2340; color: #b08d57; border: 1px solid #b08d57; padding: 3px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-left: auto;'>
                    <i class='fas fa-edit'></i> " . __('edit') . "
                </button>";
            } elseif ($is_b_cancelled) {
                $manager_edit_btn = "
                <button type='button' 
                        class='btn-edit-schedule-card' 
                        data-id='{$b_id}' 
                        data-coach-id='{$c_id}' 
                        data-date='{$b_date}' 
                        data-status='confirmed' 
                        data-attendance='pending'
                        data-class='{$b_class}' 
                        data-spec='{$b_spec}' 
                        data-room='{$b_room}' 
                        data-plan='{$b_plan}' 
                        data-msg='{$b_msg}' 
                        data-client='{$b_client}' 
                        onclick='openScheduleEditModal(this)' 
                        title='" . (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Chọn HLV Thế' : 'Assign Substitute Coach') . "'
                        style='background: linear-gradient(135deg, #ef4444, #dc2626); color: #ffffff; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(239,68,68,0.3); margin-left: auto;'>
                    <i class='fas fa-user-ninja'></i> " . __('substitute_coach_btn') . "
                </button>";
            }
        }

        $b_date_formatted = date('d/m/Y', strtotime($b['booking_date']));
        $b_datetime_local = date('Y-m-d\TH:i', strtotime($b['booking_date']));
        $b_coach_msg_escaped = addslashes(htmlspecialchars($b['coach_message'] ?? ''));

        $can_user_edit = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager']));
        $can_user_cancel = false;

        $staff_details_modal_html = "
        <div id='staff-details-modal-{$b['id']}' style='display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px;'>
            <div style='background: #ffffff; padding: 2rem; border-radius: 24px; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: left; position: relative; border: 1px solid #e2e8f0; max-height: 90vh; overflow-y: auto;'>
                <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;'>
                    <div style='display: flex; align-items: center; gap: 12px;'>
                        <div style='width: 44px; height: 44px; border-radius: 14px; background: rgba(26, 39, 68, 0.08); color: #1a2744; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;'>
                            <i class='fas fa-info-circle'></i>
                        </div>
                        <div>
                            <h3 style='margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;'>" . __('session_details_title') . "</h3>
                            <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>ID: #{$b['id']}</span>
                        </div>
                    </div>
                    <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                </div>

                <div style='display: flex; flex-direction: column; gap: 1rem;'>
                    <!-- Status & Attendance Banner -->
                    <div style='background: " . ($is_attended ? "#f0fdf4" : ($is_absent ? "#fff5f5" : ($is_b_cancelled ? "#fef2f2" : "#eff6ff"))) . "; padding: 12px 16px; border-radius: 12px; border: 1px solid " . ($is_attended ? "#bbf7d0" : ($is_absent ? "#fecaca" : ($is_b_cancelled ? "#fca5a5" : "#bfdbfe"))) . "; display: flex; flex-direction: column; gap: 10px;'>
                        <div>
                            <span style='font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;'>" . __('status') . "</span>
                            <strong style='font-size: 0.95rem; color: " . ($is_attended ? "#15803d" : ($is_absent ? "#dc2626" : ($is_b_cancelled ? "#dc2626" : "#1d4ed8"))) . ";'>" . ($is_attended ? "<i class='fas fa-check-circle'></i> " . __('attended_desc') : ($is_absent ? "<i class='fas fa-user-times'></i> " . __('absent_desc') : ($is_b_cancelled ? "<i class='fas fa-ban'></i> " . __('cancelled_status') : "<i class='far fa-calendar-check'></i> " . __('scheduled_session')))) . "</strong>
                        </div>
                        " . ($b_pt_total > 0 ? $session_check_buttons_html : "") . "
                        " . (!$is_attended && !$is_absent && !$is_b_cancelled && isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin']) ? "
                        <div style='display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;'>
                            <form method='POST' style='margin: 0;'>
                                <input type='hidden' name='action_coach_attendance' value='1'>
                                <input type='hidden' name='booking_id' value='{$b['id']}'>
                                <input type='hidden' name='attendance_value' value='present'>
                                <input type='hidden' name='week_offset' value='{$week_offset}'>
                                <button type='submit' style='background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);'>
                                    <i class='fas fa-check-circle'></i> " . __('mark_present') . "
                                </button>
                            </form>
                            <form method='POST' style='margin: 0;'>
                                <input type='hidden' name='action_coach_attendance' value='1'>
                                <input type='hidden' name='booking_id' value='{$b['id']}'>
                                <input type='hidden' name='attendance_value' value='absent'>
                                <input type='hidden' name='week_offset' value='{$week_offset}'>
                                <button type='submit' style='background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);'>
                                    <i class='fas fa-user-times'></i> " . __('mark_absent') . "
                                </button>
                            </form>
                        </div>" : "") . "
                    </div>

                    <!-- Date & Time -->
                    <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;'>
                            <i class='far fa-clock' style='color: #c9a84c;'></i> " . __('date_time') . "
                        </div>
                        <div style='font-size: 0.95rem; font-weight: 700; color: #1e293b;'>
                            {$b_date_formatted} ({$time} - {$end_time})
                        </div>
                    </div>

                    <!-- Client Info -->
                    <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #1a2744; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-user-circle' style='color: #1a2744;'></i> " . __('client_info_title') . "
                        </div>
                        <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                            <div><strong>" . __('client_name') . ":</strong> {$client_name}</div>
                            <div><strong>" . __('phone') . ":</strong> {$client_phone}</div>
                            <div><strong>" . __('email') . ":</strong> {$client_email}</div>
                        </div>
                        {$client_membership_box}
                    </div>

                    <!-- Coach Info -->
                    <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #c9a84c; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-dumbbell' style='color: #c9a84c;'></i> " . __('coach_info_title') . "
                        </div>
                        <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                            <div><strong>" . __('coach') . ":</strong> " . htmlspecialchars($b['coach_name']) . "</div>
                            <div><strong>" . __('phone') . ":</strong> {$coach_phone}</div>
                            <div><strong>" . __('email') . ":</strong> {$coach_email}</div>
                            <div><strong>" . __('training_room') . ":</strong> " . htmlspecialchars($coach_room) . "</div>
                        </div>
                    </div>

                    " . (!empty($b['class_name']) ? "
                    <!-- Class Details -->
                    <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #1a2744; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-chalkboard-teacher' style='color: #1a2744;'></i> " . __('class_name_label') . "
                        </div>
                        <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                            <div><strong>" . __('class_name_detail') . "</strong> " . htmlspecialchars($b['class_name']) . "</div>
                            " . (!empty($b['specialty']) ? "<div><strong>" . __('specialty_detail') . "</strong> " . htmlspecialchars($b['specialty']) . "</div>" : "") . "
                            " . (!empty($b['training_plan']) ? "<div><strong>" . __('plan_detail') . "</strong> " . htmlspecialchars($b['training_plan']) . "</div>" : "") . "
                        </div>
                    </div>
                    " : "") . "

                    " . (!empty($b['coach_message']) ? "
                    <!-- Coach Notes -->
                    <div style='background: #fff8e6; padding: 12px 16px; border-radius: 12px; border: 1px solid #fde68a;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-comment-dots' style='color: #d97706;'></i> " . __('coach_notes_title') . "
                        </div>
                        <div style='font-size: 0.88rem; color: #78350f;'>
                            " . htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])) . "
                        </div>
                    </div>
                    " : "") . "

                    <!-- User Rating & Review Block -->
                    " . (!empty($b['user_rating']) ? "
                    <div style='background: #fffbebf5; padding: 12px 16px; border-radius: 12px; border: 1px solid #fde68a;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #d97706; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-star' style='color: #f59e0b;'></i> " . __('member_rating_title') . " " . str_repeat('⭐', intval($b['user_rating'])) . " (" . $b['user_rating'] . "/5)
                        </div>
                        <div style='font-size: 0.88rem; color: #78350f;'>
                            \"" . htmlspecialchars($b['user_review'] ?: __('no_comment_provided')) . "\"
                        </div>
                    </div>
                    " : ($is_attended && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $b['user_id'] ? "
                    <div style='background: #fffbebf5; padding: 12px 16px; border-radius: 12px; border: 1px dashed #f59e0b; text-align: center;'>
                        <p style='margin: 0 0 8px 0; font-size: 0.85rem; color: #d97706; font-weight: 600;'>" . __('session_completed_eval_prompt') . "</p>
                        <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'; openUserEvalModal({$b['id']}, '" . addslashes(($is_vi_lang ? "HLV " : "Coach ") . $b['coach_name'] . " (" . $b_date_formatted . ")") . "');\" style='background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3);'>
                            <i class='fas fa-star'></i> " . __('rate_session_now_btn') . "
                        </button>
                    </div>
                    " : "")) . "

                    <!-- Coach Performance Notes Block -->
                    " . (!empty($b['coach_session_notes']) ? "
                    <div style='background: #f0fdf4; padding: 12px 16px; border-radius: 12px; border: 1px solid #bbf7d0;'>
                        <div style='font-size: 0.78rem; font-weight: 700; color: #166534; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;'>
                            <i class='fas fa-clipboard-list' style='color: #16a34a;'></i> " . __('coach_guidance_notes_title') . "
                        </div>
                        <div style='font-size: 0.88rem; color: #14532d;'>
                            \"" . htmlspecialchars($b['coach_session_notes']) . "\"
                        </div>
                    </div>
                    " : "") . "

                    " . ($is_attended && isset($_SESSION['user_id']) && isset($_SESSION['role']) && ($_SESSION['role'] === 'coach' || in_array($_SESSION['role'], ['admin', 'manager'])) ? "
                    <div style='margin-top: 4px;'>
                        <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'; openCoachNotesModal({$b['id']}, '" . addslashes($client_name . " (" . $b_date_formatted . ")") . "', '" . addslashes($b['coach_session_notes'] ?? '') . "');\" style='background: linear-gradient(135deg, #16a34a, #15803d); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 3px 10px rgba(22, 163, 74, 0.25);'>
                            <i class='fas fa-pen'></i> " . (!empty($b['coach_session_notes']) ? __('edit_session_notes_btn') : __('add_session_notes_btn')) . "
                        </button>
                    </div>
                    " : "") . "
                </div>

                <div style='margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 10px; align-items: center; flex-wrap: wrap;'>
                    " . ($can_user_edit ? "
                    <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'; openUserRescheduleModal({$b['id']}, '{$b_datetime_local}', '{$b_coach_msg_escaped}', '" . addslashes(htmlspecialchars($b['coach_name'])) . "');\" style='padding: 10px 18px; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; border: 1px solid #c9a84c; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); transition: all 0.2s;'>
                        <i class='far fa-clock'></i> " . __('reschedule_session_btn') . "
                    </button>
                    " : "") . "
                    " . ($can_user_cancel ? "
                    <form method='POST' style='margin: 0;' onsubmit=\"return confirm('" . addslashes(__('confirm_cancel_session_prompt')) . "');\">
                        <input type='hidden' name='action_user_cancel_booking' value='1'>
                        <input type='hidden' name='booking_id' value='{$b['id']}'>
                        <input type='hidden' name='week_offset' value='{$week_offset}'>
                        <button type='submit' style='padding: 10px 18px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #ffffff; border: none; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); transition: all 0.2s;'>
                            <i class='fas fa-calendar-times'></i> " . __('cancel_session_btn') . "
                        </button>
                    </form>
                    " : "") . "
                    <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'\" style='padding: 10px 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;'>
                        " . __('close') . "
                    </button>
                </div>
            </div>
        </div>";

        if (isset($_SESSION['user_id'])) {
            $staff_view_details_btn = "
            <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='flex'\" style='flex-shrink:0; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; white-space:nowrap;'>
                <i class='fas fa-info-circle' style='color: #0062ff;'></i> " . __('view_full_details') . "
            </button>";

            $time_str = "
            <div class='booking-card staff' style='padding: 8px 10px 8px 13px; font-size: 0.82rem; {$staff_card_style}'>
                <div style='font-size:0.75rem; font-weight:700; color:var(--cal-navy); margin-bottom:6px; display:flex; align-items:center; gap:5px;'>
                    <i class='far fa-clock' style='color:var(--cal-gold); font-size:0.7rem;'></i> {$time} – {$end_time}
                    <span style='margin-left:auto;'>{$manager_edit_btn}</span>
                </div>
                <div style='display:flex; align-items:center; gap:6px; flex-wrap:wrap;'>
                    {$staff_badge}
                    {$staff_view_details_btn}
                </div>
            </div>
            {$staff_details_modal_html}";
        } else {
            $guest_badge = "<div style='display:inline-flex; align-items:center; gap:4px; background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; padding:3px 8px; border-radius:12px; font-weight:800; font-size:0.72rem; text-transform:uppercase;'><i class='fas fa-lock'></i> " . __('guest_slot_booked') . "</div>";

            $guest_view_details_btn = "
            <button type='button' onclick=\"openGuestLoginPromptModal()\" style='flex-shrink:0; background:#1c2340; color:#c9a84c; border:1px solid #c9a84c; padding:4px 9px; border-radius:6px; font-size:0.72rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px; white-space:nowrap;'>
                <i class='fas fa-lock' style='color:#c9a84c;'></i> " . __('view_full_details') . "
            </button>";

            $time_str = "
            <div class='booking-card staff' style='padding: 8px 10px 8px 13px; font-size: 0.82rem; background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #dc2626;'>
                <div style='font-size:0.75rem; font-weight:700; color:var(--cal-navy); margin-bottom:6px; display:flex; align-items:center; gap:5px;'>
                    <i class='far fa-clock' style='color:var(--cal-gold); font-size:0.7rem;'></i> {$time} – {$end_time}
                </div>
                <div style='display:flex; align-items:center; gap:6px; flex-wrap:wrap;'>
                    {$guest_badge}
                    {$guest_view_details_btn}
                </div>
            </div>";
        }
        
        $roster[$b['coach_id']][$date][] = $time_str;
    }

    $day_names = [
        __('day_monday'),
        __('day_tuesday'),
        __('day_wednesday'),
        __('day_thursday'),
        __('day_friday'),
        __('day_saturday'),
        __('day_sunday')
    ];
    ?>
    
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && !in_array($_SESSION['role'], ['admin', 'manager'])): 
        $user_id = $_SESSION['user_id'];
        
        if ($_SESSION['role'] === 'coach') {
            $stmt_c_id = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
            $stmt_c_id->execute([$user_id]);
            $coach_pk = $stmt_c_id->fetchColumn();

            $stmt_user_b = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
                       c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
                       c.room_number as coach_room_number,
                       mem.mem_plan_name, mem.pt_sessions_total, mem.pt_sessions_used
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id
                JOIN coaches c ON b.coach_id = c.id
                JOIN users c_u ON c.user_id = c_u.id
                LEFT JOIN (
                    SELECT ms.user_id, mp.name as mem_plan_name, 
                           COALESCE(NULLIF(ms.pt_sessions_total, 0), mp.pt_sessions, 0) as pt_sessions_total, 
                           ms.pt_sessions_used
                    FROM member_subscriptions ms
                    JOIN membership_plans mp ON ms.plan_id = mp.id
                    WHERE ms.status = 'active' AND ms.end_date >= CURDATE()
                ) mem ON mem.user_id = b.user_id
                WHERE b.coach_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
                ORDER BY b.booking_date ASC
            ");
            $stmt_user_b->execute([$coach_pk, $start_date_str, $end_date_str]);
        } else {
            $stmt_user_b = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
                       c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
                       c.room_number as coach_room_number,
                       mem.mem_plan_name, mem.pt_sessions_total, mem.pt_sessions_used
                FROM bookings b 
                JOIN users u ON b.user_id = u.id
                JOIN coaches c ON b.coach_id = c.id
                JOIN users c_u ON c.user_id = c_u.id
                LEFT JOIN (
                    SELECT ms.user_id, mp.name as mem_plan_name, 
                           COALESCE(NULLIF(ms.pt_sessions_total, 0), mp.pt_sessions, 0) as pt_sessions_total, 
                           ms.pt_sessions_used
                    FROM member_subscriptions ms
                    JOIN membership_plans mp ON ms.plan_id = mp.id
                    WHERE ms.status = 'active' AND ms.end_date >= CURDATE()
                ) mem ON mem.user_id = b.user_id
                WHERE b.user_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
                ORDER BY b.booking_date ASC
            ");
            $stmt_user_b->execute([$user_id, $start_date_str, $end_date_str]);
        }
        $user_bookings = $stmt_user_b->fetchAll();

        $user_roster = [];
        foreach ($user_bookings as $b) {
            $date = date('Y-m-d', strtotime($b['booking_date']));
            $time = date('H:i', strtotime($b['booking_date']));
            $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
            
            $b_mem_plan  = $b['mem_plan_name'] ?? null;
            $b_pt_total  = (int)($b['pt_sessions_total'] ?? 0);
            $b_pt_used   = (int)($b['pt_sessions_used'] ?? 0);
            $b_pt_rem    = max(0, $b_pt_total - $b_pt_used);

            $client_membership_box = "";
            $client_card_badge = "";
            $is_vi_lang = (($_SESSION['lang'] ?? 'vi') === 'vi');
            if (!empty($b_mem_plan)) {
                $p_name_safe = htmlspecialchars($b_mem_plan);
                $is_expired_quota = ($b_pt_rem === 0 || $b_pt_used >= $b_pt_total);
                
                if ($is_expired_quota) {
                    $client_membership_box = "
                    <div style='background: #fef2f2; border: 1.5px solid #fca5a5; padding: 12px 14px; border-radius: 12px; margin-top: 8px;'>
                        <div style='display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;'>
                            <div>
                                <span style='font-size: 0.72rem; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;'>
                                    <i class='fas fa-exclamation-triangle' style='color: #dc2626;'></i> " . ($is_vi_lang ? 'Gói PT (Đã Hết Hạn)' : 'PT Package (Expired)') . "
                                </span>
                                <strong style='font-size: 0.92rem; color: #991b1b; display: block; margin-top: 2px;'>{$p_name_safe}</strong>
                            </div>
                            <div style='background: #dc2626; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; box-shadow: 0 2px 6px rgba(220,38,38,0.25); display: flex; align-items: center; gap: 5px;'>
                                <i class='fas fa-times-circle'></i> " . ($is_vi_lang ? "Đã dùng {$b_pt_used}/{$b_pt_total} buổi" : "Used {$b_pt_used}/{$b_pt_total}") . "
                            </div>
                        </div>
                        <div style='margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;'>
                            <a href='/Gym Manager/pages/membership.php' style='background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none;'>🔄 " . ($is_vi_lang ? 'Gia Hạn Gói' : 'Renew Plan') . "</a>
                            <a href='/Gym Manager/pages/membership.php' style='background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none;'>✨ " . ($is_vi_lang ? 'Chọn Gói Khác' : 'Choose New') . "</a>
                        </div>
                    </div>";
                } else {
                    $client_membership_box = "
                    <div style='background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1.5px solid #93c5fd; padding: 10px 14px; border-radius: 12px; margin-top: 8px;'>
                        <div style='display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;'>
                            <div>
                                <span style='font-size: 0.72rem; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;'>
                                    <i class='fas fa-id-card' style='color: #2563eb;'></i> " . ($is_vi_lang ? 'Gói Thành Viên (Active)' : 'Active Membership Plan') . "
                                </span>
                                <strong style='font-size: 0.92rem; color: #1e3a8a; display: block; margin-top: 2px;'>{$p_name_safe}</strong>
                            </div>
                            <div style='background: #ffffff; border: 1.5px solid #2563eb; color: #15803d; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; box-shadow: 0 2px 6px rgba(37,99,235,0.15); display: flex; align-items: center; gap: 5px;'>
                                <i class='fas fa-dumbbell' style='color: #2563eb;'></i> " . ($is_vi_lang ? "Còn {$b_pt_rem} / {$b_pt_total} buổi PT" : "{$b_pt_rem} / {$b_pt_total} PT left") . "
                            </div>
                        </div>
                        <div style='font-size: 0.75rem; color: #1d4ed8; margin-top: 6px; line-height: 1.3;'>
                            ℹ️ <strong>" . ($is_vi_lang ? 'Quy tắc điểm danh:' : 'Attendance Rule:') . "</strong> " . ($is_vi_lang ? 'Có mặt / Vắng mặt đều trừ 1 buổi PT.' : 'Both Present & Absent deduct 1 PT session.') . "
                        </div>
                    </div>";
                }

                $client_card_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: " . ($is_expired_quota ? "#fee2e2" : "#dbeafe") . "; color: " . ($is_expired_quota ? "#dc2626" : "#1e40af") . "; border: 1px solid " . ($is_expired_quota ? "#fca5a5" : "#93c5fd") . "; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 800; margin: 4px 0;'><i class='fas fa-id-card'></i> {$p_name_safe} (" . ($is_expired_quota ? "Hết Hạn" : "{$b_pt_rem}/{$b_pt_total} PT") . ")</div>";
            } else {
                $client_membership_box = "
                <div style='background: #f8fafc; border: 1px dashed #cbd5e1; padding: 8px 12px; border-radius: 10px; margin-top: 8px; font-size: 0.78rem; color: #64748b; font-weight: 600;'>
                    👤 <strong>" . ($is_vi_lang ? 'Khách Thường (Không gói TV):' : 'Standard Client (No Membership):') . "</strong> " . ($is_vi_lang ? 'Nếu điểm danh Vắng mặt, hệ thống sẽ TỰ ĐỘNG HỦY buổi tập.' : 'If marked Absent, session will be AUTO-CANCELLED.') . "
                </div>";

                $client_card_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; margin: 4px 0;'><i class='fas fa-user'></i> " . ($is_vi_lang ? 'Khách thường' : 'Standard') . "</div>";
            }

            $session_check_buttons_html = "";
            if ($b_pt_total > 0 && isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
                $session_check_buttons_html .= "
                <div style='margin-top: 6px; background: #ffffff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 10px 12px;'>
                    <div style='font-size: 0.78rem; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;'>
                        <span><i class='fas fa-list-check' style='color: #2563eb;'></i> ATTENDANCE PROGRESS ({$b_pt_total} SESSIONS):</span>
                        <span style='background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-size: 0.72rem; font-weight: 800;'>{$b_pt_used} / {$b_pt_total} SESSIONS</span>
                    </div>
                    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(105px, 1fr)); gap: 6px;'>";

                for ($s = 1; $s <= $b_pt_total; $s++) {
                    $is_done = ($s <= $b_pt_used);
                    $is_next = ($s == $b_pt_used + 1);

                    if ($is_done) {
                        $session_check_buttons_html .= "
                        <div style='background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 8px 6px; text-align: center;'>
                            <div style='font-weight: 800; color: #16a34a; font-size: 0.78rem;'><i class='fas fa-check-circle'></i> Session {$s}</div>
                            <div style='font-size: 0.68rem; color: #15803d; font-weight: 700; margin-top: 2px;'>✓ Used</div>
                        </div>";
                    } elseif ($is_next) {
                        $session_check_buttons_html .= "
                        <div style='background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 8px; padding: 8px 6px; text-align: center; box-shadow: 0 2px 6px rgba(59,130,246,0.15);'>
                            <div style='font-weight: 800; color: #1d4ed8; font-size: 0.78rem;'>📍 Session {$s}</div>
                            <div style='font-size: 0.68rem; color: #2563eb; font-weight: 700; margin-top: 2px;'>Current Session</div>
                        </div>";
                    } else {
                        $session_check_buttons_html .= "
                        <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 6px; text-align: center;'>
                            <div style='font-weight: 800; color: #64748b; font-size: 0.78rem;'>Session {$s}</div>
                            <div style='font-size: 0.68rem; color: #94a3b8; font-weight: 600; margin-top: 2px;'>Upcoming</div>
                        </div>";
                    }
                }

                $session_check_buttons_html .= "
                    </div>
                </div>";
            }

            $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
            $client_email = htmlspecialchars($b['client_email'] ?? '');
            $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
            $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
            $coach_email = htmlspecialchars($b['coach_email'] ?? '');
            $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
            
            $start_ts = strtotime($b['booking_date']);
            $end_ts = $start_ts + 3600;
            $now = time();
            
            $is_ongoing = ($now >= $start_ts && $now <= $end_ts && $b['status'] !== 'cancelled' && $b['attendance'] !== 'absent');
            $is_past = ($now > $end_ts);
            $is_attended = ($b['attendance'] === 'present' || $b['status'] === 'completed' || ($is_past && $b['status'] !== 'cancelled' && $b['attendance'] !== 'absent'));
            $is_absent = ($b['attendance'] === 'absent');
            $is_cancelled = ($b['status'] === 'cancelled');

            if ($is_cancelled) {
                $user_card_style = "style='background: #fef2f2; border: 1px solid #fca5a5; opacity: 0.85;'";
                $user_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; color: #dc2626; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='fas fa-ban'></i> " . __('cancelled_status') . "</div>";
            } elseif ($is_absent) {
                $user_card_style = "style='background: #fff5f5; border: 1px solid #fecaca; border-left: 4px solid #dc2626;'";
                $user_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #fee2e2; color: #dc2626; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='fas fa-user-times'></i> " . __('absent_label') . "</div>";
            } elseif ($is_ongoing) {
                $user_card_style = "style='background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 2px solid #f59e0b; border-left: 5px solid #d97706; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);'";
                $user_badge = "<div style='display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px; margin: 4px 0; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);'><i class='fas fa-running'></i> " . __('in_progress_label') . "</div>";
            } elseif ($is_attended) {
                $user_card_style = "style='background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.12);'";
                $user_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #16a34a, #15803d); color: #ffffff; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px; margin: 4px 0; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);'><i class='fas fa-check-circle'></i> " . __('attended_label') . "</div>";
            } else {
                $user_card_style = "style='background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #2563eb;'";
                $user_badge = "<div style='display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1d4ed8; padding: 3px 9px; border-radius: 12px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin: 4px 0;'><i class='far fa-calendar-check'></i> " . __('upcoming_status') . "</div>";
            }

            $class_info = "";
            if (!empty($b['class_name'])) {
                $class_info = "
                <div class='class-info-block' style='margin-top: 4px;'>
                    <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                    ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                    ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                    ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
                </div>";
            }

            $user_coach_note = "";
            if (!empty($b['coach_message'])) {
                $user_coach_note = "
                <div style='background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 6px 10px; border-radius: 8px; font-size: 0.78rem; margin-top: 6px;'>
                    <i class='fas fa-edit' style='color: #d97706; margin-right: 4px;'></i> <strong>" . __('coach_notes_title') . ":</strong> " . htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])) . "
                </div>";
            }

            $is_future = (strtotime($b['booking_date']) > time());
            $is_coach_class = !empty($b['class_name']);
            
            $cancel_btn_action = "";
            $cancel_modal_html = "";
            if (!$is_cancelled && !$is_attended && !$is_absent && $is_future && !$is_coach_class) {
                $cancel_btn_action = "
                <button type='button' onclick=\"document.getElementById('cancel-modal-{$b['id']}').style.display='flex'\" style='background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;'>
                    <i class='fas fa-times-circle'></i> " . __('cancel_session') . "
                </button>";

                $cancel_modal_html = "
                <div id='cancel-modal-{$b['id']}' style='display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px;'>
                    <div style='background: #ffffff; padding: 2rem; border-radius: 24px; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: left; position: relative; border: 1px solid #e2e8f0;'>
                        <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;'>
                            <div style='display: flex; align-items: center; gap: 12px;'>
                                <div style='width: 44px; height: 44px; border-radius: 14px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;'>
                                    <i class='fas fa-exclamation-triangle'></i>
                                </div>
                                <div>
                                    <h3 style='margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;'>" . __('cancel_session') . "</h3>
                                    <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>Session ID: #{$b['id']}</span>
                                </div>
                            </div>
                            <button type='button' onclick=\"document.getElementById('cancel-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                        </div>

                        <p style='color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1rem;'>
                            " . __('confirm_cancel_session') . "
                        </p>

                        <div style='background: #fffbebf5; border: 1px solid #fde68a; border-radius: 12px; padding: 10px 14px; font-size: 0.8rem; color: #b45309; margin-bottom: 1.25rem; line-height: 1.4;'>
                            <i class='fas fa-exclamation-circle' style='color: #d97706; margin-right: 4px;'></i> 
                            <strong>" . (($_SESSION['lang'] ?? 'vi') === 'en' ? 'Cancellation Policy:' : 'Quy định hủy lịch:') . "</strong> " . __('cannot_cancel_past_session') . "
                        </div>

                        <form method='POST' style='margin: 0;'>
                            <input type='hidden' name='action_user_cancel_booking' value='1'>
                            <input type='hidden' name='booking_id' value='{$b['id']}'>
                            <input type='hidden' name='week_offset' value='{$week_offset}'>
                            
                            <div style='margin-bottom: 1.5rem;'>
                                <label style='display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;'>" . __('cancel_reason') . "</label>
                                <textarea name='cancel_reason' placeholder='" . __('cancel_reason') . "...' style='width: 100%; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 14px; font-size: 0.9rem; font-family: inherit; resize: vertical; min-height: 75px; box-sizing: border-box; outline: none;'></textarea>
                            </div>

                            <div style='display: flex; gap: 12px;'>
                                <button type='button' onclick=\"document.getElementById('cancel-modal-{$b['id']}').style.display='none'\" style='flex: 1; padding: 12px; background: #f1f5f9; color: #475569; border: none; border-radius: 14px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s;'>
                                    " . __('cancel') . "
                                </button>
                                <button type='submit' style='flex: 1.5; padding: 12px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #ffffff; border: none; border-radius: 14px; font-weight: 700; font-size: 0.9rem; cursor: pointer; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); transition: all 0.2s;'>
                                    <i class='fas fa-times-circle' style='margin-right: 5px;'></i> " . __('cancel_session') . "
                                </button>
                            </div>
                        </form>
                    </div>
                </div>";
            }
                
            // Full details modal
            $b_date_formatted = date('d/m/Y', strtotime($b['booking_date']));
            $b_datetime_local = date('Y-m-d\TH:i', strtotime($b['booking_date']));
            $b_coach_msg_escaped = addslashes(htmlspecialchars($b['coach_message'] ?? ''));

            $my_can_edit = (!$is_attended 
                && !$is_absent 
                && !$is_cancelled 
                && strtotime($b['booking_date']) > time() 
                && empty($b['class_name']) 
                && isset($_SESSION['user_id']) 
                && ($b['user_id'] == $_SESSION['user_id'] || in_array($_SESSION['role'] ?? '', ['admin', 'manager'])));

            $my_can_cancel = ($my_can_edit && empty($b['class_name']));

            $details_modal_html = "
            <div id='details-modal-{$b['id']}' style='display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px;'>
                <div style='background: #ffffff; padding: 2rem; border-radius: 24px; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: left; position: relative; border: 1px solid #e2e8f0; max-height: 90vh; overflow-y: auto;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;'>
                        <div style='display: flex; align-items: center; gap: 12px;'>
                            <div style='width: 44px; height: 44px; border-radius: 14px; background: rgba(26, 39, 68, 0.08); color: #1a2744; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;'>
                                <i class='fas fa-info-circle'></i>
                            </div>
                            <div>
                                <h3 style='margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;'>" . __('session_details_title') . "</h3>
                                <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>ID: #{$b['id']}</span>
                            </div>
                        </div>
                        <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                    </div>

                    <div style='display: flex; flex-direction: column; gap: 1rem;'>
                        <!-- Status & Attendance Banner -->
                        <div style='background: " . ($is_ongoing ? "#fffbeb" : ($is_attended ? "#f0fdf4" : ($is_absent ? "#fff5f5" : ($is_cancelled ? "#fef2f2" : "#eff6ff")))) . "; padding: 12px 16px; border-radius: 12px; border: 1px solid " . ($is_ongoing ? "#fde68a" : ($is_attended ? "#bbf7d0" : ($is_absent ? "#fecaca" : ($is_cancelled ? "#fca5a5" : "#bfdbfe")))) . "; display: flex; flex-direction: column; gap: 10px;'>
                            <div>
                                <span style='font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;'>" . __('status') . "</span>
                                <strong style='font-size: 0.95rem; color: " . ($is_ongoing ? "#d97706" : ($is_attended ? "#15803d" : ($is_absent ? "#dc2626" : ($is_cancelled ? "#dc2626" : "#1d4ed8")))) . ";'>" . ($is_ongoing ? "<i class='fas fa-running'></i> " . __('in_progress_label') : ($is_attended ? "<i class='fas fa-check-circle'></i> " . __('attended_desc') : ($is_absent ? "<i class='fas fa-user-times'></i> " . __('absent_desc') : ($is_cancelled ? "<i class='fas fa-ban'></i> " . __('cancelled_status') : "<i class='far fa-calendar-check'></i> " . __('scheduled_session'))))) . "</strong>
                            </div>
                            " . ($b_pt_total > 0 ? $session_check_buttons_html : "") . "
                            " . (!$is_attended && !$is_absent && !$is_cancelled && isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin']) ? "
                            <div style='display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;'>
                                <form method='POST' style='margin: 0;'>
                                    <input type='hidden' name='action_coach_attendance' value='1'>
                                    <input type='hidden' name='booking_id' value='{$b['id']}'>
                                    <input type='hidden' name='attendance_value' value='present'>
                                    <input type='hidden' name='week_offset' value='{$week_offset}'>
                                    <button type='submit' style='background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);'>
                                        <i class='fas fa-check-circle'></i> " . __('mark_present') . "
                                    </button>
                                </form>
                                <form method='POST' style='margin: 0;'>
                                    <input type='hidden' name='action_coach_attendance' value='1'>
                                    <input type='hidden' name='booking_id' value='{$b['id']}'>
                                    <input type='hidden' name='attendance_value' value='absent'>
                                    <input type='hidden' name='week_offset' value='{$week_offset}'>
                                    <button type='submit' style='background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);'>
                                        <i class='fas fa-user-times'></i> " . __('mark_absent') . "
                                    </button>
                                </form>
                            </div>" : "") . "
                        </div>

                        <!-- Date & Time -->
                        <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                            <div style='font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;'>
                                <i class='far fa-clock' style='color: #c9a84c;'></i> " . __('date_time') . "
                            </div>
                            <div style='font-size: 0.95rem; font-weight: 700; color: #1e293b;'>
                                {$b_date_formatted} ({$time} - {$end_time})
                            </div>
                        </div>

                        <!-- Client Info -->
                        <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                            <div style='font-size: 0.78rem; font-weight: 700; color: #1a2744; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                                <i class='fas fa-user-circle' style='color: #1a2744;'></i> " . __('client_info_title') . "
                            </div>
                            <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                                <div><strong>" . __('client_name') . ":</strong> {$client_name}</div>
                                <div><strong>" . __('phone') . ":</strong> {$client_phone}</div>
                                <div><strong>" . __('email') . ":</strong> {$client_email}</div>
                            </div>
                            {$client_membership_box}
                        </div>

                        <!-- Coach Info -->
                        <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                            <div style='font-size: 0.78rem; font-weight: 700; color: #c9a84c; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                                <i class='fas fa-dumbbell' style='color: #c9a84c;'></i> " . __('coach_info_title') . "
                            </div>
                            <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                                <div><strong>" . __('coach') . ":</strong> " . htmlspecialchars($b['coach_name']) . "</div>
                                <div><strong>" . __('phone') . ":</strong> {$coach_phone}</div>
                                <div><strong>" . __('email') . ":</strong> {$coach_email}</div>
                                <div><strong>" . __('training_room') . ":</strong> " . htmlspecialchars($coach_room) . "</div>
                            </div>
                        </div>

                        " . (!empty($b['class_name']) ? "
                        <!-- Class Details -->
                        <div style='background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                            <div style='font-size: 0.78rem; font-weight: 700; color: #1a2744; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;'>
                                <i class='fas fa-chalkboard-teacher' style='color: #1a2744;'></i> " . __('class_name_label') . "
                            </div>
                            <div style='font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 4px;'>
                                <div><strong>Tên lớp / Class:</strong> " . htmlspecialchars($b['class_name']) . "</div>
                                " . (!empty($b['specialty']) ? "<div><strong>Chuyên môn / Specialty:</strong> " . htmlspecialchars($b['specialty']) . "</div>" : "") . "
                                " . (!empty($b['training_plan']) ? "<div><strong>Nội dung / Plan:</strong> " . htmlspecialchars($b['training_plan']) . "</div>" : "") . "
                            </div>
                        </div>
                        " : "") . "

                        " . (!empty($b['coach_message']) ? "
                        <!-- Coach Notes -->
                        <div style='background: #fff8e6; padding: 12px 16px; border-radius: 12px; border: 1px solid #fde68a;'>
                            <div style='font-size: 0.78rem; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;'>
                                <i class='fas fa-comment-dots' style='color: #d97706;'></i> " . __('coach_notes_title') . "
                            </div>
                            <div style='font-size: 0.88rem; color: #78350f;'>
                                " . htmlspecialchars(getLocalizedCoachMsg($b['coach_message'])) . "
                            </div>
                        </div>
                        " : "") . "
                    </div>

                    <div style='margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 10px; align-items: center; flex-wrap: wrap;'>
                        " . ($my_can_edit ? "
                        <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='none'; openUserRescheduleModal({$b['id']}, '{$b_datetime_local}', '{$b_coach_msg_escaped}', '" . addslashes(htmlspecialchars($b['coach_name'])) . "');\" style='padding: 10px 18px; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; border: 1px solid #c9a84c; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); transition: all 0.2s;'>
                            <i class='far fa-clock'></i> " . __('reschedule_session_btn') . "
                        </button>
                        " : "") . "
                        " . ($my_can_cancel ? "
                        <form method='POST' style='margin: 0;' onsubmit=\"return confirm('" . addslashes(__('confirm_cancel_session_prompt')) . "');\">
                            <input type='hidden' name='action_user_cancel_booking' value='1'>
                            <input type='hidden' name='booking_id' value='{$b['id']}'>
                            <input type='hidden' name='week_offset' value='{$week_offset}'>
                            <button type='submit' style='padding: 10px 18px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #ffffff; border: none; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); transition: all 0.2s;'>
                                <i class='fas fa-calendar-times'></i> " . __('cancel_session_btn') . "
                            </button>
                        </form>
                        " : "") . "
                        <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='none'\" style='padding: 10px 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;'>
                            " . __('close') . "
                        </button>
                    </div>
                </div>
            </div>";
                
            $view_details_btn = "
            <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='flex'\" style='flex-shrink:0; background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; white-space:nowrap;'>
                <i class='fas fa-info-circle' style='color:#0062ff;'></i> " . __('view_full_details') . "
            </button>";

            $time_str = "
            <div class='booking-card personal' {$user_card_style}>
                <div style='font-size:0.75rem; font-weight:700; color:var(--cal-navy); margin-bottom:6px; display:flex; align-items:center; gap:5px;'>
                    <i class='far fa-clock' style='color:var(--cal-gold); font-size:0.7rem;'></i> {$time} – {$end_time}
                </div>
                <div style='display:flex; align-items:center; gap:6px; flex-wrap:wrap;'>
                    {$user_badge}
                    {$view_details_btn}
                </div>
            </div>
            {$cancel_modal_html}
            {$details_modal_html}";
                
            $user_roster[$date][] = $time_str;
        }
    ?>
    <!-- My Weekly Roster -->
    <?php if (isset($user_roster)): ?>
    <div class="my-roster-section">
        <div class="roster-section-header">
            <h2><?php echo __('my_weekly_roster'); ?></h2>
        </div>
        <div class="week-day-header">
            <div class="wdh-label"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></div>
            <?php foreach($days as $d):
                $today_cls = ($d->format('Y-m-d') === date('Y-m-d')) ? ' today' : '';
            ?>
            <div class="wdh-day<?php echo $today_cls; ?>">
                <span class="wdh-dayname"><?php echo $day_names[array_search($d, $days)]; ?></span>
                <span class="wdh-date"><?php echo $d->format('d'); ?></span>
                <span class="wdh-month"><?php echo $d->format('M'); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="coach-row" style="background:var(--cal-paper);">
            <div class="coach-info-cell">
                <span class="coach-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <span style="font-size:0.75rem;color:var(--cal-muted);text-transform:uppercase;letter-spacing:0.05em;"><?php echo ucfirst($_SESSION['role'] ?? ''); ?></span>
            </div>
            <?php foreach($days as $d):
                $d_str = $d->format('Y-m-d');
                $has_booking = isset($user_roster[$d_str]);
            ?>
            <div class="coach-day-cell">
                <?php if ($has_booking): echo implode('', $user_roster[$d_str]);
                else: ?><span class="slot-off">—</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; /* end isset($user_roster) */ ?>
    <?php endif; /* end isset($_SESSION['user_id']) && !manager/admin */ ?>

    <!-- Staff Weekly Roster -->
    <div class="roster-section" id="staffRosterSection">
        <div class="roster-section-header">
            <h2><?php echo __('staff_weekly_roster'); ?></h2>
            <div class="week-nav-group">
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset - 1; ?>" class="btn-nav-week"><i class="fas fa-chevron-left"></i> <?php echo __('previous_week'); ?></a>
                <span class="week-indicator"><?php echo $monday->format('d/m/Y') . ' — ' . $sunday->format('d/m/Y'); ?></span>
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset + 1; ?>" class="btn-nav-week"><?php echo __('next_week'); ?> <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
        <div class="filter-strip">
            <strong><?php echo __('find_free_slots'); ?></strong>
            <input type="text" id="coachSearch" placeholder="<?php echo __('search_coach_placeholder'); ?>" class="filter-input-luxury">
            <select id="dayFilter" class="filter-select-luxury">
                <option value="all"><?php echo __('all_days'); ?></option>
                <?php foreach($day_names as $idx => $dname): ?>
                    <option value="<?php echo $idx; ?>"><?php echo $dname; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="week-day-header">
            <div class="wdh-label"><?php echo __('staff_name'); ?></div>
            <?php foreach($days as $d):
                $today_cls = ($d->format('Y-m-d') === date('Y-m-d')) ? ' today' : '';
            ?>
            <div class="wdh-day<?php echo $today_cls; ?>">
                <span class="wdh-dayname"><?php echo $day_names[array_search($d, $days)]; ?></span>
                <span class="wdh-date"><?php echo $d->format('d'); ?></span>
                <span class="wdh-month"><?php echo $d->format('M'); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="coachRowsWrapper">
        <?php foreach($coaches as $idx => $c): ?>
            <div class="coach-row" data-coach-name="<?php echo strtolower(htmlspecialchars($c['name'])); ?>" data-coach-idx="<?php echo $idx; ?>">
                <div class="coach-info-cell">
                    <span class="coach-name"><?php echo htmlspecialchars($c['name']); ?></span>
                    <a href="/Gym Manager/pages/coach_details.php?id=<?php echo $c['id']; ?>" class="btn-book-roster"><?php echo __('book_now_caps'); ?></a>
                </div>
                <?php foreach($days as $dayIdx => $d):
                    $d_str = $d->format('Y-m-d');
                    $has_booking = isset($roster[$c['id']][$d_str]);
                ?>
                <div class="coach-day-cell" data-day-idx="<?php echo $dayIdx; ?>">
                    <?php if ($has_booking): echo implode('', $roster[$c['id']][$d_str]);
                    else: ?><span class="slot-available"><?php echo __('available'); ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>
</div>

</section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const coachSearch = document.getElementById("coachSearch");
    const dayFilter   = document.getElementById("dayFilter");
    const wrapper     = document.getElementById("coachRowsWrapper");

    if (!wrapper || !coachSearch || !dayFilter) return;

    const rows = wrapper.querySelectorAll(".coach-row");

    function filterRows() {
        const searchText = coachSearch.value.toLowerCase().trim();
        const dayIdx     = parseInt(dayFilter.value);

        rows.forEach(row => {
            const name = (row.dataset.coachName || "").toLowerCase();
            let show = name.includes(searchText);

            if (show && !isNaN(dayIdx)) {
                const cell = row.querySelector(`.coach-day-cell[data-day-idx="${dayIdx}"]`);
                if (cell) {
                    const hasAvailable = cell.querySelector(".slot-available");
                    if (!hasAvailable) show = false;
                }
            }
            row.style.display = show ? "" : "none";
        });
    }

    coachSearch.addEventListener("input", filterRows);
    dayFilter.addEventListener("change", filterRows);
});
</script>


<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['manager', 'admin'])): ?>
<!-- MANAGER SCHEDULE EDIT MODAL -->
<div id="scheduleEditModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: var(--cal-paper); border: 1px solid var(--cal-border); border-top: 4px solid var(--cal-gold); width: 100%; max-width: 580px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); overflow: hidden;">
        <div style="background: var(--cal-navy); color: #fff; padding: 1.25rem 1.75rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-family: 'DM Serif Display', serif; font-size: 1.4rem; color: var(--cal-gold); font-weight: 400;">
                    <i class="fas fa-edit" style="margin-right: 8px;"></i>
                    <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Chỉnh Sửa Lịch Tập & Thay HLV' : 'Edit Schedule & Substitute Coach'; ?>
                </h3>
                <span style="font-size: 0.82rem; color: #cbd5e1;" id="modal_client_subtitle">
                    Client: DAT USER
                </span>
            </div>
            <button type="button" onclick="closeScheduleEditModal()" style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        
        <form method="POST" style="padding: 1.75rem; display: flex; flex-direction: column; gap: 1.1rem;">
            <input type="hidden" name="action_update_schedule" value="1">
            <input type="hidden" name="booking_id" id="modal_booking_id" value="">
            <input type="hidden" name="week_offset" value="<?php echo $week_offset; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.3px;">
                        <i class="fas fa-user-ninja" style="color: var(--cal-gold); margin-right: 4px;"></i>
                        <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'HLV Thay Thế' : 'Substitute Coach'; ?>
                    </label>
                    <select name="coach_id" id="modal_coach_id" class="filter-select-luxury" style="width: 100%; border-radius: 8px; font-weight: 600;" required>
                        <?php foreach ($coaches as $c_item): ?>
                            <option value="<?php echo $c_item['id']; ?>"><?php echo htmlspecialchars($c_item['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.3px;">
                        <i class="fas fa-info-circle" style="color: var(--cal-gold); margin-right: 4px;"></i>
                        <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Trạng Thái' : 'Booking Status'; ?>
                    </label>
                    <select name="status" id="modal_status" class="filter-select-luxury" style="width: 100%; border-radius: 8px; font-weight: 600;">
                        <option value="confirmed">CONFIRMED</option>
                        <option value="pending">PENDING</option>
                        <option value="completed">COMPLETED</option>
                        <option value="cancelled">CANCELLED</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.3px;">
                        <i class="fas fa-user-check" style="color: #16a34a; margin-right: 4px;"></i>
                        <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Điểm Danh' : 'Attendance'; ?>
                    </label>
                    <select name="attendance" id="modal_attendance" class="filter-select-luxury" style="width: 100%; border-radius: 8px; font-weight: 600;">
                        <option value="pending"><?php echo __('pending'); ?></option>
                        <option value="present"><?php echo __('attended_label'); ?> (Present)</option>
                        <option value="absent"><?php echo __('absent_label'); ?> (Absent)</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="far fa-clock" style="color: var(--cal-gold); margin-right: 4px;"></i>
                    <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Ngày & Giờ Tập Mới' : 'New Date & Time'; ?>
                </label>
                <input type="datetime-local" name="booking_date" id="modal_booking_date" class="filter-input-luxury" style="width: 100%; border-radius: 8px; font-weight: 600;" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--cal-muted); margin-bottom: 4px;">
                    <i class="fas fa-comment-alt" style="color: var(--cal-gold); margin-right: 4px;"></i>
                    <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Ghi Chú / Lý Do Thay Đổi HLV' : 'Note / Substitute Reason'; ?>
                </label>
                <textarea name="coach_message" id="modal_coach_message" placeholder="<?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Ghi chú cho học viên hoặc lý do thay HLV...' : 'Notes for student or emergency coach substitute reason...'; ?>" class="filter-input-luxury" style="width: 100%; border-radius: 8px; height: 65px; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--cal-border);">
                <button type="button" onclick="closeScheduleEditModal()" class="btn-nav-week" style="border-radius: 6px;">
                    <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Hủy Bỏ' : 'Cancel'; ?>
                </button>
                <button type="submit" class="btn-nav-week" style="background: var(--cal-navy); color: var(--cal-gold); border-color: var(--cal-navy); border-radius: 6px; font-weight: 700;">
                    <i class="fas fa-save" style="margin-right: 6px;"></i>
                    <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Lưu Thay Đổi' : 'Save Changes'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleEditModal(btn) {
    const ds = btn.dataset;
    document.getElementById('modal_booking_id').value = ds.id || '';
    document.getElementById('modal_coach_id').value = ds.coachId || '';
    document.getElementById('modal_booking_date').value = ds.date || '';
    document.getElementById('modal_status').value = ds.status || 'confirmed';
    if (document.getElementById('modal_attendance')) {
        document.getElementById('modal_attendance').value = ds.attendance || 'pending';
    }
    document.getElementById('modal_coach_message').value = ds.msg || '';
    
    const clientSub = document.getElementById('modal_client_subtitle');
    if (clientSub) {
        clientSub.innerHTML = 'Client: <strong>' + (ds.client || 'Student') + '</strong> (Booking #' + ds.id + ')';
    }
    
    const modal = document.getElementById('scheduleEditModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeScheduleEditModal() {
    const modal = document.getElementById('scheduleEditModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('scheduleEditModal');
    if (e.target === modal) {
        closeScheduleEditModal();
    }
});
</script>
<?php endif; ?>

<!-- User Reschedule Booking Modal -->
<div id="userRescheduleModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px;">
    <div style="background: #ffffff; padding: 2.2rem; border-radius: 24px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); text-align: left; position: relative; border: 1px solid #e2e8f0; animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 14px; background: rgba(201, 168, 76, 0.15); color: #c9a84c; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; border: 1px solid rgba(201, 168, 76, 0.3);">
                    <i class="far fa-clock"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #0f172a; font-family: 'Playfair Display', Georgia, serif;">
                        <?php echo __('reschedule_modal_title'); ?>
                    </h3>
                    <span id="user_reschedule_subtitle" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"></span>
                </div>
            </div>
            <button type="button" onclick="closeUserRescheduleModal()" style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;"><i class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="/Gym Manager/features/calendar.php">
            <input type="hidden" name="action_user_reschedule_booking" value="1">
            <input type="hidden" name="booking_id" id="user_reschedule_booking_id">
            <input type="hidden" name="week_offset" value="<?php echo $week_offset; ?>">

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #1a2744; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="far fa-calendar-alt" style="color: #c9a84c; margin-right: 4px;"></i>
                        <?php echo __('new_date_time_label'); ?>
                    </label>
                    <input type="datetime-local" name="new_booking_date" id="user_reschedule_new_date" class="filter-input-luxury" style="width: 100%; border-radius: 12px; font-weight: 700; padding: 0.85rem; border: 1.5px solid #cbd5e1; outline: none; font-size: 0.95rem; color: #0f172a;" required>
                </div>

                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #1a2744; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-comment-alt" style="color: #c9a84c; margin-right: 4px;"></i>
                        <?php echo __('reschedule_reason_label'); ?>
                    </label>
                    <textarea name="reschedule_reason" id="user_reschedule_reason" placeholder="<?php echo __('reschedule_reason_placeholder'); ?>" class="filter-input-luxury" style="width: 100%; border-radius: 12px; height: 85px; font-family: inherit; resize: vertical; padding: 0.85rem; border: 1.5px solid #cbd5e1; outline: none; font-size: 0.9rem; color: #0f172a;"></textarea>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="closeUserRescheduleModal()" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                    <?php echo __('cancel'); ?>
                </button>
                <button type="submit" style="padding: 10px 22px; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; border: 1px solid #c9a84c; border-radius: 12px; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.3);">
                    <i class="fas fa-check-circle"></i>
                    <?php echo __('save_changes'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserRescheduleModal(bookingId, currentDateTime, reason, coachName) {
    document.getElementById('user_reschedule_booking_id').value = bookingId;
    document.getElementById('user_reschedule_new_date').value = currentDateTime || '';
    document.getElementById('user_reschedule_reason').value = reason || '';
    
    const sub = document.getElementById('user_reschedule_subtitle');
    if (sub) {
        sub.innerText = 'Booking #' + bookingId + (coachName ? ' - Coach: ' + coachName : '');
    }

    const modal = document.getElementById('userRescheduleModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeUserRescheduleModal() {
    const modal = document.getElementById('userRescheduleModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('userRescheduleModal');
    if (e.target === modal) {
        closeUserRescheduleModal();
    }
});
</script>

<!-- Guest Login Prompt Modal -->
<div id="guestLoginPromptModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px;">
    <div style="background: #ffffff; padding: 2.2rem; border-radius: 24px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); text-align: center; position: relative; border: 1px solid #e2e8f0; animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <div style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25); border: 2px solid #c9a84c;">
            <i class="fas fa-lock"></i>
        </div>

        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; font-family: 'Playfair Display', Georgia, serif;">
            <?php echo __('guest_login_required_title'); ?>
        </h3>

        <p style="margin: 0 0 1.75rem 0; color: #64748b; font-size: 0.95rem; line-height: 1.6;">
            <?php echo __('guest_login_required_msg'); ?>
        </p>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="/Gym Manager/auth/login.php" class="cta-btn" style="width: 100%; padding: 0.95rem; background: linear-gradient(135deg, #1c2340, #0f172a); color: #c9a84c; border: 1px solid #c9a84c; border-radius: 12px; font-weight: 800; font-size: 1rem; text-decoration: none; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.3); transition: all 0.2s;">
                <i class="fas fa-sign-in-alt"></i> <?php echo __('guest_login_btn'); ?>
            </a>

            <a href="/Gym Manager/auth/register.php" style="width: 100%; padding: 0.85rem; background: #f8fafc; color: #334155; border: 1.5px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                <i class="fas fa-user-plus"></i> <?php echo __('guest_register_btn'); ?>
            </a>

            <button type="button" onclick="closeGuestLoginPromptModal()" style="margin-top: 0.25rem; background: none; border: none; color: #94a3b8; font-weight: 600; font-size: 0.88rem; cursor: pointer; padding: 6px;">
                <?php echo __('close'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function openGuestLoginPromptModal() {
    const modal = document.getElementById('guestLoginPromptModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeGuestLoginPromptModal() {
    const modal = document.getElementById('guestLoginPromptModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('guestLoginPromptModal');
    if (e.target === modal) {
        closeGuestLoginPromptModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

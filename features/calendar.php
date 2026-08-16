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
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager') {
        $booking_id = (int)$_POST['booking_id'];
        $new_coach_id = (int)$_POST['coach_id'];
        $new_booking_date = $_POST['booking_date'];
        $new_status = $_POST['status'];
        $new_coach_message = trim($_POST['coach_message'] ?? '');
        $week_offset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;
        
        try {
            $stmt_upd = $pdo->prepare("
                UPDATE bookings 
                SET coach_id = ?, 
                    booking_date = ?, 
                    status = ?, 
                    coach_message = ?, 
                    coach_seen = 0, 
                    user_seen = 0 
                WHERE id = ?
            ");
            $stmt_upd->execute([
                $new_coach_id,
                $new_booking_date,
                $new_status,
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
                    $mail->Subject = "📅 [Gym Manager] Schedule Update Notification / Cập Nhật Lịch Tập";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff;'>";
                    $mail_body .= "<h2 style='color: #1c2340; border-bottom: 2px solid #b08d57; padding-bottom: 10px; margin-top: 0;'>📅 Training Schedule Updated / Cập Nhật Lịch Tập</h2>";
                    $mail_body .= "<p style='color: #475569; font-size: 15px;'>Your training schedule has been updated by the Manager / Lịch tập của bạn đã được quản lý cập nhật.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 18px; border-left: 4px solid #b08d57; border-radius: 8px; margin: 20px 0;'>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Client / Học viên:</strong> " . htmlspecialchars($student_info['name']) . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Assigned Coach / HLV:</strong> " . htmlspecialchars($coach_info['name'] ?? 'N/A') . "</p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Date & Time / Thời gian tập:</strong> <span style='color: #2563eb; font-weight: bold;'>" . date('l, d/m/Y - H:i', strtotime($new_booking_date)) . "</span></p>";
                    $mail_body .= "<p style='margin: 6px 0;'><strong>Status / Trạng thái:</strong> <span style='color: #16a34a; font-weight: bold;'>" . strtoupper($new_status) . "</span></p>";
                    if (!empty($new_coach_message)) {
                        $mail_body .= "<p style='margin: 6px 0;'><strong>Manager Note / Ghi chú:</strong> " . htmlspecialchars($new_coach_message) . "</p>";
                    }
                    $mail_body .= "</div>";
                    $mail_body .= "<p style='color: #64748b; font-size: 13px;'>Please log in to Gym Manager to view full details / Vui lòng đăng nhập hệ thống để xem chi tiết.</p>";
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
            $stmt_check = $pdo->prepare("SELECT id, status, booking_date, class_name, user_id FROM bookings WHERE id = ?");
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
?>

<div style="padding-top: 120px; background: var(--bg-cream); min-height: 100vh;">
<section id="calendar" style="padding: 4rem 2rem; max-width: 1440px; margin: 0 auto;">
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
<style>
    /* ────────────────────────────────────────────────────────────────
       CALENDAR — MAISON EUROPÉENNE  ·  Atelier Design System
       Restrained luxury: ivory, warm slate, burnished gold accents
    ──────────────────────────────────────────────────────────────── */
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --cal-navy:      #1c2340;        /* deep night-navy */
        --cal-gold:      #b08d57;        /* burnished antique gold */
        --cal-gold-lt:   #e0cab98a;
        --cal-cream:     #f7f4ef;        /* warm ivory */
        --cal-paper:     #fdfcfa;        /* parchment white */
        --cal-border:    #e8e2d8;        /* barely-there warm stone */
        --cal-border-dk: #c9bfb0;
        --cal-text:      #1c2340;
        --cal-muted:     #6b6862;
        --cal-shadow:    rgba(28, 35, 64, 0.06);
    }

    /* ── Page background ── */
    div[style*="background: var(--bg-cream)"] {
        background: #f5f2ec !important;
    }

    /* ── Wrapper ── */
    #calendar {
        padding: 3.5rem 2rem 5rem;
        max-width: 1480px;
        margin: 0 auto;
        font-family: 'Inter', sans-serif;
        color: var(--cal-text);
    }

    /* ─────────────── PAGE TITLE ─────────────── */
    .calendar-title-wrapper {
        text-align: center;
        margin-bottom: 4.5rem;
    }
    .calendar-title-wrapper h2 {
        font-family: 'DM Serif Display', serif;
        font-weight: 400;
        font-size: 3.8rem;
        color: var(--cal-navy);
        letter-spacing: -0.5px;
        line-height: 1.15;
        margin-bottom: 0;
    }
    .calendar-title-wrapper h2 em {
        font-style: italic;
        color: var(--cal-gold);
    }
    .calendar-title-wrapper .underline {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin: 1.4rem auto 1.2rem;
    }
    .calendar-title-wrapper .underline::before,
    .calendar-title-wrapper .underline::after {
        content: '';
        display: block;
        width: 60px;
        height: 1px;
        background: var(--cal-gold);
        opacity: 0.6;
    }
    .calendar-title-wrapper .underline-diamond {
        color: var(--cal-gold);
        font-size: 0.55rem;
        opacity: 0.85;
        letter-spacing: 4px;
    }
    .calendar-title-wrapper p {
        font-family: 'Inter', sans-serif;
        color: var(--cal-muted);
        font-size: 1rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-weight: 500;
    }

    /* ─────────────── ROSTER PANEL ─────────────── */
    .roster-container {
        background: var(--cal-paper);
        padding: 3rem 3.5rem;
        border: 1px solid var(--cal-border);
        border-top: 3px solid var(--cal-navy);
        box-shadow: 0 4px 40px var(--cal-shadow), 0 1px 0 var(--cal-border-dk);
        margin-bottom: 3rem;
        overflow-x: auto;
        position: relative;
    }
    .roster-container::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
        pointer-events: none;
    }

    .roster-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        flex-wrap: wrap;
        gap: 1.5rem;
        padding-bottom: 1.8rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .roster-header-flex h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 2.1rem;
        font-weight: 400;
        color: var(--cal-navy);
        margin: 0;
        letter-spacing: -0.3px;
    }

    /* ─────────────── WEEK NAV ─────────────── */
    .week-nav-group {
        display: flex;
        align-items: center;
        gap: 0;
    }
    .btn-nav-week {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.82rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--cal-navy);
        border: 1px solid var(--cal-border-dk);
        background: transparent;
        padding: 10px 20px;
        cursor: pointer;
        transition: all 0.22s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-nav-week:hover {
        background: var(--cal-navy);
        color: var(--cal-gold);
        border-color: var(--cal-navy);
    }
    .week-indicator {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 10px 22px;
        background: var(--cal-cream);
        border-top: 1px solid var(--cal-border-dk);
        border-bottom: 1px solid var(--cal-border-dk);
        color: var(--cal-navy);
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    /* ─────────────── FILTER PANEL ─────────────── */
    .filter-panel-luxury {
        display: flex;
        gap: 14px;
        margin-bottom: 2rem;
        align-items: center;
        background: var(--cal-cream);
        padding: 1.1rem 1.6rem;
        border: 1px solid var(--cal-border);
        flex-wrap: wrap;
    }
    .filter-panel-luxury strong {
        font-family: 'DM Serif Display', serif;
        font-style: italic;
        font-size: 1.25rem;
        color: var(--cal-navy);
        font-weight: 400;
        white-space: nowrap;
    }
    .filter-input-luxury,
    .filter-select-luxury {
        font-family: 'Inter', sans-serif;
        font-size: 0.92rem;
        padding: 10px 15px;
        border: 1px solid var(--cal-border-dk);
        background: var(--cal-paper);
        color: var(--cal-text);
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-input-luxury { width: 220px; }
    .filter-select-luxury { width: 180px; }
    .filter-input-luxury:focus,
    .filter-select-luxury:focus {
        border-color: var(--cal-gold);
        box-shadow: 0 0 0 3px rgba(176, 141, 87, 0.1);
    }

    /* ─────────────── TABLE ─────────────── */
    .luxury-roster-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Inter', sans-serif;
    }
    .luxury-roster-table th {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: var(--cal-navy);
        background: var(--cal-cream);
        padding: 16px 14px;
        border: 1px solid var(--cal-border);
    }
    .luxury-roster-table th.date-sub-header {
        background: #eee8de;
        padding: 9px 14px;
        font-weight: 600;
        color: var(--cal-navy);
        letter-spacing: 0.3px;
        border: 1px solid var(--cal-border);
        font-family: 'Inter', sans-serif;
        font-style: normal;
        font-size: 0.9rem;
    }
    .luxury-roster-table td {
        border: 1px solid var(--cal-border);
        padding: 14px 12px;
        background: var(--cal-paper);
        vertical-align: top;
        font-size: 0.95rem;
        transition: background 0.15s ease;
    }
    .luxury-roster-table tr:nth-child(even) td.staff-name-col,
    .luxury-roster-table tr:nth-child(even) td.booking-action-col {
        background: #faf8f4;
    }
    .luxury-roster-table tr:hover td {
        background: rgba(176, 141, 87, 0.025);
    }

    .staff-name-col {
        font-family: 'DM Serif Display', serif;
        font-weight: 400;
        font-size: 1.3rem;
        color: var(--cal-navy);
        white-space: nowrap;
        text-align: left;
        padding: 16px 20px;
        letter-spacing: 0px;
    }
    .booking-action-col { white-space: nowrap; }

    /* ─────────────── BOOK BUTTON ─────────────── */
    .btn-book-roster {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--cal-navy);
        border: 1px solid var(--cal-navy);
        background: transparent;
        padding: 8px 14px;
        cursor: pointer;
        transition: all 0.22s ease;
        text-decoration: none;
        display: inline-block;
    }
    .btn-book-roster:hover {
        background: var(--cal-navy);
        color: var(--cal-gold);
    }

    /* ─────────────── BOOKING CARDS ─────────────── */
    .booking-card {
        background: var(--cal-paper);
        border: 1px solid var(--cal-border);
        padding: 13px 14px;
        margin-bottom: 10px;
        position: relative;
        text-align: left;
        transition: all 0.22s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        padding-left: 17px;
    }
    .booking-card:last-child { margin-bottom: 0; }
    .booking-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(28, 35, 64, 0.06);
        border-color: var(--cal-gold);
    }
    .booking-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 3px; height: 100%;
    }
    .booking-card.staff::before    { background: var(--cal-navy); }
    .booking-card.personal::before { background: var(--cal-gold); }

    .booking-time {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        color: var(--cal-navy);
        margin-bottom: 9px;
        display: flex;
        align-items: center;
        gap: 7px;
        border-bottom: 1px solid var(--cal-border);
        padding-bottom: 7px;
        letter-spacing: 0px;
    }
    .booking-time i { color: var(--cal-gold); font-size: 0.8rem; }

    .booking-badge {
        padding: 4px 0;
        margin-bottom: 4px;
        border: none;
        background: transparent;
    }
    .booking-badge:last-child { margin-bottom: 0; }

    .badge-title {
        font-size: 0.84rem;
        font-weight: 700;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .booking-user .badge-title  { color: var(--cal-navy); }
    .booking-coach .badge-title { color: var(--cal-gold); }

    .badge-info {
        font-size: 0.84rem;
        color: var(--cal-muted);
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .badge-info span { display: flex; align-items: center; gap: 5px; }
    .badge-info i {
        width: 12px;
        text-align: center;
        color: var(--cal-gold);
        opacity: 0.7;
    }

    /* ─────────────── STATUS LABELS ─────────────── */
    .status-available {
        font-family: 'Inter', sans-serif;
        font-style: normal;
        color: var(--cal-gold);
        background: transparent;
        padding: 5px 0;
        display: inline-block;
        font-weight: 600;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        border: none;
    }
    .status-available::before {
        content: "◆ ";
        font-size: 0.55rem;
        color: var(--cal-gold-lt);
        vertical-align: middle;
    }

    .status-off {
        font-family: 'Inter', sans-serif;
        font-style: normal;
        color: #9a9591;
        background: transparent;
        padding: 5px 0;
        display: inline-block;
        font-weight: 500;
        font-size: 0.88rem;
        border: none;
    }
    .status-off::before {
        content: "— ";
        color: #ccc7bf;
    }

    /* ─────────────── CLASS INFO BLOCK ─────────────── */
    .class-info-block {
        font-size: 0.78rem;
        background: var(--cal-cream);
        padding: 9px 10px;
        margin-bottom: 9px;
        border-left: 2px solid var(--cal-gold);
        border-top: 1px solid var(--cal-border);
        border-right: 1px solid var(--cal-border);
        border-bottom: 1px solid var(--cal-border);
    }
    .class-info-block strong {
        color: var(--cal-navy);
        display: block;
        margin-bottom: 5px;
        font-size: 0.78rem;
        font-family: 'Jost', sans-serif;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ─────────────── CROWD TRACKER ─────────────── */
    .crowd-tracker-card {
        background: var(--cal-paper);
        border: 1px solid var(--cal-border);
        border-top: 3px solid var(--cal-navy);
        padding: 2.5rem 3rem;
        margin: 0 auto 3rem;
        max-width: 1480px;
        display: grid;
        grid-template-columns: 1fr 2.5fr;
        gap: 3rem;
        box-shadow: 0 4px 40px var(--cal-shadow);
        position: relative;
        overflow: hidden;
    }
    .crowd-tracker-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
    }
    @media (max-width: 800px) {
        .crowd-tracker-card { grid-template-columns: 1fr; gap: 2rem; padding: 1.8rem; }
    }

    .live-occupancy-box {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-right: 1px solid var(--cal-border);
        padding-right: 3rem;
        text-align: center;
    }
    @media (max-width: 800px) {
        .live-occupancy-box {
            border-right: none;
            padding-right: 0;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--cal-border);
        }
    }
    .live-circle {
        position: relative;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: var(--cal-cream);
        border: 1.5px solid var(--cal-gold);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.4rem;
        box-shadow: 0 0 0 8px rgba(176,141,87,0.06), 0 8px 30px rgba(28,35,64,0.06);
    }
    .live-percent {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.6rem;
        font-weight: 700;
        color: var(--cal-navy);
        line-height: 1;
        margin-bottom: 4px;
    }
    .live-percent-lbl {
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--cal-muted);
    }
    .live-pulse {
        position: absolute;
        top: 16px; right: 16px;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #16a34a;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4);
        animation: pulseLive 2s infinite;
    }
    @keyframes pulseLive {
        0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70%  { transform: scale(1);    box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }
    .live-lbl-status {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--cal-navy);
        margin: 5px 0 4px;
    }
    .live-lbl-desc {
        font-size: 0.78rem;
        color: var(--cal-muted);
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* ─────────────── HEATMAP ─────────────── */
    .heatmap-box {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .heatmap-title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.8rem;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .heatmap-title-row strong {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: var(--cal-navy);
        font-weight: 600;
    }
    .heatmap-legend {
        display: flex;
        gap: 14px;
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--cal-muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .legend-item { display: flex; align-items: center; gap: 5px; }
    .legend-box { width: 12px; height: 12px; }
    .heatmap-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
    }
    .heatmap-col {
        display: flex;
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
    .heatmap-day-label {
        font-family: 'Jost', sans-serif;
        font-size: 0.68rem;
        font-weight: 600;
        color: var(--cal-navy);
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: 1px;
    }
    .heatmap-slot {
        height: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 600;
        transition: all 0.22s ease;
        cursor: pointer;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        letter-spacing: 0.5px;
    }
    .heatmap-slot::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%; left: 50%;
        transform: translateX(-50%) translateY(-6px);
        background: var(--cal-navy);
        color: white;
        padding: 7px 11px;
        font-size: 0.68rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 100;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        letter-spacing: 0.3px;
    }
    .heatmap-slot:hover::after {
        opacity: 1;
        transform: translateX(-50%) translateY(-3px);
    }
    .heatmap-slot:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px var(--cal-shadow);
    }
    .load-low  { background: #f7f4ef; color: #9a9389; border-color: var(--cal-border); }
    .load-med  { background: #ede5d8; color: #8a6c3b; border-color: #d9c9af; }
    .load-high { background: var(--cal-navy); color: var(--cal-gold); border-color: var(--cal-navy); }
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
               c.room_number as coach_room_number
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        JOIN coaches c ON b.coach_id = c.id
        JOIN users c_u ON c.user_id = c_u.id
        WHERE b.booking_date >= ? AND b.booking_date <= ? AND b.status != 'cancelled'
    ");
    $stmt_b->execute([$start_date_str, $end_date_str]);
    $bookings = $stmt_b->fetchAll();

    $roster = [];
    foreach ($bookings as $b) {
        $date = date('Y-m-d', strtotime($b['booking_date']));
        $time = date('H:i', strtotime($b['booking_date']));
        $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
        
        $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
        $client_email = htmlspecialchars($b['client_email'] ?? '');
        $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
        $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
        $coach_email = htmlspecialchars($b['coach_email'] ?? '');
        $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
        
        $class_info = "";
        if (!empty($b['class_name'])) {
            $att_color = $b['attendance'] == 'present' ? 'color: #16a34a;' : ($b['attendance'] == 'absent' ? 'color: #dc2626;' : 'color: #f59e0b;');
            $att_text = $b['attendance'] == 'present' ? __('present_label') : ($b['attendance'] == 'absent' ? __('absent_label') : __('pending'));
            
            $class_info = "
            <div class='class-info-block'>
                <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
                <div style='{$att_color} font-weight: 700;'><i class='fas fa-user-check' style='width: 14px;'></i> {$att_text}</div>
            </div>";
        }
        
        $coach_note_info = "";
        if (!empty($b['coach_message'])) {
            $coach_note_info = "
            <div style='background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 6px 10px; border-radius: 8px; font-size: 0.78rem; margin-top: 6px;'>
                <i class='fas fa-edit' style='color: #d97706; margin-right: 4px;'></i> <strong>" . __('coach_notes_title') . ":</strong> " . htmlspecialchars($b['coach_message']) . "
            </div>";
        }

        $manager_edit_btn = "";
        if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['manager', 'admin'])) {
            $b_id = (int)$b['id'];
            $c_id = (int)$b['coach_id'];
            $b_date = date('Y-m-d\TH:i', strtotime($b['booking_date']));
            $b_status = htmlspecialchars($b['status'] ?? 'confirmed');
            $b_class = htmlspecialchars($b['class_name'] ?? '');
            $b_spec = htmlspecialchars($b['specialty'] ?? '');
            $b_room = htmlspecialchars($b['room_number'] ?? '');
            $b_plan = htmlspecialchars($b['training_plan'] ?? '');
            $b_msg = htmlspecialchars($b['coach_message'] ?? '');
            $b_client = htmlspecialchars($client_name);

            $manager_edit_btn = "
            <button type='button' 
                    class='btn-edit-schedule-card' 
                    data-id='{$b_id}' 
                    data-coach-id='{$c_id}' 
                    data-date='{$b_date}' 
                    data-status='{$b_status}' 
                    data-class='{$b_class}' 
                    data-spec='{$b_spec}' 
                    data-room='{$b_room}' 
                    data-plan='{$b_plan}' 
                    data-msg='{$b_msg}' 
                    data-client='{$b_client}' 
                    onclick='openScheduleEditModal(this)' 
                    title='" . (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Sửa lịch / Thay HLV' : 'Edit Schedule / Substitute Coach') . "'
                    style='background: #1c2340; color: #b08d57; border: 1px solid #b08d57; padding: 3px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-left: auto;'>
                <i class='fas fa-edit'></i> " . (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'Edit' : 'Edit') . "
            </button>";
        }

        $b_date_formatted = date('d/m/Y', strtotime($b['booking_date']));
        $is_b_cancelled = ($b['status'] === 'cancelled');

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
                            <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>ID: #{$b['id']} | " . ($is_b_cancelled ? "<span style='color: #ef4444; font-weight: 700;'>" . __('cancelled_status') . "</span>" : "<span style='color: #16a34a; font-weight: 700;'>" . __('confirm') . "</span>") . "</span>
                        </div>
                    </div>
                    <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                </div>

                <div style='display: flex; flex-direction: column; gap: 1rem;'>
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
                            <div><strong>Điểm danh / Attendance:</strong> " . ($b['attendance'] == 'present' ? "<span style='color: #16a34a; font-weight: 700;'>" . __('present_label') . "</span>" : ($b['attendance'] == 'absent' ? "<span style='color: #dc2626; font-weight: 700;'>" . __('absent_label') . "</span>" : "<span style='color: #f59e0b; font-weight: 700;'>" . __('pending') . "</span>")) . "</div>
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
                            " . htmlspecialchars($b['coach_message']) . "
                        </div>
                    </div>
                    " : "") . "
                </div>

                <div style='margin-top: 1.5rem; text-align: right;'>
                    <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='none'\" style='padding: 10px 24px; background: #1a2744; color: #ffffff; border: none; border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s;'>
                        " . __('close') . "
                    </button>
                </div>
            </div>
        </div>";

        $staff_view_details_btn = "
        <button type='button' onclick=\"document.getElementById('staff-details-modal-{$b['id']}').style.display='flex'\" style='background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;'>
            <i class='fas fa-info-circle' style='color: #0062ff;'></i> " . __('view_full_details') . "
        </button>";

        $time_str = "
        <div class='booking-card staff' style='padding: 10px 12px; font-size: 0.82rem;'>
            <div class='booking-time' style='display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;'>
                <span><i class='far fa-clock'></i> {$time} - {$end_time}</span>
                {$manager_edit_btn}
            </div>
            
            {$class_info}
            {$coach_note_info}

            <div style='margin-top: 8px; text-align: right;'>
                {$staff_view_details_btn}
            </div>
        </div>
        {$staff_details_modal_html}";
        
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
                       c.room_number as coach_room_number
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id
                JOIN coaches c ON b.coach_id = c.id
                JOIN users c_u ON c.user_id = c_u.id
                WHERE b.coach_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
                ORDER BY b.booking_date ASC
            ");
            $stmt_user_b->execute([$coach_pk, $start_date_str, $end_date_str]);
        } else {
            $stmt_user_b = $pdo->prepare("
                SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
                       c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
                       c.room_number as coach_room_number
                FROM bookings b 
                JOIN users u ON b.user_id = u.id
                JOIN coaches c ON b.coach_id = c.id
                JOIN users c_u ON c.user_id = c_u.id
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
            $is_cancelled = ($b['status'] === 'cancelled');
            
            $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
            $client_email = htmlspecialchars($b['client_email'] ?? '');
            $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
            $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
            $coach_email = htmlspecialchars($b['coach_email'] ?? '');
            $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
            
            $class_info = "";
            if (!empty($b['class_name'])) {
                $att_color = $b['attendance'] == 'present' ? 'color: #16a34a;' : ($b['attendance'] == 'absent' ? 'color: #dc2626;' : 'color: #f59e0b;');
                $att_text = $b['attendance'] == 'present' ? __('present_label') : ($b['attendance'] == 'absent' ? __('absent_label') : __('pending'));
                
                $class_info = "
                <div class='class-info-block'>
                    <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                    ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                    ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                    ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
                    <div style='{$att_color} font-weight: 700;'><i class='fas fa-user-check' style='width: 14px;'></i> {$att_text}</div>
                </div>";
            }

            $user_coach_note = "";
            if (!empty($b['coach_message'])) {
                $user_coach_note = "
                <div style='background: #fff8e6; color: #92400e; border: 1px solid #fde68a; padding: 6px 10px; border-radius: 8px; font-size: 0.78rem; margin-top: 6px;'>
                    <i class='fas fa-edit' style='color: #d97706; margin-right: 4px;'></i> <strong>" . __('coach_notes_title') . ":</strong> " . htmlspecialchars($b['coach_message']) . "
                </div>";
            }

            $is_future = (strtotime($b['booking_date']) > time());
            $is_coach_class = !empty($b['class_name']);
            $card_style = $is_cancelled ? "style='background: #fef2f2; border: 1px solid #fca5a5; opacity: 0.85;'" : "";
            
            $cancel_btn_action = "";
            $cancel_modal_html = "";
            if (!$is_cancelled && $is_future && !$is_coach_class) {
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
                                    <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>Mã buổi tập / Session ID: #{$b['id']}</span>
                                </div>
                            </div>
                            <button type='button' onclick=\"document.getElementById('cancel-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                        </div>

                        <p style='color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.25rem;'>
                            " . __('confirm_cancel_session') . "
                        </p>

                        <form method='POST' style='margin: 0;'>
                            <input type='hidden' name='action_user_cancel_booking' value='1'>
                            <input type='hidden' name='booking_id' value='{$b['id']}'>
                            <input type='hidden' name='week_offset' value='{$week_offset}'>
                            
                            <div style='margin-bottom: 1.5rem;'>
                                <label style='display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;'>" . __('cancel_reason') . " <span style='font-weight: 400; color: #94a3b8;'>(tùy chọn)</span></label>
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
                                <span style='font-size: 0.8rem; color: #64748b; font-weight: 500;'>ID: #{$b['id']} | " . ($is_cancelled ? "<span style='color: #ef4444; font-weight: 700;'>" . __('cancelled_status') . "</span>" : "<span style='color: #16a34a; font-weight: 700;'>" . __('confirm') . "</span>") . "</span>
                            </div>
                        </div>
                        <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='none'\" style='background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 4px;'><i class='fas fa-times'></i></button>
                    </div>

                    <div style='display: flex; flex-direction: column; gap: 1rem;'>
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
                                <div><strong>Điểm danh / Attendance:</strong> " . ($b['attendance'] == 'present' ? "<span style='color: #16a34a; font-weight: 700;'>" . __('present_label') . "</span>" : ($b['attendance'] == 'absent' ? "<span style='color: #dc2626; font-weight: 700;'>" . __('absent_label') . "</span>" : "<span style='color: #f59e0b; font-weight: 700;'>" . __('pending') . "</span>")) . "</div>
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
                                " . htmlspecialchars($b['coach_message']) . "
                            </div>
                        </div>
                        " : "") . "
                    </div>

                    <div style='margin-top: 1.5rem; text-align: right;'>
                        <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='none'\" style='padding: 10px 24px; background: #1a2744; color: #ffffff; border: none; border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s;'>
                            " . __('close') . "
                        </button>
                    </div>
                </div>
            </div>";
                
            $view_details_btn = "
            <button type='button' onclick=\"document.getElementById('details-modal-{$b['id']}').style.display='flex'\" style='background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 5px 12px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;'>
                <i class='fas fa-info-circle' style='color: #0062ff;'></i> " . __('view_full_details') . "
            </button>";

            $time_str = "
            <div class='booking-card personal' {$card_style}>
                <div class='booking-time' style='display: flex; justify-content: space-between; align-items: center;'>
                    <span><i class='far fa-clock'></i> {$time} - {$end_time}</span>
                    " . ($is_cancelled ? "<span style='color: #dc2626; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;'><i class='fas fa-times-circle'></i> " . __('cancelled_status') . "</span>" : "") . "
                </div>
                
                {$class_info}
                {$user_coach_note}

                <div style='margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap;'>
                    {$view_details_btn}
                    {$cancel_btn_action}
                </div>
            </div>
            {$cancel_modal_html}
            {$details_modal_html}";
                
            $user_roster[$date][] = $time_str;
        }
    ?>
    <div class="roster-container">
        <div class="roster-header-flex">
            <h2><?php echo __('my_weekly_roster'); ?></h2>
        </div>
        
        <table class="luxury-roster-table">
            <thead>
                <tr>
                    <th rowspan="2"><?php echo __('student'); ?></th>
                    <?php foreach($day_names as $idx => $dname): ?>
                        <th><?php echo $dname; ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach($days as $d): ?>
                        <th class="date-sub-header"><?php echo $d->format('d-M'); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="staff-name-col"><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
                    <?php foreach($days as $d): 
                        $d_str = $d->format('Y-m-d');
                        $has_booking = isset($user_roster[$d_str]);
                        if ($has_booking) {
                            $times = implode("", $user_roster[$d_str]);
                            echo "<td>{$times}</td>";
                        } else {
                            echo "<td><span class='status-off'>" . __('off') . "</span></td>";
                        }
                    endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="roster-container">
        <div class="roster-header-flex">
            <h2><?php echo __('staff_weekly_roster'); ?></h2>
            <div class="week-nav-group">
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset - 1; ?>" class="btn-nav-week"><i class="fas fa-chevron-left"></i> <?php echo __('previous_week'); ?></a>
                <span class="week-indicator">
                    <?php echo $monday->format('d/m/Y') . ' - ' . $sunday->format('d/m/Y'); ?>
                </span>
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset + 1; ?>" class="btn-nav-week"><?php echo __('next_week'); ?> <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="filter-panel-luxury">
            <strong><?php echo __('find_free_slots'); ?></strong>
            <input type="text" id="coachSearch" placeholder="<?php echo __('search_coach_placeholder'); ?>" class="filter-input-luxury">
            <select id="dayFilter" class="filter-select-luxury">
                <option value="all"><?php echo __('all_days'); ?></option>
                <?php foreach($day_names as $idx => $dname): ?>
                    <option value="<?php echo $idx; ?>"><?php echo $dname; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <table id="staffTable" class="luxury-roster-table">
            <thead>
                <tr>
                    <th rowspan="2"><?php echo __('staff_name'); ?></th>
                    <th rowspan="2"><?php echo __('booking'); ?></th>
                    <?php foreach($day_names as $idx => $dname): ?>
                        <th><?php echo $dname; ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach($days as $d): ?>
                        <th class="date-sub-header"><?php echo $d->format('d-M'); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($coaches as $idx => $c): ?>
                    <tr>
                        <td class="staff-name-col"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td class="booking-action-col">
                            <a href="/Gym Manager/pages/coach_details.php?id=<?php echo $c['id']; ?>" class="btn-book-roster"><?php echo __('book_now_caps'); ?></a>
                        </td>
                        <?php foreach($days as $d): 
                            $d_str = $d->format('Y-m-d');
                            $has_booking = isset($roster[$c['id']][$d_str]);
                            if ($has_booking) {
                                $times = implode("", $roster[$c['id']][$d_str]);
                                echo "<td>{$times}</td>";
                            } else {
                                echo "<td><span class='status-available'>" . __('available') . "</span></td>";
                            }
                        endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const coachSearch = document.getElementById("coachSearch");
    const dayFilter = document.getElementById("dayFilter");
    const staffTable = document.getElementById("staffTable");
    
    if (staffTable && coachSearch && dayFilter) {
        const rows = staffTable.querySelectorAll("tbody tr");

        function filterTable() {
            const searchText = coachSearch.value.toLowerCase();
            const dayIdx = dayFilter.value;

            rows.forEach(row => {
                const coachName = row.cells[0].innerText.toLowerCase();
                let show = coachName.includes(searchText);

                if (show && dayIdx !== "all") {
                    const cell = row.cells[parseInt(dayIdx) + 2];
                    if (!cell.innerText.includes("AVAILABLE") && !cell.innerText.includes("LỊCH TRỐNG")) {
                        show = false;
                    }
                }
                row.style.display = show ? "" : "none";
            });
        }

        coachSearch.addEventListener("input", filterTable);
        dayFilter.addEventListener("change", filterTable);
    }
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
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user-ninja" style="color: var(--cal-gold); margin-right: 4px;"></i>
                        <?php echo isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi' ? 'HLV Đảm Nhận / Thay Thế' : 'Assigned / Substitute Coach'; ?>
                    </label>
                    <select name="coach_id" id="modal_coach_id" class="filter-select-luxury" style="width: 100%; border-radius: 8px; font-weight: 600;" required>
                        <?php foreach ($coaches as $c_item): ?>
                            <option value="<?php echo $c_item['id']; ?>"><?php echo htmlspecialchars($c_item['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--cal-navy); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

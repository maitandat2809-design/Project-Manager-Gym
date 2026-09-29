<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

$coach_id = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : 0;
$user_id  = $_SESSION['user_id'] ?? null;
$req_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$num_days = isset($_GET['days']) ? min(14, max(1, (int)$_GET['days'])) : 7;

if ($coach_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid coach ID']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $req_date)) {
    $req_date = date('Y-m-d');
}

// Fetch coach info
$stmt_coach = $pdo->prepare("
    SELECT c.id, c.specialty, c.room_name, c.room_number, u.name as coach_name, u.avatar
    FROM coaches c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt_coach->execute([$coach_id]);
$coach = $stmt_coach->fetch();

if (!$coach) {
    echo json_encode(['success' => false, 'message' => 'Coach not found']);
    exit();
}

// Standard daily session hours (60 minutes each, 06:00 to 22:00)
$standard_hours = [
    '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
    '12:00', '13:00', '14:00', '15:00', '16:00',
    '17:00', '18:00', '19:00', '20:00', '21:00'
];

// Pre-fetch bookings for this coach across the search window (today to today + 14 days)
$today_start = date('Y-m-d 00:00:00');
$window_end  = date('Y-m-d 23:59:59', strtotime('+14 days'));

$stmt_c_bk = $pdo->prepare("
    SELECT booking_date 
    FROM bookings 
    WHERE coach_id = ? 
      AND status != 'cancelled' 
      AND booking_date BETWEEN ? AND ?
");
$stmt_c_bk->execute([$coach_id, $today_start, $window_end]);
$coach_bookings = $stmt_c_bk->fetchAll(PDO::FETCH_COLUMN);

// Pre-fetch bookings for the logged-in user to detect conflicts
$user_bookings = [];
if ($user_id) {
    $stmt_u_bk = $pdo->prepare("
        SELECT booking_date 
        FROM bookings 
        WHERE user_id = ? 
          AND status != 'cancelled' 
          AND booking_date BETWEEN ? AND ?
    ");
    $stmt_u_bk->execute([$user_id, $today_start, $window_end]);
    $user_bookings = $stmt_u_bk->fetchAll(PDO::FETCH_COLUMN);
}

// Helper to check if a specific timestamp conflicts with a list of booking datetimes (within 60 min buffer)
function check_conflict($slot_ts, $booking_dates) {
    foreach ($booking_dates as $b_date_str) {
        $b_ts = strtotime($b_date_str);
        if (abs($slot_ts - $b_ts) < 3600) {
            return true;
        }
    }
    return false;
}

// Helper function to get day label in Vietnamese or English
function get_day_label($date_str, $is_today, $is_tomorrow) {
    $curr_lang = $_SESSION['lang'] ?? 'en';
    if ($is_today) {
        return __('today_label');
    }
    if ($is_tomorrow) {
        return __('tomorrow_label');
    }
    $day_of_week = date('N', strtotime($date_str)); // 1 (Mon) to 7 (Sun)
    if ($curr_lang === 'vi') {
        $dow_names = [
            1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 
            4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ Nhật'
        ];
        return $dow_names[$day_of_week] ?? date('D', strtotime($date_str));
    } else {
        $dow_names = [
            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 
            4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
        ];
        return $dow_names[$day_of_week] ?? date('D', strtotime($date_str));
    }
}

$now = time();
$earliest_slot = null;
$date_tabs = [];

// Compute date tabs summary and search for earliest slot
for ($i = 0; $i < $num_days; $i++) {
    $d_str = date('Y-m-d', strtotime("+$i days"));
    $is_today = ($i === 0);
    $is_tomorrow = ($i === 1);
    
    $free_count = 0;
    $total_day_slots = count($standard_hours);

    foreach ($standard_hours as $h) {
        $s_ts = strtotime("$d_str $h:00");
        $is_past = ($s_ts <= ($now + 300)); // buffer 5 mins
        $c_conflict = check_conflict($s_ts, $coach_bookings);
        $u_conflict = $user_id ? check_conflict($s_ts, $user_bookings) : false;

        $available = (!$is_past && !$c_conflict && !$u_conflict);

        if ($available) {
            $free_count++;
            if ($earliest_slot === null) {
                $end_h = date('H:i', $s_ts + 3600);
                $day_disp = get_day_label($d_str, $is_today, $is_tomorrow);
                $date_disp = date('d/m', strtotime($d_str));
                
                $earliest_slot = [
                    'date'           => $d_str,
                    'time'           => $h,
                    'time_end'       => $end_h,
                    'time_display'   => "$h - $end_h",
                    'datetime_input' => date('Y-m-d\TH:i', $s_ts),
                    'datetime_db'    => date('Y-m-d H:i:s', $s_ts),
                    'human_display'  => "$day_disp ($date_disp) lúc $h - $end_h",
                    'day_name'       => $day_disp,
                    'timestamp'      => $s_ts
                ];
            }
        }
    }

    $date_tabs[] = [
        'date'            => $d_str,
        'day_name'        => get_day_label($d_str, $is_today, $is_tomorrow),
        'formatted'       => date('d/m', strtotime($d_str)),
        'available_count' => $free_count,
        'total_count'     => $total_day_slots,
        'is_today'        => $is_today,
        'is_tomorrow'     => $is_tomorrow,
        'is_active'       => ($d_str === $req_date)
    ];
}

// Compute detailed slots for the requested date ($req_date)
$slots = [];
$total_available = 0;
$total_booked    = 0;

foreach ($standard_hours as $h) {
    $s_ts = strtotime("$req_date $h:00");
    $end_h = date('H:i', $s_ts + 3600);
    $h_num = (int)substr($h, 0, 2);

    $period = 'morning';
    if ($h_num >= 12 && $h_num < 17) {
        $period = 'afternoon';
    } elseif ($h_num >= 17) {
        $period = 'evening';
    }

    $is_past = ($s_ts <= ($now + 300));
    $c_conflict = check_conflict($s_ts, $coach_bookings);
    $u_conflict = $user_id ? check_conflict($s_ts, $user_bookings) : false;

    $status = 'available';
    $status_text = __('slot_available');
    $is_available = true;

    if ($is_past) {
        $status = 'past';
        $status_text = __('slot_past');
        $is_available = false;
    } elseif ($c_conflict) {
        $status = 'booked';
        $status_text = __('slot_booked');
        $is_available = false;
        $total_booked++;
    } elseif ($u_conflict) {
        $status = 'user_conflict';
        $status_text = __('slot_conflict');
        $is_available = false;
        $total_booked++;
    } else {
        $total_available++;
    }

    $slots[] = [
        'time'           => $h,
        'time_end'       => $end_h,
        'time_display'   => "$h - $end_h",
        'datetime_input' => date('Y-m-d\TH:i', $s_ts),
        'datetime_db'    => date('Y-m-d H:i:s', $s_ts),
        'timestamp'      => $s_ts,
        'period'         => $period,
        'status'         => $status,
        'status_text'    => $status_text,
        'is_available'   => $is_available
    ];
}

echo json_encode([
    'success'         => true,
    'coach'           => [
        'id'          => (int)$coach['id'],
        'name'        => $coach['coach_name'],
        'specialty'   => $coach['specialty'],
        'room_name'   => $coach['room_name'],
        'room_number' => $coach['room_number']
    ],
    'selected_date'   => $req_date,
    'earliest_slot'   => $earliest_slot,
    'date_tabs'       => $date_tabs,
    'slots'           => $slots,
    'summary'         => [
        'total_slots'     => count($standard_hours),
        'available_slots' => $total_available,
        'booked_slots'    => $total_booked
    ]
], JSON_UNESCAPED_UNICODE);

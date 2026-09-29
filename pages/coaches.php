<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
    header("Location: /Gym Manager/index.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

$message = '';
$error = '';

if (isset($_GET['book']) && isset($_SESSION['user_id'])) {
    $coach_id = $_GET['book'];
    
    // Default booking for tomorrow at 8 AM for simplicity
    $booking_date = date('Y-m-d 08:00:00', strtotime('+1 day'));
    
    // Check if coach is already booked at that slot
    $conflict_stmt = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND status NOT IN ('cancelled', 'rejected') AND DATE(booking_date) = DATE(?) AND HOUR(booking_date) = HOUR(?)");
    $conflict_stmt->execute([$coach_id, $booking_date, $booking_date]);
    if ($conflict_stmt->fetch()) {
        $error = __('booking_conflict_coach');
    } else {
        
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status) VALUES (?, ?, ?, 'pending')");
        if ($stmt->execute([$_SESSION['user_id'], $coach_id, $booking_date])) {
            $booking_id = $pdo->lastInsertId();
            $message = __('booking_success');

            require_once __DIR__ . '/../includes/booking_mailer.php';
            sendNewBookingNotificationEmail($pdo, $booking_id);
        }
    }
} elseif (isset($_GET['book'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

$where = "WHERE 1=1";
$params = [];

if (isset($_GET['discipline']) && !empty($_GET['discipline'])) {
    $disc_val = str_replace('_', ' ', strtolower(trim($_GET['discipline'])));
    if (stripos($disc_val, 'gym') !== false || stripos($disc_val, 'fitness') !== false) {
        $where .= " AND (LOWER(c.specialty) LIKE '%gym%' OR LOWER(c.specialty) LIKE '%fitness%')";
    } elseif (stripos($disc_val, 'yoga') !== false || stripos($disc_val, 'pilates') !== false) {
        $where .= " AND (LOWER(c.specialty) LIKE '%yoga%' OR LOWER(c.specialty) LIKE '%pilates%')";
    } elseif (stripos($disc_val, 'muay') !== false || stripos($disc_val, 'kickboxing') !== false || stripos($disc_val, 'boxing') !== false) {
        $where .= " AND (LOWER(c.specialty) LIKE '%muay%' OR LOWER(c.specialty) LIKE '%kickboxing%' OR LOWER(c.specialty) LIKE '%boxing%')";
    } else {
        $where .= " AND (LOWER(c.specialty) LIKE ? OR LOWER(c.specialty) = ?)";
        $params[] = '%' . $disc_val . '%'; 
        $params[] = $disc_val; 
    }
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_input = trim($_GET['search']);
    $keywords = array_filter(explode(' ', $search_input));
    foreach ($keywords as $kw) {
        $term = '%' . $kw . '%';
        $where .= " AND (u.name LIKE ? OR c.specialty LIKE ? OR c.bio LIKE ? OR COALESCE(c.room_name, '') LIKE ?)";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
}

$stmt = $pdo->prepare("
    SELECT c.*, u.name as coach_name, u.avatar,
    COALESCE(AVG(cr.rating), 0) as avg_rating,
    COUNT(cr.id) as review_count
    FROM coaches c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN coach_reviews cr ON c.id = cr.coach_id
    $where
    GROUP BY c.id
");
$stmt->execute($params);
$coaches = $stmt->fetchAll();

// Group coaches by specialty
$grouped_coaches = [];
foreach($coaches as $coach) {
    $specialty = trim($coach['specialty'] ?? '');
    if (strcasecmp($specialty, 'gym') === 0 || empty($specialty) || stripos($specialty, 'fitness') !== false) {
        $specialty = 'Gym & Fitness';
    } elseif (stripos($specialty, 'muay') !== false || stripos($specialty, 'kickboxing') !== false || stripos($specialty, 'boxing') !== false) {
        $specialty = 'Muay Thai';
    } elseif (stripos($specialty, 'yoga') !== false || stripos($specialty, 'pilates') !== false) {
        $specialty = 'Yoga & Pilates';
    }
    if (!isset($grouped_coaches[$specialty])) {
        $grouped_coaches[$specialty] = [];
    }
    $grouped_coaches[$specialty][] = $coach;
}

// Pre-calculate next available slot for each coach (next 3 days)
$today_start = date('Y-m-d 00:00:00');
$window_end  = date('Y-m-d 23:59:59', strtotime('+3 days'));
$stmt_b_all  = $pdo->prepare("
    SELECT coach_id, booking_date 
    FROM bookings 
    WHERE status != 'cancelled' 
      AND booking_date BETWEEN ? AND ?
");
$stmt_b_all->execute([$today_start, $window_end]);
$all_coach_bookings = [];
while ($row = $stmt_b_all->fetch(PDO::FETCH_ASSOC)) {
    $all_coach_bookings[$row['coach_id']][] = $row['booking_date'];
}

$standard_hours = [
    '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
    '12:00', '13:00', '14:00', '15:00', '16:00',
    '17:00', '18:00', '19:00', '20:00', '21:00'
];

if (!function_exists('get_coach_next_slot')) {
    function get_coach_next_slot($coach_id, $bookings_map, $hours) {
        $now = time();
        $c_bks = $bookings_map[$coach_id] ?? [];
        for ($d = 0; $d < 4; $d++) {
            $d_str = date('Y-m-d', strtotime("+$d days"));
            $day_prefix = ($d === 0) ? __('today_label') : (($d === 1) ? __('tomorrow_label') : date('d/m', strtotime($d_str)));
            foreach ($hours as $h) {
                $ts = strtotime("$d_str $h:00");
                if ($ts <= ($now + 300)) continue;
                
                $conflict = false;
                foreach ($c_bks as $b_str) {
                    if (abs($ts - strtotime($b_str)) < 3600) {
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    return [
                        'date' => $d_str,
                        'time' => $h,
                        'text' => "$day_prefix $h:00"
                    ];
                }
            }
        }
        return null;
    }
}
?>

<style>
.coaches-hero-header {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 249, 246, 0.95) 100%);
    padding: 2.8rem 2rem 2.2rem;
    border-radius: 28px;
    border: 1px solid rgba(26, 39, 68, 0.08);
    box-shadow: 0 16px 45px rgba(26, 39, 68, 0.05);
    margin-bottom: 3.5rem;
    position: relative;
    text-align: center;
}
.coaches-grid-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 2rem;
}
.discipline-chips {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 1.4rem;
}
.disc-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0.55rem 1.25rem;
    border-radius: 50px;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s ease;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
}
.disc-chip:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(26, 39, 68, 0.08);
}
.disc-chip.active {
    background: var(--primary) !important;
    border-color: var(--gold) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 16px rgba(26, 39, 68, 0.2) !important;
}
.disc-chip.active i {
    color: var(--gold);
}
@media (max-width: 1024px) {
    .coaches-hero-header {
        padding: 2rem 1.2rem 1.8rem;
    }
    .coaches-grid-container {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 640px) {
    .coaches-grid-container {
        grid-template-columns: 1fr;
    }
    .discipline-chips {
        gap: 6px;
    }
    .disc-chip {
        padding: 0.45rem 0.9rem;
        font-size: 0.8rem;
    }
}
.coaches-grid-container .card {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(26, 39, 68, 0.05);
    border: 1px solid rgba(26, 39, 68, 0.07);
    transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
    overflow: hidden;
}
.coaches-grid-container .card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(26, 39, 68, 0.1);
    border-color: rgba(201, 168, 76, 0.35);
}
.coaches-grid-container .card-content {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 1.8rem;
}
.btn-card {
    background: var(--primary);
    color: #ffffff !important;
    border: 1px solid var(--primary);
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.25s ease;
    text-decoration: none;
}
.btn-card:hover {
    background: linear-gradient(135deg, #c9a84c 0%, #e8c97a 100%);
    color: #1a2744 !important;
    border-color: var(--gold);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(201, 168, 76, 0.35);
}
</style>

<section style="padding-top: 3rem;">
    <div class="coaches-hero-header">
        <div class="section-title" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: clamp(2rem, 3.8vw, 2.8rem); margin-bottom: 0.3rem;"><?php echo __('our_coaches'); ?></h2>
            <div class="underline" style="margin: 12px auto 10px;"></div>
            <p style="margin-top: 6px; color: var(--text-muted); font-size: 1.05rem; max-width: 650px; margin-left: auto; margin-right: auto;"><?php echo __('coaches_subtitle'); ?></p>
        </div>

        <?php if($message): ?>
            <p style="color: #16a34a; text-align: center; margin-bottom: 1.5rem; background: #f0fdf4; padding: 1rem; border-radius: 16px; border: 1px solid #bbf7d0; font-weight: 600;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; padding: 1rem 1.5rem; border-radius: 16px; margin-bottom: 1.5rem; text-align: center; font-weight: 600; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="search-container" style="max-width: 620px; margin: 0 auto; text-align: center;">
            <form action="coaches.php" method="GET" id="coachSearchForm" style="display: flex; gap: 10px;">
                <?php if(isset($_GET['discipline']) && !empty($_GET['discipline'])): ?>
                    <input type="hidden" name="discipline" value="<?php echo htmlspecialchars($_GET['discipline']); ?>">
                <?php endif; ?>
                <input type="text" name="search" id="coachSearchInput" placeholder="<?php echo __('search_coach'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 0.95rem 1.6rem; border-radius: 50px; border: 2px solid #e2e8f0; font-size: 0.98rem; outline: none; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--gold)'; this.style.boxShadow='0 0 0 4px rgba(201,168,76,0.15)'" onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                <button type="submit" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 1.8rem;"><i class="fas fa-search"></i></button>
                <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="/Gym Manager/pages/coaches.php<?php echo isset($_GET['discipline']) ? '?discipline='.htmlspecialchars($_GET['discipline']) : ''; ?>" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 1.2rem; background: #e2e8f0; color: #64748b; box-shadow: none;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Discipline Quick Filter Chips -->
        <?php
        $curr_disc = strtolower(trim($_GET['discipline'] ?? ''));
        $filter_chips = [
            '' => ['label' => __('all_coaches'), 'icon' => 'fas fa-users'],
            'gym' => ['label' => 'Gym & Fitness', 'icon' => 'fas fa-dumbbell'],
            'yoga' => ['label' => 'Yoga & Pilates', 'icon' => 'fas fa-leaf'],
            'muay' => ['label' => 'Muay Thai', 'icon' => 'fas fa-fist-raised']
        ];
        ?>
        <div class="discipline-chips">
            <?php foreach($filter_chips as $k => $item): 
                $is_act = ($k === '' && empty($curr_disc)) || ($k !== '' && stripos($curr_disc, $k) !== false);
                $url = '/Gym Manager/pages/coaches.php' . ($k !== '' ? '?discipline=' . $k : '') . (!empty($_GET['search']) ? ($k !== '' ? '&' : '?') . 'search=' . urlencode($_GET['search']) : '');
            ?>
                <a href="<?php echo $url; ?>" class="disc-chip <?php echo $is_act ? 'active' : ''; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('coachSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (!query || text.includes(query)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>

    <?php if (empty($grouped_coaches)): ?>
        <p style="text-align: center; color: var(--text-muted); font-size: 1.2rem; margin-top: 2rem;"><?php echo __('no_coaches_found'); ?></p>
    <?php else: ?>
        <?php foreach ($grouped_coaches as $specialty => $coach_list): ?>
            <div style="margin-bottom: 4rem;">
                <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.8rem; display: flex; align-items: center; gap: 10px;">
                    <?php if (stripos($specialty, 'Gym') !== false): ?>
                        <i class="fas fa-dumbbell" style="color: var(--primary);"></i>
                    <?php elseif (stripos($specialty, 'Yoga') !== false): ?>
                        <i class="fas fa-leaf" style="color: #10b981;"></i>
                    <?php elseif (stripos($specialty, 'Muay') !== false): ?>
                        <i class="fas fa-fist-raised" style="color: #ef4444;"></i>
                    <?php else: ?>
                        <i class="fas fa-medal" style="color: var(--primary);"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($specialty); ?> <?php echo __('programs_label'); ?>
                </h3>
                
                <div class="coaches-grid-container">
                    <?php foreach($coach_list as $coach): ?>
                        <div class="card">
                            <?php 
                            $display_img = $coach['avatar'] ?: ($coach['image'] ?: 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=2070&auto=format&fit=crop');
                            ?>
                            <img src="<?php echo $display_img; ?>" class="card-img" alt="<?php echo $coach['coach_name']; ?>">
                            <div class="card-content">
                                <div class="card-tag"><?php echo $coach['specialty']; ?></div>
                                <h3 class="card-title"><?php echo __('coach_label') . ' ' . htmlspecialchars($coach['coach_name']); ?></h3>
                                
                                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 0.8rem;">
                                    <div style="color: #f59e0b; display: flex; gap: 2px;">
                                        <?php 
                                        $rating = round($coach['avg_rating']);
                                        for($i=1; $i<=5; $i++) echo $i <= $rating ? "★" : "☆";
                                        ?>
                                    </div>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">(<?php echo $coach['review_count']; ?> <?php echo __('reviews'); ?>)</span>
                                </div>

                                <?php 
                                $next_slot = get_coach_next_slot($coach['id'], $all_coach_bookings, $standard_hours);
                                if ($next_slot):
                                ?>
                                    <div style="margin-bottom: 1rem; display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                        <i class="fas fa-bolt" style="color: #10b981;"></i> <?php echo __('earliest_free_slot_badge'); ?> <strong><?php echo $next_slot['text']; ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-bottom: 1rem; display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                        <i class="fas fa-calendar-times"></i> <?php echo __('all_slots_full'); ?>
                                    </div>
                                <?php endif; ?>

                                <p style="margin-bottom: 1.5rem;"><?php echo $coach['bio']; ?></p>
                                <div class="card-footer" style="margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                                    <span class="price" style="font-size: 1.15rem; font-weight: 800; color: var(--primary);">$<?php echo $coach['hourly_rate']; ?><span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);"><?php echo __('per_hour'); ?></span></span>
                                    <a href="/Gym Manager/pages/coach_details.php?id=<?php echo $coach['id']; ?><?php echo $next_slot ? '&date=' . $next_slot['date'] : ''; ?>#bookingSection" class="btn-card" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.65rem 1.15rem; font-size: 0.85rem; border-radius: 12px; font-weight: 700;">
                                        <i class="fas fa-calendar-check" style="color: var(--gold);"></i> <?php echo __('view_free_slots_btn'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

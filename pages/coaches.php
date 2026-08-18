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
    
    // Check if user currently has an active uncompleted workout session
    $stmt_active = $pdo->prepare("
        SELECT b.id FROM bookings b 
        WHERE b.user_id = ? 
          AND b.status NOT IN ('cancelled', 'rejected', 'completed') 
          AND (b.attendance IS NULL OR b.attendance NOT IN ('present', 'attended', 'absent'))
          AND (UNIX_TIMESTAMP(b.booking_date) + 3600) > UNIX_TIMESTAMP(NOW())
        LIMIT 1
    ");
    $stmt_active->execute([$_SESSION['user_id']]);
    if ($stmt_active->fetch()) {
        $error = __('ongoing_session_conflict');
    } else {
        // Default booking for tomorrow at 8 AM for simplicity
        $booking_date = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
        
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
    if ($disc_val === 'gym' || $disc_val === 'gym & fitness') {
        $where .= " AND (LOWER(c.specialty) = 'gym & fitness' OR LOWER(c.specialty) = 'gym')";
    } else {
        $where .= " AND LOWER(c.specialty) = ?";
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
    if (strcasecmp($specialty, 'gym') === 0 || empty($specialty)) {
        $specialty = 'Gym & Fitness';
    }
    if (!isset($grouped_coaches[$specialty])) {
        $grouped_coaches[$specialty] = [];
    }
    $grouped_coaches[$specialty][] = $coach;
}
?>

<style>
.coaches-sticky-header {
    position: sticky;
    top: 90px;
    z-index: 990;
    background: rgba(250, 249, 246, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    padding: 1.5rem 2rem;
    border-radius: 24px;
    border: 1px solid rgba(26, 39, 68, 0.08);
    box-shadow: 0 10px 30px rgba(26, 39, 68, 0.06);
    margin-bottom: 3.5rem;
    transition: all 0.3s ease;
}
.coaches-grid-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 2rem;
}
@media (max-width: 1024px) {
    .coaches-sticky-header {
        top: 80px;
        padding: 1.2rem 1rem;
    }
    .coaches-grid-container {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 640px) {
    .coaches-grid-container {
        grid-template-columns: 1fr;
    }
}
</style>

<section style="padding-top: 3rem;">
    <div class="coaches-sticky-header">
        <div class="section-title" style="margin-bottom: 1.2rem;">
            <h2 style="font-size: clamp(1.8rem, 3.5vw, 2.6rem); margin-bottom: 0.2rem;"><?php echo __('our_coaches'); ?></h2>
            <div class="underline" style="margin: 12px auto 8px;"></div>
            <p style="margin-top: 6px; color: var(--text-muted); font-size: 0.98rem;"><?php echo __('coaches_subtitle'); ?></p>
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
        <div class="search-container" style="max-width: 600px; margin: 0 auto; text-align: center;">
            <form action="coaches.php" method="GET" id="coachSearchForm" style="display: flex; gap: 10px;">
                <?php if(isset($_GET['discipline']) && !empty($_GET['discipline'])): ?>
                    <input type="hidden" name="discipline" value="<?php echo htmlspecialchars($_GET['discipline']); ?>">
                <?php endif; ?>
                <input type="text" name="search" id="coachSearchInput" placeholder="<?php echo __('search_coach'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 0.9rem 1.5rem; border-radius: 50px; border: 2px solid #e2e8f0; font-size: 0.95rem; outline: none; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(0,98,255,0.1)'" onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                <button type="submit" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 1.8rem;"><i class="fas fa-search"></i></button>
                <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="/Gym Manager/pages/coaches.php<?php echo isset($_GET['discipline']) ? '?discipline='.htmlspecialchars($_GET['discipline']) : ''; ?>" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 1.2rem; background: #e2e8f0; color: #64748b; box-shadow: none;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
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
                                
                                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 1rem;">
                                    <div style="color: #f59e0b; display: flex; gap: 2px;">
                                        <?php 
                                        $rating = round($coach['avg_rating']);
                                        for($i=1; $i<=5; $i++) echo $i <= $rating ? "★" : "☆";
                                        ?>
                                    </div>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">(<?php echo $coach['review_count']; ?> <?php echo __('reviews'); ?>)</span>
                                </div>

                                <p style="margin-bottom: 1.5rem;"><?php echo $coach['bio']; ?></p>
                                <div class="card-footer" style="margin-top: auto;">
                                    <span class="price">$<?php echo $coach['hourly_rate']; ?><?php echo __('per_hour'); ?></span>
                                    <a href="/Gym Manager/pages/coach_details.php?id=<?php echo $coach['id']; ?>" class="btn-card"><?php echo __('details'); ?></a>
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

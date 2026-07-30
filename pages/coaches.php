<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$message = '';
if (isset($_GET['book']) && isset($_SESSION['user_id'])) {
    $coach_id = $_GET['book'];
    
    // Default booking for tomorrow at 8 AM for simplicity
    $booking_date = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
    
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status) VALUES (?, ?, ?, 'pending')");
    if ($stmt->execute([$_SESSION['user_id'], $coach_id, $booking_date])) {
        $message = __('booking_success');

        // Fetch Coach Email for Notification
        $stmt_coach = $pdo->prepare("SELECT u.email, u.name FROM users u JOIN coaches c ON u.id = c.user_id WHERE c.id = ?");
        $stmt_coach->execute([$coach_id]);
        $coach_data = $stmt_coach->fetch();

        if ($coach_data) {
            // --- Start PHPMailer Notification ---
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';
                $mail->Password   = 'hpst uazz wdsq sbnf';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Booking System');
                $mail->addAddress($coach_data['email'], $coach_data['name']);

                $mail->isHTML(false);
                $mail->Subject = "NEW BOOKING: Quick training request received!";
                $mail->Body = "Hello Coach " . $coach_data['name'] . ",\n\nA client (" . $_SESSION['user_name'] . ") has just quick-booked a session with you for " . date('d/m/Y H:i', strtotime($booking_date)) . ".\nPlease log in to confirm.\n\nGymX System";
                $mail->send();
            } catch (Exception $e) {
                error_log("Quick Booking Mail Error: " . $mail->ErrorInfo);
            }
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
    $where .= " AND LOWER(c.specialty) = ?";
    $params[] = str_replace('_', ' ', strtolower($_GET['discipline'])); 
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND u.name LIKE ?";
    $params[] = '%' . trim($_GET['search']) . '%';
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
    $specialty = $coach['specialty'] ?: 'General Fitness';
    if (!isset($grouped_coaches[$specialty])) {
        $grouped_coaches[$specialty] = [];
    }
    $grouped_coaches[$specialty][] = $coach;
}
?>

<section>
    <div class="section-title">
        <h2><?php echo __('our_coaches'); ?></h2>
        <div class="underline"></div>
        <p style="margin-top: 10px; color: var(--text-muted);"><?php echo __('coaches_subtitle'); ?></p>
    </div>

    <?php if($message): ?>
        <p style="color: #16a34a; text-align: center; margin-bottom: 2rem; background: #f0fdf4; padding: 1.2rem; border-radius: 20px; border: 1px solid #bbf7d0; font-weight: 600;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="search-container" style="max-width: 600px; margin: 0 auto 3rem; text-align: center;">
        <form action="coaches.php" method="GET" style="display: flex; gap: 10px;">
            <?php if(isset($_GET['discipline']) && !empty($_GET['discipline'])): ?>
                <input type="hidden" name="discipline" value="<?php echo htmlspecialchars($_GET['discipline']); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="<?php echo __('search_coach'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 1rem 1.5rem; border-radius: 50px; border: 2px solid #e2e8f0; font-size: 1rem; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(0,98,255,0.1)'" onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
            <button type="submit" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 2rem;"><i class="fas fa-search"></i></button>
            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="/Gym Manager/pages/coaches.php<?php echo isset($_GET['discipline']) ? '?discipline='.htmlspecialchars($_GET['discipline']) : ''; ?>" class="btn-auth" style="margin: 0; width: auto; border-radius: 50px; padding: 0 1.5rem; background: #e2e8f0; color: #64748b; box-shadow: none;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

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
                
                <div class="grid">
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

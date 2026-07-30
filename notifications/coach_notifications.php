<?php
include_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get coach ID
$stmt = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
$stmt->execute([$user_id]);
$coach_id = $stmt->fetchColumn();

if (!$coach_id) {
    echo "<section><div class='auth-container'><h1>Coach profile not found!</h1></div></section>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// Mark all as seen when visiting this page
$pdo->prepare("UPDATE bookings SET coach_seen = 1 WHERE coach_id = ? AND coach_seen = 0")->execute([$coach_id]);

// Fetch bookings
$stmt = $pdo->prepare("
    SELECT b.*, u.name as client_name, u.email as client_email, u.avatar as client_avatar 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id
    WHERE b.coach_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$coach_id]);
$bookings = $stmt->fetchAll();
?>

<section style="background: #f8fafc; min-height: 100vh; padding-top: 4rem;">
    <div style="max-width: 1200px; margin: auto;">
        <div class="section-title" style="text-align: left; margin-bottom: 3rem; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h2 style="font-size: 2.8rem; margin: 0;">Notifications Center</h2>
                <div class="underline" style="margin: 10px 0;"></div>
                <p style="color: var(--text-muted); font-size: 1.1rem;">Manage your booking requests and communicate with your students.</p>
            </div>
            <a href="/Gym Manager/dashboards/coach_dashboard.php" class="btn-card" style="background: white; border-color: var(--border-bright);">Back to Dashboard</a>
        </div>

        <?php if(empty($bookings)): ?>
            <div style="text-align: center; padding: 6rem; background: white; border-radius: 40px; border: 1px solid var(--border);">
                <div style="font-size: 5rem; color: #e2e8f0; margin-bottom: 2rem;">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h3 style="color: var(--text-main); font-weight: 800; font-size: 1.8rem;">No notifications yet</h3>
                <p style="color: var(--text-muted);">New booking requests from students will appear here.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach($bookings as $b): ?>
                    <div style="background: white; border-radius: 30px; padding: 2.5rem; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.3s ease;">
                        <div style="display: flex; gap: 2rem; align-items: flex-start;">
                            <!-- Client Avatar -->
                            <img src="<?php echo $b['client_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($b['client_name']).'&background=0062ff&color=fff&size=100'; ?>" 
                                 style="width: 80px; height: 80px; border-radius: 24px; object-fit: cover; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                            
                            <!-- Info & Actions -->
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                                    <div>
                                        <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px;"><?php echo htmlspecialchars($b['client_name']); ?></h3>
                                        <p style="color: var(--text-muted); font-weight: 500;">
                                            <i class="fas fa-envelope" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($b['client_email']); ?>
                                        </p>
                                    </div>
                                    <span class="notif-status status-<?php echo $b['status']; ?>-notif" style="padding: 8px 16px; font-size: 0.9rem;">
                                        <?php echo strtoupper($b['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 20px;">
                                    <div>
                                        <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; margin-bottom: 5px;">Training Date</p>
                                        <p style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                                            <i class="far fa-calendar-check" style="margin-right: 8px;"></i> <?php echo date('l, F d, Y', strtotime($b['booking_date'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; margin-bottom: 5px;">Time Slot</p>
                                        <p style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                                            <i class="far fa-clock" style="margin-right: 8px;"></i> <?php echo date('H:i A', strtotime($b['booking_date'])); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if($b['status'] == 'pending'): ?>
                                    <form action="process_booking.php" method="POST" style="background: white; border: 1px solid #e2e8f0; padding: 2rem; border-radius: 24px;">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 1rem;">Send a response to student:</label>
                                        <textarea name="coach_message" placeholder="Example: Hello! I'm ready to train with you at this time. Let's make it happen!" 
                                                  style="width: 100%; padding: 1.2rem; background: #f1f5f9; border: 2px solid transparent; border-radius: 15px; min-height: 100px; outline: none; margin-bottom: 1.5rem; font-size: 1rem;"></textarea>
                                        
                                        <div style="display: flex; gap: 1rem;">
                                            <button type="submit" name="action" value="confirm" class="btn-notif-action btn-accept" style="padding: 1.2rem; font-size: 1rem;">
                                                <i class="fas fa-check-circle" style="margin-right: 10px;"></i> Accept Booking
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn-notif-action btn-reject" style="padding: 1.2rem; font-size: 1rem; background: #fee2e2; color: #ef4444;">
                                                <i class="fas fa-times-circle" style="margin-right: 10px;"></i> Reject
                                            </button>
                                        </div>
                                    </form>
                                <?php elseif($b['coach_message']): ?>
                                    <div style="padding: 1.5rem; background: #f0f7ff; border-radius: 20px; border-left: 5px solid var(--primary);">
                                        <strong style="color: var(--primary); display: block; margin-bottom: 8px;">
                                            <i class="fas fa-reply" style="margin-right: 8px;"></i> Your Response:
                                        </strong>
                                        <p style="font-size: 1.05rem; line-height: 1.6; color: var(--text-main); font-style: italic;">"<?php echo htmlspecialchars($b['coach_message']); ?>"</p>
                                        <p style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted); text-align: right;">Replied on <?php echo date('M d, Y', strtotime($b['updated_at'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// MoMo Payment Success Page
// Shown after QR payment confirmed or MoMo API callback
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php';

$type    = $_GET['type'] ?? 'shop';
$isQR    = isset($_GET['qr']);
$isTest  = isset($_GET['test']);

// Read from session first, then fall back to GET params
$success = $_SESSION['momo_success'] ?? null;
unset($_SESSION['momo_success']);

// Fallback: build from URL params if session is missing
if (!$success) {
    $amount   = (int)($_GET['amount'] ?? 0);
    $order_id = (int)($_GET['order_id'] ?? 0);
    $sub_id   = (int)($_GET['sub_id']   ?? 0);

    if ($type === 'shop' && $order_id && isset($_SESSION['user_id'])) {
        // Verify order belongs to this user
        $chk = $pdo->prepare("SELECT id, total_price FROM orders WHERE id = ? AND user_id = ?");
        $chk->execute([$order_id, $_SESSION['user_id']]);
        $row = $chk->fetch();
        if ($row) {
            // Use DB amount if URL amount missing (convert USD→VND)
            if (!$amount) $amount = (int)round($row['total_price'] * 25000);
            $success = ['order_id' => $order_id, 'amount' => $amount, 'qr_mode' => true];
        }
    } elseif ($type === 'membership' && $sub_id && isset($_SESSION['user_id'])) {
        $chk = $pdo->prepare("SELECT ms.id, mp.name FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.id = ? AND ms.user_id = ?");
        $chk->execute([$sub_id, $_SESSION['user_id']]);
        $row = $chk->fetch();
        if ($row) {
            $success = ['sub_id' => $sub_id, 'plan_name' => $row['name'], 'amount' => $amount, 'qr_mode' => true];
        }
    }

    // Last resort — show generic success even without DB data
    if (!$success && ($order_id || $sub_id || $amount)) {
        $success = [
            'order_id'  => $order_id,
            'sub_id'    => $sub_id,
            'plan_name' => '',
            'amount'    => $amount,
            'qr_mode'   => true,
        ];
    }
}

$is_vi = ($_SESSION['lang'] ?? 'en') === 'vi';
?>
<style>
/* ── MoMo Success Page ─────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

.ms-bg {
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
    background: linear-gradient(160deg, #fdf4ff 0%, #f5f3ff 40%, #ede9fe 100%);
    position: relative;
    overflow: hidden;
}
.ms-bg::before, .ms-bg::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
}
.ms-bg::before {
    width: 500px; height: 500px;
    background: rgba(162,28,175,0.07);
    top: -150px; right: -100px;
}
.ms-bg::after {
    width: 350px; height: 350px;
    background: rgba(126,34,206,0.06);
    bottom: -100px; left: -80px;
}

.ms-card {
    background: #fff;
    border-radius: 32px;
    padding: 0;
    max-width: 560px;
    width: 100%;
    box-shadow:
        0 40px 100px rgba(126,34,206,0.12),
        0 0 0 1px rgba(162,28,175,0.08);
    overflow: hidden;
    position: relative;
    z-index: 2;
    animation: successCardIn 0.65s cubic-bezier(0.165,0.84,0.44,1);
}
@keyframes successCardIn {
    from { opacity: 0; transform: scale(0.88) translateY(40px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Header gradient bar */
.ms-header-bar {
    background: linear-gradient(135deg, #a21caf 0%, #7e22ce 100%);
    height: 8px;
    width: 100%;
}

.ms-body { padding: 2.8rem 2.5rem 2.5rem; text-align: center; }

/* Success icon */
.ms-icon-wrap {
    width: 100px; height: 100px;
    margin: 0 auto 1.8rem;
    position: relative;
}
.ms-icon-circle {
    width: 100px; height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a21caf, #7e22ce);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    box-shadow:
        0 20px 50px rgba(162,28,175,0.4),
        0 0 0 14px rgba(162,28,175,0.08);
    animation: iconPop 0.6s 0.3s cubic-bezier(0.175,0.885,0.32,1.275) both;
}
@keyframes iconPop {
    from { opacity: 0; transform: scale(0.4) rotate(-20deg); }
    to   { opacity: 1; transform: scale(1) rotate(0deg); }
}

/* Confetti particles */
.ms-confetti {
    position: absolute;
    width: 8px; height: 8px;
    border-radius: 2px;
    animation: confettiFall 1.5s ease-out forwards;
}
@keyframes confettiFall {
    0%   { opacity: 1; transform: translateY(0) rotate(0deg); }
    100% { opacity: 0; transform: translateY(60px) rotate(360deg); }
}

.ms-title {
    font-size: 1.95rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 0.6rem;
    letter-spacing: -0.5px;
    font-family: 'Inter', sans-serif;
}
.ms-subtitle {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.7;
    margin: 0 0 2rem;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* Amount highlight */
.ms-amount-box {
    background: linear-gradient(135deg, #fdf4ff, #f5f3ff);
    border: 2px solid rgba(162,28,175,0.2);
    border-radius: 20px;
    padding: 1.2rem 1.5rem;
    margin-bottom: 1.4rem;
    position: relative;
    overflow: hidden;
}
.ms-amount-box::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(232,121,249,0.07), transparent);
}
.ms-amount-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #7e22ce;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 6px;
    position: relative;
}
.ms-amount-value {
    font-size: 2.6rem;
    font-weight: 900;
    color: #a21caf;
    letter-spacing: -1px;
    font-family: 'Inter', sans-serif;
    position: relative;
    line-height: 1;
}
.ms-amount-value.animate-count { animation: countUp 0.8s 0.4s ease-out both; }
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px) scale(0.9); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Detail table */
.ms-details {
    background: #faf5ff;
    border: 1.5px solid rgba(162,28,175,0.1);
    border-radius: 18px;
    padding: 1.2rem 1.4rem;
    margin-bottom: 2rem;
    text-align: left;
}
.ms-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.9rem;
    color: #374151;
}
.ms-detail-row:not(:last-child) {
    border-bottom: 1px dashed #e9d5ff;
}
.ms-detail-row .dl { font-weight: 600; color: #6b21a8; }
.ms-detail-row .dv { font-weight: 700; color: #1e1b4b; }
.ms-detail-row .dv.success { color: #059669; }
.ms-detail-row .dv.amount  { color: #a21caf; font-size: 1.05rem; }

/* Buttons */
.ms-btn-row {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.ms-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.9rem 1.8rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    border: none;
    font-family: 'Inter', sans-serif;
}
.ms-btn-primary {
    background: linear-gradient(135deg, #a21caf, #7e22ce);
    color: #fff;
    box-shadow: 0 6px 22px rgba(162,28,175,0.38);
}
.ms-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(162,28,175,0.48);
    color: #fff;
}
.ms-btn-secondary {
    background: #f1f5f9;
    color: #475569;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.ms-btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

/* Test mode badge */
.ms-test-badge {
    background: #fef3c7;
    border: 1.5px solid #fbbf24;
    border-radius: 12px;
    padding: 10px 16px;
    margin-bottom: 1.5rem;
    font-size: 0.82rem;
    color: #92400e;
    font-weight: 600;
    text-align: left;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

/* Checkmark animation */
.ms-check-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 1.6rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: #059669;
    animation: fadeSlideIn 0.5s 0.5s ease both;
}
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
}
.ms-check-row .check-icon {
    width: 22px; height: 22px;
    background: #d1fae5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
</style>

<div class="ms-bg">
    <div class="ms-card">
        <div class="ms-header-bar"></div>
        <div class="ms-body">

            <?php if ($isTest): ?>
            <div class="ms-test-badge">
                <i class="fas fa-flask" style="margin-top:2px;color:#d97706;"></i>
                <div>
                    <strong><?php echo $is_vi ? 'CHẾ ĐỘ TEST:' : 'TEST MODE:'; ?></strong>
                    <?php echo $is_vi ? ' Giao dịch được mô phỏng nội bộ, không qua cổng MoMo thật.' : ' Transaction simulated locally, not through live MoMo.'; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Icon -->
            <div class="ms-icon-wrap" id="iconWrap">
                <div class="ms-icon-circle">💜</div>
            </div>

            <!-- Success checks -->
            <div class="ms-check-row">
                <div class="check-icon">✔</div>
                <?php echo $is_vi ? 'Giao dịch đã được xác nhận' : 'Transaction confirmed'; ?>
            </div>

            <?php if ($type === 'membership'): ?>
                <!-- ── MEMBERSHIP SUCCESS ── -->
                <h1 class="ms-title"><?php echo $is_vi ? 'Đăng Ký Thành Công!' : 'Subscription Successful!'; ?></h1>
                <p class="ms-subtitle">
                    <?php echo $is_vi ? 'Thẻ thành viên' : 'Your membership plan'; ?>
                    <strong style="color:#a21caf"><?php echo htmlspecialchars($success['plan_name'] ?? ''); ?></strong>
                    <?php echo $is_vi ? 'đã được kích hoạt thành công qua Ví MoMo.' : 'has been activated successfully via MoMo Wallet.'; ?>
                </p>

                <!-- Amount -->
                <div class="ms-amount-box">
                    <div class="ms-amount-label">💰 <?php echo $is_vi ? 'SỐ TIỀN ĐÃ THANH TOÁN' : 'AMOUNT PAID'; ?></div>
                    <div class="ms-amount-value animate-count"><?php echo number_format((int)($success['amount'] ?? 0), 0, ',', '.'); ?>đ</div>
                </div>

                <!-- Details -->
                <div class="ms-details">
                    <div class="ms-detail-row">
                        <span class="dl">💜 <?php echo $is_vi ? 'Phương thức' : 'Method'; ?></span>
                        <span class="dv">Ví MoMo QR</span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">💳 <?php echo $is_vi ? 'Gói đăng ký' : 'Plan'; ?></span>
                        <span class="dv" style="color:#7e22ce;"><?php echo htmlspecialchars($success['plan_name'] ?? '—'); ?></span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">✅ <?php echo $is_vi ? 'Trạng thái' : 'Status'; ?></span>
                        <span class="dv success">✅ <?php echo $is_vi ? 'Đã kích hoạt' : 'Activated'; ?></span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">🏪 <?php echo $is_vi ? 'Lưu ý' : 'Note'; ?></span>
                        <span class="dv" style="font-size:0.82rem;color:#64748b;"><?php echo $is_vi ? 'Đến quầy nhận thẻ vật lý' : 'Visit counter for physical card'; ?></span>
                    </div>
                </div>

                <div class="ms-btn-row">
                    <a href="/Gym Manager/dashboards/profile.php" class="ms-btn ms-btn-primary">
                        <i class="fas fa-id-card"></i>
                        <?php echo $is_vi ? 'Xem Thẻ Thành Viên' : 'View Membership'; ?>
                    </a>
                    <a href="/Gym Manager/index.php" class="ms-btn ms-btn-secondary">
                        <i class="fas fa-home"></i>
                        <?php echo $is_vi ? 'Trang Chủ' : 'Home'; ?>
                    </a>
                </div>

            <?php else: ?>
                <!-- ── SHOP ORDER SUCCESS ── -->
                <h1 class="ms-title"><?php echo $is_vi ? 'Thanh Toán Thành Công!' : 'Payment Successful!'; ?></h1>
                <p class="ms-subtitle">
                    <?php echo $is_vi ? 'Đơn hàng của bạn đã được thanh toán qua Ví MoMo và đang được xử lý.' : 'Your order has been paid via MoMo Wallet and is being processed.'; ?>
                </p>

                <!-- Amount -->
                <div class="ms-amount-box">
                    <div class="ms-amount-label">💰 <?php echo $is_vi ? 'SỐ TIỀN ĐÃ THANH TOÁN' : 'AMOUNT PAID'; ?></div>
                    <div class="ms-amount-value animate-count"><?php echo number_format((int)($success['amount'] ?? 0), 0, ',', '.'); ?>đ</div>
                </div>

                <!-- Details -->
                <div class="ms-details">
                    <div class="ms-detail-row">
                        <span class="dl">📦 <?php echo $is_vi ? 'Mã đơn hàng' : 'Order ID'; ?></span>
                        <span class="dv" style="color:#2563eb;">#<?php echo (int)($success['order_id'] ?? 0); ?></span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">💜 <?php echo $is_vi ? 'Phương thức' : 'Method'; ?></span>
                        <span class="dv">Ví MoMo QR</span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">✅ <?php echo $is_vi ? 'Trạng thái' : 'Status'; ?></span>
                        <span class="dv success">✅ <?php echo $is_vi ? 'Đã xác nhận' : 'Confirmed'; ?></span>
                    </div>
                    <div class="ms-detail-row">
                        <span class="dl">🚚 <?php echo $is_vi ? 'Giao hàng' : 'Delivery'; ?></span>
                        <span class="dv" style="font-size:0.82rem;color:#64748b;"><?php echo $is_vi ? '2–3 ngày làm việc' : '2–3 business days'; ?></span>
                    </div>
                </div>

                <div class="ms-btn-row">
                    <a href="/Gym Manager/pages/cart.php#order-tracking" class="ms-btn ms-btn-primary">
                        <i class="fas fa-truck"></i>
                        <?php echo $is_vi ? 'Theo Dõi Đơn Hàng' : 'Track Order'; ?>
                    </a>
                    <a href="/Gym Manager/dashboards/profile.php" class="ms-btn ms-btn-secondary">
                        <i class="fas fa-history"></i>
                        <?php echo $is_vi ? 'Lịch Sử Đơn Hàng' : 'Order History'; ?>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// ── Confetti burst effect ────────────────────────────────────
(function spawnConfetti() {
    const colors = ['#e879f9','#a21caf','#7e22ce','#c4b5fd','#f0abfc','#fbbf24'];
    const wrap = document.getElementById('iconWrap');
    if (!wrap) return;
    for (let i = 0; i < 18; i++) {
        const el = document.createElement('div');
        el.className = 'ms-confetti';
        el.style.cssText = `
            left: ${Math.random() * 120 - 10}px;
            top:  ${Math.random() * 80}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-delay: ${Math.random() * 0.6}s;
            animation-duration: ${1.2 + Math.random() * 0.8}s;
            transform: rotate(${Math.random() * 360}deg);
        `;
        wrap.appendChild(el);
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

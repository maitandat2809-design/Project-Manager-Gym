<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php';

$type    = $_GET['type'] ?? 'shop';
$isTest  = isset($_GET['test']);
$success = $_SESSION['momo_success'] ?? null;
unset($_SESSION['momo_success']);
?>
<style>
.momo-success-wrap {
    min-height: 80vh; display: flex; align-items: center; justify-content: center;
    padding: 2rem; background: linear-gradient(135deg, #f5f3ff 0%, #fdf4ff 100%);
}
.momo-success-card {
    background: #fff; border-radius: 28px; padding: 3rem 2.5rem; max-width: 560px; width: 100%;
    box-shadow: 0 30px 80px rgba(162,28,175,0.12); text-align: center;
    border: 1.5px solid rgba(162,28,175,0.15);
    animation: popIn 0.5s cubic-bezier(0.165,0.84,0.44,1);
}
@keyframes popIn { from{opacity:0;transform:scale(0.88) translateY(30px)} to{opacity:1;transform:scale(1) translateY(0)} }
.momo-icon { width: 96px; height: 96px; border-radius: 50%; background: linear-gradient(135deg, #a21caf, #7e22ce);
    display: flex; align-items: center; justify-content: center; margin: 0 auto 1.8rem; font-size: 2.8rem;
    box-shadow: 0 15px 40px rgba(162,28,175,0.35); }
.momo-success-card h1 { font-size: 1.9rem; font-weight: 900; color: #0f172a; margin: 0 0 0.5rem; }
.momo-success-card p { color: #64748b; font-size: 1rem; line-height: 1.7; margin: 0 0 2rem; }
.momo-detail-box {
    background: #fdf4ff; border-radius: 16px; padding: 1.2rem 1.5rem;
    margin-bottom: 2rem; text-align: left; border: 1px solid rgba(162,28,175,0.12);
}
.momo-detail-box p { margin: 6px 0; font-size: 0.95rem; color: #374151; }
.momo-detail-box strong { color: #a21caf; }
.momo-btn {
    display: inline-block; padding: 0.9rem 2rem; border-radius: 14px; font-weight: 700;
    font-size: 0.95rem; text-decoration: none; transition: all 0.25s; cursor: pointer; border: none;
}
.momo-btn-primary { background: linear-gradient(135deg, #a21caf, #7e22ce); color: #fff;
    box-shadow: 0 6px 20px rgba(162,28,175,0.35); }
.momo-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(162,28,175,0.45); }
.momo-btn-secondary { background: #f1f5f9; color: #475569; margin-left: 12px; }
.momo-btn-secondary:hover { background: #e2e8f0; }
</style>

<?php $is_vi_momo = isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi'; ?>
<div class="momo-success-wrap">
    <div class="momo-success-card">

        <?php if ($isTest): ?>
        <div style="background:#fef3c7;border:1.5px solid #fbbf24;border-radius:12px;padding:10px 16px;margin-bottom:1.5rem;font-size:0.85rem;color:#92400e;font-weight:600;text-align:left;">
            <i class="fas fa-flask" style="margin-right:6px;"></i>
            <strong><?php echo $is_vi_momo ? 'CHẾ ĐỘ TEST:' : 'TEST MODE:'; ?></strong> <?php echo $is_vi_momo ? 'Giao dịch này được mô phỏng cục bộ (không qua MoMo thật).' : 'This transaction is simulated locally (not live MoMo).'; ?>
        </div>
        <?php endif; ?>

        <div class="momo-icon">💜</div>

        <?php if ($type === 'membership'): ?>
            <h1><?php echo $is_vi_momo ? 'Đăng Ký Thành Công!' : 'Subscription Successful!'; ?></h1>
            <p><?php echo $is_vi_momo ? 'Thẻ thành viên' : 'Your membership plan'; ?> <strong style="color:#a21caf"><?php echo htmlspecialchars($success['plan_name'] ?? ''); ?></strong> <?php echo $is_vi_momo ? 'của bạn đã được kích hoạt ngay lập tức qua Ví MoMo.' : 'has been activated instantly via MoMo Wallet.'; ?></p>
            <div class="momo-detail-box">
                <p>💜 <strong><?php echo $is_vi_momo ? 'Phương thức:' : 'Payment Method:'; ?></strong> <?php echo $is_vi_momo ? 'Ví MoMo' : 'MoMo Wallet'; ?></p>
                <p>💰 <strong><?php echo $is_vi_momo ? 'Số tiền:' : 'Amount Paid:'; ?></strong> <?php echo number_format((int)($success['amount'] ?? 0), 0, ',', '.'); ?>đ</p>
                <p>✅ <strong><?php echo $is_vi_momo ? 'Trạng thái:' : 'Status:'; ?></strong> <?php echo $is_vi_momo ? 'Đã kích hoạt' : 'Activated'; ?></p>
                <p>📋 <strong><?php echo $is_vi_momo ? 'Lưu ý:' : 'Note:'; ?></strong> <?php echo $is_vi_momo ? 'Vui lòng đến quầy giao dịch để nhận thẻ thành viên vật lý.' : 'Please visit the front desk to receive your physical membership card.'; ?></p>
            </div>
            <a href="/Gym Manager/dashboards/profile.php" class="momo-btn momo-btn-primary">
                <i class="fas fa-id-card" style="margin-right:8px;"></i><?php echo $is_vi_momo ? 'Xem Thẻ Thành Viên' : 'View My Membership'; ?>
            </a>
            <a href="/Gym Manager/index.php" class="momo-btn momo-btn-secondary"><?php echo $is_vi_momo ? 'Về Trang Chủ' : 'Back to Home'; ?></a>

        <?php else: ?>
            <h1><?php echo $is_vi_momo ? 'Thanh Toán Thành Công!' : 'Payment Successful!'; ?></h1>
            <p><?php echo $is_vi_momo ? 'Đơn hàng của bạn đã được thanh toán qua Ví MoMo. Chúng tôi sẽ xử lý và giao hàng sớm nhất có thể.' : 'Your order has been paid via MoMo Wallet. We will process and ship it as soon as possible.'; ?></p>
            <div class="momo-detail-box">
                <p>📦 <strong><?php echo $is_vi_momo ? 'Mã đơn hàng:' : 'Order ID:'; ?></strong> #<?php echo (int)($success['order_id'] ?? 0); ?></p>
                <p>💜 <strong><?php echo $is_vi_momo ? 'Phương thức:' : 'Payment Method:'; ?></strong> <?php echo $is_vi_momo ? 'Ví MoMo' : 'MoMo Wallet'; ?></p>
                <p>✅ <strong><?php echo $is_vi_momo ? 'Trạng thái:' : 'Status:'; ?></strong> <?php echo $is_vi_momo ? 'Đã xác nhận' : 'Confirmed'; ?></p>
                <p>📧 <strong><?php echo $is_vi_momo ? 'Email xác nhận đã được gửi về hộp thư của bạn.' : 'A confirmation email has been sent to your inbox.'; ?></strong></p>
            </div>
            <a href="/Gym Manager/pages/cart.php#order-tracking" class="momo-btn momo-btn-primary">
                <i class="fas fa-truck" style="margin-right:8px;"></i><?php echo $is_vi_momo ? 'Theo Dõi Đơn Hàng (2-3 ngày)' : 'Track Order (2-3 days)'; ?>
            </a>
            <a href="/Gym Manager/dashboards/profile.php" class="momo-btn momo-btn-secondary"><?php echo $is_vi_momo ? 'Lịch Sử Đơn Hàng' : 'Order History'; ?></a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

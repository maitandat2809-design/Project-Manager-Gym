<?php
// ============================================================
// MoMo Dynamic QR Payment Page
// Generates dynamic VietQR / MoMo EMVCo payload with exact amount
// & note. Automatically detects transfer & notifies success.
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Read payment info from session, or restore from DB via GET params
$pending = $_SESSION['momo_pending'] ?? null;
if (!$pending) {
    $get_order_id = (int)($_GET['order_id'] ?? 0);
    $get_sub_id   = (int)($_GET['sub_id']   ?? 0);

    if ($get_order_id) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$get_order_id, $user_id]);
        $ord = $stmt->fetch();
        if ($ord && $ord['status'] === 'pending') {
            $amt_vnd = (int)round($ord['total_price'] * 25000);
            if ($amt_vnd < 1000) $amt_vnd = 1000;
            $pending = [
                'type'     => 'shop',
                'order_id' => $get_order_id,
                'amount'   => $amt_vnd,
                'created'  => strtotime($ord['created_at'])
            ];
            $_SESSION['momo_pending'] = $pending;
        }
    } elseif ($get_sub_id) {
        $stmt = $pdo->prepare("SELECT ms.*, mp.name as plan_name FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.id = ? AND ms.user_id = ?");
        $stmt->execute([$get_sub_id, $user_id]);
        $sub = $stmt->fetch();
        if ($sub && $sub['status'] === 'pending') {
            $pending = [
                'type'      => 'membership',
                'sub_id'    => $get_sub_id,
                'plan_name' => $sub['plan_name'],
                'amount'    => (int)$sub['price_paid'],
                'created'   => strtotime($sub['created_at'])
            ];
            $_SESSION['momo_pending'] = $pending;
        }
    }
}

if (!$pending) {
    header("Location: /Gym Manager/index.php");
    exit;
}

$type       = $pending['type']   ?? 'shop';       // 'shop' | 'membership'
$amount     = (int)($pending['amount'] ?? 0);
$order_id   = (int)($pending['order_id']  ?? 0);
$sub_id     = (int)($pending['sub_id']    ?? 0);
$plan_name  = $pending['plan_name']  ?? '';

if ($amount < 1000) $amount = 1000;
$amount_fmt = number_format($amount, 0, ',', '.') . 'đ';
$is_vi = ($_SESSION['lang'] ?? 'en') === 'vi';

// --- Build Transfer Note & EMVCo VietQR String ---
$order_note_display = ($type === 'membership') 
    ? ($is_vi ? "GymX Goi " . $plan_name : "GymX " . $plan_name) 
    : "GymX Don " . $order_id;

function removeDiacritics($str) {
    $dict = [
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd'=>'đ', 'D'=>'Đ',
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i'=>'í|ì|ỉ|ĩ|ị', 'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y'=>'ý|ỳ|ỷ|ỹ|ỵ', 'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    ];
    foreach($dict as $non => $patterns) {
        $str = preg_replace("/($patterns)/i", $non, $str);
    }
    return $str;
}

function generateMoMoVietQRPayload($amount, $orderInfo = '') {
    $f00 = '000201';
    $f01 = '010212';
    
    // Tag 38: VietQR MoMo (BIN 971025, MoMo Wallet PSP2625917900000062)
    $tag38_sub = '0010A000000727013300069710250119PSP26259179000000620208QRIBFTTA';
    $f38 = '38' . sprintf('%02d', strlen($tag38_sub)) . $tag38_sub;
    
    // Tag 53: VND Currency
    $f53 = '5303704';
    
    // Tag 54: Amount (Embeds exact amount into QR!)
    $amt_str = (string)(int)$amount;
    $f54 = '54' . sprintf('%02d', strlen($amt_str)) . $amt_str;
    
    // Tag 58: Country
    $f58 = '5802VN';
    
    // Tag 62: Additional Data (MoMo routing + Order Message)
    $tag62_sub = '0515MOMOW2W819477160622jZOAEshbN4_U_beULl7w9Q';
    if (!empty($orderInfo)) {
        $clean = removeDiacritics($orderInfo);
        $clean = substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $clean), 0, 20);
        if (!empty($clean)) {
            $tag62_sub .= '08' . sprintf('%02d', strlen($clean)) . $clean;
        }
    }
    $f62 = '62' . sprintf('%02d', strlen($tag62_sub)) . $tag62_sub;
    
    $raw = $f00 . $f01 . $f38 . $f53 . $f54 . $f58 . $f62 . '6304';
    
    // CRC16 CCITT
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($raw); $i++) {
        $crc ^= (ord($raw[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return $raw . strtoupper(sprintf('%04X', $crc));
}

$qr_payload = generateMoMoVietQRPayload($amount, $order_note_display);
$qr_img_url = "https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=8&data=" . urlencode($qr_payload);

include_once __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $is_vi ? 'vi' : 'en'; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $is_vi ? 'Thanh Toán MoMo — GymX' : 'MoMo Payment — GymX'; ?></title>
<style>
/* ── MoMo Dynamic QR Page ── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

.momo-qr-bg {
    min-height: 100vh;
    background: linear-gradient(135deg, #180026 0%, #360064 45%, #9d174d 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
    position: relative;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}
.momo-qr-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 650px 650px at 15% 20%, rgba(217, 70, 239, 0.25) 0%, transparent 70%),
        radial-gradient(ellipse 450px 450px at 85% 80%, rgba(168, 85, 247, 0.3) 0%, transparent 60%);
    pointer-events: none;
}

/* Floating ambient orbs */
.momo-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.45;
    animation: floatOrb 8s ease-in-out infinite alternate;
    pointer-events: none;
}
.orb-1 { width: 320px; height: 320px; background: #e879f9; top: -100px; left: -100px; }
.orb-2 { width: 240px; height: 240px; background: #7e22ce; bottom: 30px; right: -70px; animation-delay: 2.5s; }

@keyframes floatOrb {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(-25px) scale(1.08); }
}

.momo-qr-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(24px);
    border-radius: 32px;
    width: 100%;
    max-width: 470px;
    box-shadow:
        0 40px 100px rgba(0, 0, 0, 0.45),
        0 0 0 1px rgba(255, 255, 255, 0.2);
    overflow: hidden;
    position: relative;
    z-index: 10;
    animation: cardPop 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes cardPop {
    from { opacity: 0; transform: translateY(40px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Card Header */
.momo-card-header {
    background: linear-gradient(135deg, #a21caf 0%, #7e22ce 100%);
    padding: 1.8rem 2rem 1.4rem;
    text-align: center;
    position: relative;
}
.momo-logo-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 0.4rem;
}
.momo-logo-circle {
    width: 44px; height: 44px;
    background: #fff;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 6px 16px rgba(0,0,0,0.18);
}
.momo-card-header h2 {
    color: #fff;
    font-size: 1.35rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.3px;
}
.momo-card-header p {
    color: rgba(255,255,255,0.85);
    font-size: 0.88rem;
    margin: 4px 0 0;
    font-weight: 500;
}

/* Card Body */
.momo-card-body { padding: 1.8rem 2rem 1.8rem; }

/* Steps */
.momo-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-bottom: 1.3rem;
}
.step-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.76rem;
    color: #7e22ce;
    font-weight: 700;
    background: #fdf4ff;
    padding: 4px 10px;
    border-radius: 30px;
    border: 1px solid #f0abfc;
}
.step-item .step-num {
    width: 18px; height: 18px;
    background: #a21caf;
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.68rem;
    font-weight: 900;
}
.step-arrow { color: #d8b4fe; font-size: 0.8rem; }

/* Amount Badge */
.momo-amount-badge {
    background: linear-gradient(135deg, #fdf4ff 0%, #fae8ff 100%);
    border: 2px solid #e879f9;
    border-radius: 20px;
    padding: 1.1rem 1.4rem;
    text-align: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 6px 20px rgba(232, 121, 249, 0.15);
}
.momo-amount-badge .label {
    font-size: 0.78rem;
    font-weight: 800;
    color: #7e22ce;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 3px;
}
.momo-amount-badge .amount {
    font-size: 2.3rem;
    font-weight: 900;
    color: #a21caf;
    letter-spacing: -1px;
    line-height: 1.1;
}
.momo-amount-badge .amount-sub {
    font-size: 0.78rem;
    color: #9333ea;
    margin-top: 4px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* QR Code Display */
.momo-qr-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.4rem;
}
.momo-qr-frame {
    background: #fff;
    border: 3px solid #e879f9;
    border-radius: 22px;
    padding: 14px;
    box-shadow:
        0 10px 32px rgba(162, 28, 175, 0.18),
        0 0 0 6px rgba(232, 121, 249, 0.12);
    position: relative;
    display: inline-block;
}
.momo-qr-frame img.qr-code-img {
    width: 220px;
    height: 220px;
    display: block;
    border-radius: 12px;
}

/* MoMo Logo Badge in QR center */
.momo-qr-center-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 44px;
    height: 44px;
    background: #fff;
    border: 3px solid #a21caf;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    pointer-events: none;
}

/* Laser scan line animation */
.scan-laser {
    position: absolute;
    left: 14px; right: 14px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #e879f9, #a21caf, #e879f9, transparent);
    box-shadow: 0 0 8px #d946ef;
    border-radius: 2px;
    animation: scanAnimation 2.4s cubic-bezier(0.4, 0, 0.6, 1) infinite alternate;
    pointer-events: none;
}
@keyframes scanAnimation {
    0%   { top: 16px; opacity: 0.8; }
    100% { top: calc(100% - 18px); opacity: 0.8; }
}

.momo-scan-tip {
    margin-top: 10px;
    font-size: 0.82rem;
    color: #7e22ce;
    font-weight: 700;
    background: #fdf4ff;
    padding: 5px 14px;
    border-radius: 30px;
    border: 1px solid #f0abfc;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pulse-dot {
    width: 8px; height: 8px;
    background: #a21caf;
    border-radius: 50%;
    animation: pulseDot 1.4s infinite;
}
@keyframes pulseDot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.6); opacity: 0.4; }
}

/* Info Box */
.momo-info-box {
    background: #faf5ff;
    border: 1.5px solid #f3e8ff;
    border-radius: 16px;
    padding: 0.9rem 1.2rem;
    margin-bottom: 1.3rem;
    font-size: 0.87rem;
}
.momo-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}
.momo-info-row:not(:last-child) {
    border-bottom: 1px dashed #e9d5ff;
    padding-bottom: 7px;
    margin-bottom: 3px;
}
.momo-info-row .lbl { font-weight: 600; color: #6b21a8; }
.momo-info-row .val { font-weight: 700; color: #1e1b4b; text-align: right; }

/* ── Live Auto-Detect Status (No manual button!) ── */
.momo-live-radar {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #86efac;
    border-radius: 18px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(34, 197, 94, 0.12);
}
.radar-pulse-ring {
    position: relative;
    width: 32px; height: 32px;
    flex-shrink: 0;
}
.radar-pulse-ring .ring {
    position: absolute;
    inset: 0;
    border: 3px solid #22c55e;
    border-radius: 50%;
    animation: ringExpand 1.8s cubic-bezier(0.25, 1, 0.5, 1) infinite;
}
.radar-pulse-ring .core {
    position: absolute;
    inset: 6px;
    background: #16a34a;
    border-radius: 50%;
    box-shadow: 0 0 10px #22c55e;
}
@keyframes ringExpand {
    0%   { transform: scale(0.4); opacity: 1; }
    100% { transform: scale(1.6); opacity: 0; }
}

.radar-text-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #15803d;
    line-height: 1.3;
}
.radar-text-sub {
    font-size: 0.77rem;
    font-weight: 600;
    color: #16a34a;
    margin-top: 2px;
    line-height: 1.3;
}

/* Countdown */
.momo-timer {
    text-align: center;
    font-size: 0.82rem;
    color: #7e22ce;
    font-weight: 600;
    margin-bottom: 0.8rem;
}
.momo-timer span {
    font-weight: 900;
    color: #a21caf;
    font-size: 0.95rem;
}

/* Cancel Link */
.momo-cancel-link {
    display: block;
    text-align: center;
    color: #94a3b8;
    font-size: 0.84rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.2s;
}
.momo-cancel-link:hover { color: #dc2626; }

/* ── Fullscreen Success Overlay ── */
.momo-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 7, 26, 0.82);
    backdrop-filter: blur(14px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.momo-success-overlay.show {
    opacity: 1;
    pointer-events: all;
}
.momo-success-popup {
    background: #ffffff;
    border-radius: 36px;
    padding: 0;
    max-width: 450px;
    width: 100%;
    box-shadow: 0 35px 90px rgba(126, 34, 206, 0.4);
    overflow: hidden;
    animation: popupSpring 0.55s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
}
@keyframes popupSpring {
    from { opacity: 0; transform: scale(0.65) translateY(40px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.success-hero {
    background: linear-gradient(135deg, #a21caf 0%, #7e22ce 100%);
    padding: 2.2rem 2rem 1.8rem;
    text-align: center;
    position: relative;
}
.success-icon-badge {
    width: 86px; height: 86px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.22);
    border: 3px solid rgba(255, 255, 255, 0.6);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.6rem;
    margin: 0 auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: badgeBounce 0.6s 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes badgeBounce {
    from { transform: scale(0) rotate(-30deg); opacity: 0; }
    to   { transform: scale(1) rotate(0); opacity: 1; }
}

.success-body {
    padding: 1.8rem 2rem 2.2rem;
    text-align: center;
}
.success-title {
    font-size: 1.65rem;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 6px;
    letter-spacing: -0.4px;
}
.success-desc {
    color: #64748b;
    font-size: 0.9rem;
    margin: 0 0 1.4rem;
    line-height: 1.5;
}
.success-receipt {
    background: linear-gradient(135deg, #fdf4ff 0%, #faf5ff 100%);
    border: 2px solid #f0abfc;
    border-radius: 20px;
    padding: 1.1rem 1.5rem;
    margin-bottom: 1.4rem;
}
.receipt-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: #7e22ce;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 3px;
}
.receipt-val {
    font-size: 2.2rem;
    font-weight: 900;
    color: #a21caf;
    letter-spacing: -1px;
    line-height: 1;
}
.success-redirect-note {
    font-size: 0.82rem;
    color: #94a3b8;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
</style>
</head>
<body>

<div class="momo-qr-bg">
    <!-- Ambient glowing orbs -->
    <div class="momo-orb orb-1"></div>
    <div class="momo-orb orb-2"></div>

    <div class="momo-qr-card">
        <!-- Header -->
        <div class="momo-card-header">
            <div class="momo-logo-row">
                <div class="momo-logo-circle">💜</div>
                <h2><?php echo $is_vi ? 'Thanh Toán Qua Ví MoMo' : 'Pay via MoMo Wallet'; ?></h2>
            </div>
            <p>
                <?php if ($type === 'membership'): ?>
                    <?php echo $is_vi ? "Đăng ký gói: <strong style='color:#f5d0fe'>" . htmlspecialchars($plan_name) . "</strong>" : "Subscription: <strong style='color:#f5d0fe'>" . htmlspecialchars($plan_name) . "</strong>"; ?>
                <?php else: ?>
                    <?php echo $is_vi ? "Đơn hàng: <strong style='color:#f5d0fe'>#" . $order_id . "</strong>" : "Order: <strong style='color:#f5d0fe'>#" . $order_id . "</strong>"; ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Body -->
        <div class="momo-card-body">
            <!-- 4 Simple Steps -->
            <div class="momo-steps">
                <div class="step-item"><div class="step-num">1</div><?php echo $is_vi ? 'Mở MoMo' : 'Open MoMo'; ?></div>
                <div class="step-arrow">→</div>
                <div class="step-item"><div class="step-num">2</div><?php echo $is_vi ? 'Quét QR' : 'Scan QR'; ?></div>
                <div class="step-arrow">→</div>
                <div class="step-item"><div class="step-num">3</div><?php echo $is_vi ? 'Xác nhận' : 'Confirm'; ?></div>
            </div>

            <!-- Amount Display with exact number -->
            <div class="momo-amount-badge">
                <div class="label">💰 <?php echo $is_vi ? 'SỐ TIỀN CẦN THANH TOÁN' : 'AMOUNT TO PAY'; ?></div>
                <div class="amount"><?php echo $amount_fmt; ?></div>
                <div class="amount-sub">
                    <span>⚡</span> <?php echo $is_vi ? 'Quét QR mã sẽ tự động hiện đúng số tiền trên app' : 'QR code will automatically fill amount in app'; ?>
                </div>
            </div>

            <!-- Dynamic VietQR Code -->
            <div class="momo-qr-wrapper">
                <div class="momo-qr-frame">
                    <div class="scan-laser"></div>
                    <img class="qr-code-img"
                         src="<?php echo htmlspecialchars($qr_img_url); ?>"
                         alt="MoMo VietQR Code"
                         onerror="this.onerror=null;this.src='/Gym Manager/assets/images/momo_qr.png'">
                    <div class="momo-qr-center-badge">💜</div>
                </div>
                <div class="momo-scan-tip">
                    <div class="pulse-dot"></div>
                    <?php echo $is_vi ? 'Mở App MoMo hoặc Ngân hàng để quét' : 'Scan with MoMo or Banking App'; ?>
                </div>
            </div>

            <!-- Transaction Details Box -->
            <div class="momo-info-box">
                <div class="momo-info-row">
                    <span class="lbl">💳 <?php echo $is_vi ? 'Người nhận' : 'Recipient'; ?></span>
                    <span class="val">GymX Manager</span>
                </div>
                <div class="momo-info-row">
                    <span class="lbl">💰 <?php echo $is_vi ? 'Số tiền' : 'Amount'; ?></span>
                    <span class="val" style="color:#a21caf;"><?php echo $amount_fmt; ?></span>
                </div>
                <div class="momo-info-row">
                    <span class="lbl">📝 <?php echo $is_vi ? 'Nội dung CK' : 'Note'; ?></span>
                    <span class="val" style="color:#7e22ce; font-family:monospace; font-size:0.95rem;"><?php echo htmlspecialchars($order_note_display); ?></span>
                </div>
            </div>

            <!-- Live Auto-Detection Radar (No manual button!) -->
            <div class="momo-live-radar">
                <div class="radar-pulse-ring">
                    <div class="ring"></div>
                    <div class="core"></div>
                </div>
                <div>
                    <div class="radar-text-title"><?php echo $is_vi ? '⚡ Đang kiểm tra giao dịch MoMo trực tiếp...' : '⚡ Checking MoMo transaction live...'; ?></div>
                    <div class="radar-text-sub">
                        <?php echo $is_vi ? 'Hệ thống tự động thông báo thành công sau khi bạn chuyển tiền' : 'System auto-notifies upon receiving payment'; ?>
                    </div>
                </div>
            </div>

            <!-- Countdown Timer -->
            <div class="momo-timer">
                ⏱ <?php echo $is_vi ? 'Hiệu lực phiên thanh toán:' : 'Session expires in:'; ?> <span id="countdownTimer">15:00</span>
            </div>

            <!-- Cancel link -->
            <a class="momo-cancel-link" onclick="cancelPayment()">
                <i class="fas fa-times" style="margin-right:4px;"></i>
                <?php echo $is_vi ? 'Huỷ thanh toán' : 'Cancel payment'; ?>
            </a>
        </div>
    </div>
</div>

<!-- ── Fullscreen Success Modal (Appears automatically without clicking) ── -->
<div class="momo-success-overlay" id="successOverlay">
    <div class="momo-success-popup">
        <div class="success-hero">
            <div class="success-icon-badge">✓</div>
        </div>
        <div class="success-body">
            <h2 class="success-title"><?php echo $is_vi ? 'Thanh Toán Thành Công!' : 'Payment Successful!'; ?></h2>
            <p class="success-desc">
                <?php echo $is_vi ? 'Giao dịch qua Ví MoMo đã được xác nhận. Cảm ơn bạn!' : 'Your MoMo payment has been verified. Thank you!'; ?>
            </p>
            <div class="success-receipt">
                <div class="receipt-label">💰 <?php echo $is_vi ? 'SỐ TIỀN ĐÃ THANH TOÁN' : 'AMOUNT PAID'; ?></div>
                <div class="receipt-val" id="successAmountDisplay"><?php echo $amount_fmt; ?></div>
            </div>
            <div class="success-redirect-note">
                <i class="fas fa-spinner fa-spin"></i>
                <span id="redirectCounter"><?php echo $is_vi ? 'Đang chuyển hướng đến trang hoá đơn trong 3s...' : 'Redirecting to invoice in 3s...'; ?></span>
            </div>
        </div>
    </div>
</div>

<script>
const IS_VI       = <?php echo $is_vi ? 'true' : 'false'; ?>;
const PAY_TYPE    = '<?php echo $type; ?>';
const ORDER_ID    = <?php echo $order_id; ?>;
const SUB_ID      = <?php echo $sub_id; ?>;
const AMOUNT_FMT  = '<?php echo $amount_fmt; ?>';
const SUCCESS_URL = '/Gym Manager/pages/momo_success.php?type=<?php echo $type; ?>&qr=1&amount=<?php echo $amount; ?>&<?php echo $type === "membership" ? "sub_id={$sub_id}" : "order_id={$order_id}"; ?>';
const CANCEL_URL  = '<?php echo $type === "membership" ? "/Gym Manager/pages/membership.php" : "/Gym Manager/pages/checkout.php"; ?>';

// ── Native Web Audio API Success Chime ──────────────────────
function playPaymentSuccessSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        
        // D5 tone (587.33Hz)
        const osc1 = ctx.createOscillator();
        const g1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now);
        g1.gain.setValueAtTime(0.2, now);
        g1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc1.connect(g1);
        g1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.35);

        // A5 tone (880Hz)
        const osc2 = ctx.createOscillator();
        const g2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.14);
        g2.gain.setValueAtTime(0.25, now + 0.14);
        g2.gain.exponentialRampToValueAtTime(0.001, now + 0.7);
        osc2.connect(g2);
        g2.connect(ctx.destination);
        osc2.start(now + 0.14);
        osc2.stop(now + 0.7);
    } catch(e) {
        // Safe fallback
    }
}

// ── Confetti Particle Explosion ─────────────────────────────
function spawnConfettiExplosion() {
    const colors = ['#f43f5e', '#ec4899', '#d946ef', '#a855f7', '#6366f1', '#3b82f6', '#10b981', '#f59e0b'];
    const container = document.body;
    for (let i = 0; i < 40; i++) {
        const c = document.createElement('div');
        const bg = colors[Math.floor(Math.random() * colors.length)];
        const size = 6 + Math.random() * 8;
        c.style.cssText = `
            position: fixed;
            z-index: 100000;
            width: ${size}px;
            height: ${size}px;
            background: ${bg};
            top: 50%;
            left: 50%;
            border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
            pointer-events: none;
            transform: translate(-50%, -50%);
            transition: all ${1 + Math.random() * 0.8}s cubic-bezier(0.25, 1, 0.5, 1);
            opacity: 1;
        `;
        container.appendChild(c);

        const angle = Math.random() * Math.PI * 2;
        const dist = 120 + Math.random() * 320;
        const targetX = Math.cos(angle) * dist;
        const targetY = Math.sin(angle) * dist - 80;

        setTimeout(() => {
            c.style.transform = `translate(calc(-50% + ${targetX}px), calc(-50% + ${targetY}px)) rotate(${Math.random() * 720}deg)`;
            c.style.opacity = '0';
        }, 20);

        setTimeout(() => c.remove(), 2200);
    }
}

// ── 15-Minute Countdown Timer ───────────────────────────────
let timeLeft = 15 * 60;
const timerEl = document.getElementById('countdownTimer');
const countdown = setInterval(() => {
    timeLeft--;
    const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
    const s = String(timeLeft % 60).padStart(2, '0');
    timerEl.textContent = `${m}:${s}`;
    if (timeLeft <= 60) timerEl.style.color = '#ef4444';
    if (timeLeft <= 0) {
        clearInterval(countdown);
        clearInterval(pollInterval);
        timerEl.textContent = IS_VI ? 'Hết hạn' : 'Expired';
        cancelPayment(true);
    }
}, 1000);

// ── Real-Time Status Polling (Every 2 seconds) ──────────────
let pollInterval = null;
let isConfirmed = false;

function startPolling() {
    pollInterval = setInterval(checkPaymentStatus, 2000);
}

function checkPaymentStatus() {
    if (isConfirmed) return;
    const url = `/Gym Manager/api/momo_check_status.php?type=${PAY_TYPE}&order_id=${ORDER_ID}&sub_id=${SUB_ID}`;
    fetch(url, { method: 'GET', credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'confirmed' && !isConfirmed) {
                isConfirmed = true;
                clearInterval(pollInterval);
                clearInterval(countdown);
                triggerSuccessFlow(data);
            }
        })
        .catch(err => console.warn('MoMo poll check:', err));
}

function triggerSuccessFlow(data) {
    // 1. Play success chime
    playPaymentSuccessSound();

    // 2. Confetti
    spawnConfettiExplosion();

    // 3. Show Success Overlay
    const overlay = document.getElementById('successOverlay');
    overlay.classList.add('show');

    // 4. Update amount if returned
    if (data.amount) {
        document.getElementById('successAmountDisplay').textContent = new Intl.NumberFormat('vi-VN').format(data.amount) + 'đ';
    }

    // 5. Countdown redirect
    let sec = 3;
    const counterEl = document.getElementById('redirectCounter');
    const redirTimer = setInterval(() => {
        sec--;
        if (sec > 0) {
            counterEl.textContent = IS_VI 
                ? `Đang chuyển hướng đến trang hoá đơn trong ${sec}s...`
                : `Redirecting to invoice in ${sec}s...`;
        } else {
            clearInterval(redirTimer);
            window.location.href = SUCCESS_URL;
        }
    }, 1000);
}

// ── Cancel Payment ──────────────────────────────────────────
function cancelPayment(isTimeout = false) {
    const msg = isTimeout
        ? (IS_VI ? 'Phiên thanh toán đã hết hạn.' : 'Payment session expired.')
        : (IS_VI ? 'Bạn có chắc muốn huỷ thanh toán?' : 'Are you sure you want to cancel?');

    if (!isTimeout && !confirm(msg)) return;

    clearInterval(pollInterval);
    clearInterval(countdown);

    fetch('/Gym Manager/api/momo_confirm_qr.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: PAY_TYPE, action: 'cancel' })
    }).finally(() => {
        window.location.href = CANCEL_URL;
    });
}

// Prevent back navigation
window.history.pushState(null, '', window.location.href);
window.addEventListener('popstate', () => {
    window.history.pushState(null, '', window.location.href);
});

// Start checking status immediately!
startPolling();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

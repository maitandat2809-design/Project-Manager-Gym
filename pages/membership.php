<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
    header("Location: /Gym Manager/index.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php';

$current_lang = $_SESSION['lang'] ?? 'en';

// Fetch current user info
$user_name_qr = '';
$user_email_qr = '';
if (isset($_SESSION['user_id'])) {
    $stmt_uinfo = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt_uinfo->execute([$_SESSION['user_id']]);
    $uinfo = $stmt_uinfo->fetch();
    $user_name_qr   = $uinfo['name'] ?? '';
    $user_email_qr  = $uinfo['email'] ?? '';
}

// Fetch active plans
$plans = $pdo->query("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch user's active subscriptions
$my_subs = [];
if (isset($_SESSION['user_id'])) {
    $stmt_my = $pdo->prepare("SELECT ms.*, mp.name, mp.name_vi FROM member_subscriptions ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.user_id = ? ORDER BY ms.created_at DESC LIMIT 5");
    $stmt_my->execute([$_SESSION['user_id']]);
    $my_subs = $stmt_my->fetchAll();
}
?>

<style>
:root { 
    --mem-navy: #1a2744; 
    --mem-navy-dark: #0f172a; 
    --mem-gold: #c9a84c; 
    --mem-gold-light: #e8c97a; 
    --mem-bg: #faf9f6; 
    --mem-green: #10b981; 
    --mem-red: #ef4444; 
}

.mem-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1c2340 50%, #0f172a 100%);
    padding: 5rem 2rem 4rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid rgba(201, 168, 76, 0.15);
}
.mem-hero::before {
    content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;
    background: radial-gradient(ellipse at center, rgba(201,168,76,0.12) 0%, transparent 60%);
    animation: rotateOrb 25s linear infinite;
}
@keyframes rotateOrb { from{transform:rotate(0)} to{transform:rotate(360deg)} }
.mem-hero h1 { 
    font-family: 'Playfair Display', 'DM Serif Display', Georgia, serif;
    font-size: clamp(2.4rem, 5vw, 3.8rem);
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 1px;
    position: relative;
    z-index: 1;
}
.mem-hero h1 span { 
    color: #c9a84c; 
    font-style: italic; 
}
.mem-hero p { 
    color: rgba(255,255,255,0.75);
    font-size: 1.1rem;
    max-width: 620px;
    margin: 1rem auto 0;
    position: relative;
    z-index: 1;
}

.plans-section { 
    padding: 5rem 2rem; 
    background: var(--mem-bg); 
    position: relative;
}
.plans-grid { 
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2.2rem;
    max-width: 1300px;
    margin: 0 auto; 
}

.plan-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 2.2rem 2rem;
    box-shadow: 0 10px 35px rgba(26, 39, 68, 0.07);
    border: 1px solid rgba(201, 168, 76, 0.25);
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    display: flex;
    flex-direction: column;
}
.plan-card:hover { 
    transform: translateY(-8px);
    box-shadow: 0 22px 55px rgba(26, 39, 68, 0.15);
    border-color: rgba(201, 168, 76, 0.5);
}
.plan-card.popular { 
    border: 2px solid #c9a84c;
    box-shadow: 0 16px 45px rgba(201, 168, 76, 0.18);
}

.plan-badge {
    display: inline-block;
    padding: 5px 16px;
    border-radius: 30px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #ffffff;
    margin-bottom: 1.2rem;
    text-transform: uppercase;
}
.plan-name { 
    font-family: 'Playfair Display', 'DM Serif Display', Georgia, serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a2744;
    margin: 0 0 0.5rem; 
}
.plan-desc { 
    color: #64748b;
    font-size: 0.92rem;
    margin: 0 0 1.5rem;
    line-height: 1.6; 
}

.plan-price-box { 
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(201, 168, 76, 0.15); 
}
.plan-price { 
    font-size: 2.2rem;
    font-weight: 900;
    color: #1a2744;
}
.plan-price span { 
    font-size: 0.85rem;
    font-weight: 600;
    color: #94a3b8;
}
.plan-duration { 
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 4px; 
}

.plan-features { 
    list-style: none;
    padding: 0;
    margin: 0 0 2.2rem;
    flex-grow: 1; 
}
.plan-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 0.6rem 0;
    color: #475569;
    font-size: 0.92rem;
    border-bottom: 1px dashed rgba(201, 168, 76, 0.15);
}
.plan-features li:last-child { border-bottom: none; }
.plan-features li::before { 
    content: '✦';
    color: #c9a84c;
    font-weight: 800;
    flex-shrink: 0;
    margin-top: 1px;
}

.btn-buy {
    display: block;
    text-align: center;
    padding: 0.9rem 1.8rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.btn-buy.primary { 
    background: linear-gradient(135deg, #1a2744 0%, #2d4080 100%);
    color: #ffffff;
    box-shadow: 0 4px 18px rgba(26, 39, 68, 0.25);
}
.btn-buy.primary:hover { 
    background: linear-gradient(135deg, #c9a84c 0%, #e8c97a 100%);
    color: #1a2744;
    box-shadow: 0 8px 24px rgba(201, 168, 76, 0.35);
    transform: translateY(-2px);
}
.btn-buy.outline { 
    background: #ffffff;
    color: #1a2744;
    border: 2px solid #1a2744;
}
.btn-buy.outline:hover { 
    background: #1a2744;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26, 39, 68, 0.2);
}

.popular-ribbon {
    position: absolute;
    top: 18px;
    right: -32px;
    background: linear-gradient(135deg, #c9a84c, #e8c97a);
    color: #1a2744;
    padding: 6px 40px;
    font-size: 0.7rem;
    font-weight: 900;
    transform: rotate(35deg);
    letter-spacing: 1.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* My Subscriptions */
.my-subs-section { 
    padding: 3rem 2rem 1rem;
    max-width: 1300px;
    margin: 0 auto; 
}
.sub-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 20px rgba(26, 39, 68, 0.06);
    border: 1px solid rgba(201, 168, 76, 0.25);
    margin-bottom: 1rem;
}
.sub-status {
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.sub-status.pending { background: #fef3c7; color: #92400e; }
.sub-status.active { background: #d1fae5; color: #065f46; }
.sub-status.expired { background: #f1f5f9; color: #64748b; }
.sub-status.cancelled { background: #fee2e2; color: #991b1b; }

/* Modal */
.mem-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    padding: 1rem;
    overflow-y: auto;
}
.mem-modal-box {
    background: #ffffff;
    border-radius: 28px;
    width: 100%;
    max-width: 620px;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
    animation: modalSlideUp 0.4s cubic-bezier(0.165,0.84,0.44,1);
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(201, 168, 76, 0.2);
}
@keyframes modalSlideUp { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:translateY(0)} }
@keyframes modalPopIn { from{opacity:0;transform:scale(0.88) translateY(20px)} to{opacity:1;transform:scale(1) translateY(0)} }
.mem-modal-header {
    padding: 1.8rem 2rem 1.2rem;
    background: linear-gradient(135deg, #1c2340, #1a2744);
    position: relative;
    border-bottom: 2px solid #c9a84c;
}
.mem-modal-header h3 { 
    color: #ffffff;
    font-family: 'Playfair Display', 'DM Serif Display', Georgia, serif;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0; 
}
.mem-modal-header h3 i { color: #c9a84c; }
.mem-modal-header p { color: rgba(255,255,255,0.7); font-size: 0.9rem; margin: 0.4rem 0 0; }
.mem-modal-close {
    position: absolute; top: 1rem; right: 1rem;
    background: rgba(255,255,255,0.12); border: none; color: #fff;
    width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;
    display: flex; align-items: center; justify-content: center; transition: background 0.2s;
}
.mem-modal-close:hover { background: rgba(201, 168, 76, 0.3); }
.mem-modal-body { padding: 1.8rem 2rem 2rem; flex: 1; }
.mem-plan-summary {
    background: #faf9f6;
    border: 1px solid rgba(201, 168, 76, 0.2);
    border-radius: 16px;
    padding: 1.1rem 1.3rem;
    margin-bottom: 1.4rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.mem-plan-summary strong { font-size: 1.05rem; color: #1a2744; }
.mem-plan-summary .price-tag { font-size: 1.4rem; font-weight: 900; color: #c9a84c; }

.mem-form-group { margin-bottom: 1.2rem; }
.mem-form-group label { display: block; font-weight: 700; font-size: 0.88rem; color: #1a2744; margin-bottom: 8px; }
.mem-form-group select, .mem-form-group textarea {
    width: 100%; padding: 0.9rem 1rem; border-radius: 12px;
    border: 1.5px solid #e2e8f0; font-size: 0.9rem; color: #1a2744;
    background: #faf9f6; outline: none; transition: border 0.2s;
    font-family: inherit; box-sizing: border-box;
}
.mem-form-group select:focus, .mem-form-group textarea:focus { border-color: #c9a84c; }

.btn-submit-mem {
    width: 100%; padding: 1rem; border-radius: 50px;
    background: linear-gradient(135deg, #1a2744 0%, #2d4080 100%);
    color: #ffffff; border: none;
    font-size: 0.95rem; font-weight: 700; cursor: pointer;
    transition: all 0.25s; margin-top: 0.5rem;
    text-transform: uppercase; letter-spacing: 1px;
    box-shadow: 0 4px 18px rgba(26, 39, 68, 0.25);
}
.btn-submit-mem:hover { 
    background: linear-gradient(135deg, #c9a84c 0%, #e8c97a 100%);
    color: #1a2744;
    box-shadow: 0 8px 24px rgba(201, 168, 76, 0.35);
    transform: translateY(-2px); 
}
.btn-submit-mem:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }

/* Success state */
.mem-success { text-align:center;padding:2rem; }
.mem-success-icon { font-size:4rem;margin-bottom:1rem; }
.mem-success h3 { color:#065f46;font-size:1.4rem;font-weight:800;margin:0 0 0.5rem; }
.mem-success p { color:#64748b;margin:0 0 1.5rem; }
</style>

<!-- Hero -->
<div class="mem-hero">
    <h1><?php echo $current_lang === 'vi' ? 'THẺ <span>THÀNH VIÊN</span> GYMX' : 'GYMX <span>MEMBERSHIP</span> CARDS'; ?></h1>
    <p><?php echo $current_lang === 'vi' ? 'Chọn gói thành viên phù hợp — Bắt đầu hành trình đổi thay ngay hôm nay.' : 'Choose your perfect membership plan — Start your transformation journey today.'; ?></p>
</div>

<!-- My Active Subscriptions -->
<?php if (!empty($my_subs)): ?>
<div class="my-subs-section">
    <h2 style="font-family:'Playfair Display','DM Serif Display',serif;font-size:1.5rem;font-weight:700;color:#1a2744;margin-bottom:1.5rem;">
        <i class="fas fa-id-card" style="color:#c9a84c;margin-right:8px;"></i>
        <?php echo $current_lang === 'vi' ? 'Thẻ Thành Viên Của Tôi' : 'My Membership Cards'; ?>
    </h2>
    <?php foreach($my_subs as $sub): ?>
    <div class="sub-card">
        <div style="flex:1;min-width:200px;">
            <div style="font-family:'Playfair Display','DM Serif Display',serif;font-weight:700;font-size:1.1rem;color:#1a2744;margin-bottom:4px;">
                <?php echo htmlspecialchars($current_lang === 'vi' ? ($sub['name_vi'] ?? $sub['name']) : $sub['name']); ?>
            </div>
            <div style="font-size:0.85rem;color:#64748b;">
                <?php echo date('d/m/Y', strtotime($sub['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($sub['end_date'])); ?>
            </div>
        </div>
        <div>
            <div style="font-weight:800;color:#1a2744;font-size:1.1rem;">
                <?php echo number_format($sub['price_paid'], 0, ',', '.'); ?>đ
            </div>
        </div>
        <div>
            <span class="sub-status <?php echo $sub['status']; ?>">
                <?php
                $status_labels = ['pending'=>'Chờ xác nhận','active'=>'Đang hoạt động','expired'=>'Hết hạn','cancelled'=>'Đã hủy'];
                $status_en = ['pending'=>'Pending','active'=>'Active','expired'=>'Expired','cancelled'=>'Cancelled'];
                echo $current_lang === 'vi' ? ($status_labels[$sub['status']] ?? $sub['status']) : ($status_en[$sub['status']] ?? $sub['status']);
                ?>
            </span>
        </div>
        <?php if ($sub['status'] === 'active'): ?>
        <div style="font-size:0.85rem;color:#065f46;font-weight:600;">
            <i class="fas fa-check-circle"></i>
            <?php
            $days_left = (strtotime($sub['end_date']) - time()) / 86400;
            echo $current_lang === 'vi' ? 'Còn ' . max(0, ceil($days_left)) . ' ngày' : ceil(max(0,$days_left)) . ' days left';
            ?>
        </div>
        <?php endif; ?>

        <?php if (in_array($sub['status'], ['active', 'pending'])): 
            $sub_pname = htmlspecialchars($current_lang === 'vi' ? ($sub['name_vi'] ?? $sub['name']) : $sub['name'], ENT_QUOTES);
        ?>
        <div>
            <button onclick="cancelAndRefund(<?php echo $sub['id']; ?>, '<?php echo addslashes($sub_pname); ?>')" 
                    style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:6px 14px;border-radius:10px;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all 0.2s;"
                    onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='#fee2e2'">
                <i class="fas fa-trash-alt" style="margin-right:4px;"></i>
                <?php echo $current_lang === 'vi' ? 'Xóa thẻ thành viên' : 'Delete Membership'; ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Plans Grid -->
<div class="plans-section">
    <div style="text-align:center;margin-bottom:3.5rem;">
        <div style="font-size:0.75rem;font-weight:700;letter-spacing:3px;color:#c9a84c;text-transform:uppercase;margin-bottom:0.5rem;">
            <?php echo $current_lang === 'vi' ? 'CÁC GÓI DỊCH VỤ' : 'MEMBERSHIP PLANS'; ?>
        </div>
        <h2 style="font-family:'Playfair Display','DM Serif Display',serif;font-size:clamp(2rem,4.5vw,3.2rem);font-weight:700;color:#1a2744;margin:0 0 0.5rem;">
            <?php echo $current_lang === 'vi' ? 'Chọn Gói Phù Hợp Với Bạn' : 'Find Your Perfect Plan'; ?>
        </h2>
        <div style="width:60px;height:2px;background:#c9a84c;margin:12px auto 16px;position:relative;">
            <span style="position:absolute;top:-4px;left:50%;transform:translateX(-50%);font-size:8px;color:#c9a84c;">◆</span>
        </div>
        <p style="color:#64748b;max-width:500px;margin:0 auto;">
            <?php echo $current_lang === 'vi' ? 'Linh hoạt, minh bạch, không phí ẩn.' : 'Flexible, transparent, no hidden fees.'; ?>
        </p>
    </div>

    <div class="plans-grid">
        <?php foreach($plans as $plan):
            $features = explode('|', $current_lang === 'vi' ? ($plan['features_vi'] ?? $plan['features']) : $plan['features']);
            $badge_color = $plan['badge_color'] ?? '#2563eb';
        ?>
        <div class="plan-card <?php echo $plan['is_popular'] ? 'popular' : ''; ?>">
            <?php if ($plan['is_popular']): ?>
            <div class="popular-ribbon">POPULAR</div>
            <?php endif; ?>

            <?php if ($plan['badge']): ?>
            <div class="plan-badge" style="background:<?php echo htmlspecialchars($badge_color); ?>;">
                <?php echo htmlspecialchars($plan['badge']); ?>
            </div>
            <?php endif; ?>

            <div class="plan-name"><?php echo htmlspecialchars($current_lang === 'vi' ? ($plan['name_vi'] ?? $plan['name']) : $plan['name']); ?></div>
            <p class="plan-desc"><?php echo htmlspecialchars($current_lang === 'vi' ? ($plan['description_vi'] ?? $plan['description']) : $plan['description']); ?></p>

            <div class="plan-price-box">
                <div class="plan-price">
                    <?php echo number_format($plan['price'], 0, ',', '.'); ?>đ
                    <span>/<?php echo $plan['duration_days'] >= 365 ? ($current_lang === 'vi' ? 'năm' : 'year') : ($current_lang === 'vi' ? 'tháng' : 'month'); ?></span>
                </div>
                <div class="plan-duration">
                    <i class="fas fa-calendar-alt" style="color:<?php echo htmlspecialchars($badge_color); ?>;margin-right:5px;"></i>
                    <?php echo $plan['duration_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?>
                </div>
            </div>

            <ul class="plan-features">
                <?php foreach($features as $f): ?>
                <li><?php echo htmlspecialchars(trim($f)); ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="/Gym Manager/auth/login.php" class="btn-buy <?php echo $plan['is_popular'] ? 'primary' : 'outline'; ?>">
                <i class="fas fa-lock" style="margin-right:6px;"></i>
                <?php echo $current_lang === 'vi' ? 'Đăng nhập để mua' : 'Login to Purchase'; ?>
            </a>
            <?php else: 
                $p_name = htmlspecialchars($current_lang === 'vi' ? ($plan['name_vi'] ?? $plan['name']) : $plan['name'], ENT_QUOTES);
                $p_fmt = number_format($plan['price'], 0, ',', '.') . 'đ';
            ?>
            <button class="btn-buy <?php echo $plan['is_popular'] ? 'primary' : 'outline'; ?>"
                    data-plan-id="<?php echo $plan['id']; ?>"
                    data-plan-name="<?php echo $p_name; ?>"
                    data-plan-price="<?php echo $plan['price']; ?>"
                    data-plan-formatted="<?php echo $p_fmt; ?>"
                    onclick="handleBuyClick(this)">
                <i class="fas fa-credit-card" style="margin-right:6px;"></i>
                <?php echo $current_lang === 'vi' ? 'Mua Thẻ Ngay' : 'Get This Plan'; ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Purchase Modal -->
<div class="mem-modal-overlay" id="memModal">
    <div class="mem-modal-box" style="max-width:600px;">
        <div class="mem-modal-header">
            <h3><i class="fas fa-id-card" style="margin-right:8px;"></i><?php echo $current_lang === 'vi' ? 'Đăng Ký Thẻ Thành Viên' : 'Purchase Membership'; ?></h3>
            <p><?php echo $current_lang === 'vi' ? 'Hoàn tất đăng ký trong vài giây.' : 'Complete your registration in seconds.'; ?></p>
            <button class="mem-modal-close" onclick="closeMemberModal()">×</button>
        </div>

        <div class="mem-modal-body">
            <!-- Form View -->
            <div id="memFormView">
                <div class="mem-plan-summary">
                    <strong id="modalPlanName"></strong>
                    <span class="price-tag" id="modalPlanPrice"></span>
                </div>

                <div class="mem-form-group">
                    <label><?php echo $current_lang === 'vi' ? 'Phương thức thanh toán' : 'Payment Method'; ?></label>
                    <select id="memPaymentMethod" onchange="toggleMemMomoHint()">
                        <option value="cod" selected><?php echo $current_lang === 'vi' ? '💵 Thanh toán trực tiếp tại quầy (Tiền mặt / Thẻ)' : '💵 Pay at Counter (Cash / Card)'; ?></option>
                        <option value="momo">💜 <?php echo $current_lang === 'vi' ? 'Ví MoMo — Thanh toán tức thì & kích hoạt ngay' : 'MoMo Wallet — Instant payment & instant activation'; ?></option>
                    </select>
                    <!-- MoMo hint -->
                    <div id="memMomoHint" style="display:none;margin-top:10px;background:#fdf4ff;border:1.5px solid #e879f9;border-radius:10px;padding:10px 14px;font-size:0.88rem;color:#7e22ce;">
                        <i class="fas fa-info-circle" style="margin-right:6px;"></i>
                        <?php echo $current_lang === 'vi' ? 'Bạn sẽ được chuyển đến trang thanh toán MoMo. Thẻ thành viên sẽ được kích hoạt tự động ngay sau khi thanh toán thành công.' : 'You will be redirected to MoMo. Membership will activate instantly after payment.'; ?>
                    </div>
                </div>

                <div class="mem-form-group" style="margin-top:1rem;">
                    <label><?php echo $current_lang === 'vi' ? 'Ghi chú (tuỳ chọn)' : 'Note (optional)'; ?></label>
                    <textarea id="memNote" rows="2" placeholder="<?php echo $current_lang === 'vi' ? 'Ghi chú thêm cho bộ phận tiếp nhận...' : 'Additional notes for our team...'; ?>"></textarea>
                </div>

                <button class="btn-submit-mem" id="btnSubmitMem" onclick="submitMembership()">
                    <i class="fas fa-check-circle" style="margin-right:8px;"></i>
                    <?php echo $current_lang === 'vi' ? 'Xác Nhận Đăng Ký' : 'Confirm Registration'; ?>
                </button>
            </div>

            <!-- Success View -->
            <div id="memSuccessView" style="display:none;"></div>
        </div>
    </div>
</div>

<script>
let selectedPlanId   = null;
let selectedPlanPrice = 0;

function toggleMemMomoHint() {
    var sel  = document.getElementById('memPaymentMethod');
    var hint = document.getElementById('memMomoHint');
    if (hint) hint.style.display = (sel && sel.value === 'momo') ? 'block' : 'none';
}

function handleBuyClick(btn) {
    openMemberModal(
        btn.getAttribute('data-plan-id'),
        btn.getAttribute('data-plan-name'),
        parseFloat(btn.getAttribute('data-plan-price')),
        btn.getAttribute('data-plan-formatted')
    );
}

function openMemberModal(planId, planName, planPrice, priceFormatted) {
    selectedPlanId    = planId;
    selectedPlanPrice = planPrice;

    document.getElementById('memFormView').style.display  = 'block';
    document.getElementById('memSuccessView').style.display = 'none';

    const btn = document.getElementById('btnSubmitMem');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';

    document.getElementById('modalPlanName').textContent  = planName;
    document.getElementById('modalPlanPrice').textContent = priceFormatted;
    document.getElementById('memNote').value = '';

    const sel = document.getElementById('memPaymentMethod');
    if (sel) sel.value = 'cod';

    document.getElementById('memModal').style.display = 'flex';
}

function closeMemberModal() {
    document.getElementById('memModal').style.display = 'none';
}

function submitMembership() {
    if (!selectedPlanId) return;
    const method = document.getElementById('memPaymentMethod').value;
    const btn    = document.getElementById('btnSubmitMem');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Đang xử lý..." : "Processing..."; ?>';

    // ── MoMo Payment ─────────────────────────────────────────────
    if (method === 'momo') {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Đang kết nối MoMo..." : "Connecting to MoMo..."; ?>';
        fetch('/Gym Manager/api/momo_pay_membership.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: parseInt(selectedPlanId) })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.payUrl) {
                window.location.href = data.payUrl;
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
                alert('❌ ' + (data.message || '<?php echo $current_lang === "vi" ? "Lỗi kết nối MoMo" : "MoMo connection error"; ?>'));
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
            alert('❌ <?php echo $current_lang === "vi" ? "Không thể kết nối đến máy chủ." : "Could not connect to server."; ?>');
        });
        return;
    }

    const fd = new FormData();
    fd.append('plan_id',        selectedPlanId);
    fd.append('payment_method', method);
    fd.append('note',           document.getElementById('memNote').value);

    fetch('/Gym Manager/api/buy_membership.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(async data => {
            if (data.success || data.awaiting_payment) {
                showPendingView(data);
            } else if (data.has_existing) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
                
                const isEn = GYMX_LANG === 'en';
                const confirmDelete = await showGymXConfirm(
                    isEn
                        ? `You currently have an active membership ("${data.existing_plan_name}"). You cannot register multiple memberships.\n\nWould you like to DELETE your previous membership now to register this new one?`
                        : `Bạn hiện có gói thẻ thành viên "${data.existing_plan_name}" đang tồn tại. Hệ thống không cho phép đăng ký nhiều thẻ thành viên cùng lúc.\n\nBạn có muốn XÓA gói thẻ trước đó để tiến hành đăng ký gói mới này không?`,
                    isEn ? 'Delete Previous Membership?' : 'Xóa Thẻ Thành Viên Cũ?',
                    '⚠️'
                );

                if (confirmDelete) {
                    deleteAndReSubmit(data.existing_sub_id);
                }
            } else {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
                showGymXAlert(data.message || 'Không thể đăng ký.', 'Lỗi Đăng Ký', '❌');
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
            showGymXAlert('<?php echo $current_lang === "vi" ? "Lỗi kết nối. Vui lòng thử lại." : "Connection error."; ?>', 'Lỗi Kết Nối', '⚠️');
        });
}

async function deleteAndReSubmit(subId) {
    const isEn = GYMX_LANG === 'en';
    try {
        const fd = new FormData();
        fd.append('action', 'delete_membership');
        fd.append('sub_id', subId);

        const res = await fetch('/Gym Manager/api/buy_membership.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            await showGymXAlert(
                isEn ? 'Previous membership deleted. Now registering your new membership package...' : 'Đã xóa thẻ thành viên trước đó. Đang tiến hành đăng ký gói mới...',
                isEn ? 'Deleted Successfully' : 'Đã Xóa Thành Công',
                '✅'
            );
            submitMembership();
        } else {
            showGymXAlert(data.message || (isEn ? 'Failed to delete previous membership.' : 'Lỗi khi xóa thẻ cũ.'), isEn ? 'Deletion Failed' : 'Xóa Thất Bại', '❌');
        }
    } catch(e) {
        showGymXAlert(isEn ? 'Connection error. Please try again.' : 'Lỗi kết nối. Vui lòng thử lại.', isEn ? 'Connection Error' : 'Lỗi Kết Nối', '⚠️');
    }
}

// ── Success / Pending view with mandatory Counter Pick-up Notification ──
function showPendingView(data) {
    document.getElementById('memFormView').style.display   = 'none';
    const c = document.getElementById('memSuccessView');
    c.style.display = 'block';
    c.innerHTML = `
        <div style="text-align:center;padding:1.5rem 0.5rem;animation:modalPopIn .4s ease-out;">
            <div style="width:90px;height:90px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 12px 30px rgba(16,185,129,.35);">
                <i class="fas fa-check-circle" style="font-size:2.8rem;color:#fff;"></i>
            </div>
            <h3 style="font-size:1.5rem;font-weight:900;color:#0f172a;margin-bottom:.5rem;">🎉 <?php echo $current_lang === "vi" ? "Đăng Ký Thành Công!" : "Registration Successful!"; ?></h3>
            
            <div style="background:#f0fdf4;border:2px solid #86efac;border-radius:16px;padding:1.25rem 1rem;margin:1.2rem 0;text-align:center;box-shadow:0 4px 15px rgba(16,185,129,0.1);">
                <p style="color:#166534;font-size:1.05rem;font-weight:800;margin:0;line-height:1.5;">
                    <i class="fas fa-store" style="margin-right:8px;font-size:1.2rem;color:#15803d;"></i>
                    <?php echo $current_lang === "vi" 
                        ? "Đăng ký thành công, vui lòng đến quầy giao dịch để nhận thẻ thành viên." 
                        : "Registration successful! Please visit the service counter to receive your membership card."; ?>
                </p>
            </div>

            <p style="color:#64748b;font-size:.9rem;margin-bottom:1.4rem;"><?php echo $current_lang === "vi" ? "Thẻ của bạn sẽ được kích hoạt tại quầy giao dịch sau khi xác nhận thông tin." : "Your membership card will be activated at the counter upon verification."; ?></p>
            
            <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:1.2rem;margin-bottom:1.5rem;text-align:left;">
                <p style="margin:6px 0;font-size:.9rem;"><strong>💳 <?php echo $current_lang === "vi" ? "Gói đăng ký:" : "Registered Package:"; ?></strong> <span style="color:#2563eb;font-weight:800;">${data.plan_name || ''}</span></p>
                <p style="margin:6px 0;font-size:.9rem;"><strong>💰 <?php echo $current_lang === "vi" ? "Số tiền:" : "Amount:"; ?></strong> <span style="color:#ef4444;font-weight:800;">${data.price}</span></p>
                <p style="margin:6px 0;font-size:.9rem;"><strong>💵 <?php echo $current_lang === "vi" ? "Phương thức:" : "Payment Method:"; ?></strong> <span style="color:#059669;font-weight:700;"><?php echo $current_lang === "vi" ? "Thanh toán trực tiếp tại quầy" : "Pay at counter"; ?></span></p>
            </div>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                <button onclick="closeMemberModal(); location.reload();" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:.85rem 2rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.95rem;box-shadow:0 4px 15px rgba(37,99,235,0.3);">
                    <i class="fas fa-check" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Hoàn Tất" : "Done"; ?>
                </button>
            </div>
        </div>`;
}

document.getElementById('memModal').addEventListener('click', function(e) {
    if (e.target === this) closeMemberModal();
});

async function cancelAndRefund(subId, planName) {
    const isEn = GYMX_LANG === 'en';
    const ok = await showGymXConfirm(
        isEn 
            ? `Are you sure you want to DELETE the "${planName}" membership?`
            : `Bạn có chắc chắn muốn XÓA gói thẻ thành viên "${planName}"?`,
        isEn ? 'Delete Membership' : 'Xác Nhận Xóa Thẻ',
        '🗑️'
    );
    if (!ok) return;

    try {
        const fd = new FormData();
        fd.append('action', 'delete_membership');
        fd.append('sub_id', subId);

        const res = await fetch('/Gym Manager/api/buy_membership.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            await showGymXAlert(
                data.message || (isEn ? 'Membership deleted successfully.' : 'Đã xóa thẻ thành viên thành công.'),
                isEn ? 'Deletion Successful' : 'Xóa Thẻ Thành Công',
                '✅'
            );
            location.reload();
        } else {
            showGymXAlert(data.message || (isEn ? 'Failed to delete membership.' : 'Lỗi khi xóa thẻ.'), isEn ? 'Deletion Failed' : 'Xóa Thẻ Thất Bại', '❌');
        }
    } catch(e) {
        showGymXAlert(isEn ? 'Connection error. Please try again.' : 'Lỗi kết nối. Vui lòng thử lại.', isEn ? 'Connection Error' : 'Lỗi Kết Nối', '⚠️');
    }
}

// Auto open plan modal if plan_id parameter is passed in URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const planId = urlParams.get('plan_id');
    if (planId) {
        const btn = document.querySelector(`.btn-buy[data-plan-id="${planId}"]`);
        if (btn) {
            handleBuyClick(btn);
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

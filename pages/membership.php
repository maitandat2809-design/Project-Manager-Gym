<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['coach', 'manager', 'admin'])) {
    header("Location: /Gym Manager/index.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/membership_loyalty.php';
include_once __DIR__ . '/../includes/header.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$curr_user_id = $_SESSION['user_id'] ?? 0;

// Fetch current user loyalty status & membership history
$loyalty_info = getUserMembershipLoyaltyInfo($pdo, $curr_user_id);
$is_loyal     = $loyalty_info['is_eligible'];

// Fetch current user info
$user_name_qr = '';
$user_email_qr = '';
if ($curr_user_id > 0) {
    $stmt_uinfo = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt_uinfo->execute([$curr_user_id]);
    $uinfo = $stmt_uinfo->fetch();
    $user_name_qr   = $uinfo['name'] ?? '';
    $user_email_qr  = $uinfo['email'] ?? '';
}

// Fetch active plans
$plans = $pdo->query("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch user's active/past subscriptions
$my_subs = [];
if ($curr_user_id > 0) {
    $stmt_my = $pdo->prepare("
        SELECT ms.*, mp.name, mp.name_vi, mp.duration_days as plan_duration 
        FROM member_subscriptions ms 
        JOIN membership_plans mp ON ms.plan_id = mp.id 
        WHERE ms.user_id = ? 
        ORDER BY ms.created_at DESC 
        LIMIT 5
    ");
    $stmt_my->execute([$curr_user_id]);
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
.my-subs-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.my-subs-heading h2 { margin: 0 !important; }
.membership-overview-btn {
    background: #ffffff;
    color: #1a2744;
    border: 1.5px solid #cbd5e1;
    padding: 0.65rem 1rem;
    border-radius: 12px;
    font-size: 0.84rem;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05);
}
.membership-overview-btn:hover { color: #1d4ed8; border-color: #60a5fa; background: #eff6ff; transform: translateY(-1px); }
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
/* Membership records are presented only in the Details dialog beside the heading. */
.sub-card { display: none; }
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
.sub-card-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
.sub-details-btn {
    background: #ffffff;
    color: #1a2744;
    border: 1.5px solid #cbd5e1;
    padding: 6px 13px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.sub-details-btn:hover {
    border-color: #1d4ed8;
    background: #eff6ff;
    color: #1d4ed8;
    transform: translateY(-1px);
}
.sub-history-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 100002;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.68);
    backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
}
.sub-history-modal-box {
    width: min(100%, 560px);
    max-height: min(760px, 90vh);
    overflow-y: auto;
    background: #ffffff;
    border-radius: 22px;
    box-shadow: 0 25px 70px rgba(15, 23, 42, 0.36);
    border: 1px solid rgba(201, 168, 76, 0.35);
}
.sub-history-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.35rem 1.5rem;
    color: #ffffff;
    background: linear-gradient(135deg, #1a2744, #0f172a);
}
.sub-history-close {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border: 0;
    border-radius: 50%;
    color: #ffffff;
    background: rgba(255,255,255,0.13);
    font-size: 1.2rem;
    cursor: pointer;
}
.sub-history-body { padding: 1.35rem 1.5rem 1.5rem; }
.sub-history-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    margin-bottom: 1.35rem;
}
.sub-history-item {
    padding: 0.8rem 0.9rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.sub-history-item span { display: block; color: #64748b; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
.sub-history-item strong { display: block; color: #1a2744; font-size: 0.9rem; overflow-wrap: anywhere; }
.sub-history-timeline { list-style: none; padding: 0; margin: 0; }
.sub-history-timeline li { position: relative; padding: 0 0 1rem 1.6rem; color: #475569; font-size: 0.86rem; line-height: 1.45; }
.sub-history-timeline li::before { content: ''; position: absolute; left: 2px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #c9a84c; box-shadow: 0 0 0 4px #fef3c7; }
.sub-history-timeline li:not(:last-child)::after { content: ''; position: absolute; left: 6px; top: 17px; bottom: 0; width: 2px; background: #e2e8f0; }
@media (max-width: 560px) { .sub-history-grid { grid-template-columns: 1fr; } .sub-card-actions { width: 100%; } }

/* Loyalty Member Banner & Badges */
.loyalty-hero-banner {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #1e293b 100%);
    border: 2px solid #f59e0b;
    border-radius: 26px;
    padding: 2.2rem 2.5rem;
    max-width: 1300px;
    margin: 2.5rem auto 0;
    box-shadow: 0 16px 45px rgba(245, 158, 11, 0.2);
    position: relative;
    overflow: hidden;
    color: #ffffff;
}
.loyalty-hero-banner::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.25) 0%, transparent 70%);
    pointer-events: none;
}
.loyalty-badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 800;
    padding: 6px 16px;
    border-radius: 20px;
    letter-spacing: 1px;
    text-transform: uppercase;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
    margin-bottom: 0.8rem;
}
.loyalty-perks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.2rem;
    margin-top: 1.6rem;
}
.loyalty-perk-box {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 18px;
    padding: 1.2rem 1.4rem;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    backdrop-filter: blur(6px);
    transition: transform 0.25s, border-color 0.25s;
}
.loyalty-perk-box:hover {
    transform: translateY(-3px);
    border-color: #f59e0b;
    background: rgba(255, 255, 255, 0.1);
}
.loyalty-perk-box .perk-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
.loyalty-perk-box .perk-text strong {
    display: block;
    font-size: 0.96rem;
    color: #fef3c7;
    margin-bottom: 4px;
    font-weight: 800;
}
.loyalty-perk-box .perk-text span {
    display: block;
    font-size: 0.82rem;
    color: #cbd5e1;
    line-height: 1.4;
}
.loyalty-ribbon {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    z-index: 2;
}
.plan-card.loyalty-active {
    border: 2px solid #f59e0b;
    box-shadow: 0 16px 45px rgba(245, 158, 11, 0.16);
}
.plan-price-orig {
    font-size: 1rem;
    font-weight: 600;
    color: #94a3b8;
    text-decoration: line-through;
    margin-bottom: 2px;
}
.plan-savings-pill {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.76rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
}
.perk-highlight {
    background: #fffbeb !important;
    border: 1px dashed #fcd34d !important;
    border-radius: 10px;
    padding: 8px 12px !important;
    color: #92400e !important;
    font-weight: 700;
    margin-bottom: 6px;
}
.perk-highlight::before {
    content: '★' !important;
    color: #d97706 !important;
}
.btn-renew-fast {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    border: none;
    padding: 7px 16px;
    border-radius: 12px;
    font-size: 0.82rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    text-decoration: none;
}
.btn-renew-fast:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(245, 158, 11, 0.4);
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: #ffffff;
}
.new-member-teaser-box {
    max-width: 1300px;
    margin: 2rem auto 0;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1.5px dashed #c9a84c;
    border-radius: 18px;
    padding: 1.2rem 1.8rem;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #1a2744;
}

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

/* ── Basic Plan Specs & Actions (Card View) ── */
.plan-basic-specs {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1rem 1.1rem;
    margin: 0 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.plan-spec-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.86rem;
    gap: 8px;
}
.plan-spec-row .spec-label {
    color: #64748b;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.plan-spec-row .spec-val {
    color: #1a2744;
    font-weight: 700;
    text-align: right;
}
.plan-highlight-pill {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 700;
    text-align: center;
    margin-top: 4px;
}
.plan-actions {
    margin-top: auto;
    display: grid;
    grid-template-columns: minmax(104px, 0.8fr) minmax(0, 1.45fr);
    gap: 0.65rem;
    align-items: center;
    width: 100%;
}
.btn-plan-details {
    min-width: 0;
    box-sizing: border-box;
    background: #ffffff;
    color: #1a2744;
    border: 1.5px solid #cbd5e1;
    border-radius: 50px;
    padding: 0.85rem 0.95rem;
    font-weight: 800;
    font-size: 0.84rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: normal;
    line-height: 1.2;
}
.btn-plan-details:hover {
    border-color: #1a2744;
    background: #f1f5f9;
    color: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}
.plan-actions .btn-buy {
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
    margin: 0;
    padding: 0.85rem 1rem;
    font-size: 0.84rem;
    white-space: normal;
    line-height: 1.2;
    overflow-wrap: anywhere;
}
@media (max-width: 480px) {
    .plan-actions {
        grid-template-columns: 1fr;
    }
    .btn-plan-details, .plan-actions .btn-buy {
        width: 100%;
    }
}

/* ── Category Filter Tabs on Main Page ── */
.plans-filter-tabs {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-bottom: 2.8rem;
    flex-wrap: wrap;
}
.plan-cat-tab {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #64748b;
    padding: 0.75rem 1.6rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.plan-cat-tab:hover {
    border-color: #cbd5e1;
    color: #1a2744;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}
.plan-cat-tab.active {
    background: #1a2744;
    color: #ffffff;
    border-color: #1a2744;
    box-shadow: 0 6px 20px rgba(26, 39, 68, 0.2);
}
.plan-cat-tab.active i {
    color: #c9a84c;
}

/* ── Clean & Simple Membership Details Modal (Tabbed, Uncluttered) ── */
.plan-details-modal-box {
    background: #ffffff;
    border-radius: 24px;
    width: 100%;
    max-width: 680px;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.28);
    animation: modalSlideUp 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    border: 1px solid rgba(201, 168, 76, 0.25);
}

.plan-details-modal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1a2744 100%);
    color: #ffffff;
    padding: 1.6rem 2rem 1.4rem;
    position: relative;
    border-bottom: 2px solid #c9a84c;
}
.plan-details-modal-header .header-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0.8rem;
}
.plan-details-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #ffffff;
    text-transform: uppercase;
}
.plan-details-modal-close {
    background: rgba(255, 255, 255, 0.12);
    border: none;
    color: #cbd5e1;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.plan-details-modal-close:hover {
    background: rgba(239, 68, 68, 0.8);
    color: #ffffff;
}

.plan-details-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}
.plan-details-title {
    font-family: 'Playfair Display', 'DM Serif Display', Georgia, serif;
    font-size: 1.55rem;
    font-weight: 800;
    margin: 0;
    color: #ffffff;
    line-height: 1.2;
}
.plan-details-desc {
    margin: 4px 0 0;
    font-size: 0.88rem;
    color: #94a3b8;
    max-width: 400px;
}
.plan-details-price-badge {
    text-align: right;
    flex-shrink: 0;
}
.plan-details-price-main {
    font-size: 1.65rem;
    font-weight: 900;
    color: #e8c97a;
    line-height: 1;
}
.plan-details-price-cycle {
    font-size: 0.82rem;
    color: #cbd5e1;
    font-weight: 600;
}
.plan-details-price-orig {
    font-size: 0.82rem;
    color: #94a3b8;
    text-decoration: line-through;
    margin-bottom: 2px;
}
.plan-details-save-pill {
    background: rgba(234, 179, 8, 0.2);
    color: #fde047;
    border: 1px solid rgba(234, 179, 8, 0.4);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 800;
    display: inline-block;
    margin-top: 4px;
}

/* ── Modern Tabs Navigation ── */
.plan-details-tabs-bar {
    display: flex;
    gap: 8px;
    background: #f8fafc;
    padding: 0.65rem 1.6rem;
    border-bottom: 1px solid #e2e8f0;
}
.plan-dtab-btn {
    flex: 1;
    background: transparent;
    border: none;
    border-radius: 12px;
    padding: 0.65rem 0.8rem;
    font-weight: 700;
    font-size: 0.86rem;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
}
.plan-dtab-btn:hover {
    color: #1a2744;
    background: #f1f5f9;
}
.plan-dtab-btn.active {
    background: #1a2744;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(26, 39, 68, 0.18);
}
.plan-dtab-btn.active i {
    color: #c9a84c;
}

/* ── Modal Body & Tab Panes ── */
.plan-details-body {
    padding: 1.4rem 1.8rem;
    overflow-y: auto;
    flex: 1;
}
.plan-dtab-pane {
    display: none;
    animation: fadeInTab 0.3s ease-out;
}
.plan-dtab-pane.active {
    display: block;
}
@keyframes fadeInTab {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Tab 1: Overview 4 Grid (Simple, Clean, Exactly 4 Items) ── */
.plan-overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.95rem;
}
.plan-overview-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 1rem 1.15rem;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.plan-overview-card:hover {
    border-color: #c9a84c;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}
.plan-oc-label {
    font-size: 0.74rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}
.plan-oc-val {
    font-size: 1.12rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
}
.plan-oc-sub {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 3px;
    line-height: 1.35;
}

/* ── Tab 2: Perks & Privileges (Simple & Focused) ── */
.plan-perks-list {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}
.plan-perk-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.85rem 1.1rem;
    border-radius: 14px;
    font-size: 0.88rem;
    color: #1e293b;
    font-weight: 600;
}
.plan-perk-item i {
    color: #10b981;
    font-size: 1.05rem;
    flex-shrink: 0;
}
.plan-loyalty-highlight-box {
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    margin-top: 0.9rem;
    color: #92400e;
}
.plan-loyalty-hl-title {
    font-weight: 800;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
    color: #b45309;
}
.plan-loyalty-hl-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 12px;
    font-size: 0.8rem;
    color: #78350f;
}

/* ── Tab 3: Policies (Simple 3 Rules) ── */
.plan-policies-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.plan-policy-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.9rem 1.1rem;
}
.plan-policy-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.plan-policy-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}
.plan-policy-desc {
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.4;
    margin: 0;
}

/* ── Modal Footer Bar ── */
.plan-details-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 0.95rem 1.8rem;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
}
.btn-details-cancel {
    background: #ffffff;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    border-radius: 50px;
    padding: 0.7rem 1.4rem;
    font-weight: 700;
    font-size: 0.86rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-details-cancel:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.btn-details-action {
    background: linear-gradient(135deg, #1a2744 0%, #2d4080 100%);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    padding: 0.72rem 1.7rem;
    font-weight: 800;
    font-size: 0.86rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(26, 39, 68, 0.22);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.btn-details-action:hover {
    background: linear-gradient(135deg, #c9a84c 0%, #e8c97a 100%);
    color: #1a2744;
    box-shadow: 0 6px 20px rgba(201, 168, 76, 0.35);
    transform: translateY(-2px);
}

@media (max-width: 640px) {
    .plan-details-title-row { flex-direction: column; align-items: flex-start; }
    .plan-details-price-badge { text-align: left; }
    .plan-overview-grid { grid-template-columns: 1fr; }
    .plan-loyalty-hl-grid { grid-template-columns: 1fr; }
    .plan-details-tabs-bar { padding: 0.5rem 1rem; }
    .plan-details-body { padding: 1.2rem 1.2rem; }
}

@media print {
    body * { visibility: hidden !important; }
    #planDetailsModal, #planDetailsModal * { visibility: visible !important; }
    #planDetailsModal {
        position: absolute !important;
        inset: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        display: block !important;
    }
    .plan-details-modal-box { max-width: 100% !important; box-shadow: none !important; border: 1px solid #000 !important; }
    .plan-dtab-pane { display: block !important; }
    .plan-details-tabs-bar, .plan-details-footer, .plan-details-modal-close { display: none !important; }
}
</style>

<!-- Hero -->
<div class="mem-hero">
    <h1><?php echo $current_lang === 'vi' ? 'THẺ <span>THÀNH VIÊN</span> GYMX' : 'GYMX <span>MEMBERSHIP</span> CARDS'; ?></h1>
    <p><?php echo $current_lang === 'vi' ? 'Chọn gói thành viên phù hợp — Bắt đầu hành trình đổi thay ngay hôm nay.' : 'Choose your perfect membership plan — Start your transformation journey today.'; ?></p>
</div>

<!-- My Active Subscriptions -->
<?php if (!empty($my_subs)): ?>
<div class="my-subs-section">
    <div class="my-subs-heading">
        <h2 style="font-family:'Playfair Display','DM Serif Display',serif;font-size:1.5rem;font-weight:700;color:#1a2744;">
            <i class="fas fa-id-card" style="color:#c9a84c;margin-right:8px;"></i>
            <?php echo $current_lang === 'vi' ? 'Thẻ Thành Viên Của Tôi' : 'My Membership Cards'; ?>
        </h2>
        <button type="button" class="membership-overview-btn" onclick="document.getElementById('membership-history-overview').style.display='flex'">
            <i class="fas fa-history"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết Lịch Sử' : 'Details'; ?>
        </button>
    </div>
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

        <?php
            $sub_pname = htmlspecialchars($current_lang === 'vi' ? ($sub['name_vi'] ?? $sub['name']) : $sub['name'], ENT_QUOTES);
            $sub_modal_id = 'membership-history-' . (int)$sub['id'];
        ?>
        <div class="sub-card-actions">
            <button type="button" class="sub-details-btn" onclick="document.getElementById('<?php echo $sub_modal_id; ?>').style.display='flex'">
                <i class="fas fa-file-alt"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?>
            </button>
        <?php if (in_array($sub['status'], ['active', 'pending', 'expired'])): ?>
            <?php if ($sub['status'] === 'active' || $sub['status'] === 'expired'): ?>
            <button class="btn-renew-fast" onclick="openRenewalForPlan(<?php echo $sub['plan_id']; ?>)" title="<?php echo $current_lang === 'vi' ? 'Gia hạn ngay với ưu đãi hội viên thân thiết' : 'Renew now with loyalty perks'; ?>">
                <i class="fas fa-bolt"></i>
                <?php echo $current_lang === 'vi' ? 'Gia Hạn (-10% & +Ngày)' : 'Renew (-10% & +Days)'; ?>
            </button>
            <?php endif; ?>
            <button onclick="cancelAndRefund(<?php echo $sub['id']; ?>, '<?php echo addslashes($sub_pname); ?>')" 
                    style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:6px 14px;border-radius:10px;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all 0.2s;"
                    onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='#fee2e2'">
                <i class="fas fa-trash-alt" style="margin-right:4px;"></i>
                <?php echo $current_lang === 'vi' ? 'Xóa thẻ' : 'Delete'; ?>
            </button>
        <?php endif; ?>
        </div>
    </div>

    <?php
        $payment_labels_en = ['cod' => 'Pay at counter', 'bank_transfer' => 'Bank transfer', 'momo' => 'MoMo e-wallet'];
        $payment_labels_vi = ['cod' => 'Thanh toán tại quầy', 'bank_transfer' => 'Chuyển khoản ngân hàng', 'momo' => 'Ví điện tử MoMo'];
        $payment_label = $current_lang === 'vi'
            ? ($payment_labels_vi[$sub['payment_method'] ?? ''] ?? ($sub['payment_method'] ?? '—'))
            : ($payment_labels_en[$sub['payment_method'] ?? ''] ?? ($sub['payment_method'] ?? '—'));
        $status_label = $current_lang === 'vi'
            ? ($status_labels[$sub['status']] ?? $sub['status'])
            : ($status_en[$sub['status']] ?? $sub['status']);
    ?>
    <div id="<?php echo $sub_modal_id; ?>" class="sub-history-modal" onclick="if(event.target===this) this.style.display='none'">
        <div class="sub-history-modal-box" role="dialog" aria-modal="true" aria-label="<?php echo $current_lang === 'vi' ? 'Chi tiết thẻ thành viên' : 'Membership details'; ?>">
            <div class="sub-history-head">
                <div>
                    <div style="font-size:0.72rem;font-weight:800;letter-spacing:0.09em;text-transform:uppercase;color:#c9a84c;margin-bottom:5px;"><?php echo $current_lang === 'vi' ? 'HỒ SƠ GÓI THÀNH VIÊN' : 'MEMBERSHIP RECORD'; ?></div>
                    <h3 style="margin:0;font-family:'Playfair Display','DM Serif Display',serif;font-size:1.35rem;line-height:1.3;"><?php echo $sub_pname; ?></h3>
                </div>
                <button type="button" class="sub-history-close" aria-label="<?php echo $current_lang === 'vi' ? 'Đóng' : 'Close'; ?>" onclick="document.getElementById('<?php echo $sub_modal_id; ?>').style.display='none'">&times;</button>
            </div>
            <div class="sub-history-body">
                <div class="sub-history-grid">
                    <div class="sub-history-item"><span><?php echo $current_lang === 'vi' ? 'Tình trạng' : 'Status'; ?></span><strong><?php echo htmlspecialchars($status_label); ?></strong></div>
                    <div class="sub-history-item"><span><?php echo $current_lang === 'vi' ? 'Đã thanh toán' : 'Amount paid'; ?></span><strong><?php echo number_format((float)$sub['price_paid'], 0, ',', '.'); ?> VND</strong></div>
                    <div class="sub-history-item"><span><?php echo $current_lang === 'vi' ? 'Ngày bắt đầu' : 'Start date'; ?></span><strong><?php echo date('d/m/Y', strtotime($sub['start_date'])); ?></strong></div>
                    <div class="sub-history-item"><span><?php echo $current_lang === 'vi' ? 'Ngày kết thúc' : 'End date'; ?></span><strong><?php echo date('d/m/Y', strtotime($sub['end_date'])); ?></strong></div>
                    <div class="sub-history-item"><span><?php echo $current_lang === 'vi' ? 'Phương thức thanh toán' : 'Payment method'; ?></span><strong><?php echo htmlspecialchars($payment_label); ?></strong></div>
                    <div class="sub-history-item"><span>PT 1:1</span><strong><?php echo (int)($sub['pt_sessions_used'] ?? 0); ?> / <?php echo (int)($sub['pt_sessions_total'] ?? 0); ?> <?php echo $current_lang === 'vi' ? 'buổi đã dùng' : 'sessions used'; ?></strong></div>
                </div>
                <h4 style="margin:0 0 1rem;color:#1a2744;font-size:1rem;"><i class="fas fa-history" style="color:#c9a84c;margin-right:6px;"></i><?php echo $current_lang === 'vi' ? 'Lịch sử trạng thái' : 'Status history'; ?></h4>
                <ul class="sub-history-timeline">
                    <li><strong><?php echo $current_lang === 'vi' ? 'Đã tạo yêu cầu' : 'Request created'; ?></strong><br><?php echo !empty($sub['created_at']) ? date('d/m/Y H:i', strtotime($sub['created_at'])) : '—'; ?></li>
                    <li><strong><?php echo htmlspecialchars($status_label); ?></strong><br><?php echo $current_lang === 'vi' ? 'Thời hạn gói: ' : 'Plan validity: '; ?><?php echo date('d/m/Y', strtotime($sub['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($sub['end_date'])); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div id="membership-history-overview" class="sub-history-modal" onclick="if(event.target===this) this.style.display='none'">
        <div class="sub-history-modal-box" role="dialog" aria-modal="true" aria-label="<?php echo $current_lang === 'vi' ? 'Lịch sử thẻ thành viên' : 'Membership history'; ?>">
            <div class="sub-history-head">
                <div>
                    <div style="font-size:0.72rem;font-weight:800;letter-spacing:0.09em;text-transform:uppercase;color:#c9a84c;margin-bottom:5px;"><?php echo $current_lang === 'vi' ? 'TỔNG HỢP TÀI KHOẢN' : 'ACCOUNT OVERVIEW'; ?></div>
                    <h3 style="margin:0;font-family:'Playfair Display','DM Serif Display',serif;font-size:1.35rem;line-height:1.3;"><?php echo $current_lang === 'vi' ? 'Lịch Sử Thẻ Thành Viên' : 'Membership History'; ?></h3>
                </div>
                <button type="button" class="sub-history-close" aria-label="<?php echo $current_lang === 'vi' ? 'Đóng' : 'Close'; ?>" onclick="document.getElementById('membership-history-overview').style.display='none'">&times;</button>
            </div>
            <div class="sub-history-body">
                <?php foreach ($my_subs as $history_sub):
                    $history_status_labels = ['pending'=>'Chờ xác nhận','active'=>'Đang hoạt động','expired'=>'Hết hạn','cancelled'=>'Đã hủy'];
                    $history_status_en = ['pending'=>'Pending','active'=>'Active','expired'=>'Expired','cancelled'=>'Cancelled'];
                    $history_status = $current_lang === 'vi' ? ($history_status_labels[$history_sub['status']] ?? $history_sub['status']) : ($history_status_en[$history_sub['status']] ?? $history_sub['status']);
                ?>
                    <div style="border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:0.85rem;background:#fff;">
                        <div style="display:flex;justify-content:space-between;gap:0.8rem;align-items:flex-start;flex-wrap:wrap;">
                            <div>
                                <strong style="display:block;color:#1a2744;font-size:1rem;margin-bottom:4px;"><?php echo htmlspecialchars($current_lang === 'vi' ? ($history_sub['name_vi'] ?? $history_sub['name']) : $history_sub['name']); ?></strong>
                                <span style="color:#64748b;font-size:0.83rem;"><?php echo date('d/m/Y', strtotime($history_sub['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($history_sub['end_date'])); ?></span>
                            </div>
                            <span class="sub-status <?php echo htmlspecialchars($history_sub['status']); ?>"><?php echo htmlspecialchars($history_status); ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:0.8rem;flex-wrap:wrap;margin-top:0.75rem;padding-top:0.75rem;border-top:1px dashed #e2e8f0;color:#475569;font-size:0.84rem;">
                            <span><i class="fas fa-wallet" style="color:#c9a84c;margin-right:5px;"></i><?php echo number_format((float)$history_sub['price_paid'], 0, ',', '.'); ?> VND</span>
                            <span><i class="far fa-calendar-alt" style="color:#2563eb;margin-right:5px;"></i><?php echo $current_lang === 'vi' ? 'Tạo: ' : 'Created: '; ?><?php echo !empty($history_sub['created_at']) ? date('d/m/Y', strtotime($history_sub['created_at'])) : '—'; ?></span>
                        </div>
                        <?php if ($history_sub['status'] === 'active'): ?>
                            <div style="margin-top:0.7rem;color:#047857;font-size:0.84rem;font-weight:800;">
                                <i class="fas fa-check-circle"></i>
                                <?php $history_days_left = max(0, (int)ceil((strtotime($history_sub['end_date']) - time()) / 86400)); ?>
                                <?php echo $current_lang === 'vi' ? 'Còn ' . $history_days_left . ' ngày sử dụng' : $history_days_left . ' days remaining'; ?>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:0.85rem;">
                            <?php if (in_array($history_sub['status'], ['active', 'expired'])): ?>
                                <button type="button" class="btn-renew-fast" onclick="openRenewalForPlan(<?php echo (int)$history_sub['plan_id']; ?>)" style="font-size:0.78rem;">
                                    <i class="fas fa-bolt"></i> <?php echo $current_lang === 'vi' ? 'Gia Hạn' : 'Renew'; ?>
                                </button>
                            <?php endif; ?>
                            <?php if (in_array($history_sub['status'], ['active', 'pending', 'expired'])): ?>
                                <button type="button" onclick="cancelAndRefund(<?php echo (int)$history_sub['id']; ?>, '<?php echo addslashes(htmlspecialchars($current_lang === 'vi' ? ($history_sub['name_vi'] ?? $history_sub['name']) : $history_sub['name'], ENT_QUOTES)); ?>')" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:6px 12px;border-radius:10px;font-size:0.78rem;font-weight:800;cursor:pointer;">
                                    <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'vi' ? 'Xóa' : 'Delete'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Loyalty Member Privilege Hero Banner (for active & returning members) -->
<?php if ($is_loyal): ?>
<div style="padding: 0 2rem;">
    <div class="loyalty-hero-banner">
        <div class="loyalty-badge-chip">
            <i class="fas fa-crown"></i> <?php echo htmlspecialchars($loyalty_info['tier_badge']); ?> • <?php echo $current_lang === 'vi' ? $loyalty_info['tier_name_vi'] : $loyalty_info['tier_name_en']; ?>
        </div>
        <h2 style="font-family:'Playfair Display','DM Serif Display',serif; font-size:clamp(1.6rem, 3.5vw, 2.2rem); font-weight:800; margin:0 0 0.6rem; color:#fff;">
            <?php echo __('loyalty_banner_title'); ?>
        </h2>
        <p style="color:rgba(255,255,255,0.85); font-size:1rem; margin:0; max-width:850px; line-height:1.6;">
            <?php echo __('loyalty_banner_desc'); ?>
        </p>

        <div class="loyalty-perks-grid">
            <div class="loyalty-perk-box">
                <div class="perk-icon"><i class="fas fa-percentage"></i></div>
                <div class="perk-text">
                    <strong><?php echo __('loyalty_discount_title'); ?></strong>
                    <span><?php echo __('loyalty_discount_desc'); ?></span>
                </div>
            </div>
            <div class="loyalty-perk-box">
                <div class="perk-icon"><i class="fas fa-calendar-plus"></i></div>
                <div class="perk-text">
                    <strong><?php echo __('loyalty_bonus_days_title'); ?></strong>
                    <span><?php echo __('loyalty_bonus_days_desc'); ?></span>
                </div>
            </div>
            <div class="loyalty-perk-box">
                <div class="perk-icon"><i class="fas fa-dumbbell"></i></div>
                <div class="perk-text">
                    <strong><?php echo __('loyalty_pt_title'); ?></strong>
                    <span><?php echo __('loyalty_pt_desc'); ?></span>
                </div>
            </div>
            <div class="loyalty-perk-box">
                <div class="perk-icon"><i class="fas fa-gift"></i></div>
                <div class="perk-text">
                    <strong><?php echo __('loyalty_gifts_title'); ?></strong>
                    <span><?php echo __('loyalty_gifts_desc'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div style="padding: 0 2rem;">
    <div class="new-member-teaser-box">
        <div style="width:44px; height:44px; background:#fef3c7; color:#d97706; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
            <i class="fas fa-award"></i>
        </div>
        <div style="font-size:0.95rem; line-height:1.5; color:#1e293b;">
            <?php echo __('new_member_teaser_text'); ?>
        </div>
    </div>
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

    <!-- Category Filter Tabs -->
    <div class="plans-filter-tabs">
        <button type="button" class="plan-cat-tab active" data-cat="all" onclick="filterPlansByCategory('all')">
            <i class="fas fa-th-large"></i> <?php echo $current_lang === 'vi' ? 'Tất Cả Gói' : 'All Plans'; ?>
        </button>
        <button type="button" class="plan-cat-tab" data-cat="starter" onclick="filterPlansByCategory('starter')">
            <i class="fas fa-spa"></i> <?php echo $current_lang === 'vi' ? 'Cơ Bản & Yoga' : 'Starter & Yoga'; ?>
        </button>
        <button type="button" class="plan-cat-tab" data-cat="pro" onclick="filterPlansByCategory('pro')">
            <i class="fas fa-dumbbell"></i> <?php echo $current_lang === 'vi' ? 'Chuyên Sâu & PT' : 'Pro & PT Coaching'; ?>
        </button>
        <button type="button" class="plan-cat-tab" data-cat="vip" onclick="filterPlansByCategory('vip')">
            <i class="fas fa-crown"></i> <?php echo $current_lang === 'vi' ? 'VIP Cả Năm' : 'VIP Annual'; ?>
        </button>
    </div>

    <div class="plans-grid">
        <?php foreach($plans as $plan):
            $features = explode('|', $current_lang === 'vi' ? ($plan['features_vi'] ?? $plan['features']) : $plan['features']);
            $badge_color = $plan['badge_color'] ?? '#2563eb';
            $perks = getPlanLoyaltyPerks($plan, $loyalty_info);
            $has_perks = !empty($perks['is_eligible']);
            $p_name = htmlspecialchars($current_lang === 'vi' ? ($plan['name_vi'] ?? $plan['name']) : $plan['name'], ENT_QUOTES);

            $p_name_lower = strtolower($plan['name']);
            $plan_category = 'starter';
            if ($plan['duration_days'] >= 365) {
                $plan_category = 'vip';
            } elseif (strpos($p_name_lower, 'personal') !== false || strpos($p_name_lower, 'pt') !== false || strpos($p_name_lower, 'fat loss') !== false || strpos($p_name_lower, 'kickboxing') !== false) {
                $plan_category = 'pro';
            } else {
                $plan_category = 'starter';
            }

            $plan_sheet_data = [
                'id'              => (int)$plan['id'],
                'code'            => 'GYMX-MP-0' . $plan['id'],
                'name'            => ($current_lang === 'vi' ? ($plan['name_vi'] ?? $plan['name']) : $plan['name']),
                'badge'           => $plan['badge'] ?? 'STANDARD',
                'badge_color'     => $badge_color,
                'description'     => ($current_lang === 'vi' ? ($plan['description_vi'] ?? $plan['description']) : $plan['description']),
                'price'           => (float)$plan['price'],
                'price_formatted' => number_format($plan['price'], 0, ',', '.') . 'đ',
                'duration_days'   => (int)$plan['duration_days'],
                'pt_sessions'     => (int)($plan['pt_sessions'] ?? 0),
                'features'        => $features,
                'has_perks'       => $has_perks,
                'perks'           => $perks,
                'is_popular'      => (int)$plan['is_popular'],
                'can_buy'         => isset($_SESSION['user_id']),
            ];
        ?>
        <div class="plan-card <?php echo $has_perks ? 'loyalty-active' : ''; ?> <?php echo $plan['is_popular'] ? 'popular' : ''; ?>" data-category="<?php echo $plan_category; ?>">
            <?php if ($has_perks): ?>
            <div class="loyalty-ribbon">
                <i class="fas fa-crown"></i> <?php echo __('renewal_discount_tag'); ?> -<?php echo $perks['discount_percent']; ?>%
            </div>
            <?php elseif ($plan['is_popular']): ?>
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
                <?php if ($has_perks): ?>
                    <div class="plan-price-orig">
                        <del><?php echo number_format($plan['price'], 0, ',', '.'); ?>đ</del>
                    </div>
                    <div class="plan-price" style="color: #b45309;">
                        <?php echo number_format($perks['final_price'], 0, ',', '.'); ?>đ
                        <span>/<?php echo $plan['duration_days'] >= 365 ? ($current_lang === 'vi' ? 'năm' : 'year') : ($current_lang === 'vi' ? 'tháng' : 'month'); ?></span>
                    </div>
                    <div>
                        <span class="plan-savings-pill">
                            <i class="fas fa-piggy-bank"></i> <?php echo __('save_amount'); ?> <?php echo number_format($perks['discount_amount'], 0, ',', '.'); ?>đ
                        </span>
                    </div>
                    <div class="plan-duration" style="margin-top: 6px;">
                        <i class="fas fa-calendar-alt" style="color:<?php echo htmlspecialchars($badge_color); ?>;margin-right:5px;"></i>
                        <strong><?php echo $plan['duration_days']; ?> + <?php echo $perks['bonus_days']; ?> <?php echo __('bonus_days_label'); ?></strong>
                        <span style="color:#16a34a; font-weight:700;">(<?php echo $perks['total_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?>)</span>
                    </div>
                <?php else: ?>
                    <div class="plan-price">
                        <?php echo number_format($plan['price'], 0, ',', '.'); ?>đ
                        <span>/<?php echo $plan['duration_days'] >= 365 ? ($current_lang === 'vi' ? 'năm' : 'year') : ($current_lang === 'vi' ? 'tháng' : 'month'); ?></span>
                    </div>
                    <div class="plan-duration">
                        <i class="fas fa-calendar-alt" style="color:<?php echo htmlspecialchars($badge_color); ?>;margin-right:5px;"></i>
                        <?php echo $plan['duration_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Basic Overview Specs (Thông tin cơ bản) -->
            <div class="plan-basic-specs">
                <div class="plan-spec-row">
                    <span class="spec-label"><i class="fas fa-clock" style="color: #2563eb;"></i> <?php echo $current_lang === 'vi' ? 'Thời hạn:' : 'Duration:'; ?></span>
                    <span class="spec-val">
                        <?php if ($has_perks): ?>
                            <strong><?php echo $perks['total_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?></strong> <small style="color:#16a34a;">(+<?php echo $perks['bonus_days']; ?>)</small>
                        <?php else: ?>
                            <strong><?php echo $plan['duration_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?></strong>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="plan-spec-row">
                    <span class="spec-label"><i class="fas fa-user-ninja" style="color: #7c3aed;"></i> <?php echo $current_lang === 'vi' ? 'HLV PT 1:1:' : 'PT Sessions:'; ?></span>
                    <span class="spec-val">
                        <?php
                        $pts = (int)($plan['pt_sessions'] ?? 0);
                        if ($has_perks) {
                            echo "<strong>{$perks['total_pt']} " . ($current_lang === 'vi' ? 'buổi' : 'sessions') . "</strong> <small style='color:#7c3aed;'>(+{$perks['bonus_pt']})</small>";
                        } else {
                            echo $pts > 0 ? "<strong>{$pts} " . ($current_lang === 'vi' ? 'buổi' : 'sessions') . "</strong>" : ($current_lang === 'vi' ? '1 buổi định hướng' : '1 orientation');
                        }
                        ?>
                    </span>
                </div>
                <div class="plan-spec-row">
                    <span class="spec-label"><i class="fas fa-shield-alt" style="color: #059669;"></i> <?php echo $current_lang === 'vi' ? 'Đặc quyền:' : 'Access:'; ?></span>
                    <span class="spec-val">
                        <?php
                        if ($plan['duration_days'] >= 365) {
                            echo $current_lang === 'vi' ? 'VIP All-Access' : 'VIP All-Access';
                        } elseif (stripos($plan['name'], 'Yoga') !== false) {
                            echo $current_lang === 'vi' ? 'Studio Yoga VIP' : 'Yoga Studio';
                        } elseif (stripos($plan['name'], 'Kickboxing') !== false) {
                            echo $current_lang === 'vi' ? 'Lớp Võ & GroupX' : 'Combat & GroupX';
                        } elseif (stripos($plan['name'], 'Fat Loss') !== false) {
                            echo $current_lang === 'vi' ? 'HIIT Chuyên Sâu' : 'HIIT Fat Loss';
                        } elseif (stripos($plan['name'], 'Personal') !== false) {
                            echo $current_lang === 'vi' ? 'HLV Riêng Quốc Tế' : 'Pro PT Coaching';
                        } else {
                            echo $current_lang === 'vi' ? 'Toàn bộ Gym & Cardio' : 'Full Gym & Cardio';
                        }
                        ?>
                    </span>
                </div>
                <div class="plan-highlight-pill">
                    <i class="fas fa-sparkles" style="color:#d97706;margin-right:4px;"></i>
                    <?php echo $current_lang === 'vi' ? 'Miễn phí đo InBody & tủ đồ locker' : 'Free InBody scan & smart locker included'; ?>
                </div>
            </div>

            <!-- Actions (Details button + Buy/Renew button) -->
            <div class="plan-actions">
                <button type="button" class="btn-plan-details" 
                        data-sheet="<?php echo htmlspecialchars(json_encode($plan_sheet_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                        onclick="openPlanDetailsByBtn(this)">
                    <i class="fas fa-file-invoice"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?>
                </button>

                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="/Gym Manager/auth/login.php" class="btn-buy <?php echo $plan['is_popular'] ? 'primary' : 'outline'; ?>">
                    <i class="fas fa-lock" style="margin-right:6px;"></i>
                    <?php echo $current_lang === 'vi' ? 'Đăng nhập' : 'Login'; ?>
                </a>
                <?php elseif ($has_perks): 
                    $p_fmt    = number_format($perks['final_price'], 0, ',', '.') . 'đ';
                    $orig_fmt = number_format($plan['price'], 0, ',', '.') . 'đ';
                    // Short, two-button card layout: keep the renewal action clear without overflowing the plan card.
                    $btn_txt = $perks['is_same_plan_renewal']
                        ? ($current_lang === 'vi' ? 'Gia Hạn + Ưu Đãi' : 'Renew + Perks')
                        : ($current_lang === 'vi' ? 'Đổi Gói + Ưu Đãi' : 'Switch Plan + Perks');
                ?>
                <button class="btn-buy primary"
                        style="background: linear-gradient(135deg, #b45309 0%, #d97706 100%); box-shadow: 0 4px 18px rgba(217,119,6,0.35);"
                        data-plan-id="<?php echo $plan['id']; ?>"
                        data-plan-name="<?php echo $p_name; ?>"
                        data-plan-price="<?php echo $perks['final_price']; ?>"
                        data-orig-price="<?php echo $plan['price']; ?>"
                        data-orig-formatted="<?php echo $orig_fmt; ?>"
                        data-discount-amount="<?php echo $perks['discount_amount']; ?>"
                        data-discount-percent="<?php echo $perks['discount_percent']; ?>"
                        data-bonus-days="<?php echo $perks['bonus_days']; ?>"
                        data-bonus-pt="<?php echo $perks['bonus_pt']; ?>"
                        data-total-days="<?php echo $perks['total_days']; ?>"
                        data-total-pt="<?php echo $perks['total_pt']; ?>"
                        data-is-renewal="1"
                        data-renewal-type="<?php echo $perks['renewal_type']; ?>"
                        data-start-date="<?php echo date('d/m/Y', strtotime($perks['start_date'])); ?>"
                        data-end-date="<?php echo date('d/m/Y', strtotime($perks['end_date'])); ?>"
                        data-plan-formatted="<?php echo $p_fmt; ?>"
                        onclick="handleBuyClick(this)">
                    <i class="fas <?php echo $perks['is_same_plan_renewal'] ? 'fa-bolt' : 'fa-crown'; ?>" style="margin-right:6px;"></i>
                    <?php echo $btn_txt; ?>
                </button>
                <?php else: 
                    $p_fmt = number_format($plan['price'], 0, ',', '.') . 'đ';
                ?>
                <button class="btn-buy <?php echo $plan['is_popular'] ? 'primary' : 'outline'; ?>"
                        data-plan-id="<?php echo $plan['id']; ?>"
                        data-plan-name="<?php echo $p_name; ?>"
                        data-plan-price="<?php echo $plan['price']; ?>"
                        data-orig-price="<?php echo $plan['price']; ?>"
                        data-discount-amount="0"
                        data-discount-percent="0"
                        data-bonus-days="0"
                        data-bonus-pt="0"
                        data-total-days="<?php echo $plan['duration_days']; ?>"
                        data-total-pt="<?php echo (int)($plan['pt_sessions'] ?? 0); ?>"
                        data-is-renewal="0"
                        data-plan-formatted="<?php echo $p_fmt; ?>"
                        onclick="handleBuyClick(this)">
                    <i class="fas fa-credit-card" style="margin-right:6px;"></i>
                    <?php echo $current_lang === 'vi' ? 'Mua Thẻ' : 'Get Plan'; ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Plan Details Modal (Clean, Simple, Tabbed) -->
<div class="mem-modal-overlay" id="planDetailsModal" style="display:none;">
    <div class="plan-details-modal-box">
        <!-- Header -->
        <div class="plan-details-modal-header">
            <div class="header-top-row">
                <span class="plan-details-badge" id="sheetPlanBadge">STARTER</span>
                <button type="button" class="plan-details-modal-close" onclick="closePlanDetailsModal()" title="<?php echo $current_lang === 'vi' ? 'Đóng' : 'Close'; ?>">&times;</button>
            </div>
            <div class="plan-details-title-row">
                <div>
                    <h3 class="plan-details-title" id="sheetPlanName">Basic Gym Package</h3>
                    <p class="plan-details-desc" id="sheetPlanDesc">Gói tập Gym cơ bản linh hoạt theo tháng cho mọi đối tượng.</p>
                </div>
                <div class="plan-details-price-badge" id="sheetPriceCard">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>

        <!-- 3 Simple Tabs -->
        <div class="plan-details-tabs-bar">
            <button type="button" class="plan-dtab-btn active" id="btnPlanTabOverview" onclick="switchPlanTab('overview')">
                <i class="fas fa-info-circle"></i> <?php echo $current_lang === 'vi' ? 'Tổng Quan' : 'Overview'; ?>
            </button>
            <button type="button" class="plan-dtab-btn" id="btnPlanTabPerks" onclick="switchPlanTab('perks')">
                <i class="fas fa-gift"></i> <?php echo $current_lang === 'vi' ? 'Quyền Lợi' : 'Privileges'; ?>
            </button>
            <button type="button" class="plan-dtab-btn" id="btnPlanTabPolicies" onclick="switchPlanTab('policies')">
                <i class="fas fa-shield-alt"></i> <?php echo $current_lang === 'vi' ? 'Chính Sách' : 'Policies'; ?>
            </button>
        </div>

        <!-- Body with 3 Tab Panes (Uncluttered, Simple) -->
        <div class="plan-details-body">
            <!-- TAB 1: OVERVIEW (Exactly 4 clear cards) -->
            <div class="plan-dtab-pane active" id="planTab-overview">
                <div class="plan-overview-grid">
                    <div class="plan-overview-card">
                        <div class="plan-oc-label">
                            <i class="fas fa-calendar-check" style="color:#2563eb;"></i>
                            <?php echo $current_lang === 'vi' ? 'Thời Hạn' : 'Duration'; ?>
                        </div>
                        <div class="plan-oc-val" id="sheetDurationVal">30 Ngày</div>
                        <div class="plan-oc-sub" id="sheetDurationNote"><?php echo $current_lang === 'vi' ? 'Check-in tự do 05:30 – 22:00' : 'Open hours 05:30 – 22:00 daily'; ?></div>
                    </div>

                    <div class="plan-overview-card">
                        <div class="plan-oc-label">
                            <i class="fas fa-user-ninja" style="color:#7c3aed;"></i>
                            <?php echo $current_lang === 'vi' ? 'HLV Cá Nhân (PT)' : '1:1 Coaching'; ?>
                        </div>
                        <div class="plan-oc-val" id="sheetPtVal">1 Buổi Định Hướng</div>
                        <div class="plan-oc-sub" id="sheetPtNote"><?php echo $current_lang === 'vi' ? 'Đo InBody & hướng dẫn bài tập' : 'Fitness assessment & onboarding'; ?></div>
                    </div>

                    <div class="plan-overview-card">
                        <div class="plan-oc-label">
                            <i class="fas fa-dumbbell" style="color:#059669;"></i>
                            <?php echo $current_lang === 'vi' ? 'Khu Vực Tập' : 'Discipline Access'; ?>
                        </div>
                        <div class="plan-oc-val" id="sheetAccessVal">Full Gym & Cardio</div>
                        <div class="plan-oc-sub" id="sheetAccessNote"><?php echo $current_lang === 'vi' ? 'Dàn máy Technogym & tạ tự do' : 'Pro machines & free weights'; ?></div>
                    </div>

                    <div class="plan-overview-card">
                        <div class="plan-oc-label">
                            <i class="fas fa-concierge-bell" style="color:#d97706;"></i>
                            <?php echo $current_lang === 'vi' ? 'Tiện Ích Đi Kèm' : 'Amenities Included'; ?>
                        </div>
                        <div class="plan-oc-val" id="sheetAmenityVal"><?php echo $current_lang === 'vi' ? 'Miễn Phí 100%' : '100% Free'; ?></div>
                        <div class="plan-oc-sub" id="sheetAmenityNote"><?php echo $current_lang === 'vi' ? 'Tủ locker, tắm nóng lạnh, Wi-Fi' : 'Smart locker, hot shower, Wi-Fi'; ?></div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PRIVILEGES (Simple focused list) -->
            <div class="plan-dtab-pane" id="planTab-perks">
                <div class="plan-perks-list" id="sheetFeaturesList">
                    <!-- Populated dynamically -->
                </div>
                <!-- Loyalty perks highlight if eligible -->
                <div id="sheetLoyaltyPerksBox" class="plan-loyalty-highlight-box" style="display:none;"></div>
            </div>

            <!-- TAB 3: POLICIES (Simple 3 rules) -->
            <div class="plan-dtab-pane" id="planTab-policies">
                <div class="plan-policies-list">
                    <div class="plan-policy-box">
                        <div class="plan-policy-icon"><i class="fas fa-qrcode"></i></div>
                        <div>
                            <div class="plan-policy-title"><?php echo $current_lang === 'vi' ? 'Check-in Điện Tử Thông Minh' : 'Smart Digital Check-in'; ?></div>
                            <p class="plan-policy-desc"><?php echo $current_lang === 'vi' ? 'Quét mã QR tiện lợi qua điện thoại khi đến quầy lễ tân để bắt đầu buổi tập.' : 'Quick QR scan on your smartphone upon arrival at front desk.'; ?></p>
                        </div>
                    </div>

                    <div class="plan-policy-box">
                        <div class="plan-policy-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <div class="plan-policy-title"><?php echo $current_lang === 'vi' ? 'Bảo Vệ Quyền Lợi & Bảo Lưu' : 'Satisfaction & Freeze Guarantee'; ?></div>
                            <p class="plan-policy-desc"><?php echo $current_lang === 'vi' ? 'Hỗ trợ bảo lưu ngày tập khi bận công tác hoặc đổi gói trong 7 ngày đầu sử dụng.' : 'Flexible membership pause and 7-day initial guarantee for total peace of mind.'; ?></p>
                        </div>
                    </div>

                    <div class="plan-policy-box">
                        <div class="plan-policy-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-lock"></i></div>
                        <div>
                            <div class="plan-policy-title"><?php echo $current_lang === 'vi' ? 'Tiện Nghi & An Toàn Tập Luyện' : 'Safety & Personal Locker'; ?></div>
                            <p class="plan-policy-desc"><?php echo $current_lang === 'vi' ? 'Trang bị tủ locker khóa riêng an toàn mỗi buổi; vui lòng mang giày tập thể thao sạch.' : 'Secure private locker provided each session; clean athletic footwear required.'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer / Action bar -->
        <div class="plan-details-footer">
            <button type="button" class="btn-details-cancel" onclick="closePlanDetailsModal()">
                <?php echo $current_lang === 'vi' ? 'Đóng' : 'Close'; ?>
            </button>
            <button type="button" class="btn-details-action" id="btnSheetBuyNow" onclick="handleBuyFromSheet()">
                <i class="fas fa-credit-card"></i> <?php echo $current_lang === 'vi' ? 'Đăng Ký Gói Này' : 'Register This Plan'; ?>
            </button>
        </div>
    </div>
</div>

<!-- Purchase Modal -->
<div class="mem-modal-overlay" id="memModal">
    <div class="mem-modal-box" style="max-width:600px;">
        <div class="mem-modal-header">
            <h3><i class="fas fa-id-card" style="margin-right:8px;"></i><?php echo $current_lang === 'vi' ? 'Đăng Ký / Gia Hạn Thẻ' : 'Purchase / Renew Membership'; ?></h3>
            <p><?php echo $current_lang === 'vi' ? 'Hoàn tất đăng ký chỉ với vài bước đơn giản' : 'Complete registration in a few simple steps'; ?></p>
            <button class="mem-modal-close" onclick="closeMemberModal()">&times;</button>
        </div>
        <div class="mem-modal-body">
            <!-- Form View -->
            <div id="memFormView">
                <div class="mem-plan-summary">
                    <strong id="modalPlanName"></strong>
                    <span class="price-tag" id="modalPlanPrice"></span>
                </div>

                <!-- Loyalty Perks Box in Modal -->
                <div id="modalLoyaltyBox" style="display:none; background:#fffbeb; border:1.5px solid #fcd34d; border-radius:18px; padding:1.2rem; margin-bottom:1.4rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-weight:800; color:#b45309; font-size:0.92rem; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fas fa-crown"></i> <?php echo __('renewal_perks_summary_title'); ?>
                        </span>
                        <span id="modalDiscountTag" style="background:#b45309; color:#fff; font-weight:800; font-size:0.75rem; padding:3px 10px; border-radius:20px;">-10%</span>
                    </div>
                    <div style="font-size:0.86rem; color:#475569; display:grid; grid-template-columns:1fr 1fr; gap:6px 12px; margin-bottom:8px;">
                        <div><?php echo $current_lang === 'vi' ? 'Giá gốc:' : 'Original:'; ?> <del id="modalOrigPrice" style="color:#94a3b8;"></del></div>
                        <div style="text-align:right;"><?php echo $current_lang === 'vi' ? 'Tiết kiệm:' : 'Save:'; ?> <strong id="modalSavings" style="color:#16a34a;"></strong></div>
                        <div><?php echo $current_lang === 'vi' ? 'Thời hạn:' : 'Duration:'; ?> <strong id="modalBonusDays" style="color:#2563eb;"></strong></div>
                        <div style="text-align:right;"><?php echo $current_lang === 'vi' ? 'Buổi PT 1:1:' : '1:1 PT:'; ?> <strong id="modalBonusPt" style="color:#7c3aed;"></strong></div>
                    </div>
                    <div style="border-top:1px dashed #cbd5e1; padding-top:8px; font-size:0.82rem; color:#64748b; line-height:1.4;">
                        🎁 <?php echo $current_lang === 'vi' ? 'Quà tặng tri ân: <strong>Bình nước GymX Shaker VIP Pro + 2 Guest Pass + Ưu đãi 10% tại Shop</strong>' : 'Loyalty Gifts: <strong>GymX Shaker VIP Pro + 2 Guest Passes + 10% Shop Discount</strong>'; ?>
                    </div>
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
let selectedPlanId    = null;
let selectedPlanPrice = 0;
let currentPerks      = null;
let currentSheetPlan  = null;

function switchPlanTab(tabKey) {
    const validTabs = ['overview', 'perks', 'policies'];
    if (!validTabs.includes(tabKey)) tabKey = 'overview';
    
    document.querySelectorAll('.plan-dtab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.plan-dtab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    const targetBtn = document.getElementById('btnPlanTab' + tabKey.charAt(0).toUpperCase() + tabKey.slice(1));
    const targetPane = document.getElementById('planTab-' + tabKey);
    if (targetBtn) targetBtn.classList.add('active');
    if (targetPane) targetPane.classList.add('active');
}

function filterPlansByCategory(cat) {
    document.querySelectorAll('.plan-cat-tab').forEach(b => {
        b.classList.toggle('active', b.getAttribute('data-cat') === cat);
    });
    document.querySelectorAll('.plan-card').forEach(card => {
        const cardCat = card.getAttribute('data-category');
        if (cat === 'all' || cardCat === cat) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function openPlanDetailsByBtn(btn) {
    const raw = btn.getAttribute('data-sheet');
    if (!raw) return;
    try {
        const data = JSON.parse(raw);
        openPlanDetailsModal(data);
    } catch(e) {
        console.error("JSON parse error:", e);
    }
}

function openPlanDetailsModal(data) {
    currentSheetPlan = data;
    const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'en') === 'vi';
    
    // Always start at Overview tab
    switchPlanTab('overview');

    // Header & Meta
    const badgeEl = document.getElementById('sheetPlanBadge');
    badgeEl.textContent = data.badge || (isVi ? 'TIÊU CHUẨN' : 'STANDARD');
    badgeEl.style.background = data.badge_color || '#2563eb';
    
    document.getElementById('sheetPlanName').textContent = data.name;
    document.getElementById('sheetPlanDesc').textContent = data.description;
    
    // Pricing card
    const priceCard = document.getElementById('sheetPriceCard');
    const cycleText = data.duration_days >= 365 ? (isVi ? 'năm' : 'year') : (isVi ? 'tháng' : 'month');
    
    if (data.has_perks && data.perks) {
        priceCard.innerHTML = `
            <div class="plan-details-price-orig"><del>${data.perks.orig_price_formatted}</del></div>
            <div class="plan-details-price-main" style="color: #fbbf24;">
                ${Number(data.perks.final_price).toLocaleString('vi-VN')}đ
                <span class="plan-details-price-cycle">/${cycleText}</span>
            </div>
            <span class="plan-details-save-pill">
                <i class="fas fa-crown"></i> -${data.perks.discount_percent}% (${isVi ? 'Tiết kiệm' : 'Save'} ${Number(data.perks.discount_amount).toLocaleString('vi-VN')}đ)
            </span>
        `;
    } else {
        priceCard.innerHTML = `
            <div class="plan-details-price-main">
                ${data.price_formatted}
                <span class="plan-details-price-cycle">/${cycleText}</span>
            </div>
            <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">
                ${isVi ? 'Giá chính thức trọn gói' : 'Official rate'}
            </div>
        `;
    }
    
    // Tab 1: Overview 4 Grid (Simple, clean)
    const durVal = document.getElementById('sheetDurationVal');
    const durNote = document.getElementById('sheetDurationNote');
    if (data.has_perks && data.perks) {
        durVal.innerHTML = `${data.perks.total_days} ${isVi ? 'Ngày' : 'Days'} <span style="color:#16a34a;font-size:0.85rem;font-weight:700;">(+${data.perks.bonus_days})</span>`;
        durNote.textContent = isVi ? 'Gia hạn liên tục, cộng dồn ngày tập' : 'Continuous renewal bonus applied';
    } else {
        durVal.textContent = `${data.duration_days} ${isVi ? 'Ngày' : 'Days'}`;
        durNote.textContent = isVi ? 'Check-in tự do 05:30 – 22:00 hàng ngày' : 'Unlimited check-in 05:30 – 22:00 daily';
    }
    
    const ptVal = document.getElementById('sheetPtVal');
    const ptNote = document.getElementById('sheetPtNote');
    if (data.has_perks && data.perks) {
        ptVal.innerHTML = `${data.perks.total_pt} ${isVi ? 'Buổi 1:1' : 'Sessions 1:1'} <span style="color:#7c3aed;font-size:0.85rem;font-weight:700;">(+${data.perks.bonus_pt})</span>`;
        ptNote.textContent = isVi ? 'HLV kèm riêng định hướng bài tập' : 'Personal trainer coaching included';
    } else if (data.pt_sessions > 0) {
        ptVal.textContent = `${data.pt_sessions} ${isVi ? 'Buổi Tập 1:1' : '1:1 Sessions'}`;
        ptNote.textContent = isVi ? 'Huấn luyện viên riêng kèm sát từng buổi' : 'Certified trainer direct 1-on-1 supervision';
    } else {
        ptVal.textContent = isVi ? '1 Buổi Định Hướng 1:1' : '1 Orientation Session';
        ptNote.textContent = isVi ? 'Tư vấn thể trạng & hướng dẫn máy tập' : 'Complimentary posture assessment & onboarding';
    }
    
    const accVal = document.getElementById('sheetAccessVal');
    const accNote = document.getElementById('sheetAccessNote');
    const nameLower = (data.name || '').toLowerCase();
    if (nameLower.includes('yoga')) {
        accVal.textContent = isVi ? 'Studio Yoga & Thiền Định' : 'Yoga & Meditation Studio';
        accNote.textContent = isVi ? 'Thảm tập cao cấp, phòng xông tinh dầu' : 'Premium mat, props & aromatherapy';
    } else if (nameLower.includes('kickboxing') || nameLower.includes('groupx')) {
        accVal.textContent = isVi ? 'Khu Boxing & GroupX' : 'Combat & GroupX Studio';
        accNote.textContent = isVi ? 'Bao cát tiêu chuẩn, găng tập chuyên nghiệp' : 'Heavy bags, pro gloves & group energy';
    } else if (nameLower.includes('annual') || data.duration_days >= 365) {
        accVal.textContent = isVi ? 'Toàn Bộ Hệ Thống (VIP All-Access)' : 'Full Center (VIP All-Access)';
        accNote.textContent = isVi ? 'Gym, Cardio, Yoga, Boxing không giới hạn' : 'Unlimited gym, cardio, yoga & all classes';
    } else if (nameLower.includes('fat loss')) {
        accVal.textContent = isVi ? 'Khu Vực Huấn Luyện Y Khoa' : 'Medical Fat Loss Training';
        accNote.textContent = isVi ? 'Lộ trình HIIT & cố vấn dinh dưỡng riêng' : 'High-intensity burn zone & meal guidance';
    } else {
        accVal.textContent = isVi ? 'Full Gym & Máy Tập Cardio' : 'Full Gym & Cardio Zone';
        accNote.textContent = isVi ? 'Dàn máy Technogym & khu tạ tự do' : 'Technogym machines & free weights zone';
    }
    
    document.getElementById('sheetAmenityVal').textContent = isVi ? 'Miễn Phí 100%' : '100% Free';
    document.getElementById('sheetAmenityNote').textContent = isVi 
        ? 'Tủ locker bảo mật, tắm nóng lạnh, Wi-Fi' 
        : 'Smart locker, hot shower, high-speed Wi-Fi';

    // Tab 2: Perks list (Clean & focused)
    const featList = document.getElementById('sheetFeaturesList');
    featList.innerHTML = '';
    if (Array.isArray(data.features) && data.features.length > 0) {
        data.features.forEach(f => {
            const item = document.createElement('div');
            item.className = 'plan-perk-item';
            item.innerHTML = `<i class="fas fa-check-circle"></i> <span>${f.trim()}</span>`;
            featList.appendChild(item);
        });
    }
    
    // Loyalty highlight box
    const loyaltyBox = document.getElementById('sheetLoyaltyPerksBox');
    if (data.has_perks && data.perks) {
        loyaltyBox.style.display = 'block';
        loyaltyBox.innerHTML = `
            <div class="plan-loyalty-hl-title">
                <i class="fas fa-crown"></i> ${isVi ? 'Đặc quyền Hội viên Thân thiết của bạn:' : 'Your Loyalty Member Perks:'}
            </div>
            <div class="plan-loyalty-hl-grid">
                <div>🎁 <strong>+${data.perks.bonus_days} ${isVi ? 'ngày tập tặng thêm' : 'bonus days free'}</strong></div>
                <div>🥊 <strong>+${data.perks.bonus_pt} ${isVi ? 'buổi PT 1:1 cá nhân hóa' : 'bonus 1:1 PT session'}</strong></div>
                <div>🍶 <strong>${isVi ? 'Bình Shaker VIP + 2 Guest Pass' : 'VIP Shaker + 2 Guest Passes'}</strong></div>
                <div>🏷️ <strong>${isVi ? 'Giảm 10% tại Cửa hàng GymX' : '10% OFF at GymX Shop'}</strong></div>
            </div>
        `;
    } else {
        loyaltyBox.style.display = 'none';
        loyaltyBox.innerHTML = '';
    }
    
    // Action button
    const buyBtn = document.getElementById('btnSheetBuyNow');
    if (!data.can_buy) {
        buyBtn.innerHTML = `<i class="fas fa-lock"></i> ${isVi ? 'Đăng Nhập Để Mua' : 'Login to Purchase'}`;
    } else if (data.has_perks) {
        buyBtn.innerHTML = `<i class="fas fa-crown"></i> ${isVi ? 'Xác Nhận Gia Hạn Ngay' : 'Confirm Renewal Now'}`;
    } else {
        buyBtn.innerHTML = `<i class="fas fa-credit-card"></i> ${isVi ? 'Đăng Ký Gói Này' : 'Register This Plan'}`;
    }
    
    document.getElementById('planDetailsModal').style.display = 'flex';
}

function closePlanDetailsModal() {
    document.getElementById('planDetailsModal').style.display = 'none';
}

function handleBuyFromSheet() {
    if (!currentSheetPlan) return;
    if (!currentSheetPlan.can_buy) {
        window.location.href = '/Gym Manager/auth/login.php';
        return;
    }
    closePlanDetailsModal();
    const btn = document.querySelector(`.btn-buy[data-plan-id="${currentSheetPlan.id}"]`);
    if (btn) {
        handleBuyClick(btn);
    } else {
        window.location.href = `/Gym Manager/pages/membership.php?plan_id=${currentSheetPlan.id}`;
    }
}

function printPlanSheet() {
    window.print();
}

document.getElementById('planDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closePlanDetailsModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePlanDetailsModal();
        closeMemberModal();
    }
});

function toggleMemMomoHint() {
    var sel  = document.getElementById('memPaymentMethod');
    var hint = document.getElementById('memMomoHint');
    if (hint) hint.style.display = (sel && sel.value === 'momo') ? 'block' : 'none';
}

function openRenewalForPlan(planId) {
    const btn = document.querySelector(`.btn-buy[data-plan-id="${planId}"]`);
    if (btn) {
        handleBuyClick(btn);
    } else {
        window.location.href = `/Gym Manager/pages/membership.php?plan_id=${planId}`;
    }
}

function handleBuyClick(btn) {
    const isRenewal = btn.getAttribute('data-is-renewal') === '1';
    const perks = {
        isRenewal: isRenewal,
        origFormatted: btn.getAttribute('data-orig-formatted') || '',
        discountAmount: btn.getAttribute('data-discount-amount') || '0',
        discountPercent: btn.getAttribute('data-discount-percent') || '0',
        bonusDays: btn.getAttribute('data-bonus-days') || '0',
        bonusPt: btn.getAttribute('data-bonus-pt') || '0',
        totalDays: btn.getAttribute('data-total-days') || '30',
        totalPt: btn.getAttribute('data-total-pt') || '0',
        startDate: btn.getAttribute('data-start-date') || '',
        endDate: btn.getAttribute('data-end-date') || '',
    };

    openMemberModal(
        btn.getAttribute('data-plan-id'),
        btn.getAttribute('data-plan-name'),
        parseFloat(btn.getAttribute('data-plan-price')),
        btn.getAttribute('data-plan-formatted'),
        perks
    );
}

function openMemberModal(planId, planName, planPrice, priceFormatted, perks = null) {
    selectedPlanId    = planId;
    selectedPlanPrice = planPrice;
    currentPerks      = perks;

    document.getElementById('memFormView').style.display  = 'block';
    document.getElementById('memSuccessView').style.display = 'none';

    const btn = document.getElementById('btnSubmitMem');
    btn.disabled = false;

    document.getElementById('modalPlanName').textContent  = planName;
    document.getElementById('modalPlanPrice').textContent = priceFormatted;
    document.getElementById('memNote').value = '';

    const loyaltyBox = document.getElementById('modalLoyaltyBox');
    if (perks && perks.isRenewal) {
        if (loyaltyBox) loyaltyBox.style.display = 'block';
        document.getElementById('modalDiscountTag').textContent = `-${perks.discountPercent}%`;
        document.getElementById('modalOrigPrice').textContent = perks.origFormatted;
        document.getElementById('modalSavings').textContent = `-${Number(perks.discountAmount).toLocaleString('vi-VN')}đ`;
        const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'en') === 'vi';
        document.getElementById('modalBonusDays').textContent = `+${perks.bonusDays} ${isVi ? 'ngày' : 'days'} (${perks.totalDays} ${isVi ? 'ngày' : 'days'})`;
        document.getElementById('modalBonusPt').textContent = `${perks.totalPt} ${isVi ? 'buổi' : 'sessions'} (+${perks.bonusPt} ${isVi ? 'tặng' : 'bonus'})`;
        btn.innerHTML = '<i class="fas fa-crown" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Gia Hạn & Nhận Ưu Đãi" : "Confirm Renewal & Claim Perks"; ?>';
    } else {
        if (loyaltyBox) loyaltyBox.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
    }

    const sel = document.getElementById('memPaymentMethod');
    if (sel) sel.value = 'cod';
    toggleMemMomoHint();

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
            } else {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
                const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'en') === 'vi';
                showGymXAlert(data.message || (isVi ? 'Không thể đăng ký.' : 'Registration failed.'), isVi ? 'Lỗi Đăng Ký' : 'Registration Error', '❌');
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i><?php echo $current_lang === "vi" ? "Xác Nhận Đăng Ký" : "Confirm Registration"; ?>';
            const isVi = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG : 'en') === 'vi';
            showGymXAlert(isVi ? 'Lỗi kết nối. Vui lòng thử lại.' : 'Connection error. Please try again.', isVi ? 'Lỗi Kết Nối' : 'Connection Error', '⚠️');
        });
}

// ── Success / Pending view with mandatory Counter Pick-up Notification & Renewal details ──
function showPendingView(data) {
    document.getElementById('memFormView').style.display   = 'none';
    const c = document.getElementById('memSuccessView');
    c.style.display = 'block';

    const isRen = data.is_renewal;
    const isVi  = GYMX_LANG === 'vi';

    c.innerHTML = `
        <div style="text-align:center;padding:1.5rem 0.5rem;animation:modalPopIn .4s ease-out;">
            <div style="width:90px;height:90px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 12px 30px rgba(16,185,129,.35);">
                <i class="fas ${isRen ? 'fa-crown' : 'fa-check-circle'}" style="font-size:2.8rem;color:#fff;"></i>
            </div>
            <h3 style="font-size:1.5rem;font-weight:900;color:#0f172a;margin-bottom:.5rem;">
                🎉 ${isRen ? (isVi ? "Đăng Ký Gia Hạn Thành Công!" : "Renewal Registered Successfully!") : (isVi ? "Đăng Ký Thành Công!" : "Registration Successful!")}
            </h3>
            
            <div style="background:#f0fdf4;border:2px solid #86efac;border-radius:16px;padding:1.25rem 1rem;margin:1.2rem 0;text-align:center;box-shadow:0 4px 15px rgba(16,185,129,0.1);">
                <p style="color:#166534;font-size:1.05rem;font-weight:800;margin:0;line-height:1.5;">
                    <i class="fas fa-store" style="margin-right:8px;font-size:1.2rem;color:#15803d;"></i>
                    ${isVi 
                        ? (isRen ? "Đăng ký gia hạn thành công! Vui lòng đến quầy nhận quà tri ân và hoàn tất kích hoạt thẻ." : "Đăng ký thành công, vui lòng đến quầy giao dịch để nhận thẻ thành viên.") 
                        : (isRen ? "Renewal registered! Please visit counter to claim your gifts and complete activation." : "Registration successful! Please visit the service counter to receive your membership card.")}
                </p>
            </div>

            <p style="color:#64748b;font-size:.9rem;margin-bottom:1.4rem;">
                ${isVi ? "Thẻ của bạn sẽ được kích hoạt tại quầy giao dịch sau khi xác nhận thông tin." : "Your membership card will be activated at the counter upon verification."}
            </p>
            
            <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:1.2rem;margin-bottom:1.5rem;text-align:left;">
                <p style="margin:6px 0;font-size:.9rem;">
                    <strong>💳 ${isVi ? "Gói đăng ký:" : "Registered Package:"}</strong> 
                    <span style="color:#2563eb;font-weight:800;">${data.plan_name || ''}</span>
                    ${isRen ? `<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:800;margin-left:6px;"><i class="fas fa-crown"></i> ${isVi ? "GIA HẠN" : "RENEWAL"}</span>` : ''}
                </p>
                <p style="margin:6px 0;font-size:.9rem;">
                    <strong>💰 ${isVi ? "Số tiền thanh toán:" : "Amount to pay:"}</strong> 
                    <span style="color:#ef4444;font-weight:800;">${data.price}</span>
                    ${data.discount_amount && data.discount_amount !== '0đ' ? `<span style="color:#16a34a;font-size:0.82rem;font-weight:700;margin-left:6px;">(${isVi ? 'Đã tiết kiệm' : 'Saved'} ${data.discount_amount})</span>` : ''}
                </p>
                ${isRen && data.bonus_days ? `
                <p style="margin:6px 0;font-size:.88rem;color:#166534;">
                    <strong>⏳ ${isVi ? "Thời hạn thẻ:" : "Validity:"}</strong> ${data.start_date} → ${data.end_date} 
                    <span style="font-weight:700;">(+${data.bonus_days} ${isVi ? 'ngày tặng miễn phí' : 'bonus days'})</span>
                </p>` : ''}
                ${isRen && data.total_pt ? `
                <p style="margin:6px 0;font-size:.88rem;color:#7c3aed;">
                    <strong>🥊 PT 1:1:</strong> ${data.total_pt} ${isVi ? 'buổi tập cá nhân hóa' : 'sessions'} (+${data.bonus_pt || 1} ${isVi ? 'tặng thêm' : 'bonus'})
                </p>` : ''}
                ${isRen ? `
                <p style="margin:6px 0;font-size:.85rem;color:#b45309;border-top:1px dashed #e2e8f0;padding-top:6px;margin-top:6px;">
                    <strong>🎁 ${isVi ? "Quà VIP nhận tại quầy:" : "VIP Gifts at counter:"}</strong> Bình GymX Shaker VIP Pro + 2 Guest Pass + Voucher 10% Shop
                </p>` : ''}
                <p style="margin:6px 0;font-size:.9rem;">
                    <strong>💵 ${isVi ? "Phương thức:" : "Payment Method:"}</strong> 
                    <span style="color:#059669;font-weight:700;">${isVi ? "Thanh toán trực tiếp tại quầy" : "Pay at counter"}</span>
                </p>
            </div>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                <button onclick="closeMemberModal(); location.reload();" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:.85rem 2rem;border-radius:12px;font-weight:700;cursor:pointer;font-size:.95rem;box-shadow:0 4px 15px rgba(37,99,235,0.3);">
                    <i class="fas fa-check" style="margin-right:8px;"></i>${isVi ? "Hoàn Tất" : "Done"}
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

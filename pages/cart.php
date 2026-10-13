<?php 
// ============================================================
// Shopping Cart & Order Tracking / Purchase History Page
// Tab 1: Shopping Cart (Stands first)
// Tab 2: Order Tracking & History with Inline Product Reviews
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /Gym Manager/dashboards/admin.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

// Handle cart item removal
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: /Gym Manager/pages/cart.php");
    exit();
}

// Handle clear entire cart
if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: /Gym Manager/pages/cart.php");
    exit();
}

$order_msg = '';
$order_msg_type = 'success';

// Handle Product Review Form POST (Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product_review']) && isset($_SESSION['user_id'])) {
    $rev_p_id   = intval($_POST['product_id'] ?? 0);
    $rev_rating = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $rev_comment = trim($_POST['comment'] ?? '');
    $rev_user_id = (int)$_SESSION['user_id'];
    
    if ($rev_p_id > 0 && !empty($rev_comment)) {
        try {
            $chk = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
            $chk->execute([$rev_user_id, $rev_p_id]);
            $existing_rev = $chk->fetch();

            if ($existing_rev) {
                $upd = $pdo->prepare("UPDATE product_reviews SET rating = ?, comment = ? WHERE id = ?");
                $upd->execute([$rev_rating, $rev_comment, $existing_rev['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                $ins->execute([$rev_p_id, $rev_user_id, $rev_rating, $rev_comment]);
            }
            $order_msg = (isset($current_lang) && $current_lang === 'vi') ? '✅ Cảm ơn bạn đã gửi đánh giá sản phẩm!' : '✅ Thank you for reviewing the product!';
            $order_msg_type = 'success';
        } catch (Exception $e) {
            $order_msg = 'Review error: ' . $e->getMessage();
            $order_msg_type = 'error';
        }
    }
}

// Handle Order Confirmation by Customer
if (isset($_POST['action_confirm_order']) && isset($_SESSION['user_id'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check if order exists and is pending
    $stmt_o = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt_o->execute([$order_id, $user_id]);
    $order = $stmt_o->fetch();
    
    if ($order) {
        // Update order status to completed
        $stmt_upd = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt_upd->execute([$order_id]);
        
        $order_msg = __('order_confirmed_msg');
        $order_msg_type = 'success';
        
        // --- Send Email Notification to Managers via PHPMailer ---
        try {
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
            
            $stmt_mgrs = $pdo->query("SELECT email, name FROM users WHERE role = 'manager'");
            $managers = $stmt_mgrs->fetchAll();
            
            if (!empty($managers)) {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Order System');
                
                $has_mgr = false;
                foreach ($managers as $mgr) {
                    if (!empty($mgr['email'])) {
                        $mail->addAddress($mgr['email'], $mgr['name']);
                        $has_mgr = true;
                    }
                }
                
                if ($has_mgr) {
                    $mail->isHTML(true);
                    $mail->Subject = "✅ Customer Confirmed Order #" . $order_id . " Delivery Successful";
                    
                    // Fetch items
                    $stmt_items = $pdo->prepare("SELECT oi.*, p.name, p.name_vi FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $o_items = $stmt_items->fetchAll();
                    
                    $u_name = $_SESSION['user_name'] ?? 'Customer';
                    $u_email = $_SESSION['user_email'] ?? '';
                    
                    $m_body = "<div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
                    $m_body .= "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 15px; text-align: center;'>";
                    $m_body .= "<h2 style='margin: 0; font-size: 1.5rem;'>Order #" . $order_id . " Received & Confirmed</h2>";
                    $m_body .= "<p style='margin: 5px 0 0; opacity: 0.95;'>The customer has confirmed successful delivery.</p></div>";
                    
                    $m_body .= "<div style='background: #f8fafc; padding: 18px; border-radius: 15px; margin: 20px 0; border: 1px solid #e2e8f0;'>";
                    $m_body .= "<strong>Customer Name:</strong> " . htmlspecialchars($u_name) . " (" . htmlspecialchars($u_email) . ")<br>";
                    $m_body .= "<strong>Order ID:</strong> #" . $order_id . "<br>";
                    $m_body .= "<strong>Total Revenue:</strong> $" . number_format($order['total_price'], 2) . "<br>";
                    $m_body .= "<strong>Payment Method:</strong> " . strtoupper($order['payment_method']) . "<br>";
                    $m_body .= "<strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>COMPLETED</span><br>";
                    $m_body .= "</div>";
                    
                    $m_body .= "<h4>Purchased Products:</h4><ul>";
                    foreach ($o_items as $it) {
                        $m_body .= "<li>" . htmlspecialchars($it['name']) . (!empty($it['variant']) ? " (" . htmlspecialchars($it['variant']) . ")" : "") . " x" . $it['quantity'] . " - $" . number_format($it['price'] * $it['quantity'], 2) . "</li>";
                    }
                    $m_body .= "</ul>";
                    
                    $m_body .= "<div style='background: #eff6ff; padding: 15px; border-radius: 12px; border-left: 4px solid #0062ff; margin-top: 25px;'>";
                    $m_body .= "<strong>Manager Action Required:</strong> You can now view and update business reports for this sale.<br><br>";
                    $m_body .= "<a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=yearly_report' style='background: #0062ff; color: white; padding: 10px 18px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block;'>View Yearly Business Report</a>";
                    $m_body .= "</div>";
                    
                    $m_body .= "</div>";
                    
                    $mail->Body = $m_body;
                    $mail->send();
                }
            }
        } catch (Exception $mailEx) {
            error_log("Order Confirm Mail Error: " . $mailEx->getMessage());
        }
    }
}

// Handle Order Cancellation by Customer
if (isset($_POST['action_cancel_order']) && isset($_SESSION['user_id'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Check if order exists and is pending
    $stmt_o = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt_o->execute([$order_id, $user_id]);
    $order = $stmt_o->fetch();
    
    if ($order) {
        try {
            $pdo->beginTransaction();
            // Update order status to cancelled
            $stmt_upd = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt_upd->execute([$order_id]);
            
            // Restore stock
            $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$order_id]);
            $o_items = $stmt_items->fetchAll();
            
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ?, sales_count = GREATEST(0, sales_count - ?) WHERE id = ?");
            foreach ($o_items as $item) {
                $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            $order_msg = __('order_cancelled_msg');
            $order_msg_type = 'info';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $order_msg = __('order_cancel_error') . ": " . $e->getMessage();
            $order_msg_type = 'error';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';

// --- Process Cart Items ---
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $base_ids = array_unique(array_map(function($k) { return explode('_', $k)[0]; }, $ids));
    
    $placeholders = str_repeat('?,', count($base_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_values($base_ids));
    
    $db_products = [];
    foreach ($stmt->fetchAll() as $p) {
        $db_products[$p['id']] = $p;
    }

    foreach ($ids as $cart_key) {
        $parts = explode('_', $cart_key);
        $p_id = $parts[0];
        $weight = isset($parts[1]) ? $parts[1] : '';
        
        if (!isset($db_products[$p_id])) continue;
        $p = $db_products[$p_id];
        
        $price = $p['price'];
        $name_suffix = '';
        if ($weight === '1kg') { $price += 10; $name_suffix = ' (1 kg)'; }
        elseif ($weight === '2kg') { $price += 25; $name_suffix = ' (2 kg)'; }
        elseif ($weight === '5kg') { $price += 60; $name_suffix = ' (5 kg)'; }
        elseif ($weight === '500g') { $name_suffix = ' (500g)'; }

        $qty = $_SESSION['cart'][$cart_key];
        $subtotal = $price * $qty;
        $total += $subtotal;
        
        $p_name = (isset($current_lang) && $current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name'];
        
        $cart_items[] = [
            'cart_key' => $cart_key,
            'id' => $p['id'],
            'name' => $p_name . $name_suffix,
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $subtotal,
            'image' => $p['image']
        ];
    }
}

// --- Member Loyalty Discount Calculation ---
require_once __DIR__ . '/../includes/membership_loyalty.php';
$user_loyalty = null;
$is_loyal_member = false;
$member_discount_rate = 0.0;
$member_discount = 0.0;

if (isset($_SESSION['user_id'])) {
    $user_loyalty = getUserMembershipLoyaltyInfo($pdo, (int)$_SESSION['user_id']);
    if (!empty($user_loyalty['eligible_for_renewal_perks'])) {
        $is_loyal_member = true;
        $member_discount_rate = 0.10;
        $member_discount = round($total * $member_discount_rate, 2);
    }
}
$final_total = max(0, $total - $member_discount);
$final_total_vnd = (int)round($final_total * 25000);

// --- Fetch User Orders & Reviews ---
$user_orders = [];
$user_reviews_map = []; // [product_id => review_data]

if (isset($_SESSION['user_id'])) {
    $u_id = (int)$_SESSION['user_id'];
    $stmt_u_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt_u_orders->execute([$u_id]);
    $user_orders = $stmt_u_orders->fetchAll();

    // Fetch existing user reviews
    $stmt_revs = $pdo->prepare("SELECT product_id, rating, comment, created_at FROM product_reviews WHERE user_id = ?");
    $stmt_revs->execute([$u_id]);
    foreach ($stmt_revs->fetchAll() as $r) {
        $user_reviews_map[$r['product_id']] = $r;
    }
}

$is_vi = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi');
$pending_count   = count(array_filter($user_orders, fn($o) => $o['status'] === 'pending'));
$completed_count = count(array_filter($user_orders, fn($o) => $o['status'] === 'completed'));
$cancelled_count = count(array_filter($user_orders, fn($o) => $o['status'] === 'cancelled'));
?>

<style>
/* ── Cart & Tracking Tabs ── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.cart-page-section {
    padding: 3rem 0;
    min-height: 85vh;
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
}
.cart-page-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Tab Header / Segmented Controller */
.cart-tabs-nav {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin: 0 auto 2.5rem;
    background: #ffffff;
    padding: 8px;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    border: 1px solid #e2e8f0;
    max-width: fit-content;
}
.cart-tab-btn {
    border: none;
    background: transparent;
    padding: 12px 28px;
    border-radius: 18px;
    font-size: 0.96rem;
    font-weight: 800;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
}
.cart-tab-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.cart-tab-btn.active {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
}
.cart-tab-btn .tab-badge {
    background: #e2e8f0;
    color: #334155;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 0.76rem;
    font-weight: 800;
    transition: all 0.25s;
}
.cart-tab-btn.active .tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

/* Tab Contents */
.cart-tab-pane {
    display: none;
    animation: fadeInTab 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.cart-tab-pane.active {
    display: block;
}
@keyframes fadeInTab {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Order Sub-Filter Buttons */
.order-sub-filter-row {
    display: flex;
    gap: 8px;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    align-items: center;
}
.order-sub-filter-btn {
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.86rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.order-sub-filter-btn:hover {
    border-color: #0f172a;
    color: #0f172a;
}
.order-sub-filter-btn.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
}

/* Stepper Animation */
@keyframes pulseStep {
    0%, 100% { transform: scale(1); box-shadow: 0 0 15px rgba(59,130,246,0.6); }
    50% { transform: scale(1.08); box-shadow: 0 0 25px rgba(59,130,246,0.9); }
}

/* Star Rating Picker */
.star-rating-box {
    display: flex;
    gap: 6px;
    font-size: 1.4rem;
    cursor: pointer;
    user-select: none;
}
.star-rating-box .star {
    color: #cbd5e1;
    transition: color 0.15s, transform 0.15s;
}
.star-rating-box .star:hover,
.star-rating-box .star.active {
    color: #f59e0b;
    transform: scale(1.15);
}
</style>

<section class="cart-page-section">
    <div class="cart-page-container">

        <!-- Title -->
        <div class="section-title" style="text-align: center; margin-bottom: 2rem;">
            <h2 style="font-size: 2.2rem; font-weight: 900; color: #0f172a; margin-bottom: 0.4rem; letter-spacing: -0.5px;">
                <?php echo $is_vi ? 'Giỏ Hàng & Đơn Hàng' : 'Cart & Orders'; ?>
            </h2>
            <div class="underline" style="width: 80px; height: 4px; background: var(--primary); margin: 0 auto; border-radius: 2px;"></div>
        </div>

        <?php if (!empty($order_msg)): ?>
            <div style="background: <?php echo $order_msg_type === 'success' ? '#dcfce7' : ($order_msg_type === 'info' ? '#eff6ff' : '#fee2e2'); ?>; color: <?php echo $order_msg_type === 'success' ? '#166534' : ($order_msg_type === 'info' ? '#1e40af' : '#991b1b'); ?>; padding: 1.2rem 1.5rem; border-radius: 18px; margin-bottom: 2rem; border: 1px solid <?php echo $order_msg_type === 'success' ? '#bbf7d0' : ($order_msg_type === 'info' ? '#bfdbfe' : '#fca5a5'); ?>; font-weight: 700; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <?php echo $order_msg; ?>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const type = <?php echo json_encode($order_msg_type); ?>;
                    const msg = <?php echo json_encode($order_msg); ?>;
                    let title = <?php echo json_encode(__('order_notification')); ?>;
                    let icon = '📦';
                    if (type === 'success') {
                        title = <?php echo json_encode(__('confirm_order_success')); ?>;
                        icon = '✅';
                    } else if (type === 'info') {
                        title = <?php echo json_encode(__('cancel_order_success')); ?>;
                        icon = '🗑️';
                    } else if (type === 'error') {
                        title = <?php echo json_encode(__('error_notification')); ?>;
                        icon = '❌';
                    }
                    if (typeof showGymXAlert === 'function') {
                        showGymXAlert(msg, title, icon);
                    }
                });
            </script>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- TAB NAVIGATION (Giỏ Hàng đứng trước Theo Dõi Đơn Hàng)       -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="cart-tabs-nav">
            <!-- TAB 1: Giỏ Hàng (ĐỨNG TRƯỚC) -->
            <button type="button" class="cart-tab-btn active" id="tabBtnCart" onclick="switchCartTab('cart')">
                <i class="fas fa-shopping-cart"></i>
                <span><?php echo __('shopping_cart'); ?></span>
                <span class="tab-badge" id="tabCartCountBadge"><?php echo !empty($cart_items) ? count($cart_items) : '0'; ?></span>
            </button>

            <!-- TAB 2: Theo Dõi & Lịch Sử Đơn Hàng -->
            <button type="button" class="cart-tab-btn" id="tabBtnOrders" onclick="switchCartTab('orders')">
                <i class="fas fa-truck-fast"></i>
                <span><?php echo $is_vi ? 'Theo Dõi & Lịch Sử Đơn Hàng' : 'Order Tracking & History'; ?></span>
                <span class="tab-badge"><?php echo count($user_orders); ?></span>
            </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- PANE 1: GIỎ HÀNG (SHOPPING CART)                             -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="cart-tab-pane active" id="cartTabContent">
            <?php if (empty($cart_items)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: #ffffff; border-radius: 28px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto;">
                    <div style="width: 80px; height: 80px; border-radius: 24px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2.5rem; color: #94a3b8;">
                        🛍️
                    </div>
                    <h3 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;"><?php echo __('cart_empty'); ?></h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 2rem;">
                        <?php echo $is_vi ? 'Giỏ hàng của bạn chưa có sản phẩm nào. Hãy khám phá ngay các sản phẩm chất lượng cao của chúng tôi!' : 'Your shopping cart is empty. Explore our top-tier fitness products!'; ?>
                    </p>
                    <a href="/Gym Manager/pages/shop.php" class="cta-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 0.9rem 2.2rem; text-decoration: none; border-radius: 16px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 25px rgba(0, 98, 255, 0.25);">
                        <i class="fas fa-arrow-left"></i> <?php echo __('go_shopping'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="admin-grid" style="grid-template-columns: 1fr 370px; gap: 2rem;">
                    <!-- Cart Items Table Card -->
                    <div style="background: #ffffff; border-radius: 28px; padding: 2rem; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0;">
                        <div class="table-responsive" style="margin-top: 0; border: none; box-shadow: none; overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #f1f5f9; text-align: left; font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <th style="padding: 1rem 0.8rem;"><?php echo __('product'); ?></th>
                                        <th style="padding: 1rem 0.8rem;"><?php echo __('price'); ?></th>
                                        <th style="padding: 1rem 0.8rem; text-align: center;"><?php echo __('quantity'); ?></th>
                                        <th style="padding: 1rem 0.8rem; text-align: right;"><?php echo __('subtotal'); ?></th>
                                        <th style="padding: 1rem 0.8rem; text-align: center;"><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 1.2rem 0.8rem; display: flex; align-items: center; gap: 1.2rem;">
                                            <img src="<?php echo htmlspecialchars(get_image_url($item['image'])); ?>" style="width: 64px; height: 64px; border-radius: 14px; object-fit: contain; background: #f8fafc; padding: 4px; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                            <span style="font-weight: 800; color: #0f172a; font-size: 0.95rem; line-height: 1.4;"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </td>
                                        <td style="padding: 1.2rem 0.8rem; font-weight: 700; color: #334155;"><?php echo number_format((int)round($item['price'] * 25000), 0, ',', '.'); ?>đ</td>
                                        <td style="padding: 1.2rem 0.8rem; text-align: center;">
                                            <div style="display: inline-flex; align-items: center; border: 1.5px solid #cbd5e1; border-radius: 12px; overflow: hidden; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                                <button type="button" onclick="modifyCartQty('<?php echo $item['cart_key']; ?>', -1)" style="border: none; background: #f8fafc; width: 30px; height: 30px; font-weight: 800; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">-</button>
                                                <input type="number" id="cart_qty_val_<?php echo $item['cart_key']; ?>" value="<?php echo $item['qty']; ?>" min="1" max="99" onchange="modifyCartQty('<?php echo $item['cart_key']; ?>', 0, this.value)" style="width: 44px; height: 30px; text-align: center; border: none; font-weight: 800; font-size: 0.92rem; color: #0f172a; outline: none; background: transparent;">
                                                <button type="button" onclick="modifyCartQty('<?php echo $item['cart_key']; ?>', 1)" style="border: none; background: #f8fafc; width: 30px; height: 30px; font-weight: 800; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">+</button>
                                            </div>
                                        </td>
                                        <td style="padding: 1.2rem 0.8rem; font-weight: 800; color: var(--primary); text-align: right; font-size: 1.05rem;">
                                            <span id="cart_subtotal_<?php echo $item['cart_key']; ?>"><?php echo number_format((int)round($item['subtotal'] * 25000), 0, ',', '.'); ?>đ</span>
                                        </td>
                                        <td style="padding: 1.2rem 0.8rem; text-align: center;">
                                            <a href="/Gym Manager/pages/cart.php?remove=<?php echo $item['cart_key']; ?>" style="color: #ef4444; font-weight: 700; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'" title="<?php echo __('remove_from_cart'); ?>">
                                                <i class="fas fa-trash-alt"></i> <?php echo __('remove'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.8rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 1rem;">
                            <a href="/Gym Manager/pages/shop.php" style="color: var(--primary); font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.92rem;">
                                <i class="fas fa-arrow-left"></i> <?php echo __('go_shopping'); ?>
                            </a>
                            <a href="/Gym Manager/pages/cart.php?clear_cart=1" style="background: rgba(239,68,68,0.08); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); padding: 9px 18px; border-radius: 12px; font-weight: 700; font-size: 0.86rem; text-decoration: none; display: flex; align-items: center; gap: 6px;" onclick="return confirmLinkCustom(event, this, '<?php echo __('clear_cart_prompt'); ?>', '<?php echo __('clear_cart'); ?>', '🗑️');">
                                <i class="fas fa-trash-alt"></i> <?php echo __('clear_cart'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Cart Totals Card -->
                    <div style="background: #ffffff; border-radius: 28px; padding: 2.2rem; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; height: fit-content;">
                        <h3 style="margin: 0 0 1.5rem; color: #0f172a; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.3px;"><?php echo __('cart_totals'); ?></h3>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #64748b; font-weight: 600;">
                            <span><?php echo __('subtotal'); ?>:</span>
                            <span style="font-weight: 800; color: #0f172a;"><span id="cart_subtotal_vnd"><?php echo number_format((int)round($total * 25000), 0, ',', '.'); ?>đ</span></span>
                        </div>

                        <?php if ($is_loyal_member): ?>
                            <div id="cart_discount_row" style="display: flex; justify-content: space-between; margin-bottom: 1.2rem; color: #b45309; background: #fffbeb; padding: 10px 14px; border-radius: 14px; border: 1.5px solid #fde68a; font-weight: 700; font-size: 0.88rem; align-items: center;">
                                <span><i class="fas fa-crown" style="color: #f59e0b; margin-right: 6px;"></i> <?php echo $is_vi ? 'Đặc quyền Hội viên (-10%)' : 'VIP Member Perk (-10%)'; ?>:</span>
                                <span style="font-weight: 800; color: #16a34a;">-<span id="cart_discount_vnd"><?php echo number_format((int)round($member_discount * 25000), 0, ',', '.'); ?>đ</span></span>
                            </div>
                        <?php else: ?>
                            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 12px; margin-bottom: 1.2rem; font-size: 0.82rem; color: #64748b; line-height: 1.5;">
                                <i class="fas fa-gift" style="color: #2563eb; margin-right: 5px;"></i>
                                <?php echo $is_vi 
                                    ? 'Hội viên GymX được <strong>giảm ngay 10%</strong> mọi phụ kiện & Whey. <a href="/Gym Manager/pages/membership.php" style="color:#2563eb;font-weight:700;">Xem gói thẻ</a>' 
                                    : 'GymX members get <strong>10% OFF</strong> all shop orders. <a href="/Gym Manager/pages/membership.php" style="color:#2563eb;font-weight:700;">Explore plans</a>'; ?>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-weight: 900; color: var(--primary); font-size: 1.35rem; border-top: 2px solid #f1f5f9; padding-top: 1.4rem;">
                            <span><?php echo __('total'); ?>:</span>
                            <div style="text-align: right;">
                                <span id="cart_total_vnd" style="font-size: 1.5rem; font-weight: 900; color: #10b981;"><?php echo number_format($final_total_vnd, 0, ',', '.'); ?>đ</span>
                            </div>
                        </div>
                        <a href="/Gym Manager/pages/checkout.php" class="btn-auth" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; padding: 1.1rem; border-radius: 16px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 25px rgba(0, 98, 255, 0.25);">
                            <i class="fas fa-lock"></i> <?php echo __('proceed_checkout'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- PANE 2: THEO DÕI ĐƠN HÀNG & LỊCH SỬ MUA HÀNG               -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="cart-tab-pane" id="ordersTabContent">
            <?php if (empty($user_orders)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: #ffffff; border-radius: 28px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto;">
                    <div style="width: 80px; height: 80px; border-radius: 24px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2.5rem; color: #94a3b8;">
                        🚚
                    </div>
                    <h3 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
                        <?php echo $is_vi ? 'Chưa Có Đơn Hàng Nào' : 'No Orders Found'; ?>
                    </h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 2rem;">
                        <?php echo $is_vi ? 'Bạn chưa đặt mua đơn hàng nào. Hãy mua sắm ngay để trải nghiệm dịch vụ giao hàng nhanh chóng!' : 'You have not placed any orders yet. Start shopping now!'; ?>
                    </p>
                    <a href="/Gym Manager/pages/shop.php" class="cta-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 0.9rem 2.2rem; text-decoration: none; border-radius: 16px; font-weight: 800; font-size: 1rem;">
                        <i class="fas fa-shopping-bag"></i> <?php echo __('go_shopping'); ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Sub Filter Row -->
                <div class="order-sub-filter-row">
                    <button type="button" class="order-sub-filter-btn active" onclick="filterOrderList('all', this)">
                        <span><?php echo $is_vi ? 'Tất Cả' : 'All'; ?></span>
                        <span style="background: rgba(0,0,0,0.06); padding: 2px 7px; border-radius: 12px; font-size: 0.75rem;"><?php echo count($user_orders); ?></span>
                    </button>
                    <button type="button" class="order-sub-filter-btn" onclick="filterOrderList('pending', this)">
                        <span>🚚 <?php echo $is_vi ? 'Đang Vận Chuyển' : 'Shipping'; ?></span>
                        <span style="background: rgba(245,158,11,0.2); color: #d97706; padding: 2px 7px; border-radius: 12px; font-size: 0.75rem;"><?php echo $pending_count; ?></span>
                    </button>
                    <button type="button" class="order-sub-filter-btn" onclick="filterOrderList('completed', this)">
                        <span>✅ <?php echo $is_vi ? 'Đã Hoàn Tất' : 'Completed'; ?></span>
                        <span style="background: rgba(16,185,129,0.2); color: #059669; padding: 2px 7px; border-radius: 12px; font-size: 0.75rem;"><?php echo $completed_count; ?></span>
                    </button>
                    <?php if ($cancelled_count > 0): ?>
                    <button type="button" class="order-sub-filter-btn" onclick="filterOrderList('cancelled', this)">
                        <span>✖ <?php echo $is_vi ? 'Đã Hủy' : 'Cancelled'; ?></span>
                        <span style="background: rgba(239,68,68,0.2); color: #dc2626; padding: 2px 7px; border-radius: 12px; font-size: 0.75rem;"><?php echo $cancelled_count; ?></span>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Orders Grid -->
                <div style="display: grid; gap: 1.8rem;" id="ordersContainer">
                    <?php foreach ($user_orders as $ord): 
                        $stmt_o_items = $pdo->prepare("SELECT oi.*, p.name, p.name_vi, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                        $stmt_o_items->execute([$ord['id']]);
                        $ord_items = $stmt_o_items->fetchAll();
                        $is_pending = ($ord['status'] === 'pending');
                        $is_completed = ($ord['status'] === 'completed');
                        $is_cancelled = ($ord['status'] === 'cancelled');
                        $card_status_class = $is_pending ? 'order-card-pending' : ($is_completed ? 'order-card-completed' : 'order-card-cancelled');
                    ?>
                        <div class="order-card-item <?php echo $card_status_class; ?>" style="background: #ffffff; border-radius: 26px; padding: 1.8rem; box-shadow: 0 15px 40px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; transition: transform 0.2s;">
                            
                            <!-- Order Header -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 44px; height: 44px; border-radius: 14px; background: <?php echo $is_pending ? '#fef3c7' : ($is_completed ? '#dcfce7' : '#fee2e2'); ?>; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                                        <?php echo $is_pending ? '🚚' : ($is_completed ? '📦' : '✖'); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 900; font-size: 1.15rem; color: #0f172a;">
                                            <?php echo __('order'); ?> #<?php echo $ord['id']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($ord['created_at'])); ?>
                                            <?php if (!empty($ord['phone'])): ?>
                                                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($ord['phone']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php if ($is_pending): ?>
                                        <?php if ($ord['payment_method'] === 'momo' && !empty($ord['momo_confirmed'])): ?>
                                            <span style="background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                                                💜 <?php echo $is_vi ? 'Đã Thanh Toán (MoMo)' : 'Paid (MoMo)'; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                                            🚚 <?php echo __('order_status_delivering'); ?>
                                        </span>
                                    <?php elseif ($is_completed): ?>
                                        <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                                            ✔ <?php echo __('status_completed'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                                            ✖ <?php echo __('cancelled_status'); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.78rem; text-transform: uppercase;">
                                        <?php echo ($ord['payment_method'] === 'momo') ? '💜 MOMO' : strtoupper($ord['payment_method']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- ── VẬN CHUYỂN: Stepper Timeline (For Pending Orders) ── -->
                            <?php if ($is_pending): ?>
                                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 1.4rem; border-radius: 20px; margin-bottom: 1.4rem; color: white; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);">
                                    <div style="font-size: 0.82rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 6px;">
                                        <span>🚚</span> <?php echo $is_vi ? 'TIẾN TRÌNH VẬN CHUYỂN' : 'DELIVERY PROGRESS'; ?>
                                        <?php if ($ord['payment_method'] === 'momo' && !empty($ord['momo_confirmed'])): ?>
                                            <span style="background: rgba(162, 28, 175, 0.25); color: #f0abfc; padding: 2px 10px; border-radius: 12px; font-size: 0.74rem; border: 1px solid rgba(240, 171, 252, 0.3);">
                                                💜 <?php echo $is_vi ? 'Đã nhận tiền MoMo' : 'MoMo Paid'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; position: relative; max-width: 650px; margin: 0 auto; padding: 10px 0;">
                                        <div style="position: absolute; top: 25px; left: 10%; right: 10%; height: 4px; background: rgba(255,255,255,0.15); z-index: 1;"></div>
                                        <div style="position: absolute; top: 25px; left: 10%; width: 40%; height: 4px; background: linear-gradient(90deg, #10b981, #3b82f6); z-index: 2;"></div>

                                        <!-- Step 1 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 900; font-size: 0.85rem; box-shadow: 0 0 12px rgba(16,185,129,0.5);">
                                                ✓
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 800; color: #e2e8f0;">
                                                <?php echo ($ord['payment_method'] === 'momo' && !empty($ord['momo_confirmed'])) ? ($is_vi ? 'Đã Đặt & Đã Thanh Toán' : 'Placed & Paid') : __('step_placed'); ?>
                                            </div>
                                        </div>

                                        <!-- Step 2 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 900; font-size: 0.85rem; box-shadow: 0 0 15px rgba(59,130,246,0.8); animation: pulseStep 2s infinite;">
                                                🚚
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 800; color: #60a5fa;"><?php echo __('step_shipping'); ?></div>
                                        </div>

                                        <!-- Step 3 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: rgba(255,255,255,0.2); color: #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 900; font-size: 0.85rem;">
                                                3
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8;"><?php echo __('step_completed'); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($ord['address'])): ?>
                                        <div style="margin-top: 1rem; padding-top: 0.8rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.82rem; color: #cbd5e1; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i>
                                            <span><?php echo $is_vi ? 'Giao tới:' : 'Deliver to:'; ?> <strong><?php echo htmlspecialchars($ord['address']); ?></strong></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- ── LỊCH SỬ MUA HÀNG: Danh Sách Sản Phẩm & Đánh Giá ── -->
                            <div style="margin-bottom: 1.2rem;">
                                <div style="font-size: 0.82rem; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 0.8rem;">
                                    <?php echo $is_vi ? 'SẢN PHẨM TRONG ĐƠN HÀNG' : 'PURCHASED PRODUCTS'; ?>
                                </div>

                                <div style="display: grid; gap: 10px;">
                                    <?php foreach ($ord_items as $oi): 
                                        $has_review = isset($user_reviews_map[$oi['product_id']]);
                                        $rev_data   = $has_review ? $user_reviews_map[$oi['product_id']] : null;
                                        $p_display_name = ($is_vi && !empty($oi['name_vi'])) ? $oi['name_vi'] : $oi['name'];
                                    ?>
                                        <div style="background: #f8fafc; border-radius: 16px; padding: 12px 16px; border: 1px solid #e2e8f0;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                                <div style="display: flex; align-items: center; gap: 12px; flex-grow: 1; min-width: 220px;">
                                                    <img src="<?php echo htmlspecialchars(get_image_url($oi['image'])); ?>" style="width: 52px; height: 52px; border-radius: 12px; object-fit: contain; background: white; padding: 3px; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                    <div>
                                                        <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem; line-height: 1.3;">
                                                            <?php echo htmlspecialchars($p_display_name); ?>
                                                            <?php if (!empty($oi['variant'])): ?>
                                                                <span style="font-size: 0.78rem; font-weight: 600; color: #64748b;">(<?php echo htmlspecialchars($oi['variant']); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                                            <?php echo __('quantity'); ?>: <strong><?php echo $oi['quantity']; ?></strong> × <?php echo number_format((int)round($oi['price'] * 25000), 0, ',', '.'); ?>đ
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Review trigger area (For completed orders) -->
                                                <?php if ($is_completed): ?>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <?php if ($has_review): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 5px 12px; border-radius: 20px;">
                                                                <span style="color: #f59e0b; font-size: 0.9rem;">
                                                                    <?php for($s=1; $s<=5; $s++) echo $s <= $rev_data['rating'] ? '★' : '☆'; ?>
                                                                </span>
                                                                <span style="color: #059669; font-weight: 800; font-size: 0.78rem;">
                                                                    ✓ <?php echo $is_vi ? 'Đã Đánh Giá' : 'Reviewed'; ?>
                                                                </span>
                                                            </div>
                                                            <button type="button" onclick="toggleReviewForm('rev-form-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>')" style="background: none; border: none; color: var(--primary); font-weight: 700; font-size: 0.78rem; cursor: pointer; text-decoration: underline;">
                                                                <?php echo $is_vi ? 'Sửa' : 'Edit'; ?>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" onclick="toggleReviewForm('rev-form-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>')" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 7px 16px; border-radius: 12px; font-weight: 800; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(245,158,11,0.25);">
                                                                ⭐ <?php echo $is_vi ? 'Đánh Giá Sản Phẩm' : 'Review Product'; ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Inline Review Form (Toggled upon click) -->
                                            <?php if ($is_completed): ?>
                                                <div id="rev-form-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>" style="display: none; margin-top: 12px; padding: 14px; background: #ffffff; border-radius: 14px; border: 1.5px solid #cbd5e1; animation: fadeInTab 0.25s ease;">
                                                    <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                                                        ⭐ <?php echo $is_vi ? 'Đánh giá sản phẩm:' : 'Review product:'; ?> <em><?php echo htmlspecialchars($p_display_name); ?></em>
                                                    </div>

                                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                                        <div style="display: flex; align-items: center; gap: 12px;">
                                                            <span style="font-size: 0.82rem; font-weight: 700; color: #475569;"><?php echo $is_vi ? 'Chọn số sao:' : 'Rating:'; ?></span>
                                                            <div class="star-rating-box" id="star-box-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>">
                                                                <?php 
                                                                $cur_r = $has_review ? (int)$rev_data['rating'] : 5;
                                                                for ($st = 1; $st <= 5; $st++): ?>
                                                                    <span class="star <?php echo $st <= $cur_r ? 'active' : ''; ?>" onclick="selectRatingStars(<?php echo $ord['id']; ?>, <?php echo $oi['product_id']; ?>, <?php echo $st; ?>)">★</span>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <input type="hidden" id="star-input-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>" value="<?php echo $cur_r; ?>">
                                                        </div>

                                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                            <input type="text" 
                                                                   id="comment-input-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>" 
                                                                   placeholder="<?php echo $is_vi ? 'Viết nhận xét của bạn về chất lượng sản phẩm...' : 'Write your honest review...'; ?>" 
                                                                   value="<?php echo $has_review ? htmlspecialchars($rev_data['comment']) : ''; ?>" 
                                                                   style="flex-grow: 1; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 0.88rem; outline: none; background: #f8fafc;">
                                                            
                                                             <button type="button" onclick="submitReviewAjax(<?php echo $ord['id']; ?>, <?php echo $oi['product_id']; ?>)" class="btn-auth" style="margin: 0; padding: 10px 20px; font-size: 0.85rem; border-radius: 12px; font-weight: 800;">
                                                                <i class="fas fa-paper-plane"></i> <?php echo $is_vi ? 'Gửi Đánh Giá' : 'Submit'; ?>
                                                            </button>
                                                            <button type="button" onclick="toggleReviewForm('rev-form-<?php echo $ord['id']; ?>-<?php echo $oi['product_id']; ?>')" style="background: #f1f5f9; color: #64748b; border: none; padding: 10px 16px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                                                                <?php echo __('cancel'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Total & Action Footer -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1.2rem;">
                                <div>
                                    <span style="font-size: 0.88rem; color: #64748b; font-weight: 600;"><?php echo __('total_payment'); ?>:</span>
                                    <strong style="font-size: 1.3rem; color: #10b981; margin-left: 6px; font-weight: 900;"><?php echo number_format((int)round($ord['total_price'] * 25000), 0, ',', '.'); ?>đ</strong>
                                </div>

                                <?php if ($is_pending): ?>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <form method="POST" style="margin: 0;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('confirm_order_prompt'); ?>', '<?php echo __('confirm_order_success'); ?>', '✅');">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="action_confirm_order" value="1">
                                            <button type="submit" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 20px; border-radius: 14px; font-weight: 800; font-size: 0.86rem; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(16,185,129,0.3); transition: transform 0.2s;">
                                                <i class="fas fa-check-circle"></i> <?php echo __('confirm_delivery'); ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="margin: 0;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('cancel_order_prompt'); ?>', '<?php echo __('cancel_order'); ?>', '⚠️');">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="action_cancel_order" value="1">
                                            <button type="submit" style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); padding: 10px 18px; border-radius: 14px; font-weight: 800; font-size: 0.86rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                <i class="fas fa-times-circle"></i> <?php echo __('cancel_order'); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
// ── Tab Switching Logic ──────────────────────────────────────
function switchCartTab(tabName) {
    const btnCart = document.getElementById('tabBtnCart');
    const btnOrders = document.getElementById('tabBtnOrders');
    const paneCart = document.getElementById('cartTabContent');
    const paneOrders = document.getElementById('ordersTabContent');

    if (tabName === 'orders') {
        btnCart.classList.remove('active');
        btnOrders.classList.add('active');
        paneCart.classList.remove('active');
        paneOrders.classList.add('active');
        window.location.hash = 'order-tracking';
    } else {
        btnOrders.classList.remove('active');
        btnCart.classList.add('active');
        paneOrders.classList.remove('active');
        paneCart.classList.add('active');
        // Clear hash or set to cart
        if (window.location.hash === '#order-tracking') {
            history.replaceState(null, null, ' ');
        }
    }
}

// ── Auto-activate tab based on Hash or URL params ────────────
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    const urlParams = new URLSearchParams(window.location.search);
    if (hash === '#order-tracking' || urlParams.get('tab') === 'orders') {
        switchCartTab('orders');
    }
});

// ── Sub-filtering orders inside Orders Tab ──────────────────
function filterOrderList(status, btnElement) {
    const btns = document.querySelectorAll('.order-sub-filter-btn');
    btns.forEach(b => b.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');

    const cards = document.querySelectorAll('.order-card-item');
    cards.forEach(card => {
        if (status === 'all') {
            card.style.display = 'block';
        } else if (status === 'pending' && card.classList.contains('order-card-pending')) {
            card.style.display = 'block';
        } else if (status === 'completed' && card.classList.contains('order-card-completed')) {
            card.style.display = 'block';
        } else if (status === 'cancelled' && card.classList.contains('order-card-cancelled')) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ── Review Toggle & Rating Picker ───────────────────────────
function toggleReviewForm(formId) {
    const el = document.getElementById(formId);
    if (el) {
        el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }
}

function selectRatingStars(orderId, productId, ratingValue) {
    const box = document.getElementById(`star-box-${orderId}-${productId}`);
    const input = document.getElementById(`star-input-${orderId}-${productId}`);
    if (input) input.value = ratingValue;

    if (box) {
        const stars = box.querySelectorAll('.star');
        stars.forEach((s, idx) => {
            if (idx < ratingValue) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    }
}

function submitReviewAjax(orderId, productId) {
    const starInput = document.getElementById(`star-input-${orderId}-${productId}`);
    const commentInput = document.getElementById(`comment-input-${orderId}-${productId}`);

    const rating = starInput ? parseInt(starInput.value) : 5;
    const comment = commentInput ? commentInput.value.trim() : '';

    if (!comment) {
        alert(<?php echo json_encode($is_vi ? 'Vui lòng nhập nhận xét của bạn!' : 'Please enter your review comment!'); ?>);
        return;
    }

    fetch('/Gym Manager/api/submit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            product_id: productId,
            rating: rating,
            comment: comment
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            // Hide review form and reload or update UI
            toggleReviewForm(`rev-form-${orderId}-${productId}`);
            window.location.reload();
        } else {
            alert('❌ ' + (data.message || 'Lỗi gửi đánh giá'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('❌ Lỗi kết nối máy chủ');
    });
}

// ── Quantity Adjuster for Shopping Cart ──────────────────────
function modifyCartQty(cartKey, changeDelta, exactValue) {
    var payload = { cart_key: cartKey };
    if (exactValue === 0) {
        payload.action = 'remove';
    } else if (changeDelta !== 0) {
        payload.action = 'change';
        payload.quantity = changeDelta;
    } else {
        payload.action = 'set';
        payload.quantity = parseInt(exactValue) || 1;
    }

    fetch('/Gym Manager/api/update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (data.cart_empty) {
                window.location.reload();
                return;
            }
            if (data.current_qty <= 0) {
                window.location.reload();
            } else {
                var qtyInput = document.getElementById('cart_qty_val_' + cartKey);
                if (qtyInput) qtyInput.value = data.current_qty;
                var subtotalSpan = document.getElementById('cart_subtotal_' + cartKey);
                if (subtotalSpan) subtotalSpan.innerText = data.item_subtotal_formatted;
            }
            var subTotalVnd = document.getElementById('cart_subtotal_vnd');
            if (subTotalVnd) subTotalVnd.innerText = data.subtotal_formatted || data.total_formatted;
            var discVnd = document.getElementById('cart_discount_vnd');
            if (discVnd && data.member_discount_formatted) discVnd.innerText = data.member_discount_formatted;
            var totalVnd = document.getElementById('cart_total_vnd');
            if (totalVnd && data.total_vnd_formatted) totalVnd.innerText = data.total_vnd_formatted;

            var cartBadges = document.querySelectorAll('.cart-count-badge, .nav-cart-count, .nav-cart-btn .nav-badge');
            cartBadges.forEach(function(b) { b.innerText = data.cart_count; });
        } else {
            alert('❌ ' + (data.message || 'Lỗi cập nhật số lượng'));
        }
    })
    .catch(function(err) {
        console.error(err);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

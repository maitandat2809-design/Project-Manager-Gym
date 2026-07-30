<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Gym Manager/auth/login.php");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: /Gym Manager/pages/shop.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';

// Process Order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate phone number
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = __('phone_invalid');
    } else {
        $total_price = 0;
        
        // Calculate total and verify items
    $ids = array_keys($_SESSION['cart']);
    $base_ids = array_unique(array_map(function($k) { return explode('_', $k)[0]; }, $ids));
    
    $placeholders = str_repeat('?,', count($base_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_values($base_ids));
    
    $db_products = [];
    foreach ($stmt->fetchAll() as $p) {
        $db_products[$p['id']] = $p;
    }

    $checkout_items = [];
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
        $total_price += $price * $qty;
        
        $p_name = (isset($current_lang) && $current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name'];
        $checkout_items[] = [
            'id' => $p['id'],
            'name' => $p_name . $name_suffix,
            'price' => $price,
            'qty' => $qty,
            'variant' => $weight
        ];
    }

    try {
        $pdo->beginTransaction();

        // 1. Create Order
        $address = trim($_POST['address'] ?? '');
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, phone, address) VALUES (?, ?, 'completed', ?, ?)");
        $stmt->execute([$user_id, $total_price, $phone, $address]);
        $order_id = $pdo->lastInsertId();

        // Save info back to user profile
        $stmt_u = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ? AND (phone IS NULL OR address IS NULL OR phone != ? OR address != ?)");
        $stmt_u->execute([$phone, $address, $user_id, $phone, $address]);

        // 2. Create Order Items and Update Sales Count
        $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, variant) VALUES (?, ?, ?, ?, ?)");
        $stmt_sales = $pdo->prepare("UPDATE products SET sales_count = sales_count + ?, stock = GREATEST(0, stock - ?) WHERE id = ?");
        foreach ($checkout_items as $item) {
            $stmt_item->execute([$order_id, $item['id'], $item['qty'], $item['price'], $item['variant']]);
            $stmt_sales->execute([$item['qty'], $item['qty'], $item['id']]);
        }

        $pdo->commit();

        // --- Start PHPMailer Notification ---
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        // Get user email if missing from session
        $user_email = $_SESSION['user_email'] ?? '';
        if (empty($user_email)) {
            $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt_u->execute([$_SESSION['user_id']]);
            $user_email = $stmt_u->fetchColumn();
            $_SESSION['user_email'] = $user_email;
        }

        // 1. Send to Customer
        if ($user_email) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Premium Shop');
                $mail->addAddress($user_email, $_SESSION['user_name']); 
                $mail->isHTML(true);
                $mail->Subject = "GymX: Your Order Confirmation #" . $order_id;
                
                $html_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 15px;'>";
                $html_body .= "<h2 style='color: #0062ff; text-align: center;'>Thank You For Your Purchase!</h2>";
                $html_body .= "<p>Hello <strong>" . $_SESSION['user_name'] . "</strong>, your order has been successfully placed.</p>";
                $html_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 10px; margin: 20px 0;'>";
                $html_body .= "<strong>Order ID:</strong> #" . $order_id . "<br>";
                $html_body .= "<strong>Total Amount:</strong> $" . number_format($total_price, 2) . "<br>";
                $html_body .= "<strong>Status:</strong> Processing<br>";
                $html_body .= "</div>";
                $html_body .= "<h4>Items List:</h4><ul style='list-style: none; padding: 0;'>";
                foreach ($checkout_items as $item) {
                    $html_body .= "<li style='padding: 10px 0; border-bottom: 1px solid #eee;'>" . $item['name'] . " x " . $item['qty'] . " - <span style='color: #0062ff;'>$" . number_format($item['price'] * $item['qty'], 2) . "</span></li>";
                }
                $html_body .= "</ul>";
                $html_body .= "<p style='margin-top: 30px; text-align: center; color: #64748b; font-size: 0.9rem;'>Stay Healthy with GymX!</p></div>";

                $mail->Body = $html_body;
                $mail->send();
            } catch (Exception $e) {
                error_log("Order Mail to Customer Error: " . $e->getMessage());
            }
        }

        // 2. Send to Managers
        try {
            $stmt_mgrs = $pdo->prepare("SELECT email, name FROM users WHERE role = 'manager'");
            $stmt_mgrs->execute();
            $managers = $stmt_mgrs->fetchAll();

            if (count($managers) > 0) {
                $mail2 = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail2->CharSet = 'UTF-8';
                $mail2->isSMTP();
                $mail2->Host       = 'smtp.gmail.com';
                $mail2->SMTPAuth   = true;
                $mail2->Username   = 'lamtuoi155@gmail.com';     
                $mail2->Password   = 'hpst uazz wdsq sbnf';   
                $mail2->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail2->Port       = 587;

                $mail2->setFrom('lamtuoi155@gmail.com', 'GymX Premium Shop');
                
                $has_manager = false;
                foreach ($managers as $mgr) {
                    if ($mgr['email']) {
                        $mail2->addAddress($mgr['email'], $mgr['name']);
                        $has_manager = true;
                    }
                }

                if ($has_manager) {
                    $mail2->isHTML(true);
                    $mail2->Subject = "URGENT: New Order #" . $order_id . " - Customer: " . $_SESSION['user_name'];
                    $admin_body = "<h3>New Order Alert</h3>";
                    $admin_body .= "<p>A new order has been placed by <strong>" . $_SESSION['user_name'] . "</strong> (" . $user_email . ")</p>";
                    
                    // Rebuild html_body just for manager to be safe
                    $html_body2 = "<div style='background: #f8fafc; padding: 15px; border-radius: 10px; margin: 20px 0;'>";
                    $html_body2 .= "<strong>Order ID:</strong> #" . $order_id . "<br>";
                    $html_body2 .= "<strong>Total Amount:</strong> $" . number_format($total_price, 2) . "<br>";
                    $html_body2 .= "</div><h4>Items List:</h4><ul>";
                    foreach ($checkout_items as $item) {
                        $html_body2 .= "<li>" . $item['name'] . " x " . $item['qty'] . " - $" . number_format($item['price'] * $item['qty'], 2) . "</li>";
                    }
                    $html_body2 .= "</ul>";
                    
                    $admin_body .= $html_body2;
                    $admin_body .= "<p><a href='http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/admin.php?page=orders' style='background: #0062ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Manage Order</a></p>";
                    
                    $mail2->Body = $admin_body;
                    $mail2->send();
                }
            }
        } catch (Exception $e) {
            error_log("Order Mail to Manager Error: " . $e->getMessage());
        }
        // --- End PHPMailer Notification ---
        
        // Clear Cart
        unset($_SESSION['cart']);
        $success = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Payment error: " . $e->getMessage();
    }
    } // End of phone validation else block
}
?>

<section>
    <div class="auth-container" style="max-width: 600px;">
        <?php if (isset($success)): ?>
            <div style="text-align: center; padding: 4rem 1rem; animation: fadeInUp 0.8s ease;">
                <div style="width: 120px; height: 120px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2.5rem; border: 3px solid #16a34a; box-shadow: 0 15px 35px rgba(22, 163, 74, 0.15);">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h2 style="font-size: 3rem; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; font-weight: 900; letter-spacing: -1px;"><?php echo __('order_success_title'); ?></h2>
                <div style="width: 60px; height: 6px; background: var(--grad-primary); margin: 0 auto 2rem; border-radius: 10px;"></div>
                <p style="color: var(--text-muted); font-size: 1.2rem; margin-bottom: 2rem; max-width: 450px; margin-left: auto; margin-right: auto; line-height: 1.8;"><?php echo __('order_success_subtitle'); ?></p>
                
                <!-- Review Products -->
                <div style="margin: 2rem 0; text-align: left; background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 800; font-size: 1.2rem;"><?php echo __('rate_purchased_products'); ?></h3>
                    <?php 
                    $o_stmt = $pdo->prepare("SELECT oi.*, p.name, p.name_vi, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $o_stmt->execute([$order_id]);
                    $items = $o_stmt->fetchAll();
                    foreach($items as $item): ?>
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 15px; background: #f8fafc; padding: 12px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 1rem;">
                            <img src="<?php echo $item['image']; ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                            <div style="flex-grow: 1;">
                                <p style="color: var(--text-main); font-weight: 700; margin: 0;">
                                    <?php echo (isset($current_lang) && $current_lang === 'vi' && !empty($item['name_vi'])) ? htmlspecialchars($item['name_vi']) : htmlspecialchars($item['name']); ?>
                                    <?php if (!empty($item['variant'])): ?>
                                        <span style="color: var(--primary); font-size: 0.85rem;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <form action="profile.php" method="POST" style="display: flex; gap: 10px; align-items: center; margin: 0; flex-grow: 1;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <select name="rating" required style="background: white; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px; font-size: 0.85rem; outline: none; font-weight: 600; color: var(--text-main);">
                                    <option value="5">5 ★</option>
                                    <option value="4">4 ★</option>
                                    <option value="3">3 ★</option>
                                    <option value="2">2 ★</option>
                                    <option value="1">1 ★</option>
                                </select>
                                <input type="text" name="comment" placeholder="<?php echo __('short_review_placeholder'); ?>" required style="flex-grow: 1; background: white; border: 1px solid #e2e8f0; padding: 8px 15px; border-radius: 10px; font-size: 0.85rem; outline: none;">
                                <button type="submit" name="submit_product_review" class="btn-auth" style="margin: 0; padding: 8px 20px; font-size: 0.85rem; width: auto; border-radius: 10px;"><?php echo __('review_btn'); ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
 
                <div style="display: flex; gap: 1.5rem; justify-content: center;">
                    <a href="/Gym Manager/dashboards/profile.php" class="cta-btn" style="background: white; color: var(--primary); border: 2px solid var(--primary); font-size: 0.95rem; box-shadow: none;"><?php echo __('view_orders'); ?></a>
                    <a href="/Gym Manager/pages/shop.php" class="cta-btn" style="font-size: 0.95rem;"><?php echo __('back_to_shop'); ?></a>
                </div>
            </div>
        <?php else: 
            // Get current user info for autofill
            $stmt_curr = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
            $stmt_curr->execute([$_SESSION['user_id']]);
            $curr_user = $stmt_curr->fetch();
            
            // Calculate display items
            $total_display_price = 0;
            $display_items = [];
            $cart_keys = array_keys($_SESSION['cart']);
            if (!empty($cart_keys)) {
                $b_ids = array_unique(array_map(function($k) { return explode('_', $k)[0]; }, $cart_keys));
                $pl = str_repeat('?,', count($b_ids) - 1) . '?';
                $st = $pdo->prepare("SELECT * FROM products WHERE id IN ($pl)");
                $st->execute(array_values($b_ids));
                $d_prods = [];
                foreach ($st->fetchAll() as $p) {
                    $d_prods[$p['id']] = $p;
                }
                foreach ($cart_keys as $k) {
                    $parts = explode('_', $k);
                    $p_id = $parts[0];
                    $w = isset($parts[1]) ? $parts[1] : '';
                    if (!isset($d_prods[$p_id])) continue;
                    $p = $d_prods[$p_id];
                    $price = $p['price'];
                    $ns = '';
                    if ($w === '1kg') { $price += 10; $ns = ' (1 kg)'; }
                    elseif ($w === '2kg') { $price += 25; $ns = ' (2 kg)'; }
                    elseif ($w === '5kg') { $price += 60; $ns = ' (5 kg)'; }
                    elseif ($w === '500g') { $ns = ' (500g)'; }
                    $qty = $_SESSION['cart'][$k];
                    $total_display_price += $price * $qty;
                    $p_name = (isset($current_lang) && $current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name'];
                    $display_items[] = [
                        'name' => $p_name . $ns,
                        'qty' => $qty,
                        'price' => $price,
                        'image' => $p['image']
                    ];
                }
            }
        ?>
            <div class="section-title">
                <h2><?php echo __('checkout_confirmation'); ?></h2>
                <div class="underline"></div>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border: 1px solid #fca5a5; font-weight: 600; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <div style="background: #f8fafc; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"><?php echo __('order_summary'); ?></h3>
                    <?php foreach($display_items as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                                <div>
                                    <h4 style="margin: 0; color: var(--text-main); font-size: 1rem;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo __('quantity'); ?>: <?php echo $item['qty']; ?></span>
                                </div>
                            </div>
                            <div style="font-weight: 700; color: var(--primary);">
                                $<?php echo number_format($item['price'] * $item['qty'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">
                        <span><?php echo __('total'); ?>:</span>
                        <span>$<?php echo number_format($total_display_price, 2); ?></span>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"><?php echo __('shipping_info'); ?></h3>
                    <div class="form-group">
                        <label><?php echo __('shipping_address'); ?></label>
                        <input type="text" name="address" placeholder="<?php echo __('address_placeholder'); ?>" required 
                               value="<?php echo isset($curr_user['address']) ? htmlspecialchars($curr_user['address']) : ''; ?>"
                               style="background: white;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label><?php echo __('phone_number'); ?></label>
                        <input type="text" name="phone" placeholder="090x xxx xxx" required pattern="[0-9]{10}" title="<?php echo __('phone_invalid'); ?>" 
                               value="<?php echo isset($curr_user['phone']) ? htmlspecialchars($curr_user['phone']) : ''; ?>"
                               style="background: white;">
                    </div>
                              <div style="background: #f8fafc; padding: 2rem; border-radius: 20px; margin-bottom: 2.5rem; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"><?php echo __('payment_method'); ?></h3>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem; color: var(--text-main); font-weight: 600;">
                        <label style="display: flex; gap: 1.2rem; align-items: center; cursor: pointer; padding: 10px; border-radius: 10px; transition: 0.3s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <input type="radio" name="payment_method" value="cod" checked onchange="toggleBankInfo()" style="width: 20px; height: 20px; accent-color: var(--primary);">
                            <span><?php echo __('payment_cod'); ?></span>
                        </label>
                        
                        <div>
                            <label style="display: flex; gap: 1.2rem; align-items: center; cursor: pointer; padding: 10px; border-radius: 10px; transition: 0.3s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                <input type="radio" name="payment_method" value="bank" onchange="toggleBankInfo()" style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <span><?php echo __('payment_bank'); ?></span>
                            </label>
                            
                            <div id="bank-info" style="display: none; margin-top: 1rem; padding: 1.5rem; background: white; border-radius: 15px; border: 1px solid var(--border-bright); box-shadow: 0 10px 25px rgba(0,98,255,0.05); animation: fadeInUp 0.5s ease;">
                                <div style="display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;">
                                    <div style="text-align: center; background: #fff; padding: 10px; border-radius: 15px; border: 1px dashed #ccc; flex-shrink: 0;">
                                        <!-- Using VietQR API with real account -->
                                        <img src="https://img.vietqr.io/image/MB-0364112108-compact2.png?amount=<?php echo floor($total_display_price * 25000); ?>&addInfo=Order%20Payment&accountName=GYMX%20STORE" style="width: 180px; height: auto;" alt="VietQR">
                                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 10px; font-weight: 500;"><i class="fas fa-qrcode"></i> <?php echo __('scan_banking_app'); ?></p>
                                    </div>
                                    <div style="flex-grow: 1;">
                                        <h4 style="margin: 0 0 15px 0; color: var(--primary); font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;"><?php echo __('bank_details'); ?></h4>
                                        <p style="margin: 8px 0; font-size: 0.95rem;"><strong>Bank:</strong> MB Bank (Military Bank)</p>
                                        <p style="margin: 8px 0; font-size: 0.95rem;"><strong>Account Name:</strong> GYMX STORE</p>
                                        <p style="margin: 8px 0; font-size: 0.95rem;"><strong>Account Number:</strong> <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 5px; font-family: monospace; letter-spacing: 1px;">0364112108</span></p>
                                        <p style="margin: 8px 0; font-size: 0.95rem;"><strong><?php echo __('amount_to_pay'); ?></strong> <span style="color: #ef4444; font-weight: 700;">$<?php echo number_format($total_display_price, 2); ?></span> (<?php echo number_format($total_display_price * 25000, 0, ',', '.'); ?> VND)</p>
                                        <div style="margin: 15px 0 0 0; font-size: 0.85rem; color: #b45309; background: #fef3c7; padding: 10px 15px; border-radius: 10px; border: 1px solid #fde68a; display: flex; gap: 10px; align-items: flex-start;">
                                            <i class="fas fa-info-circle" style="margin-top: 3px;"></i> 
                                            <span><?php echo __('bank_transfer_warning'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
 
                <button type="button" class="btn-auth" onclick="confirmCheckout()"><?php echo __('complete_order'); ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- Custom Confirm Modal -->
<div id="checkoutConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div style="background: white; padding: 2.5rem; border-radius: 25px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: left;">
        <div style="text-align: center; font-size: 3rem; margin-bottom: 1rem;">🛒</div>
        <h3 style="margin: 0 0 1rem 0; font-weight: 700; color: var(--text-main); font-size: 1.2rem; line-height: 1.5; text-align: center;"><?php echo __('confirm_purchase_prompt'); ?></h3>
        
        <div style="background: #f8fafc; border-radius: 15px; padding: 15px; margin-bottom: 1.5rem; border: 1px solid var(--border); max-height: 200px; overflow-y: auto;">
            <h4 style="margin: 0 0 10px 0; font-size: 1rem;"><?php echo __('items_label'); ?></h4>
            <?php if(!empty($display_items)): ?>
                <?php foreach($display_items as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px; color: var(--text-main);">
                        <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['qty']; ?>)</span>
                        <span style="font-weight: 600;">$<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div style="border-top: 1px solid #cbd5e1; margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between; font-weight: 700;">
                    <span><?php echo __('total'); ?>:</span>
                    <span>$<?php echo number_format($total_display_price, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div style="background: #f8fafc; border-radius: 15px; padding: 15px; margin-bottom: 1.5rem; border: 1px solid var(--border);">
            <div style="margin-bottom: 8px; font-size: 0.95rem;"><strong><?php echo __('phone_number'); ?>:</strong> <span id="modal-phone"></span></div>
            <div style="font-size: 0.95rem;"><strong><?php echo __('address'); ?>:</strong> <span id="modal-address"></span></div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button onclick="submitCheckout()" class="btn-auth" style="margin: 0; padding: 10px 25px; font-size: 1rem; width: auto;"><?php echo __('confirm_order'); ?></button>
            <button onclick="closeCheckoutModal()" style="background: #f1f5f9; color: var(--text-muted); border: none; padding: 10px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1rem;"><?php echo __('cancel'); ?></button>
        </div>
    </div>
</div>

<script>
function toggleBankInfo() {
    var bankRadio = document.querySelector('input[name="payment_method"][value="bank"]');
    var bankInfo = document.getElementById('bank-info');
    if(bankRadio && bankRadio.checked) {
        bankInfo.style.display = 'block';
    } else if (bankInfo) {
        bankInfo.style.display = 'none';
    }
}

function confirmCheckout() {
    // Basic validation
    let phone = document.querySelector('input[name="phone"]').value;
    let address = document.querySelector('input[name="address"]').value;
    let form = document.getElementById('checkoutForm');
    
    if(!phone || !address || !form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Update modal with current inputs
    document.getElementById('modal-phone').innerText = phone;
    document.getElementById('modal-address').innerText = address;

    document.getElementById('checkoutConfirmModal').style.display = 'flex';
}
function closeCheckoutModal() {
    document.getElementById('checkoutConfirmModal').style.display = 'none';
}
function submitCheckout() {
    document.getElementById('checkoutForm').submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

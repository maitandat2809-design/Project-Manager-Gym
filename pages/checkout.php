<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /Gym Manager/dashboards/admin.php");
    exit();
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

        $payment_method = $_POST['payment_method'] ?? 'cod';
        $address = trim($_POST['address'] ?? '');

        // Create Order for COD or other standard methods (status = 'pending' representing delivering/in transit)
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, phone, address, payment_method, admin_seen) VALUES (?, ?, 'pending', ?, ?, ?, 0)");
        $stmt->execute([$user_id, $total_price, $phone, $address, $payment_method]);
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
                    $admin_body .= "<p><a href='http://" . $_SERVER['HTTP_HOST'] . "/Gym%20Manager/dashboards/manager.php?page=orders' style='background: #0062ff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 12px; font-weight: bold;'>Manage Orders Panel</a></p>";
                    
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
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
    } // End of phone validation else block
}
?>

<section>
    <div class="auth-container" style="max-width: 600px;">
        <?php if (isset($success)): ?>
            <!-- Order Success Modal Popup -->
            <div id="orderSuccessModal" style="display: flex; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 1rem; animation: fadeIn 0.3s ease-out;">
                <div style="background: #ffffff; padding: 2.5rem 2rem; border-radius: 28px; width: 100%; max-width: 520px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); border: 1px solid rgba(22, 163, 74, 0.2); text-align: center; position: relative; animation: modalPopIn 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);">
                    
                    <button onclick="closeOrderSuccessModal()" style="position: absolute; top: 1.2rem; right: 1.2rem; background: #f1f5f9; border: none; color: #64748b; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">✕</button>

                    <div style="width: 90px; height: 90px; background: linear-gradient(135deg, #16a34a, #15803d); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 12px 30px rgba(22, 163, 74, 0.35);">
                        <i class="fas fa-check" style="font-size: 2.5rem; color: #ffffff;"></i>
                    </div>

                    <h2 style="font-size: 1.7rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem; letter-spacing: -0.5px;"><?php echo __('order_pay_success_title'); ?></h2>
                    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.8rem; line-height: 1.6; max-width: 440px; margin-left: auto; margin-right: auto;"><?php echo __('order_pay_success_subtitle'); ?></p>

                    <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 20px; padding: 1.25rem 1.5rem; margin-bottom: 1.8rem; text-align: left;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                            <span style="color: #475569; font-weight: 600; font-size: 0.9rem;"><?php echo __('order_pay_id'); ?>:</span>
                            <strong style="color: #0062ff; font-size: 0.95rem;">#<?php echo $order_id; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                            <span style="color: #475569; font-weight: 600; font-size: 0.9rem;"><?php echo __('order_pay_total'); ?>:</span>
                            <strong style="color: #16a34a; font-size: 1.1rem; font-weight: 800;">$<?php echo number_format($total_price, 2); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                            <span style="color: #475569; font-weight: 600; font-size: 0.9rem;"><?php echo __('order_pay_phone'); ?>:</span>
                            <strong style="color: #0f172a; font-size: 0.9rem;"><?php echo htmlspecialchars($phone); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                            <span style="color: #475569; font-weight: 600; font-size: 0.9rem;"><?php echo __('order_pay_address'); ?>:</span>
                            <strong style="color: #0f172a; font-size: 0.9rem; max-width: 240px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($address); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #475569; font-weight: 600; font-size: 0.9rem;"><?php echo __('order_pay_method'); ?>:</span>
                            <strong style="color: #2563eb; font-size: 0.9rem;">
                                <?php 
                                $pm = $_POST['payment_method'] ?? 'cod';
                                if ($pm === 'bank') echo __('payment_bank');
                                elseif ($pm === 'momo') echo __('payment_momo');
                                else echo __('payment_cod');
                                ?>
                            </strong>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="/Gym Manager/dashboards/profile.php" class="cta-btn" style="background: linear-gradient(135deg, #0062ff, #004ed8); color: white; border: none; font-size: 0.95rem; padding: 0.85rem 1.6rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,98,255,0.3); text-decoration: none; font-weight: 700;">
                            <i class="fas fa-box" style="margin-right: 8px;"></i><?php echo __('order_pay_view_history'); ?>
                        </a>
                        <button onclick="closeOrderSuccessModal()" style="background: #f1f5f9; color: #475569; border: none; padding: 0.85rem 1.5rem; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 0.95rem;">
                            <?php echo __('close'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <script>
            function closeOrderSuccessModal() {
                var m = document.getElementById('orderSuccessModal');
                if (m) m.style.display = 'none';
            }
            </script>
            <style>
            @keyframes modalPopIn {
                0% { opacity: 0; transform: scale(0.85) translateY(20px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }
            </style>

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
                            <img src="<?php echo htmlspecialchars(get_image_url($item['image'])); ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                            <div style="flex-grow: 1;">
                                <p style="color: var(--text-main); font-weight: 700; margin: 0;">
                                    <?php echo (isset($current_lang) && $current_lang === 'vi' && !empty($item['name_vi'])) ? htmlspecialchars($item['name_vi']) : htmlspecialchars($item['name']); ?>
                                    <?php if (!empty($item['variant'])): ?>
                                        <span style="color: var(--primary); font-size: 0.85rem;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <form action="/Gym Manager/dashboards/profile.php" method="POST" style="display: flex; gap: 10px; align-items: center; margin: 0; flex-grow: 1;">
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
            $total_price_vnd = (int)round($total_display_price * 25000);
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
                                <img src="<?php echo htmlspecialchars(get_image_url($item['image'])); ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
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
                        <span>$<?php echo number_format($total_display_price, 2); ?> <span style="font-size:0.95rem; color:var(--text-muted); font-weight:600;">(<?php echo number_format($total_price_vnd, 0, ',', '.'); ?>đ)</span></span>
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
                </div>

                <div style="background: #f8fafc; padding: 2rem; border-radius: 20px; margin-bottom: 2.5rem; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700;"><?php echo __('payment_method'); ?></h3>
                    <?php if (!empty($_SESSION['momo_error'])): ?>
                        <div style="background:#fdf4ff;border:1.5px solid #e879f9;color:#a21caf;padding:1rem;border-radius:12px;margin-bottom:1.2rem;font-weight:600;">
                            <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><?php echo htmlspecialchars($_SESSION['momo_error']); unset($_SESSION['momo_error']); ?>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem; color: var(--text-main); font-weight: 600;">
                        <!-- COD Option -->
                        <label id="lbl-cod" style="display: flex; gap: 1.2rem; align-items: center; cursor: pointer; padding: 12px 16px; border-radius: 12px; border: 2px solid transparent; transition: 0.3s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <input type="radio" name="payment_method" value="cod" checked style="width: 20px; height: 20px; accent-color: var(--primary);" onchange="highlightPayment()">
                            <span>🚚 <?php echo __('payment_cod'); ?></span>
                        </label>
                        <!-- MoMo Option -->
                        <label id="lbl-momo" style="display: flex; gap: 1.2rem; align-items: center; cursor: pointer; padding: 12px 16px; border-radius: 12px; border: 2px solid transparent; transition: 0.3s;" onmouseover="this.style.background='#fdf4ff'" onmouseout="this.style.background='transparent'">
                            <input type="radio" name="payment_method" value="momo" style="width: 20px; height: 20px; accent-color: #a21caf;" onchange="highlightPayment()">
                            <span style="display:flex;align-items:center;gap:10px;">
                                <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" onerror="this.onerror=null; this.src='https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-MoMo-Square.png';" style="width:32px;height:32px;border-radius:8px;object-fit:cover;" alt="MoMo">
                                <span><?php echo __('payment_momo'); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="button" class="btn-auth" id="btnCheckout" onclick="confirmCheckout()"><?php echo __('complete_order'); ?></button>
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
            <div style="margin-bottom: 8px; font-size: 0.95rem;"><strong><?php echo __('payment_method'); ?>:</strong> <span id="modal-payment-method" style="color: var(--primary); font-weight: 700;"></span></div>
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
function highlightPayment() {
    var isMomo = document.querySelector('input[name="payment_method"][value="momo"]').checked;
    var lblMomo = document.getElementById('lbl-momo');
    var lblCod  = document.getElementById('lbl-cod');
    if (lblMomo) lblMomo.style.borderColor = isMomo ? '#a21caf' : 'transparent';
    if (lblCod)  lblCod.style.borderColor  = !isMomo ? 'var(--primary)' : 'transparent';
}

function confirmCheckout() {
    var form = document.getElementById('checkoutForm');
    var phoneInput   = document.querySelector('input[name="phone"]');
    var addressInput = document.querySelector('input[name="address"]');
    var payMethod    = document.querySelector('input[name="payment_method"]:checked').value;

    if (!form.checkValidity()) {
        var invalidInput = form.querySelector(':invalid');
        if (invalidInput) {
            invalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            invalidInput.focus();
            invalidInput.style.borderColor = '#ef4444';
            invalidInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
            setTimeout(function() { invalidInput.style.borderColor = ''; invalidInput.style.boxShadow = ''; }, 2500);
        }
        form.reportValidity();
        return;
    }

    var phone   = phoneInput   ? phoneInput.value.trim()   : '';
    var address = addressInput ? addressInput.value.trim() : '';

    // ── MoMo Flow ──────────────────────────────────────────────
    if (payMethod === 'momo') {
        var btn = document.getElementById('btnCheckout');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kết nối MoMo...';

        fetch('/gymmanager/api/momo_pay_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone, address: address })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.payUrl) {
                window.location.href = data.payUrl;
            } else {
                btn.disabled = false;
                btn.innerHTML = '<?php echo addslashes(__('complete_order')); ?>';
                alert('❌ ' + (data.message || 'Lỗi kết nối MoMo'));
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<?php echo addslashes(__('complete_order')); ?>';
            alert('❌ Không thể kết nối đến máy chủ. Vui lòng thử lại.');
        });
        return;
    }

    // ── COD / Standard Flow ─────────────────────────────────────
    var paymentLabel = '<?php echo addslashes(__('payment_cod')); ?>';
    document.getElementById('modal-payment-method').innerText = paymentLabel;
    document.getElementById('modal-phone').innerText   = phone;
    document.getElementById('modal-address').innerText = address;
    document.getElementById('checkoutConfirmModal').style.display = 'flex';
}

function closeCheckoutModal() {
    document.getElementById('checkoutConfirmModal').style.display = 'none';
}
function submitCheckout() {
    var btn = event.target;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes(__('processing')); ?>...';
    }
    document.getElementById('checkoutForm').submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /Gym Manager/dashboards/admin.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

// Handle cart updates
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: /Gym Manager/pages/cart.php");
    exit();
}

if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: /Gym Manager/pages/cart.php");
    exit();
}

$order_msg = '';
$order_msg_type = 'success';

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

// Fetch active & recent orders for logged-in user
$user_orders = [];
if (isset($_SESSION['user_id'])) {
    $stmt_u_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt_u_orders->execute([$_SESSION['user_id']]);
    $user_orders = $stmt_u_orders->fetchAll();
}
?>

<section style="padding: 3rem 0; min-height: 80vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        
        <div class="section-title" style="text-align: center; margin-bottom: 2.5rem;">
            <h2 style="font-size: 2.2rem; font-weight: 900; color: var(--text-main); margin-bottom: 0.5rem;"><?php echo __('shopping_cart'); ?></h2>
            <div class="underline" style="width: 80px; height: 4px; background: var(--primary); margin: 0 auto; border-radius: 2px;"></div>
        </div>

        <?php if (!empty($order_msg)): ?>
            <div style="background: <?php echo $order_msg_type === 'success' ? '#dcfce7' : ($order_msg_type === 'info' ? '#eff6ff' : '#fee2e2'); ?>; color: <?php echo $order_msg_type === 'success' ? '#166534' : ($order_msg_type === 'info' ? '#1e40af' : '#991b1b'); ?>; padding: 1.2rem 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid <?php echo $order_msg_type === 'success' ? '#bbf7d0' : ($order_msg_type === 'info' ? '#bfdbfe' : '#fca5a5'); ?>; font-weight: 700; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
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
                    showGymXAlert(msg, title, icon);
                });
            </script>
        <?php endif; ?>

        <!-- Order Tracking Component (If Logged In & Has Orders) -->
        <?php if (!empty($user_orders)): ?>
            <div id="order-tracking" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 24px; padding: 2rem; color: white; margin-bottom: 3rem; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25); border: 1px solid rgba(255,255,255,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1.2rem;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 45px; height: 45px; background: linear-gradient(135deg, #0062ff, #3b82f6); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 6px 15px rgba(0,98,255,0.4);">
                            🚚
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #ffffff; letter-spacing: -0.3px;"><?php echo __('track_order'); ?></h3>
                            <p style="margin: 3px 0 0; font-size: 0.85rem; color: #94a3b8; font-weight: 600;"><?php echo __('est_delivery'); ?></p>
                        </div>
                    </div>
                    <span style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.3); padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; letter-spacing: 0.5px;">
                        ⚡ <?php echo __('express_delivery'); ?>
                    </span>
                </div>

                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($user_orders as $ord): 
                        $stmt_o_items = $pdo->prepare("SELECT oi.*, p.name, p.name_vi, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                        $stmt_o_items->execute([$ord['id']]);
                        $ord_items = $stmt_o_items->fetchAll();
                        $is_pending = ($ord['status'] === 'pending');
                        $is_completed = ($ord['status'] === 'completed');
                        $is_cancelled = ($ord['status'] === 'cancelled');
                    ?>
                        <div style="background: rgba(255,255,255,0.05); border-radius: 20px; padding: 1.5rem; border: 1px solid rgba(255,255,255,0.08); transition: transform 0.2s;">
                            <!-- Order Header -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem;">
                                <div>
                                    <span style="font-weight: 900; font-size: 1.1rem; color: #ffffff;"><?php echo __('order'); ?> #<?php echo $ord['id']; ?></span>
                                    <span style="font-size: 0.8rem; color: #94a3b8; margin-left: 10px;">(<?php echo date('d/m/Y H:i', strtotime($ord['created_at'])); ?>)</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if ($is_pending): ?>
                                        <span style="background: #f59e0b; color: #000; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                                            🚚 <?php echo __('order_status_delivering'); ?>
                                        </span>
                                    <?php elseif ($is_completed): ?>
                                        <span style="background: #10b981; color: #ffffff; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                            ✔ <?php echo __('status_completed'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #ef4444; color: #ffffff; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                            ✖ <?php echo __('cancelled_status'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stepper Timeline for Pending Orders -->
                            <?php if ($is_pending): ?>
                                <div style="background: rgba(15, 23, 42, 0.6); padding: 1.2rem; border-radius: 16px; margin-bottom: 1.2rem; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="display: flex; justify-content: space-between; position: relative; max-width: 700px; margin: 0 auto; padding: 10px 0;">
                                        <!-- Line background -->
                                        <div style="position: absolute; top: 25px; left: 10%; right: 10%; height: 4px; background: rgba(255,255,255,0.15); z-index: 1;"></div>
                                        <div style="position: absolute; top: 25px; left: 10%; width: 40%; height: 4px; background: linear-gradient(90deg, #10b981, #3b82f6); z-index: 2;"></div>

                                        <!-- Step 1 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: bold; font-size: 0.85rem; box-shadow: 0 0 10px rgba(16,185,129,0.5);">
                                                ✓
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700; color: #e2e8f0;"><?php echo __('step_placed'); ?></div>
                                        </div>

                                        <!-- Step 2 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: bold; font-size: 0.85rem; box-shadow: 0 0 15px rgba(59,130,246,0.8); animation: pulse 2s infinite;">
                                                🚚
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 800; color: #60a5fa;"><?php echo __('step_shipping'); ?></div>
                                        </div>

                                        <!-- Step 3 -->
                                        <div style="text-align: center; z-index: 3; flex: 1;">
                                            <div style="width: 34px; height: 34px; background: rgba(255,255,255,0.2); color: #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: bold; font-size: 0.85rem;">
                                                3
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 600; color: #94a3b8;"><?php echo __('step_completed'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Items Summary -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-bottom: 1.2rem;">
                                <?php foreach ($ord_items as $oi): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); padding: 8px 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                        <img src="<?php echo htmlspecialchars(get_image_url($oi['image'])); ?>" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                        <div style="overflow: hidden;">
                                            <div style="font-size: 0.85rem; font-weight: 700; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo (isset($current_lang) && $current_lang === 'vi' && !empty($oi['name_vi'])) ? htmlspecialchars($oi['name_vi']) : htmlspecialchars($oi['name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #94a3b8;">x<?php echo $oi['quantity']; ?> - $<?php echo number_format($oi['price'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Total & Action Controls -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 1rem;">
                                <div>
                                    <span style="font-size: 0.85rem; color: #94a3b8;"><?php echo __('total_payment'); ?>: </span>
                                    <strong style="font-size: 1.2rem; color: #10b981; margin-left: 5px;">$<?php echo number_format($ord['total_price'], 2); ?></strong>
                                    <span style="font-size: 0.75rem; color: #64748b; margin-left: 8px;">(<?php echo strtoupper($ord['payment_method']); ?>)</span>
                                </div>

                                <?php if ($is_pending): ?>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <form method="POST" style="margin: 0;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('confirm_order_prompt'); ?>', '<?php echo __('confirm_order_success'); ?>', '✅');">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="action_confirm_order" value="1">
                                            <button type="submit" name="action_confirm_order" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(16,185,129,0.3); transition: transform 0.2s;">
                                                <i class="fas fa-check-circle"></i> <?php echo __('confirm_delivery'); ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="margin: 0;" onsubmit="return confirmOrderCustom(event, this, '<?php echo __('cancel_order_prompt'); ?>', '<?php echo __('cancel_order'); ?>', '⚠️');">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="action_cancel_order" value="1">
                                            <button type="submit" name="action_cancel_order" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 10px 16px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                <i class="fas fa-times-circle"></i> <?php echo __('cancel_order'); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Shopping Cart Section -->
        <?php if (empty($cart_items)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 25px; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                <i class="fas fa-shopping-bag" style="font-size: 3.5rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-muted); font-size: 1.2rem; font-weight: 600; margin-bottom: 1.5rem;"><?php echo __('cart_empty'); ?></p>
                <a href="/Gym Manager/pages/shop.php" class="cta-btn" style="display: inline-block; padding: 0.9rem 2.2rem; text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 1rem;"><?php echo __('go_shopping'); ?></a>
            </div>
        <?php else: ?>
            <div class="admin-grid" style="grid-template-columns: 1fr 350px; gap: 2rem;">
                <div style="background: white; border-radius: 25px; padding: 2rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div class="table-responsive" style="margin-top: 0; border: none; box-shadow: none; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                                    <th style="padding: 1rem;"><?php echo __('product'); ?></th>
                                    <th style="padding: 1rem;"><?php echo __('price'); ?></th>
                                    <th style="padding: 1rem; text-align: center;"><?php echo __('quantity'); ?></th>
                                    <th style="padding: 1rem; text-align: right;"><?php echo __('subtotal'); ?></th>
                                    <th style="padding: 1rem; text-align: center;"><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 1rem; display: flex; align-items: center; gap: 1.2rem;">
                                        <img src="<?php echo htmlspecialchars(get_image_url($item['image'])); ?>" style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; background: white; padding: 2px; border: 1px solid var(--border);">
                                        <span style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </td>
                                    <td style="padding: 1rem; font-weight: 600;">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td style="padding: 1rem; font-weight: 600; text-align: center;">
                                        <div style="display: inline-flex; align-items: center; border: 1.5px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                            <button type="button" onclick="modifyCartQty('<?php echo $item['cart_key']; ?>', -1)" style="border: none; background: #f8fafc; width: 28px; height: 28px; font-weight: bold; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">-</button>
                                            <input type="number" id="cart_qty_val_<?php echo $item['cart_key']; ?>" value="<?php echo $item['qty']; ?>" min="1" max="99" onchange="modifyCartQty('<?php echo $item['cart_key']; ?>', 0, this.value)" style="width: 42px; height: 28px; text-align: center; border: none; font-weight: 800; font-size: 0.9rem; color: #0f172a; outline: none; background: transparent;">
                                            <button type="button" onclick="modifyCartQty('<?php echo $item['cart_key']; ?>', 1)" style="border: none; background: #f8fafc; width: 28px; height: 28px; font-weight: bold; cursor: pointer; color: #334155; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">+</button>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; font-weight: 800; color: var(--primary); text-align: right;">$<span id="cart_subtotal_<?php echo $item['cart_key']; ?>"><?php echo number_format($item['subtotal'], 2); ?></span></td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <a href="/Gym Manager/pages/cart.php?remove=<?php echo $item['cart_key']; ?>" style="color: #ef4444; font-weight: 700; text-decoration: none; font-size: 0.9rem;" title="<?php echo __('remove_from_cart'); ?>">
                                            <i class="fas fa-trash-alt"></i> <?php echo __('remove'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 1rem;">
                        <a href="/Gym Manager/pages/shop.php" style="color: var(--primary); font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                            <i class="fas fa-arrow-left"></i> <?php echo __('go_shopping'); ?>
                        </a>
                        <a href="/Gym Manager/pages/cart.php?clear_cart=1" style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 8px 16px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 6px;" onclick="return confirmLinkCustom(event, this, '<?php echo __('clear_cart_prompt'); ?>', '<?php echo __('clear_cart'); ?>', '🗑️');">
                            <i class="fas fa-trash-alt"></i> <?php echo __('clear_cart'); ?>
                        </a>
                    </div>
                </div>

                <div class="admin-sidebar" style="background: white; border-radius: 25px; padding: 2.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border); height: fit-content;">
                    <h3 style="margin-bottom: 2rem; color: var(--text-main); font-weight: 800; font-size: 1.5rem;"><?php echo __('cart_totals'); ?></h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.2rem; color: var(--text-muted);">
                        <span><?php echo __('subtotal'); ?>:</span>
                        <span style="font-weight: 600; color: var(--text-main);">$<span id="cart_subtotal_usd"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2.5rem; font-weight: 800; color: var(--primary); font-size: 1.4rem; border-top: 2px solid var(--bg-main); padding-top: 1.5rem;">
                        <span><?php echo __('total'); ?>:</span>
                        <span>$<span id="cart_total_usd"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    <a href="/Gym Manager/pages/checkout.php" class="btn-auth" style="display: block; text-align: center; text-decoration: none; padding: 1.1rem; border-radius: 14px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 20px rgba(0, 98, 255, 0.2);"><?php echo __('proceed_checkout'); ?></a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
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
            var subTotalUsd = document.getElementById('cart_subtotal_usd');
            if (subTotalUsd) subTotalUsd.innerText = data.total_formatted;
            var totalUsd = document.getElementById('cart_total_usd');
            if (totalUsd) totalUsd.innerText = data.total_formatted;

            var cartBadges = document.querySelectorAll('.cart-count-badge, .nav-cart-count');
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

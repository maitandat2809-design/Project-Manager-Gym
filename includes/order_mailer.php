<?php
// ============================================================
// Order Mailer: Sends MoMo Payment Confirmation Emails
// Sends to both Customer (User) and Gym Managers
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

if (!function_exists('sendMoMoOrderSuccessEmail')) {
    /**
     * Send email notification to User and Managers upon successful MoMo payment
     *
     * @param PDO $pdo
     * @param int $order_id
     * @return bool
     */
    function sendMoMoOrderSuccessEmail($pdo, $order_id) {
        $order_id = intval($order_id);
        if ($order_id <= 0) return false;

        try {
            // Check order and prevent duplicate emails
            $stmt = $pdo->prepare("
                SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) return false;

            // If already sent, skip to avoid spam
            if (!empty($order['momo_mail_sent'])) {
                return true;
            }

            // Mark as sent in DB
            $upd = $pdo->prepare("UPDATE orders SET momo_mail_sent = 1 WHERE id = ?");
            $upd->execute([$order_id]);

            // Fetch order items
            $stmt_items = $pdo->prepare("
                SELECT oi.*, p.name, p.name_vi, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt_items->execute([$order_id]);
            $items = $stmt_items->fetchAll();

            $curr_lang = $_SESSION['lang'] ?? 'en';
            $is_vi_mailer = false;

            $customer_name  = $order['customer_name'] ?? ($is_vi_mailer ? 'Khách Hàng' : 'Customer');
            $customer_email = $order['customer_email'] ?? '';
            $phone          = !empty($order['phone']) ? $order['phone'] : ($order['customer_phone'] ?? '');
            $address        = $order['address'] ?? '';
            $total_usd      = number_format($order['total_price'], 2);
            $total_vnd      = number_format((int)round($order['total_price'] * 25000), 0, ',', '.');
            $created_at     = date('d/m/Y H:i', strtotime($order['created_at']));

            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $tracking_url = "http://{$host}/Gym%20Manager/pages/cart.php#order-tracking";
            $manager_url  = "http://{$host}/Gym%20Manager/dashboards/manager.php?page=orders";

            // Items HTML table
            $items_rows = '';
            foreach ($items as $it) {
                $p_name = ($is_vi_mailer && !empty($it['name_vi'])) ? $it['name_vi'] : $it['name'];
                $var = !empty($it['variant']) ? " <span style='color:#64748b;font-size:0.85em;'>({$it['variant']})</span>" : '';
                $item_total = number_format((int)round($it['price'] * $it['quantity'] * 25000), 0, ',', '.') . ' VND';
                $items_rows .= "
                    <tr style='border-bottom: 1px solid #e2e8f0;'>
                        <td style='padding: 12px 10px; font-weight: 600; color: #0f172a;'>
                            {$p_name}{$var}
                        </td>
                        <td style='padding: 12px 10px; text-align: center; color: #475569; font-weight: 600;'>
                            x{$it['quantity']}
                        </td>
                        <td style='padding: 12px 10px; text-align: right; font-weight: 700; color: #7e22ce;'>
                            {$item_total}
                        </td>
                    </tr>
                ";
            }

            // ── 1. SEND EMAIL TO CUSTOMER ──────────────────────────────
            if (!empty($customer_email)) {
                try {
                    $mailUser = new PHPMailer(true);
                    $mailUser->CharSet = 'UTF-8';
                    $mailUser->isSMTP();
                    $mailUser->Host       = 'smtp.gmail.com';
                    $mailUser->SMTPAuth   = true;
                    $mailUser->Username   = 'lamtuoi155@gmail.com';     
                    $mailUser->Password   = 'hpst uazz wdsq sbnf';   
                    $mailUser->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mailUser->Port       = 587;

                    $mailUser->setFrom('lamtuoi155@gmail.com', 'GymX Fitness Center');
                    $mailUser->addAddress($customer_email, $customer_name);
                    $mailUser->isHTML(true);
                    $mailUser->Subject = $is_vi_mailer 
                        ? "💜 GymX: Xác Nhận Đã Thanh Toán MoMo Thành Công - Đơn Hàng #{$order_id}"
                        : "💜 GymX: MoMo Payment Confirmed - Order #{$order_id}";

                    $userBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 620px; margin: auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;'>
                        <div style='background: linear-gradient(135deg, #a21caf 0%, #7e22ce 100%); padding: 30px 25px; text-align: center; color: #ffffff;'>
                            <div style='font-size: 3rem; margin-bottom: 10px;'>💜</div>
                            <h2 style='margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.3px;'>" . ($is_vi_mailer ? "Thanh Toán MoMo Thành Công!" : "MoMo Payment Successful!") . "</h2>
                            <p style='margin: 8px 0 0; opacity: 0.9; font-size: 0.95rem;'>" . ($is_vi_mailer ? "Cảm ơn bạn đã mua sắm tại GymX. Đơn hàng của bạn đang được đóng gói và vận chuyển!" : "Thank you for shopping with GymX. Your order is being packed and prepared for delivery!") . "</p>
                        </div>

                        <div style='padding: 25px;'>
                            <p style='font-size: 1rem; color: #334155; margin-top: 0;'>
                                " . ($is_vi_mailer ? "Xin chào <strong>{$customer_name}</strong>, GymX đã nhận được thanh toán qua <strong>Ví MoMo</strong> cho đơn hàng <strong>#{$order_id}</strong>." : "Hello <strong>{$customer_name}</strong>, GymX has received your payment via <strong>MoMo Wallet</strong> for order <strong>#{$order_id}</strong>.") . "
                            </p>

                            <!-- Order Summary Box -->
                            <div style='background: #fdf4ff; border: 1.5px solid #f0abfc; border-radius: 16px; padding: 18px; margin: 20px 0;'>
                                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                                    <span style='color: #64748b; font-size: 0.9rem;'>" . ($is_vi_mailer ? "Mã đơn hàng:" : "Order ID:") . "</span>
                                    <strong style='color: #a21caf;'>#{$order_id}</strong>
                                </div>
                                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                                    <span style='color: #64748b; font-size: 0.9rem;'>" . ($is_vi_mailer ? "Thời gian đặt:" : "Order Time:") . "</span>
                                    <strong style='color: #0f172a;'>{$created_at}</strong>
                                </div>
                                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                                    <span style='color: #64748b; font-size: 0.9rem;'>" . ($is_vi_mailer ? "Phương thức:" : "Payment Method:") . "</span>
                                    <strong style='color: #7e22ce;'>MoMo (" . ($is_vi_mailer ? "Đã thanh toán" : "Paid") . ")</strong>
                                </div>
                                <div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>
                                    <span style='color: #64748b; font-size: 0.9rem;'>" . ($is_vi_mailer ? "Trạng thái:" : "Status:") . "</span>
                                    <strong style='color: #d97706;'>🚚 " . ($is_vi_mailer ? "Đang vận chuyển (2 - 3 ngày)" : "Shipping (2 - 3 days)") . "</strong>
                                </div>
                                <div style='display: flex; justify-content: space-between; border-top: 1px dashed #e9d5ff; padding-top: 10px; margin-top: 8px;'>
                                    <span style='font-weight: 700; color: #0f172a;'>" . ($is_vi_mailer ? "Tổng tiền thanh toán:" : "Total Paid:") . "</span>
                                    <strong style='font-size: 1.25rem; color: #a21caf;'>{$total_vnd} VND</strong>
                                </div>
                            </div>

                            <!-- Delivery Address -->
                            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px; margin-bottom: 20px; font-size: 0.9rem; color: #334155;'>
                                <strong style='color: #0f172a;'>📍 " . ($is_vi_mailer ? "Thông tin nhận hàng:" : "Delivery Address:") . "</strong><br>
                                <span style='color: #64748b;'>" . ($is_vi_mailer ? "Địa chỉ:" : "Address:") . "</span> {$address}<br>
                                <span style='color: #64748b;'>" . ($is_vi_mailer ? "Số điện thoại:" : "Phone:") . "</span> {$phone}
                            </div>

                            <!-- Products Table -->
                            <h4 style='margin: 0 0 10px; color: #0f172a;'>" . ($is_vi_mailer ? "Chi tiết sản phẩm đã mua:" : "Purchased Items:") . "</h4>
                            <table style='width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 25px;'>
                                <thead>
                                    <tr style='background: #f1f5f9; text-align: left; font-size: 0.82rem; color: #64748b; text-transform: uppercase;'>
                                        <th style='padding: 10px;'>" . ($is_vi_mailer ? "Sản phẩm" : "Product") . "</th>
                                        <th style='padding: 10px; text-align: center;'>" . ($is_vi_mailer ? "Số lượng" : "Qty") . "</th>
                                        <th style='padding: 10px; text-align: right;'>" . ($is_vi_mailer ? "Thành tiền" : "Subtotal") . "</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$items_rows}
                                </tbody>
                            </table>

                            <!-- CTA Button -->
                            <div style='text-align: center; margin: 30px 0 10px;'>
                                <a href='{$tracking_url}' style='background: linear-gradient(135deg, #a21caf 0%, #7e22ce 100%); color: #ffffff; padding: 14px 30px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; text-decoration: none; display: inline-block; box-shadow: 0 6px 20px rgba(162,28,175,0.35);'>
                                    🚚 " . ($is_vi_mailer ? "Theo Dõi Quá Trình Vận Chuyển Đơn Hàng" : "Track Order Status & Delivery") . "
                                </a>
                            </div>

                            <p style='text-align: center; font-size: 0.82rem; color: #94a3b8; margin-top: 25px;'>
                                " . ($is_vi_mailer ? "Khi nhận được hàng, vui lòng vào trang Theo dõi đơn hàng để xác nhận và đánh giá sản phẩm nhé! ❤️" : "Once received, please visit your Order Tracking page to confirm delivery and leave a review! ❤️") . "
                            </p>
                        </div>
                    </div>
                    ";

                    $mailUser->Body = $userBody;
                    $mailUser->send();
                } catch (Exception $eMailUser) {
                    error_log("MoMo Order Mail to User Error: " . $eMailUser->getMessage());
                }
            }

            // ── 2. SEND EMAIL TO MANAGERS ──────────────────────────────
            try {
                $stmt_mgrs = $pdo->query("SELECT name, email FROM users WHERE role IN ('manager', 'admin')");
                $managers = $stmt_mgrs->fetchAll();

                $has_manager = false;
                $mailMgr = new PHPMailer(true);
                $mailMgr->CharSet = 'UTF-8';
                $mailMgr->isSMTP();
                $mailMgr->Host       = 'smtp.gmail.com';
                $mailMgr->SMTPAuth   = true;
                $mailMgr->Username   = 'lamtuoi155@gmail.com';     
                $mailMgr->Password   = 'hpst uazz wdsq sbnf';   
                $mailMgr->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mailMgr->Port       = 587;

                $mailMgr->setFrom('lamtuoi155@gmail.com', 'GymX System Alert');

                foreach ($managers as $mgr) {
                    if (!empty($mgr['email']) && $mgr['email'] !== $customer_email) {
                        $mailMgr->addAddress($mgr['email'], $mgr['name']);
                        $has_manager = true;
                    }
                }

                if ($has_manager) {
                    $mailMgr->isHTML(true);
                    $mailMgr->Subject = $is_vi_mailer
                        ? "💜 [ĐÃ THANH TOÁN MOMO] Đơn Hàng Mới #{$order_id} - Khách: {$customer_name}"
                        : "💜 [MOMO PAID] New Order #{$order_id} - Customer: {$customer_name}";

                    $mgrBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 620px; margin: auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;'>
                        <div style='background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding: 25px; text-align: center; color: #ffffff;'>
                            <h2 style='margin: 0; font-size: 1.4rem; font-weight: 800;'>" . ($is_vi_mailer ? "💜 Đơn Hàng Mới Đã Thanh Toán Qua MoMo!" : "💜 New Order Paid via MoMo!") . "</h2>
                            <p style='margin: 6px 0 0; opacity: 0.9; font-size: 0.9rem;'>" . ($is_vi_mailer ? "Khách hàng đã chuyển tiền thành công qua MoMo. Vui lòng đóng gói và xuất kho giao hàng." : "Payment received successfully via MoMo. Please pack items and dispatch.") . "</p>
                        </div>

                        <div style='padding: 22px;'>
                            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px; margin-bottom: 20px; font-size: 0.9rem;'>
                                <strong>" . ($is_vi_mailer ? "Thông tin khách hàng:" : "Customer Information:") . "</strong><br>
                                • " . ($is_vi_mailer ? "Tên:" : "Name:") . " <strong>{$customer_name}</strong> ({$customer_email})<br>
                                • " . ($is_vi_mailer ? "SĐT:" : "Phone:") . " <strong>{$phone}</strong><br>
                                • " . ($is_vi_mailer ? "Địa chỉ nhận hàng:" : "Address:") . " <strong>{$address}</strong><br>
                                • " . ($is_vi_mailer ? "Mã đơn hàng:" : "Order ID:") . " <strong style='color:#a21caf;'>#{$order_id}</strong><br>
                                • " . ($is_vi_mailer ? "Doanh thu:" : "Revenue:") . " <strong style='color:#16a34a;font-size:1.1em;'>{$total_vnd} VND</strong><br>
                                • " . ($is_vi_mailer ? "Phương thức:" : "Method:") . " <strong style='color:#7e22ce;'>MoMo (" . ($is_vi_mailer ? "ĐÃ NHẬN TIỀN" : "PAID") . ")</strong><br>
                                • " . ($is_vi_mailer ? "Trạng thái:" : "Status:") . " <strong style='color:#d97706;'>Shipping (pending)</strong>
                            </div>

                            <h4 style='margin: 0 0 10px; color: #0f172a;'>" . ($is_vi_mailer ? "Danh sách sản phẩm cần đóng gói:" : "Items to Pack:") . "</h4>
                            <table style='width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 22px;'>
                                <thead>
                                    <tr style='background: #f1f5f9; text-align: left; font-size: 0.82rem; color: #64748b; text-transform: uppercase;'>
                                        <th style='padding: 10px;'>" . ($is_vi_mailer ? "Sản phẩm" : "Product") . "</th>
                                        <th style='padding: 10px; text-align: center;'>" . ($is_vi_mailer ? "Số lượng" : "Qty") . "</th>
                                        <th style='padding: 10px; text-align: right;'>" . ($is_vi_mailer ? "Giá" : "Price") . "</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$items_rows}
                                </tbody>
                            </table>

                            <div style='text-align: center; margin: 25px 0 10px;'>
                                <a href='{$manager_url}' style='background: #0f172a; color: #ffffff; padding: 12px 26px; border-radius: 12px; font-weight: 800; font-size: 0.92rem; text-decoration: none; display: inline-block;'>
                                    📦 " . ($is_vi_mailer ? "Mở Bảng Quản Trị Đơn Hàng" : "Open Orders Dashboard") . "
                                </a>
                            </div>
                        </div>
                    </div>
                    ";

                    $mailMgr->Body = $mgrBody;
                    $mailMgr->send();
                }
            } catch (Exception $eMailMgr) {
                error_log("MoMo Order Mail to Manager Error: " . $eMailMgr->getMessage());
            }

            return true;
        } catch (Exception $e) {
            error_log("sendMoMoOrderSuccessEmail Error: " . $e->getMessage());
            return false;
        }
    }
}

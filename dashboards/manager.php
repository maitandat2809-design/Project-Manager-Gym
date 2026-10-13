<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: /Gym Manager/index.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';
?>
<!-- html2canvas & jsPDF for Image and PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function openInvoiceModal(id) {
    const modal = document.getElementById('invoice-modal-' + id);
    if (modal) modal.style.display = 'flex';
}
function closeInvoiceModal(id) {
    const modal = document.getElementById('invoice-modal-' + id);
    if (modal) modal.style.display = 'none';
}
function downloadInvoicePDF(id) {
    const card = document.getElementById('invoice-card-' + id);
    if (!card) return;
    
    html2canvas(card, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const { jsPDF } = window.jspdf;
        const imgData = canvas.toDataURL('image/jpeg', 0.98);
        const imgWidth = 210;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        const pdf = new jsPDF('p', 'mm', [imgWidth, imgHeight]);
        pdf.addImage(imgData, 'JPEG', 0, 0, imgWidth, imgHeight);
        pdf.save(`Invoice_#${id}_GymManager.pdf`);
    }).catch(err => {
        console.error("PDF Invoice export error:", err);
        if (typeof showGymXAlert === 'function') {
            showGymXAlert("Could not export invoice PDF.");
        } else {
            console.error("Could not export invoice PDF.");
        }
    });
}
function downloadInvoiceImage(id, format) {
    const card = document.getElementById('invoice-card-' + id);
    if (!card) return;
    
    html2canvas(card, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `Invoice_#${id}_GymManager.${format}`;
        link.href = canvas.toDataURL(`image/${format === 'jpg' ? 'jpeg' : 'png'}`, 0.98);
        link.click();
    }).catch(err => {
        console.error("Canvas export error:", err);
        if (typeof showGymXAlert === 'function') {
            showGymXAlert("Could not export invoice image.");
        } else {
            console.error("Could not export invoice image.");
        }
    });
}
function captureElementFull(element) {
    return new Promise((resolve, reject) => {
        const clone = element.cloneNode(true);
        
        clone.style.width = '1450px';
        clone.style.maxWidth = 'none';
        clone.style.position = 'absolute';
        clone.style.left = '-9999px';
        clone.style.top = '0';
        clone.style.zIndex = '-9999';
        clone.style.background = '#ffffff';

        // Copy canvas content (Chart.js)
        const origCanvases = element.querySelectorAll('canvas');
        const cloneCanvases = clone.querySelectorAll('canvas');
        origCanvases.forEach((origCanvas, i) => {
            if (cloneCanvases[i]) {
                const destCtx = cloneCanvases[i].getContext('2d');
                cloneCanvases[i].width = origCanvas.width;
                cloneCanvases[i].height = origCanvas.height;
                destCtx.drawImage(origCanvas, 0, 0);
            }
        });

        // Make all table containers 100% visible and unconstrained
        const scrollables = clone.querySelectorAll('.table-responsive, table, div');
        scrollables.forEach(el => {
            el.style.overflow = 'visible';
            el.style.overflowX = 'visible';
            el.style.maxWidth = 'none';
        });

        // Hide action buttons in clone for clean report
        const actionBtns = clone.querySelectorAll('.invoice-export-btn, button');
        actionBtns.forEach(btn => btn.style.display = 'none');

        document.body.appendChild(clone);

        setTimeout(() => {
            html2canvas(clone, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                width: 1450,
                windowWidth: 1450
            }).then(canvas => {
                document.body.removeChild(clone);
                resolve(canvas);
            }).catch(err => {
                if (document.body.contains(clone)) document.body.removeChild(clone);
                reject(err);
            });
        }, 150);
    });
}
function downloadYearlyReportPDF(year, evt) {
    const reportArea = document.getElementById('yearly-report-export-area');
    if (!reportArea) return;
    
    let btn = evt ? evt.currentTarget : null;
    let originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
        btn.disabled = true;
    }
    
    captureElementFull(reportArea).then(canvas => {
        const { jsPDF } = window.jspdf;
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });

        const imgWidth = 297;
        const pageHeight = 210;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save(`GymManager_Yearly_Report_${year}.pdf`);

        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }).catch(err => {
        console.error("Report PDF export error:", err);
        if (typeof showGymXAlert === 'function') {
            showGymXAlert("Could not export report PDF.");
        }
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}
function downloadMonthlyReportPDF(month, year, evt) {
    const reportArea = document.getElementById('monthly-report-export-area');
    if (!reportArea) return;
    
    let btn = evt ? evt.currentTarget : null;
    let originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
        btn.disabled = true;
    }
    
    captureElementFull(reportArea).then(canvas => {
        const { jsPDF } = window.jspdf;
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });

        const imgWidth = 297;
        const pageHeight = 210;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save(`GymManager_Monthly_Report_${month}_${year}.pdf`);

        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }).catch(err => {
        console.error("Report PDF export error:", err);
        if (typeof showGymXAlert === 'function') {
            showGymXAlert("Could not export report PDF.");
        }
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}
</script>
<?php

$page = $_GET['page'] ?? 'dashboard';
if ($page == 'memberships') {
    header("Location: /Gym Manager/dashboards/manager.php?page=member_subscriptions");
    exit();
}
$message = '';
$current_lang = $_SESSION['lang'] ?? 'en';

// Handle Member Subscriptions Actions (Add / Update / Delete)
if ($page == 'member_subscriptions' && isset($_POST['add_subscription'])) {
    $user_id        = intval($_POST['user_id'] ?? 0);
    $plan_id        = intval($_POST['plan_id'] ?? 0);
    $start_date     = $_POST['start_date'] ?? date('Y-m-d');
    $end_date       = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $price_paid     = floatval($_POST['price_paid'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $status         = $_POST['status'] ?? 'active';
    $note           = trim($_POST['note'] ?? '');

    if ($user_id <= 0 || $plan_id <= 0) {
        $message = "Error: Please select a valid user and membership plan.";
    } else {
        try {
            if (isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] == '1') {
                // Preserve membership purchase history by expiring rather than deleting
                $stmt_arch_old = $pdo->prepare("UPDATE member_subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'");
                $stmt_arch_old->execute([$user_id]);
            }

            $stmt_ins = $pdo->prepare("
                INSERT INTO member_subscriptions 
                (user_id, plan_id, start_date, end_date, price_paid, payment_method, status, note, admin_seen) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt_ins->execute([$user_id, $plan_id, $start_date, $end_date, $price_paid, $payment_method, $status, $note]);
            $message = "Membership subscription created successfully!";
        } catch (Exception $e) {
            $message = "Error creating membership subscription: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && isset($_POST['update_subscription'])) {
    $sub_id         = intval($_POST['sub_id'] ?? 0);
    $plan_id        = intval($_POST['plan_id'] ?? 0);
    $start_date     = $_POST['start_date'];
    $end_date       = $_POST['end_date'];
    $price_paid     = floatval($_POST['price_paid'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $status         = $_POST['status'] ?? 'active';
    $note           = trim($_POST['note'] ?? '');

    if ($sub_id <= 0 || $plan_id <= 0) {
        $message = "Error: Invalid subscription or plan.";
    } else {
        try {
            $stmt_upd = $pdo->prepare("
                UPDATE member_subscriptions 
                SET plan_id = ?, start_date = ?, end_date = ?, price_paid = ?, payment_method = ?, status = ?, note = ?, admin_seen = 1 
                WHERE id = ?
            ");
            $stmt_upd->execute([$plan_id, $start_date, $end_date, $price_paid, $payment_method, $status, $note, $sub_id]);
            $message = "Membership subscription updated successfully!";
        } catch (Exception $e) {
            $message = "Error updating subscription: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && (isset($_GET['approve_sub']) || isset($_POST['do_approve_sub']))) {
    $sub_id = intval($_POST['sub_id'] ?? $_GET['approve_sub'] ?? 0);
    if ($sub_id > 0) {
        try {
            $stmt_app = $pdo->prepare("
                UPDATE member_subscriptions ms 
                JOIN membership_plans mp ON ms.plan_id = mp.id 
                SET ms.status = 'active', 
                    ms.admin_seen = 1,
                    ms.pt_sessions_total = CASE 
                        WHEN ms.pt_sessions_total > 0 THEN ms.pt_sessions_total 
                        ELSE (mp.pt_sessions + COALESCE(ms.bonus_pt, 0)) 
                    END 
                WHERE ms.id = ?
            ");
            $stmt_app->execute([$sub_id]);

            // Send notification email to user
            try {
                $stmt_info = $pdo->prepare("
                    SELECT ms.*, u.name as user_name, u.email as user_email, mp.name as plan_name, mp.name_vi as plan_name_vi 
                    FROM member_subscriptions ms 
                    JOIN users u ON ms.user_id = u.id 
                    JOIN membership_plans mp ON ms.plan_id = mp.id 
                    WHERE ms.id = ?
                ");
                $stmt_info->execute([$sub_id]);
                $sub_info = $stmt_info->fetch();

                if ($sub_info && !empty($sub_info['user_email'])) {
                    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lamtuoi155@gmail.com';
                    $mail->Password   = 'hpst uazz wdsq sbnf';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Membership System');

                    $mail->addAddress($sub_info['user_email'], $sub_info['user_name']);
                    $mail->isHTML(true);
                    
                    // Membership activation emails are always sent in English.
                    $is_vi_email = false;
                    $p_name_display = ($is_vi_email && !empty($sub_info['plan_name_vi'])) ? $sub_info['plan_name_vi'] : $sub_info['plan_name'];
                    $is_ren = !empty($sub_info['is_renewal']);
                    $mail_title = $is_vi_email
                        ? ($is_ren ? "🎉 [GymX Tri Ân] Thẻ Thành Viên Gia Hạn (" . $p_name_display . ") Đã Kích Hoạt!" : "🎉 [GymX] Thẻ Thành Viên " . $p_name_display . " Của Bạn Đã Được Kích Hoạt!")
                        : ($is_ren ? "🎉 [GymX Loyalty] Membership Renewal (" . $p_name_display . ") Activated!" : "🎉 [GymX] Your Membership (" . $p_name_display . ") Has Been Activated!");
                    $mail->Subject = $mail_title;

                    $user_body = "<div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 20px; background: #ffffff;'>";
                    $user_body .= "<div style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 15px; text-align: center;'>";
                    $user_body .= "<h2 style='margin: 0; font-size: 1.5rem;'>🎉 " . ($is_vi_email ? ($is_ren ? "Gia Hạn Thẻ Thành Công & Kích Hoạt Đặc Quyền!" : "Kích Hoạt Thẻ Thành Viên Thành Công!") : ($is_ren ? "Membership Renewed & Perks Activated!" : "Membership Activated Successfully!")) . "</h2>";
                    $user_body .= "<p style='margin: 5px 0 0; opacity: 0.95;'>" . ($is_vi_email ? "Chào " . htmlspecialchars($sub_info['user_name']) . ", thẻ thành viên của bạn đã sẵn sàng sử dụng." : "Hello " . htmlspecialchars($sub_info['user_name']) . ", your membership is active and ready to use.") . "</p></div>";
                    $user_body .= "<div style='background: #f0fdf4; padding: 18px; border-radius: 15px; margin: 20px 0; border: 1px solid #86efac;'>";
                    $user_body .= "<strong>" . ($is_vi_email ? "Gói Thẻ:" : "Plan:") . "</strong> " . htmlspecialchars($p_name_display) . "<br>";
                    $user_body .= "<strong>" . ($is_vi_email ? "Thời Hạn:" : "Duration:") . "</strong> " . date('d/m/Y', strtotime($sub_info['start_date'])) . " " . ($is_vi_email ? "đến" : "to") . " " . date('d/m/Y', strtotime($sub_info['end_date'])) . "<br>";
                    if (!empty($sub_info['bonus_days'])) {
                        $user_body .= "<strong>" . ($is_vi_email ? "Ngày Tập Tặng Thêm:" : "Bonus Days:") . "</strong> <span style='color: #15803d; font-weight: bold;'>+" . $sub_info['bonus_days'] . ($is_vi_email ? " ngày tập miễn phí tri ân hội viên" : " free bonus days") . "</span><br>";
                    }
                    if ($sub_info['pt_sessions_total'] > 0) {
                        $user_body .= "<strong>" . ($is_vi_email ? "Số Buổi PT 1:1 Đi Kèm:" : "1:1 PT Sessions:") . "</strong> " . $sub_info['pt_sessions_total'] . ($is_vi_email ? " buổi" : " sessions") . (!empty($sub_info['bonus_pt']) ? ($is_vi_email ? " (Đã bao gồm +{$sub_info['bonus_pt']} buổi tặng thêm)" : " (Includes +{$sub_info['bonus_pt']} bonus sessions)") : "") . "<br>";
                    }
                    if ($is_ren) {
                        $user_body .= "<strong>" . ($is_vi_email ? "Đặc Quyền Hội Viên Thân Thiết:" : "Loyal Member Perks:") . "</strong> " . ($is_vi_email ? "Tặng Bình nước GymX Shaker VIP Pro + 2 Guest Pass mời bạn bè + Giảm 10% tại GymX Shop" : "Free GymX Shaker VIP Pro + 2 Guest Passes + 10% OFF at GymX Shop") . "<br>";
                    }
                    $user_body .= "<strong>" . ($is_vi_email ? "Trạng Thái:" : "Status:") . "</strong> <span style='color: #15803d; font-weight: bold;'>ACTIVE</span></div>";
                    $user_body .= "<p style='text-align: center; color: #64748b;'>" . ($is_vi_email ? "Cảm ơn bạn đã tiếp tục đồng hành cùng GymX Center! Chúc bạn có những giờ phút tập luyện hiệu quả và bứt phá thể lực." : "Thank you for training with GymX Center! Wishing you effective workouts and peak fitness.") . "</p></div>";

                    $mail->Body = $user_body;
                    $mail->send();
                }
            } catch (Exception $mailErr) {
                error_log("Approval email error: " . $mailErr->getMessage());
            }

            $message = ($current_lang === 'en')
                ? "🎉 Membership card #" . $sub_id . " activated successfully!"
                : "🎉 Đã xác nhận kích hoạt thẻ thành viên #" . $sub_id . " thành công!";
        } catch (Exception $e) {
            $message = ($current_lang === 'en')
                ? "Error activating membership card: " . $e->getMessage()
                : "Lỗi khi kích hoạt thẻ: " . $e->getMessage();
        }
    }
}

if ($page == 'member_subscriptions' && (isset($_GET['reject_sub']) || isset($_POST['do_reject_sub']))) {
    $sub_id = intval($_POST['sub_id'] ?? $_GET['reject_sub']);
    $reason = trim($_POST['reject_reason'] ?? '');
    try {
        $stmt_rej = $pdo->prepare("UPDATE member_subscriptions SET status = 'cancelled', admin_seen = 1 WHERE id = ?");
        $stmt_rej->execute([$sub_id]);
        $message = ($current_lang === 'en')
            ? "🔴 Rejected / cancelled membership card subscription #" . $sub_id . "!" . ($reason ? " (Reason: " . htmlspecialchars($reason) . ")" : "")
            : "🔴 Đã từ chối / hủy yêu cầu đăng ký thẻ thành viên #" . $sub_id . "!" . ($reason ? " (Lý do: " . htmlspecialchars($reason) . ")" : "");
    } catch (Exception $e) {
        $message = ($current_lang === 'en')
            ? "Error cancelling membership: " . $e->getMessage()
            : "Lỗi khi hủy thẻ thành viên: " . $e->getMessage();
    }
}

if ($page == 'member_subscriptions' && isset($_GET['delete_sub'])) {
    $sub_id = intval($_GET['delete_sub']);
    try {
        $stmt_del = $pdo->prepare("DELETE FROM member_subscriptions WHERE id = ?");
        $stmt_del->execute([$sub_id]);
        $message = "Membership subscription deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting subscription: " . $e->getMessage();
    }
}

// Handle Actions (Add/Update/Delete)
if ($page == 'products' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    if ($price >= 1000) {
        $price = round($price / 25000, 2);
    }
    $stock = $_POST['stock'];
    $desc = $_POST['description'] ?: 'Premium quality fitness equipment/supplement.';
    $category = $_POST['category'] ?? 'supplement';
    $has_weight = isset($_POST['has_weight_variants']) ? 1 : 0;
    
    // Handle Image Upload
    $image_path = 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?q=80&w=2070&auto=format&fit=crop'; // Default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/products/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('prod_') . '.' . $file_extension;
        $target_file = $upload_dir_fs . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = '/Gym Manager/assets/img/products/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, description, image, category, has_weight_variants) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $stock, $desc, $image_path, $category, $has_weight]);
    $message = "Product added successfully!";
}

if ($page == 'products' && isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    if ($price >= 1000) {
        $price = round($price / 25000, 2);
    }
    $stock = $_POST['stock'];
    $desc = $_POST['description'];
    $category = $_POST['category'] ?? 'supplement';
    $has_weight = isset($_POST['has_weight_variants']) ? 1 : 0;
    
    // Handle Image Update
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/products/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('prod_') . '.' . $file_extension;
        $target_file = $upload_dir_fs . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $new_image_path = '/Gym Manager/assets/img/products/' . $file_name;
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, image = ?, category = ?, has_weight_variants = ? WHERE id = ?");
            $stmt->execute([$name, $price, $stock, $desc, $new_image_path, $category, $has_weight, $id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, category = ?, has_weight_variants = ? WHERE id = ?");
        $stmt->execute([$name, $price, $stock, $desc, $category, $has_weight, $id]);
    }
    $message = "Product updated successfully!";
}

if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
}

if ($page == 'users' && isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    try {
        $pdo->beginTransaction();
        
        // Update user role
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $uid]);
        
        // Management of coaches table entry
        if ($new_role == 'coach') {
            $stmt = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
            $stmt->execute([$uid]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO coaches (user_id, specialty, hourly_rate, bio) VALUES (?, 'Fitness', 20.00, 'New coach instructor')");
                $stmt->execute([$uid]);
            }
        }
        
        $pdo->commit();
        $message = "User role updated successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

if ($page == 'users' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'user';
    $phone = trim($_POST['phone'] ?? '');

    // Check email duplicate
    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $check_e->execute([$email]);

    // Check phone duplicate
    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != ''");
        $check_p_stmt->execute([$phone]);
        $check_p = $check_p_stmt->fetch();
    }

    if ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã tồn tại trong hệ thống!" : "⚠️ Error: Email '$email' is already registered!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã tồn tại trong hệ thống!" : "⚠️ Error: Phone number '$phone' is already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role" . ($phone !== '' ? ", phone" : "") . ") VALUES (?, ?, ?, ?" . ($phone !== '' ? ", ?" : "") . ")");
        $params = [$name, $email, $password, $role];
        if ($phone !== '') $params[] = $phone;
        $stmt->execute($params);
        $message = ($current_lang === 'vi') ? "Tạo người dùng thành công!" : "User created successfully!";
    }
}

if ($page == 'users' && isset($_POST['update_user_details'])) {
    $uid = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone'] ?? '');

    // Check Email duplicate for another user
    $check_e = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
    $check_e->execute([$email, $uid]);

    // Check Phone duplicate for another user
    $check_p = null;
    if ($phone !== '') {
        $check_p_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != '' AND id != ?");
        $check_p_stmt->execute([$phone, $uid]);
        $check_p = $check_p_stmt->fetch();
    }

    if ($check_e->fetch()) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Email '$email' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Email '$email' is already registered to another account!";
    } elseif ($check_p) {
        $message = ($current_lang === 'vi') ? "⚠️ Lỗi: Số điện thoại '$phone' đã được sử dụng bởi một tài khoản khác!" : "⚠️ Error: Phone number '$phone' is already registered to another account!";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?" . ($phone !== '' ? ", phone = ?" : "") . " WHERE id = ?");
        $params = [$name, $email, $role];
        if ($phone !== '') $params[] = $phone;
        $params[] = $uid;
        $stmt->execute($params);
        $message = ($current_lang === 'vi') ? "Cập nhật thông tin người dùng thành công!" : "User updated successfully!";
    }
}

if (isset($_GET['delete_user'])) {
    $uid = $_GET['delete_user'];
    if ($uid == $_SESSION['user_id']) {
        $message = "Error: You cannot delete yourself!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $message = "User deleted successfully!";
    }
}

// Booking Actions (Add / Update / Delete / Change Status)
if ($page == 'bookings' && isset($_POST['add_booking'])) {
    $user_id = $_POST['user_id'];
    $coach_id = $_POST['coach_id'];
    $booking_date = $_POST['booking_date'];
    $status = $_POST['status'] ?? 'pending';
    
    try {
        $requested_time = strtotime($booking_date);
        $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
        $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);

        // 1. Coach slot conflict
        $stmt_c_chk = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
        $stmt_c_chk->execute([$coach_id, $start_buffer, $end_buffer]);
        
        // 2. User slot conflict
        $stmt_u_chk = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
        $stmt_u_chk->execute([$user_id, $start_buffer, $end_buffer]);

        if ($stmt_c_chk->fetch()) {
            $message = ($current_lang === 'vi')
                ? "⚠️ Lỗi: HLV này đã có lịch dạy vào khung giờ này (trùng trong vòng 60 phút). Vui lòng chọn thời gian hoặc HLV khác!"
                : "⚠️ Error: This coach is already booked for another session at this time (overlapping 60min slot).";
        } elseif ($stmt_u_chk->fetch()) {
            $message = ($current_lang === 'vi')
                ? "⚠️ Lỗi: Học viên đã có lịch tập với HLV khác vào cùng khung giờ này (trùng trong vòng 60 phút). Không thể đăng ký 2 HLV cùng 1 khung giờ!"
                : "⚠️ Error: The client already has a session scheduled with another coach at this time slot.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, coach_id, booking_date, status, coach_seen, user_seen) VALUES (?, ?, ?, ?, 0, 0)");
            $stmt->execute([$user_id, $coach_id, $booking_date, $status]);
            $message = "Booking created successfully!";
            
            // Optional: PHPMailer to both User and Coach
            try {
                $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmt_u->execute([$user_id]);
                $usr = $stmt_u->fetch();

                $stmt_c = $pdo->prepare("SELECT u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                $stmt_c->execute([$coach_id]);
                $cch = $stmt_c->fetch();

                if ($usr && $usr['email']) {
                    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lamtuoi155@gmail.com';     
                    $mail->Password   = 'hpst uazz wdsq sbnf';   
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                    $mail->addAddress($usr['email'], $usr['name']);
                    if ($cch && $cch['email']) {
                        $mail->addCC($cch['email'], $cch['name']);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "🆕 New GymX Booking Scheduled by Admin";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $mail_body .= "<h2 style='color: #0062ff;'>New Appointment</h2>";
                    $mail_body .= "<p>An administrator has scheduled a new training session for you.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                    $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($usr['name']) . "<br>";
                    $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($cch['name']) . "<br>";
                    $mail_body .= "<strong>Time:</strong> " . date('l, d/m/Y - H:i', strtotime($booking_date)) . "<br>";
                    $mail_body .= "<strong>Status:</strong> " . strtoupper($status) . "<br>";
                    $mail_body .= "</div>";
                    $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                    
                    $mail->Body = $mail_body;
                    $mail->send();
                }
            } catch (Exception $mailEx) {
                error_log("Mail error during booking insert: " . $mailEx->getMessage());
            }
        }
    } catch (Exception $e) {
        $message = "Error creating booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $user_id = $_POST['user_id'];
    $coach_id = $_POST['coach_id'];
    $booking_date = $_POST['booking_date'];
    $status = $_POST['status'];
    $coach_message = $_POST['coach_message'] ?? '';
    
    try {
        $requested_time = strtotime($booking_date);
        $start_buffer = date('Y-m-d H:i:s', $requested_time - 3599);
        $end_buffer = date('Y-m-d H:i:s', $requested_time + 3599);

        $has_conflict = false;
        if ($status !== 'cancelled') {
            // 1. Coach slot conflict
            $stmt_c_chk = $pdo->prepare("SELECT id FROM bookings WHERE coach_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $stmt_c_chk->execute([$coach_id, $booking_id, $start_buffer, $end_buffer]);
            
            // 2. User slot conflict
            $stmt_u_chk = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND id != ? AND status != 'cancelled' AND booking_date BETWEEN ? AND ?");
            $stmt_u_chk->execute([$user_id, $booking_id, $start_buffer, $end_buffer]);

            if ($stmt_c_chk->fetch()) {
                $has_conflict = true;
                $message = ($current_lang === 'vi')
                    ? "⚠️ Lỗi: HLV này đã có lịch dạy vào khung giờ này (trùng trong vòng 60 phút). Vui lòng chọn thời gian hoặc HLV khác!"
                    : "⚠️ Error: This coach is already booked for another session at this time (overlapping 60min slot).";
            } elseif ($stmt_u_chk->fetch()) {
                $has_conflict = true;
                $message = ($current_lang === 'vi')
                    ? "⚠️ Lỗi: Học viên đã có lịch tập với HLV khác vào cùng khung giờ này (trùng trong vòng 60 phút). Không thể đăng ký 2 HLV cùng 1 khung giờ!"
                    : "⚠️ Error: The client already has a session scheduled with another coach at this time slot.";
            }
        }

        if (!$has_conflict) {
            $stmt = $pdo->prepare("UPDATE bookings SET user_id = ?, coach_id = ?, booking_date = ?, status = ?, coach_message = ?, coach_seen = 0, user_seen = 0 WHERE id = ?");
            $stmt->execute([$user_id, $coach_id, $booking_date, $status, $coach_message, $booking_id]);
            $message = "Booking updated successfully!";
        }
        
        // Optional: PHPMailer rescheduling notice
        try {
            $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt_u->execute([$user_id]);
            $usr = $stmt_u->fetch();

            $stmt_c = $pdo->prepare("SELECT u.name, u.email FROM coaches c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $stmt_c->execute([$coach_id]);
            $cch = $stmt_c->fetch();

            if ($usr && $usr['email']) {
                require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lamtuoi155@gmail.com';     
                $mail->Password   = 'hpst uazz wdsq sbnf';   
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                $mail->addAddress($usr['email'], $usr['name']);
                if ($cch && $cch['email']) {
                    $mail->addCC($cch['email'], $cch['name']);
                }

                $mail->isHTML(true);
                $mail->Subject = "🔄 GymX Booking Updated";
                
                $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                $mail_body .= "<h2 style='color: #f59e0b;'>Booking Rescheduled</h2>";
                $mail_body .= "<p>An administrator has updated your training session details.</p>";
                $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($usr['name']) . "<br>";
                $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($cch['name']) . "<br>";
                $mail_body .= "<strong>New Time:</strong> " . date('l, d/m/Y - H:i', strtotime($booking_date)) . "<br>";
                $mail_body .= "<strong>Status:</strong> " . strtoupper($status) . "<br>";
                if ($coach_message) {
                    $mail_body .= "<strong>Notes:</strong> " . htmlspecialchars($coach_message) . "<br>";
                }
                $mail_body .= "</div>";
                $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                
                $mail->Body = $mail_body;
                $mail->send();
            }
        } catch (Exception $mailEx) {
            error_log("Mail error during booking update: " . $mailEx->getMessage());
        }
    } catch (Exception $e) {
        $message = "Error updating booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_GET['delete_booking'])) {
    $booking_id = $_GET['delete_booking'];
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $message = "Booking deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting booking: " . $e->getMessage();
    }
}

if ($page == 'bookings' && isset($_GET['change_status']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    $new_status = $_GET['change_status'];
    
    if (in_array($new_status, ['pending', 'confirmed', 'cancelled'])) {
        try {
            if ($new_status === 'cancelled') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = ?, cancel_reason = 'Cancelled by Manager', coach_seen = 0, user_seen = 0 WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET status = ?, coach_seen = 0, user_seen = 0 WHERE id = ?");
            }
            $stmt->execute([$new_status, $booking_id]);
            $message = "Booking status updated to " . strtoupper($new_status) . " successfully!";
            
            // Send status change email
            try {
                // Fetch details
                $stmt_b = $pdo->prepare("SELECT b.booking_date, u.name as user_name, u.email as user_email, c_u.name as coach_name, c_u.email as coach_email 
                                         FROM bookings b 
                                         JOIN users u ON b.user_id = u.id 
                                         JOIN coaches c ON b.coach_id = c.id 
                                         JOIN users c_u ON c.user_id = c_u.id 
                                         WHERE b.id = ?");
                $stmt_b->execute([$booking_id]);
                $b_info = $stmt_b->fetch();
                
                if ($b_info && $b_info['user_email']) {
                    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'lamtuoi155@gmail.com';     
                    $mail->Password   = 'hpst uazz wdsq sbnf';   
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('lamtuoi155@gmail.com', 'GymX Admin System');
                    $mail->addAddress($b_info['user_email'], $b_info['user_name']);
                    if ($b_info['coach_email']) {
                        $mail->addCC($b_info['coach_email'], $b_info['coach_name']);
                    }

                    $mail->isHTML(true);
                    $color = ($new_status == 'confirmed') ? '#10b981' : (($new_status == 'cancelled') ? '#ef4444' : '#64748b');
                    $mail->Subject = "🔔 GymX Booking Status Update";
                    
                    $mail_body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 20px;'>";
                    $mail_body .= "<h2 style='color: " . $color . ";'>Booking Status Updated</h2>";
                    $mail_body .= "<p>An administrator has updated the status of your training session.</p>";
                    $mail_body .= "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>";
                    $mail_body .= "<strong>Client:</strong> " . htmlspecialchars($b_info['user_name']) . "<br>";
                    $mail_body .= "<strong>Coach:</strong> " . htmlspecialchars($b_info['coach_name']) . "<br>";
                    $mail_body .= "<strong>Time:</strong> " . date('l, d/m/Y - H:i', strtotime($b_info['booking_date'])) . "<br>";
                    $mail_body .= "<strong>New Status:</strong> <span style='color: " . $color . "; font-weight: bold;'>" . strtoupper($new_status) . "</span><br>";
                    $mail_body .= "</div>";
                    $mail_body .= "<p>Please log in to the system to view details.</p></div>";
                    
                    $mail->Body = $mail_body;
                    $mail->send();
                }
            } catch (Exception $mailEx) {
                error_log("Mail error during status change: " . $mailEx->getMessage());
            }
        } catch (Exception $e) {
            $message = "Error updating booking status: " . $e->getMessage();
        }
    }
}

// Order Management Actions for Manager
if ($page == 'orders' && isset($_GET['change_order_status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = $_GET['change_order_status'];
    
    if (in_array($new_status, ['pending', 'completed', 'cancelled'])) {
        try {
            $stmt_o = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt_o->execute([$order_id]);
            $old_status = $stmt_o->fetchColumn();
            
            if ($old_status) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_seen = 1 WHERE id = ?");
                $stmt->execute([$new_status, $order_id]);
                
                // If changing to cancelled from pending/completed, restore stock
                if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $o_items = $stmt_items->fetchAll();
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ?, sales_count = GREATEST(0, sales_count - ?) WHERE id = ?");
                    foreach ($o_items as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                    }
                }
                
                // If un-cancelling (cancelled -> completed/pending), deduct stock
                if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
                    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmt_items->execute([$order_id]);
                    $o_items = $stmt_items->fetchAll();
                    $stmt_stock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?), sales_count = sales_count + ? WHERE id = ?");
                    foreach ($o_items as $item) {
                        $stmt_stock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                    }
                }
                
                $pdo->commit();
                $message = "Order #$order_id status updated to " . strtoupper($new_status) . " successfully!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error updating order status: " . $e->getMessage();
        }
    }
}

if ($page == 'orders' && isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
        $pdo->commit();
        $message = "Order #$order_id deleted successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "Error deleting order: " . $e->getMessage();
    }
}

// Equipment Management Actions for Manager
if ($page == 'equipment' && isset($_POST['add_equipment'])) {
    $name = trim($_POST['name'] ?? '');
    $discipline = $_POST['discipline'] ?? 'gym';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $warranty_months = intval($_POST['warranty_months'] ?? 12);
    $warranty_expiry_date = !empty($_POST['warranty_expiry_date']) 
        ? $_POST['warranty_expiry_date'] 
        : date('Y-m-d', strtotime("+{$warranty_months} months", strtotime($purchase_date)));
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) 
        ? $_POST['next_maintenance_date'] 
        : date('Y-m-d', strtotime('+3 months', strtotime($purchase_date)));
    $notes = trim($_POST['notes'] ?? '');
    $image_path = '/Gym Manager/assets/img/equipment/treadmills.png';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir_fs = __DIR__ . '/../assets/img/equipment/';
        if (!is_dir($upload_dir_fs)) {
            mkdir($upload_dir_fs, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = 'eq_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir_fs . $file_name)) {
            $image_path = '/Gym Manager/assets/img/equipment/' . $file_name;
        }
    } elseif (!empty($_POST['custom_image_url'])) {
        $image_path = trim($_POST['custom_image_url']);
    }

    if (empty($name) || empty($description)) {
        $message = ($current_lang === 'vi') ? "Lỗi: Vui lòng nhập tên và mô tả thiết bị." : "Error: Equipment name and description are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO equipment 
                (name, discipline, description, status, image, purchase_date, warranty_months, warranty_expiry_date, next_maintenance_date, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $discipline, $description, $status, $image_path,
                $purchase_date, $warranty_months, $warranty_expiry_date, $next_maintenance_date, $notes
            ]);
            $new_eq_id = $pdo->lastInsertId();
            $_GET['details_id'] = $new_eq_id;
            $message = ($current_lang === 'vi') ? "Thêm mới thiết bị và hồ sơ bảo hành thành công!" : "Equipment and warranty details added successfully!";
        } catch (Exception $e) {
            $message = "Error adding equipment: " . $e->getMessage();
        }
    }
}

if ($page == 'equipment' && isset($_POST['update_equipment'])) {
    $eq_id = intval($_POST['equipment_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $discipline = $_POST['discipline'] ?? 'gym';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_months = intval($_POST['warranty_months'] ?? 12);
    $warranty_expiry_date = !empty($_POST['warranty_expiry_date']) ? $_POST['warranty_expiry_date'] : null;
    if ($purchase_date && empty($warranty_expiry_date)) {
        $warranty_expiry_date = date('Y-m-d', strtotime("+{$warranty_months} months", strtotime($purchase_date)));
    }
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
    $notes = trim($_POST['notes'] ?? '');

    if ($eq_id <= 0 || empty($name) || empty($description)) {
        $message = ($current_lang === 'vi') ? "Lỗi: Thông tin thiết bị không hợp lệ." : "Error: Invalid equipment details.";
    } else {
        try {
            $new_image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_fs = __DIR__ . '/../assets/img/equipment/';
                if (!is_dir($upload_dir_fs)) {
                    mkdir($upload_dir_fs, 0777, true);
                }
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'eq_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir_fs . $file_name)) {
                    $new_image_path = '/Gym Manager/assets/img/equipment/' . $file_name;
                }
            } elseif (!empty($_POST['custom_image_url'])) {
                $new_image_path = trim($_POST['custom_image_url']);
            }

            if ($new_image_path) {
                $stmt = $pdo->prepare("
                    UPDATE equipment 
                    SET name = ?, discipline = ?, description = ?, status = ?, image = ?, 
                        purchase_date = ?, warranty_months = ?, warranty_expiry_date = ?, next_maintenance_date = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $discipline, $description, $status, $new_image_path,
                    $purchase_date, $warranty_months, $warranty_expiry_date, $next_maintenance_date, $notes,
                    $eq_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE equipment 
                    SET name = ?, discipline = ?, description = ?, status = ?, 
                        purchase_date = ?, warranty_months = ?, warranty_expiry_date = ?, next_maintenance_date = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $discipline, $description, $status,
                    $purchase_date, $warranty_months, $warranty_expiry_date, $next_maintenance_date, $notes,
                    $eq_id
                ]);
            }
            $_GET['details_id'] = $eq_id;
            $message = ($current_lang === 'vi') ? "Cập nhật thiết bị và thông tin bảo hành thành công!" : "Equipment and warranty details updated successfully!";
        } catch (Exception $e) {
            $message = "Error updating equipment: " . $e->getMessage();
        }
    }
}

if ($page == 'equipment' && isset($_POST['log_maintenance'])) {
    $eq_id = intval($_POST['equipment_id'] ?? 0);
    $maint_note = trim($_POST['maintenance_note'] ?? '');
    $next_interval = intval($_POST['next_interval_months'] ?? 3);
    $new_next_date = date('Y-m-d', strtotime("+{$next_interval} months"));

    if ($eq_id > 0) {
        try {
            $log_entry = "\n[" . date('d/m/Y H:i') . "] Bảo dưỡng: " . ($maint_note ?: "Kiểm tra kỹ thuật định kỳ đạt chuẩn.");
            $stmt = $pdo->prepare("
                UPDATE equipment 
                SET status = 'active',
                    next_maintenance_date = ?,
                    notes = CONCAT(COALESCE(notes, ''), ?)
                WHERE id = ?
            ");
            $stmt->execute([$new_next_date, $log_entry, $eq_id]);
            $_GET['details_id'] = $eq_id;
            $message = ($current_lang === 'vi') 
                ? "Đã ghi nhận bảo trì thành công! Kỳ bảo hành/bảo trì kế tiếp: " . date('d/m/Y', strtotime($new_next_date))
                : "Maintenance logged successfully! Next maintenance: " . date('d/m/Y', strtotime($new_next_date));
        } catch (Exception $e) {
            $message = "Error logging maintenance: " . $e->getMessage();
        }
    }
}

if ($page == 'equipment' && isset($_GET['delete_equipment'])) {
    $eq_id = intval($_GET['delete_equipment']);
    try {
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->execute([$eq_id]);
        $message = ($current_lang === 'vi') ? "Đã xóa thiết bị thành công!" : "Equipment deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting equipment: " . $e->getMessage();
    }
}
?>

<section class="admin-section">
    <div class="admin-header">
        <h1>Manager Dashboard</h1>
        <div class="auth-btns">
            <span style="color: var(--text-muted); margin-right: 1rem;">Hello Manager, <strong><?php echo $_SESSION['user_name']; ?></strong></span>
        </div>
    </div>

    <div class="admin-grid">
        <div class="admin-sidebar">
            <ul>
                <li><a href="/Gym Manager/dashboards/manager.php?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>"><?php echo __("overview"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="<?php echo in_array($page, ['member_subscriptions', 'memberships']) ? 'active' : ''; ?>"><?php echo __("membership_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=products" class="<?php echo $page == 'products' ? 'active' : ''; ?>"><?php echo __("product_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=orders" class="<?php echo $page == 'orders' ? 'active' : ''; ?>"><?php echo __("manage_orders"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=bookings" class="<?php echo $page == 'bookings' ? 'active' : ''; ?>"><?php echo __("booking_management"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=equipment" class="<?php echo $page == 'equipment' ? 'active' : ''; ?>">Equipment Management</a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=yearly_report" class="<?php echo $page == 'yearly_report' ? 'active' : ''; ?>"><?php echo __("yearly_report"); ?></a></li>
                <li><a href="/Gym Manager/dashboards/manager.php?page=monthly_report" class="<?php echo $page == 'monthly_report' ? 'active' : ''; ?>"><?php echo __("monthly_report"); ?></a></li>
            </ul>
        </div>

        <div class="admin-content" style="min-width: 0;">
            <?php if($message): ?>
                <p style="color: <?php echo (stripos($message, 'Lỗi') !== false || stripos($message, 'Error') !== false) ? '#ff4444' : '#00C851'; ?>; margin-bottom: 1rem; font-weight: 600;"><?php echo $message; ?></p>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const msgText = <?php echo json_encode($message); ?>;
                        const lower = msgText.toLowerCase();
                        let title = <?php echo json_encode(__('notification')); ?>;
                        let icon = 'ℹ️';
                        if (lower.includes('lỗi') || lower.includes('error')) {
                            title = <?php echo json_encode(__('error_notification')); ?>;
                            icon = '❌';
                        } else if (lower.includes('completed') || lower.includes('thành công') || lower.includes('nhận hàng')) {
                            title = <?php echo json_encode(__('order_confirmed_success')); ?>;
                            icon = '✅';
                        } else if (lower.includes('cancelled') || lower.includes('hủy')) {
                            title = <?php echo json_encode(__('order_cancelled_success')); ?>;
                            icon = '🗑️';
                        }
                        showGymXAlert(msgText, title, icon);
                    });
                </script>
            <?php endif; ?>

            <?php if ($page == 'dashboard'): ?>
                <h2><?php echo __("system_overview"); ?></h2>
                <div class="grid" style="margin-top: 2rem;">
                    <div style="background: white; padding: 2.5rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: var(--primary); text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;"><?php echo __("total_users"); ?></h3>
                        <p style="font-size: 3rem; font-weight: 800; color: var(--text-main);">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>
                        </p>
                    </div>
                    <div style="background: white; padding: 2.5rem; border-radius: 25px; text-align: center; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <h3 style="color: var(--primary); text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;">Total Orders</h3>
                        <p style="font-size: 3rem; font-weight: 800; color: var(--text-main);">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); ?>
                        </p>
                    </div>
                </div>

            <?php elseif ($page == 'products'): 
                $edit_prod = null;
                if (isset($_GET['edit_product'])) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt->execute([$_GET['edit_product']]);
                    $edit_prod = $stmt->fetch();
                }

                // Calculate Product Stock Metrics for Real-time Manager Monitoring
                $prods_all = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
                $total_products_cnt = count($prods_all);
                $total_stock_units = 0;
                $total_sales_cnt = 0;
                $low_stock_cnt = 0;

                foreach ($prods_all as $pr_item) {
                    $stk_val = (int)($pr_item['stock'] ?? 0);
                    $total_stock_units += $stk_val;
                    $total_sales_cnt += (int)($pr_item['sales_count'] ?? 0);
                    if ($stk_val <= 5) {
                        $low_stock_cnt++;
                    }
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo __("product_management"); ?></h2>
                </div>

                <!-- Product Stock Metrics Dashboard -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin: 1.5rem 0;">
                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Tổng sản phẩm' : 'Total Products'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;"><?php echo $total_products_cnt; ?></div>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Tổng tồn kho' : 'Total Warehouse Stock'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #16a34a;"><?php echo number_format($total_stock_units); ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'món' : 'units'; ?></span></div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Đã bán (Khách mua)' : 'Total Items Sold'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #d97706;"><?php echo number_format($total_sales_cnt); ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'sản phẩm' : 'sold'; ?></span></div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.25rem 1.5rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?php echo ($current_lang === 'vi') ? 'Cảnh báo tồn kho' : 'Low Stock Alert'; ?></div>
                            <div style="font-size: 1.4rem; font-weight: 900; color: #dc2626;"><?php echo $low_stock_cnt; ?> <span style="font-size: 0.8rem; font-weight: 600; color: #475569;"><?php echo ($current_lang === 'vi') ? 'mặt hàng (≤5)' : 'items (≤5)'; ?></span></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($edit_prod): ?>
                <!-- Edit Product Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="product_id" value="<?php echo $edit_prod['id']; ?>">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_prod['name']); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá Bán (VNĐ)' : 'Price (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price" value="<?php echo (int)round($edit_prod['price'] * 25000); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("stock"); ?></label>
                            <input type="number" name="stock" value="<?php echo $edit_prod['stock']; ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Category</label>
                            <select name="category" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="supplement" <?php echo ($edit_prod['category'] ?? '') == 'supplement' ? 'selected' : ''; ?>>Supplement</option>
                                <option value="accessory" <?php echo ($edit_prod['category'] ?? '') == 'accessory' ? 'selected' : ''; ?>>Accessory</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="has_weight_variants" value="1" id="edit_has_weight" style="width: 20px; height: 20px;" <?php echo !empty($edit_prod['has_weight_variants']) ? 'checked' : ''; ?>>
                        <label for="edit_has_weight" style="font-weight: 600; cursor: pointer;">Enable Weight Options (Grams, KG)</label>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Product Image</label>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <?php if ($edit_prod['image']): ?>
                                <img src="<?php echo get_image_url($edit_prod['image']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;">
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: #f1f5f9; border-radius: 10px; width: 100%;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" required style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"><?php echo htmlspecialchars($edit_prod['description']); ?></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_product" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo __("update_product"); ?></button>
                        <a href="/Gym Manager/dashboards/manager.php?page=products" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Product Form -->
                <form method="POST" enctype="multipart/form-data" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Product Name</label>
                            <input type="text" name="name" required placeholder="e.g. Whey Protein">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo ($current_lang === 'vi') ? 'Giá Bán (VNĐ)' : 'Price (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price" required placeholder="<?php echo ($current_lang === 'vi') ? 'Ví dụ: 250000' : 'e.g. 250000'; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("stock"); ?></label>
                            <input type="number" name="stock" required placeholder="10">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Category</label>
                            <select name="category" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="supplement">Supplement</option>
                                <option value="accessory">Accessory</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="has_weight_variants" value="1" id="add_has_weight" style="width: 20px; height: 20px;">
                        <label for="add_has_weight" style="font-weight: 600; cursor: pointer;">Enable Weight Options (Grams, KG)</label>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;">Product Image</label>
                        <input type="file" name="image" accept="image/*" required style="padding: 0.8rem; background: #f1f5f9; border-radius: 10px; width: 100%;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600;">Description</label>
                        <textarea name="description" placeholder="Describe the product..." style="width: 100%; padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; outline: none; min-height: 100px;"></textarea>
                    </div>
                    <button type="submit" name="add_product" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo __("add_product"); ?></button>
                </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th><?php echo __("price"); ?></th>
                                <th><?php echo ($current_lang === 'vi') ? 'Số Lượng Tồn Kho' : 'Stock Quantity'; ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($prods_all as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><img src="<?php echo get_image_url($p['image']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <?php if ($current_lang === 'vi' && !empty($p['name_vi'])): ?>
                                        <div style="font-size: 0.78rem; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($p['name_vi']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: #7e22ce;"><?php echo number_format((int)round($p['price'] * 25000), 0, ',', '.'); ?>đ</strong></td>
                                <td>
                                    <?php 
                                    $stk = (int)$p['stock'];
                                    $sales = (int)($p['sales_count'] ?? 0);
                                    if ($stk > 10): ?>
                                        <span style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-check-circle"></i> <?php echo ($current_lang === 'vi') ? "Còn tồn: {$stk}" : "In Stock: {$stk}"; ?>
                                        </span>
                                    <?php elseif ($stk > 0): ?>
                                        <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde047; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-exclamation-circle"></i> <?php echo ($current_lang === 'vi') ? "Sắp hết: Chỉ còn {$stk}" : "Low Stock: {$stk}"; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-times-circle"></i> <?php echo ($current_lang === 'vi') ? "HẾT HÀNG (0)" : "OUT OF STOCK (0)"; ?>
                                        </span>
                                    <?php endif; ?>
                                    <div style="font-size: 0.78rem; color: #475569; font-weight: 700; margin-top: 5px;">
                                        🛒 <?php echo ($current_lang === 'vi') ? "Đã bán: {$sales} SP" : "Sold: {$sales} units"; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/manager.php?page=products&edit_product=<?php echo $p['id']; ?>" class="action-btn btn-edit">Edit</a>
                                        <a href="/Gym Manager/dashboards/manager.php?page=products&delete_product=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn xóa sản phẩm này?' : 'Confirm delete this product?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Sản Phẩm' : 'Delete Product'; ?>', '🗑️');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'users'):  
                if ($_SESSION['role'] !== 'admin') {
                    echo "<script>window.location.href='manager.php';</script>";
                    exit();
                }

                $edit_u = null;
                if (isset($_GET['edit_user'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_GET['edit_user']]);
                    $edit_u = $stmt->fetch();
                }
            ?>
                <h2>User Permissions Management</h2>

                <!-- Add New User Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main);">Add New User</h3>
                    <input type="hidden" name="role" value="user">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Full Name</label>
                            <input type="text" name="name" required placeholder="User Name" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("email"); ?></label>
                            <input type="email" name="email" required placeholder="email@example.com" style="background: #f1f5f9;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Password</label>
                            <input type="password" name="password" required placeholder="******" style="background: #f1f5f9;">
                        </div>
                        <button type="submit" name="add_user" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 1.8rem;">Add User</button>
                    </div>
                </form>

                <?php if ($edit_u): ?>
                    <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 2px solid var(--primary);">
                        <input type="hidden" name="user_id" value="<?php echo $edit_u['id']; ?>">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_u['name']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_u['email']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600;">System Role</label>
                                <select name="role" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                    <option value="user" <?php echo $edit_u['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="coach" <?php echo $edit_u['role'] == 'coach' ? 'selected' : ''; ?>><?php echo __("coach"); ?></option>
                                    <option value="manager" <?php echo $edit_u['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $edit_u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="update_user_details" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">Update User</button>
                            <a href="/Gym Manager/dashboards/manager.php?page=users" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th><?php echo __("email"); ?></th>
                                <th><?php echo __("status"); ?></th>
                                <th><?php echo __("role"); ?></th>
                                <th><?php echo __("actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
                            foreach($all_users as $u): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--primary); font-size: 0.8rem;">
                                            <?php echo strtoupper($u['name'][0]); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 5px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">
                                        <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%;"></span> Active
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $u['role'] == 'admin' ? '#f0fdf4' : ($u['role'] == 'manager' ? '#fff7ed' : ($u['role'] == 'coach' ? '#eff6ff' : '#f8fafc')); 
                                    ?>; color: <?php 
                                        echo $u['role'] == 'admin' ? '#22c55e' : ($u['role'] == 'manager' ? '#f97316' : ($u['role'] == 'coach' ? '#3b82f6' : '#64748b')); 
                                    ?>; padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                        <?php echo strtoupper($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell-flex">
                                        <a href="/Gym Manager/dashboards/manager.php?page=users&edit_user=<?php echo $u['id']; ?>" class="action-btn btn-edit" style="font-size: 0.75rem; padding: 8px 12px;">Edit</a>
                                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="/Gym Manager/dashboards/manager.php?page=users&delete_user=<?php echo $u['id']; ?>" class="action-btn btn-delete" style="font-size: 0.75rem; padding: 8px 12px;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn xóa người dùng này? Thao tác không thể hoàn tác.' : 'WARNING: Are you sure you want to delete this user? This action cannot be undone.'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Người Dùng' : 'Delete User'; ?>', '⚠️');">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'orders'): 
                $stat_ord_total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                $stat_ord_pending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
                $stat_ord_completed = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
                $stat_ord_cancelled = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();

                $ord_search = trim($_GET['search'] ?? '');
                $ord_status = $_GET['status_filter'] ?? '';

                $ord_query = "SELECT o.*, u.name as user_name, u.email as user_email 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id";
                $ord_params = [];
                $ord_conds = [];

                if ($ord_search !== '') {
                    $ord_conds[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id = ? OR o.phone LIKE ?)";
                    $ord_params[] = "%$ord_search%";
                    $ord_params[] = "%$ord_search%";
                    $ord_params[] = intval($ord_search);
                    $ord_params[] = "%$ord_search%";
                }

                if ($ord_status !== '' && in_array($ord_status, ['pending', 'completed', 'cancelled'])) {
                    $ord_conds[] = "o.status = ?";
                    $ord_params[] = $ord_status;
                }

                if (!empty($ord_conds)) {
                    $ord_query .= " WHERE " . implode(" AND ", $ord_conds);
                }

                $ord_query .= " ORDER BY o.created_at DESC";
                $stmt_ord = $pdo->prepare($ord_query);
                $stmt_ord->execute($ord_params);
                $mgr_orders = $stmt_ord->fetchAll();
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("manage_orders"); ?></h2>
                </div>

                <!-- Orders Statistics -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Total Orders</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_ord_total; ?></p>
                    </div>
                    <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5;">
                        <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Pending (2-3 Days)</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_ord_pending; ?></p>
                    </div>
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7;">
                        <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Completed</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_ord_completed; ?></p>
                    </div>
                    <div style="background: #fef2f2; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #fee2e2;">
                        <h4 style="color: #991b1b; font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Cancelled</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #dc2626; margin-top: 5px;"><?php echo $stat_ord_cancelled; ?></p>
                    </div>
                </div>

                <!-- Search & Filter Bar -->
                <div style="background: white; padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid var(--border); box-shadow: var(--shadow-premium);">
                    <form method="GET" action="/Gym Manager/dashboards/manager.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin: 0;">
                        <input type="hidden" name="page" value="orders">
                        <input type="text" name="search" placeholder="Search by customer, phone, or Order ID..." value="<?php echo htmlspecialchars($ord_search); ?>" style="flex: 1; min-width: 250px; padding: 0.8rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); outline: none; background: #f8fafc;">
                        <select name="status_filter" style="padding: 0.8rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); outline: none; background: #f8fafc; font-weight: 600;">
                            <option value="">-- All Statuses --</option>
                            <option value="pending" <?php echo $ord_status === 'pending' ? 'selected' : ''; ?>>Pending (Delivering 2-3 days)</option>
                            <option value="completed" <?php echo $ord_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $ord_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn-auth" style="padding: 0.8rem 1.8rem; font-size: 0.9rem; margin: 0; width: auto; border-radius: 12px;">Filter</button>
                        <?php if ($ord_search !== '' || $ord_status !== ''): ?>
                            <a href="/Gym Manager/dashboards/manager.php?page=orders" style="color: var(--text-muted); font-size: 0.9rem; text-decoration: underline; font-weight: 600;">Reset Filters</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive" style="background: white; border-radius: 25px; padding: 1.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 1.2rem; border-radius: 15px 0 0 15px; text-align: left;">ORDER ID</th>
                                <th style="padding: 1.2rem; text-align: left;">DATE</th>
                                <th style="padding: 1.2rem; text-align: left;">CUSTOMER INFO</th>
                                <th style="padding: 1.2rem; text-align: left;">ADDRESS / PHONE</th>
                                <th style="padding: 1.2rem; text-align: left;">TOTAL & METHOD</th>
                                <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;">DETAILS & ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mgr_orders)): ?>
                                <tr><td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted); font-weight: 600;">No orders found matching criteria.</td></tr>
                            <?php else: ?>
                                <?php foreach ($mgr_orders as $o): 
                                    $stmt_o_items = $pdo->prepare("SELECT oi.*, p.name, p.name_vi FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $stmt_o_items->execute([$o['id']]);
                                    $o_items = $stmt_o_items->fetchAll();
                                ?>
                                    <tr style="box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: background 0.2s;">
                                        <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px; font-weight: 800; color: var(--primary);">
                                            #<?php echo $o['id']; ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white;">
                                            <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($o['user_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($o['user_email']); ?></div>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; font-size: 0.85rem;">
                                            <div><strong>📞 <?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></strong></div>
                                            <div style="color: var(--text-muted); margin-top: 3px; max-width: 200px; white-space: normal; line-height: 1.3;">📍 <?php echo htmlspecialchars($o['address'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td style="padding: 1.2rem; background: white;">
                                            <div style="font-size: 1.1rem; font-weight: 800; color: #10b981;"><?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ</div>
                                            <?php if (!empty($o['member_discount']) && $o['member_discount'] > 0): ?>
                                                <div style="font-size: 0.74rem; color: #b45309; font-weight: 700; margin-bottom: 3px;">
                                                    <i class="fas fa-crown"></i> <?php echo $current_lang === 'vi' ? 'Giảm HV:' : 'Member Disc:'; ?> -<?php echo number_format((int)round($o['member_discount'] * 25000), 0, ',', '.'); ?>đ
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($o['payment_method'] === 'momo'): ?>
                                                <?php if (!empty($o['momo_confirmed'])): ?>
                                                    <span style="background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; padding: 3px 8px; border-radius: 10px; font-weight: 800; font-size: 0.75rem;">
                                                        💜 MOMO (<?php echo $current_lang === 'vi' ? 'Đã TT' : 'Paid'; ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; padding: 3px 8px; border-radius: 10px; font-weight: 700; font-size: 0.75rem;">
                                                        💜 MOMO (<?php echo $current_lang === 'vi' ? 'Chờ TT' : 'Pending'; ?>)
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 10px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">
                                                    <?php echo strtoupper($o['payment_method']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                            <button type="button" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 7px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; background: linear-gradient(135deg, #1a2744, #2563eb); color: white; border: none;" onclick="showOrderDetailsModal(<?php echo $o['id']; ?>)">
                                                <i class="fas fa-eye"></i> <?php echo $current_lang === 'vi' ? 'Xem Chi Tiết' : 'Details'; ?>
                                            </button>

                                            <!-- Hidden Modal Content for Order #<?php echo $o['id']; ?> -->
                                            <div id="order-modal-content-<?php echo $o['id']; ?>" style="display:none;">
                                                <!-- Order Header & Status Badge -->
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1.2rem; border-bottom: 1.5px solid #e2e8f0;">
                                                    <div>
                                                        <h3 style="margin: 0; color: var(--primary); font-weight: 800; font-size: 1.2rem;">
                                                            <?php echo $current_lang === 'vi' ? 'Đơn Hàng #' : 'Order #'; ?><?php echo $o['id']; ?>
                                                        </h3>
                                                        <span style="font-size: 0.8rem; color: #64748b;">
                                                            📅 <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <?php if ($o['status'] === 'pending'): ?>
                                                            <span style="background: #ffedd5; color: #c2410c; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; display: inline-block; border: 1px solid #fed7aa;">
                                                                🚚 Pending (Delivering 2-3 days)
                                                            </span>
                                                        <?php elseif ($o['status'] === 'completed'): ?>
                                                            <span style="background: #dcfce7; color: #166534; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; display: inline-block; border: 1px solid #bbf7d0;">
                                                                ✔ Completed
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="background: #fee2e2; color: #991b1b; padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; display: inline-block; border: 1px solid #fca5a5;">
                                                                ✖ Cancelled
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Customer & Shipping Card -->
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; text-align: left;">
                                                    <div>
                                                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px;">
                                                            <?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Customer Info'; ?>
                                                        </div>
                                                        <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($o['user_name']); ?></div>
                                                        <div style="font-size: 0.82rem; color: #475569;"><?php echo htmlspecialchars($o['user_email']); ?></div>
                                                    </div>
                                                    <div>
                                                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px;">
                                                            <?php echo $current_lang === 'vi' ? 'Giao Hàng & Thanh Toán' : 'Shipping & Payment'; ?>
                                                        </div>
                                                        <div style="font-size: 0.82rem; color: #334155;">📞 <strong><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></strong></div>
                                                        <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;">📍 <?php echo htmlspecialchars($o['address'] ?? 'N/A'); ?></div>
                                                        <div style="margin-top: 6px;">
                                                            <?php if ($o['payment_method'] === 'momo' && !empty($o['momo_confirmed'])): ?>
                                                                <span style="background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; padding: 3px 10px; border-radius: 10px; font-weight: 800; font-size: 0.75rem;">
                                                                    💜 MOMO (<?php echo $current_lang === 'vi' ? 'Đã Thanh Toán Online' : 'Online Paid'; ?>)
                                                                </span>
                                                            <?php else: ?>
                                                                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">
                                                                    💳 <?php echo strtoupper($o['payment_method']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Ordered Items Table -->
                                                <div style="margin-bottom: 1.5rem;">
                                                    <div style="font-size: 0.88rem; font-weight: 800; color: var(--primary); margin-bottom: 0.8rem; display: flex; align-items: center; gap: 6px; text-align: left;">
                                                        <i class="fas fa-box-open" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết Sản Phẩm Đã Đặt' : 'Ordered Products Detail'; ?>
                                                    </div>
                                                    <table style="width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                                                        <thead>
                                                            <tr style="background: #f1f5f9; color: #475569; font-weight: 700; text-align: left;">
                                                                <th style="padding: 10px 12px;"><?php echo $current_lang === 'vi' ? 'Sản Phẩm' : 'Product'; ?></th>
                                                                <th style="padding: 10px 12px; text-align: center;"><?php echo $current_lang === 'vi' ? 'Đơn Giá' : 'Unit Price'; ?></th>
                                                                <th style="padding: 10px 12px; text-align: center;"><?php echo $current_lang === 'vi' ? 'SL' : 'Qty'; ?></th>
                                                                <th style="padding: 10px 12px; text-align: right;"><?php echo $current_lang === 'vi' ? 'Thành Tiền' : 'Subtotal'; ?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($o_items as $item): 
                                                                $pname = ($current_lang === 'vi' && !empty($item['name_vi'])) ? $item['name_vi'] : $item['name'];
                                                                $subtotal = $item['price'] * $item['quantity'];
                                                            ?>
                                                                <tr style="border-bottom: 1px solid #f1f5f9; text-align: left;">
                                                                    <td style="padding: 10px 12px; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($pname); ?></td>
                                                                    <td style="padding: 10px 12px; text-align: center; color: #64748b;"><?php echo number_format((int)round($item['price'] * 25000), 0, ',', '.'); ?>đ</td>
                                                                    <td style="padding: 10px 12px; text-align: center; font-weight: 800; color: var(--primary);">x<?php echo $item['quantity']; ?></td>
                                                                    <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #10b981;"><?php echo number_format((int)round($subtotal * 25000), 0, ',', '.'); ?>đ</td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <?php if (!empty($o['member_discount']) && $o['member_discount'] > 0): ?>
                                                                <tr style="background: #fafaf9; font-size: 0.9rem; color: #64748b;">
                                                                    <td colspan="3" style="padding: 8px 12px; text-align: right;"><?php echo $current_lang === 'vi' ? 'Tạm tính sản phẩm:' : 'Products Subtotal:'; ?></td>
                                                                    <td style="padding: 8px 12px; text-align: right; font-weight: 700;"><?php echo number_format((int)round(($o['total_price'] + $o['member_discount']) * 25000), 0, ',', '.'); ?>đ</td>
                                                                </tr>
                                                                <tr style="background: #fffbeb; font-size: 0.88rem; color: #b45309; font-weight: 700;">
                                                                    <td colspan="3" style="padding: 8px 12px; text-align: right;"><i class="fas fa-crown"></i> <?php echo $current_lang === 'vi' ? 'Đặc quyền Hội viên (-10%):' : 'VIP Member Perk (-10%):'; ?></td>
                                                                    <td style="padding: 8px 12px; text-align: right; color: #16a34a;">-<?php echo number_format((int)round($o['member_discount'] * 25000), 0, ',', '.'); ?>đ</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                            <tr style="background: #fafaf9; font-weight: 800; font-size: 0.95rem;">
                                                                <td colspan="3" style="padding: 12px; text-align: right; color: var(--primary);"><?php echo $current_lang === 'vi' ? 'TỔNG CỘNG ĐƠN HÀNG:' : 'ORDER TOTAL:'; ?></td>
                                                                <td style="padding: 12px; text-align: right; color: #10b981; font-size: 1.1rem;"><?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ</td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                                <!-- Action Controls inside Modal -->
                                                <div style="background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; text-align: left;">
                                                    <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas fa-cogs" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Thao Tác Quản Lý Đơn Hàng' : 'Order Management Actions'; ?>
                                                    </div>
                                                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                                        <?php if ($o['status'] === 'pending'): ?>
                                                            <?php if (($o['payment_method'] ?? '') === 'momo' && empty($o['momo_confirmed'])): ?>
                                                            <!-- MoMo Confirm Button -->
                                                            <button type="button"
                                                                onclick="confirmMomoPayment(<?php echo $o['id']; ?>, 'shop')"
                                                                style="font-size: 0.82rem; padding: 8px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 800; background: linear-gradient(135deg, #a21caf, #7e22ce); color: white; border: none; box-shadow: 0 4px 14px rgba(162,28,175,0.35);">
                                                                💜 <?php echo $current_lang === 'vi' ? 'Xác Nhận Đã Nhận Tiền MoMo' : 'Confirm MoMo Received'; ?>
                                                            </button>
                                                            <?php endif; ?>
                                                            <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=completed&order_id=<?php echo $o['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xác nhận đơn hàng #' . $o['id'] . ' đã giao hàng thành công?' : 'Confirm order #' . $o['id'] . ' delivered successfully?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xác Nhận Đơn Hàng' : 'Confirm Order'; ?>', '✅');">
                                                                <i class="fas fa-check"></i> <?php echo $current_lang === 'vi' ? 'Đã Giao Hàng (Completed)' : 'Mark Completed'; ?>
                                                            </a>
                                                            <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=cancelled&order_id=<?php echo $o['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Hủy đơn hàng #' . $o['id'] . ' và tự động hoàn trả hàng vào kho?' : 'Cancel order #' . $o['id'] . ' and return items to stock?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Đơn Hàng' : 'Cancel Order'; ?>', '⚠️');">
                                                                <i class="fas fa-ban"></i> <?php echo $current_lang === 'vi' ? 'Hủy Đơn & Hoàn Kho' : 'Cancel Order'; ?>
                                                            </a>
                                                        <?php elseif ($o['status'] === 'cancelled'): ?>
                                                            <a href="/Gym Manager/dashboards/manager.php?page=orders&change_order_status=pending&order_id=<?php echo $o['id']; ?>" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Khôi phục đơn hàng #' . $o['id'] . ' về trạng thái Đang xử lý?' : 'Restore order #' . $o['id'] . ' to Pending status?'; ?>', '<?php echo $current_lang === 'vi' ? 'Khôi Phục Đơn Hàng' : 'Restore Order'; ?>', '🔄');">
                                                                <i class="fas fa-redo"></i> <?php echo $current_lang === 'vi' ? 'Khôi Phục Đơn' : 'Restore Order'; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=orders&delete_order=<?php echo $o['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none; margin-left: auto;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xóa vĩnh viễn dữ liệu đơn hàng #' . $o['id'] . '?' : 'Permanently delete order #' . $o['id'] . '?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Đơn Hàng' : 'Delete Order'; ?>', '🗑️');">
                                                            <i class="fas fa-trash"></i> <?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete Order'; ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Details Modal Shell -->
                <div id="orderDetailsModalShell" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
                    <div style="background: #ffffff; width: 100%; max-width: 680px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden; animation: euModalPop 0.3s ease forwards;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.6rem; background: linear-gradient(135deg, #1a2744, #2563eb); color: white;">
                            <div style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-receipt" style="color: var(--gold);"></i>
                                <span id="orderDetailsModalTitle"><?php echo $current_lang === 'vi' ? 'Chi Tiết & Quản Lý Đơn Hàng' : 'Order Details & Actions'; ?></span>
                            </div>
                            <button type="button" onclick="closeOrderDetailsModal()" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                &times;
                            </button>
                        </div>
                        <div id="orderDetailsModalBody" style="padding: 1.6rem; max-height: 80vh; overflow-y: auto;"></div>
                    </div>
                </div>

                <script>
                function showOrderDetailsModal(orderId) {
                    const src = document.getElementById('order-modal-content-' + orderId);
                    const body = document.getElementById('orderDetailsModalBody');
                    const shell = document.getElementById('orderDetailsModalShell');
                    if (src && body && shell) {
                        body.innerHTML = src.innerHTML;
                        shell.style.display = 'flex';
                    }
                }
                function closeOrderDetailsModal() {
                    const shell = document.getElementById('orderDetailsModalShell');
                    if (shell) shell.style.display = 'none';
                }
                window.addEventListener('click', function(e) {
                    const shell = document.getElementById('orderDetailsModalShell');
                    if (e.target === shell) {
                        closeOrderDetailsModal();
                    }
                });

                function confirmMomoPayment(orderId, type) {
                    const isVi = <?php echo $current_lang === 'vi' ? 'true' : 'false'; ?>;
                    const msg = isVi 
                        ? (type === 'shop' ? 'Xác nhận đã nhận tiền MoMo cho đơn hàng #' + orderId + '?' : 'Xác nhận đã nhận tiền MoMo cho gói #' + orderId + '?')
                        : (type === 'shop' ? 'Confirm MoMo payment received for order #' + orderId + '?' : 'Confirm MoMo payment for plan #' + orderId + '?');
                    if (!confirm(msg)) return;

                    fetch('/Gym Manager/api/momo_admin_confirm.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: orderId, type: type })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert(isVi ? '✅ Đã xác nhận thanh toán MoMo thành công! Đơn hàng đã được cập nhật hoàn tất.' : '✅ MoMo payment confirmed successfully!');
                            window.location.reload();
                        } else {
                            alert(isVi ? '❌ Lỗi: ' + (data.message || 'Không thể xác nhận') : '❌ Error: ' + (data.message || 'Failed'));
                        }
                    })
                    .catch(err => {
                        alert(isVi ? '❌ Lỗi kết nối máy chủ.' : '❌ Server connection error.');
                    });
                }
                </script>

            <?php elseif ($page == 'bookings'): 
                // Fetch stats
                $stat_total = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
                $stat_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
                $stat_confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
                $stat_cancelled = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

                // Filters & Search logic
                $search = $_GET['search'] ?? '';
                $status_filter = $_GET['status_filter'] ?? '';
                
                $query_str = "SELECT b.*, u.name as user_name, u.email as user_email, u.avatar as user_avatar, 
                                     c_u.name as coach_name, c.specialty as coach_specialty, c.hourly_rate as coach_rate,
                                     c.room_name as coach_room_name, c.room_number as coach_room_number
                              FROM bookings b 
                              JOIN users u ON b.user_id = u.id 
                              JOIN coaches c ON b.coach_id = c.id 
                              JOIN users c_u ON c.user_id = c_u.id";
                
                $params = [];
                $conditions = [];
                
                if ($search !== '') {
                    $conditions[] = "(u.name LIKE ? OR c_u.name LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($status_filter !== '' && in_array($status_filter, ['pending', 'confirmed', 'cancelled'])) {
                    $conditions[] = "b.status = ?";
                    $params[] = $status_filter;
                }
                
                if (!empty($conditions)) {
                    $query_str .= " WHERE " . implode(" AND ", $conditions);
                }
                
                $query_str .= " ORDER BY b.booking_date DESC";
                
                $stmt = $pdo->prepare($query_str);
                $stmt->execute($params);
                $bookings = $stmt->fetchAll();

                // Fetch all users for dropdown (only 'user' role or any, let's allow booking for any user)
                $users_list = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name ASC")->fetchAll();
                if (empty($users_list)) {
                    // Fallback to all users if none have 'user' role
                    $users_list = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
                }
                
                // Fetch all coaches for dropdown
                $coaches_list = $pdo->query("SELECT c.id, u.name as coach_name, c.specialty, c.room_name, c.room_number FROM coaches c JOIN users u ON c.user_id = u.id ORDER BY u.name ASC")->fetchAll();

                // Check if we are editing a booking
                $edit_booking = null;
                if (isset($_GET['edit_booking'])) {
                    $stmt_eb = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                    $stmt_eb->execute([$_GET['edit_booking']]);
                    $edit_booking = $stmt_eb->fetch();
                }
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("booking_management"); ?></h2>
                </div>

                <!-- Statistics Cards -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;"><?php echo __("total_bookings"); ?></h4>
                        <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_total; ?></p>
                    </div>
                    <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5;">
                        <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase;">Pending</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_pending; ?></p>
                    </div>
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7;">
                        <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase;">Confirmed</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_confirmed; ?></p>
                    </div>
                    <div style="background: #fafafa; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border);">
                        <h4 style="color: #4b5563; font-size: 0.8rem; text-transform: uppercase;">Cancelled</h4>
                        <p style="font-size: 2rem; font-weight: 800; color: #4b5563; margin-top: 5px;"><?php echo $stat_cancelled; ?></p>
                    </div>
                </div>

                <?php if ($edit_booking): ?>
                <!-- Edit Reschedule Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="booking_id" value="<?php echo $edit_booking['id']; ?>">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i> Edit & Reschedule Booking #<?php echo $edit_booking['id']; ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("client"); ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($users_list as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $edit_booking['user_id'] == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("coach"); ?></label>
                            <select name="coach_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($coaches_list as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $edit_booking['coach_id'] == $c['id'] ? 'selected' : ''; ?>>Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)<?php echo $c['room_number'] ? ' - ' . htmlspecialchars($c['room_number']) : ''; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Booking Date</label>
                            <input type="datetime-local" name="booking_date" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_booking['booking_date'])); ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("status"); ?></label>
                            <select name="status" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="pending" <?php echo $edit_booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $edit_booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $edit_booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Notes / Coach's Message</label>
                            <input type="text" name="coach_message" value="<?php echo htmlspecialchars($edit_booking['coach_message'] ?? ''); ?>" placeholder="Enter cancellation reason or trainer advice..." style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_booking" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;">Save Changes</button>
                        <a href="/Gym Manager/dashboards/manager.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;">Cancel</a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Add Booking Form -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-plus" style="color: var(--primary);"></i> Create Direct Booking
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("client"); ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected>-- Select Client --</option>
                                <?php foreach($users_list as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("coach"); ?></label>
                            <select name="coach_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected>-- Select Coach --</option>
                                <?php foreach($coaches_list as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">Coach <?php echo htmlspecialchars($c['coach_name']); ?> (<?php echo htmlspecialchars($c['specialty']); ?>)<?php echo $c['room_number'] ? ' - ' . htmlspecialchars($c['room_number']) : ''; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;">Date Time</label>
                            <input type="datetime-local" name="booking_date" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo __("status"); ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="pending">Pending</option>
                                <option value="confirmed" selected>Confirmed</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_booking" class="btn-auth" style="margin-top: 1.5rem; width: auto; padding: 1.1rem 2.5rem;">Create Booking</button>
                </form>
                <?php endif; ?>

                <!-- Search & Filters -->
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem; background: #f8fafc; padding: 1.2rem; border-radius: 20px; border: 1px solid var(--border);">
                    <input type="hidden" name="page" value="bookings">
                    <div style="flex: 2; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search client or coach name..." style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-size: 0.95rem; outline: none;">
                    </div>
                    <div style="flex: 1;">
                        <select name="status_filter" style="width: 100%; padding: 1rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-weight: 600; outline: none; font-size: 0.95rem;">
                            <option value="">-- All Statuses --</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-auth" style="margin-top: 0; width: auto; padding: 1rem 2rem; font-size: 0.95rem;">Search</button>
                    <?php if ($search !== '' || $status_filter !== ''): ?>
                        <a href="/Gym Manager/dashboards/manager.php?page=bookings" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); padding: 1rem 1.5rem; border-radius: 15px; text-decoration: none; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center;">Reset Filter</a>
                    <?php endif; ?>
                </form>

                <!-- Bookings Table -->
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 20px; border: 1px solid var(--border); margin-top: 2rem;">
                        <i class="far fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">No bookings found matching filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 1.2rem; border-radius: 15px 0 0 15px;"><?php echo __("client"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("coach"); ?></th>
                                    <th style="padding: 1.2rem;"><?php echo __("date_time"); ?></th>
                                    <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;">DETAILS & ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bookings as $b): ?>
                                <tr>
                                    <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo $b['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($b['user_name']).'&background=0062ff&color=fff&size=40'; ?>" 
                                                 style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
                                            <div>
                                                <strong style="color: var(--text-main); font-weight: 700;"><?php echo htmlspecialchars($b['user_name']); ?></strong>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                                <?php 
                                                $phone_to_show = !empty($b['user_phone']) ? $b['user_phone'] : '';
                                                if (!empty($phone_to_show)): ?>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;"><i class="fas fa-phone-alt" style="font-size: 0.75rem; color: var(--primary);"></i> <?php echo htmlspecialchars($phone_to_show); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($b['class_name'])): ?>
                                                    <div style="margin-top: 4px;">
                                                        <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.2px;">
                                                            <i class="fas fa-box" style="margin-right: 3px;"></i> <?php echo htmlspecialchars($b['class_name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div>
                                            <strong style="color: var(--text-main);">Coach <?php echo htmlspecialchars($b['coach_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($b['coach_specialty']); ?></div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <i class="far fa-calendar-alt" style="color: var(--primary); margin-right: 5px;"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                            <?php echo date('l', strtotime($b['booking_date'])); ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                        <button type="button" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 7px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; background: linear-gradient(135deg, #1a2744, #2563eb); color: white; border: none;" onclick="showBookingDetailsModal(<?php echo $b['id']; ?>)">
                                            <i class="fas fa-eye"></i> <?php echo $current_lang === 'vi' ? 'Xem Chi Tiết' : 'Details'; ?>
                                        </button>

                                        <!-- Hidden Modal Content for Booking #<?php echo $b['id']; ?> -->
                                        <div id="booking-modal-content-<?php echo $b['id']; ?>" style="display:none;">
                                            <!-- Booking Header & Status Badge -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1.2rem; border-bottom: 1.5px solid #e2e8f0;">
                                                <div>
                                                    <h3 style="margin: 0; color: var(--primary); font-weight: 800; font-size: 1.2rem;">
                                                        <?php echo $current_lang === 'vi' ? 'Buổi Tập #' : 'Booking #'; ?><?php echo $b['id']; ?>
                                                    </h3>
                                                    <span style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                                                        📅 <?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?> (<?php echo date('l', strtotime($b['booking_date'])); ?>)
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="status-badge status-<?php echo $b['status']; ?>" style="display: inline-block; padding: 6px 14px; font-weight: 800; font-size: 0.8rem; border-radius: 20px;">
                                                        <?php echo strtoupper($b['status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Client & Coach Info Cards -->
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem; background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; text-align: left;">
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        👤 <?php echo $current_lang === 'vi' ? 'Học Viên' : 'Client Info'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                                    <div style="font-size: 0.82rem; color: #475569;"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                                    <?php if (!empty($b['user_phone'])): ?>
                                                        <div style="font-size: 0.82rem; color: #334155; margin-top: 2px;">📞 <?php echo htmlspecialchars($b['user_phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        🏋️ <?php echo $current_lang === 'vi' ? 'Huấn Luyện Viên' : 'Coach Info'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Coach <?php echo htmlspecialchars($b['coach_name']); ?></div>
                                                    <div style="font-size: 0.82rem; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($b['coach_specialty']); ?></div>
                                                    <?php if (!empty($b['coach_room_number'])): ?>
                                                        <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;">
                                                            🏛️ <?php echo $current_lang === 'vi' ? 'Phòng' : 'Room'; ?>: <strong><?php echo htmlspecialchars($b['coach_room_number']); ?></strong> <?php echo !empty($b['coach_room_name']) ? '(' . htmlspecialchars($b['coach_room_name']) . ')' : ''; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Notes Card -->
                                            <div style="background: #f1f5f9; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-sticky-note" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Ghi Chú Buổi Tập' : 'Session Notes'; ?>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #334155; font-weight: 600; line-height: 1.4;">
                                                    <?php if (!empty($b['coach_message'])): ?>
                                                        <?php echo htmlspecialchars($b['coach_message']); ?>
                                                    <?php else: ?>
                                                        <span style="color: #94a3b8; font-style: italic; font-weight: 500;"><?php echo $current_lang === 'vi' ? 'Chưa có ghi chú' : 'None'; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Action Controls inside Modal -->
                                            <div style="background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-cogs" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Thao Tác Quản Lý Buổi Tập' : 'Booking Management Actions'; ?>
                                                </div>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                                    <?php if ($b['status'] == 'pending'): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;">
                                                            <i class="fas fa-check"></i> <?php echo $current_lang === 'vi' ? 'Xác Nhận (Confirm)' : 'Confirm'; ?>
                                                        </a>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn hủy buổi tập này?' : 'Are you sure you want to cancel this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel Booking'; ?>', '❌');">
                                                            <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel'; ?>
                                                        </a>
                                                    <?php elseif ($b['status'] == 'confirmed'): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=cancelled&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn hủy buổi tập này?' : 'Are you sure you want to cancel this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel Booking'; ?>', '❌');">
                                                            <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Hủy Buổi Tập' : 'Cancel'; ?>
                                                        </a>
                                                    <?php elseif ($b['status'] == 'cancelled'): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=bookings&change_status=confirmed&booking_id=<?php echo $b['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;">
                                                            <i class="fas fa-redo"></i> <?php echo $current_lang === 'vi' ? 'Xác Nhận Lại' : 'Re-confirm'; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="/Gym Manager/dashboards/manager.php?page=bookings&edit_booking=<?php echo $b['id']; ?>" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;">
                                                        <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Đổi Lịch (Edit)' : 'Edit'; ?>
                                                    </a>
                                                    
                                                    <a href="/Gym Manager/dashboards/manager.php?page=bookings&delete_booking=<?php echo $b['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; background: #fff1f2; color: #ef4444; text-decoration: none; margin-left: auto;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn XÓA VĨNH VIỄN buổi tập này?' : 'WARNING: Are you sure you want to PERMANENTLY DELETE this booking?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete Permanently'; ?>', '🗑️');">
                                                        <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete'; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Booking Details Modal Shell -->
                    <div id="bookingDetailsModalShell" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
                        <div style="background: #ffffff; width: 100%; max-width: 640px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden; animation: euModalPop 0.3s ease forwards;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.6rem; background: linear-gradient(135deg, #1a2744, #2563eb); color: white;">
                                <div style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-calendar-check" style="color: var(--gold);"></i>
                                    <span><?php echo $current_lang === 'vi' ? 'Chi Tiết & Quản Lý Buổi Tập' : 'Booking Details & Actions'; ?></span>
                                </div>
                                <button type="button" onclick="closeBookingDetailsModal()" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                    &times;
                                </button>
                            </div>
                            <div id="bookingDetailsModalBody" style="padding: 1.6rem; max-height: 80vh; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <script>
                    function showBookingDetailsModal(bookingId) {
                        const src = document.getElementById('booking-modal-content-' + bookingId);
                        const body = document.getElementById('bookingDetailsModalBody');
                        const shell = document.getElementById('bookingDetailsModalShell');
                        if (src && body && shell) {
                            body.innerHTML = src.innerHTML;
                            shell.style.display = 'flex';
                        }
                    }
                    function closeBookingDetailsModal() {
                        const shell = document.getElementById('bookingDetailsModalShell');
                        if (shell) shell.style.display = 'none';
                    }
                    window.addEventListener('click', function(e) {
                        const shell = document.getElementById('bookingDetailsModalShell');
                        if (e.target === shell) {
                            closeBookingDetailsModal();
                        }
                    });
                    </script>
                <?php endif; ?>
            <?php elseif ($page == 'yearly_report'): 
                /* European editorial reporting style: restrained colour, generous spacing, clear hierarchy. */
            ?>
            <style>
                .eu-report-shell { --eu-ink:#172033; --eu-wine:#7c2637; --eu-gold:#b28a4a; --eu-paper:#f7f4ee; --eu-line:#ded8cf; --eu-green:#206451; }
                .eu-report-hero { background:linear-gradient(118deg,#172033 0%,#25324a 68%,#7c2637 170%); border-radius:24px; padding:2rem 2.2rem; margin:0 0 1.4rem; color:#fff; display:flex; justify-content:space-between; align-items:center; gap:1.5rem; box-shadow:0 18px 38px rgba(23,32,51,.15); }
                .eu-report-kicker { margin:0 0 .45rem; color:#ddc99c; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.16em; }
                .eu-report-hero h2 { margin:0; color:#fff; font-family:Georgia,'Times New Roman',serif; font-size:clamp(1.6rem,3vw,2.3rem); letter-spacing:-.025em; }
                .eu-report-hero p { margin:.5rem 0 0; color:#d9dfeb; font-size:.93rem; }
                .eu-report-hero strong { color:#f1d493; font-size:1.28rem; }
                .eu-report-controls { display:flex; align-items:center; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }
                .eu-report-controls form { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; margin:0; padding:.4rem; border:1px solid rgba(255,255,255,.2); border-radius:12px; background:rgba(255,255,255,.08); }
                .eu-report-controls label { color:#f4f0e8; font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                .eu-report-controls select { appearance:none; background:#fff; color:var(--eu-ink); border:0; border-radius:8px; padding:.65rem 2rem .65rem .75rem; font-weight:700; }
                .eu-report-btn { background:#d9bc7c; color:#172033; border:0; padding:.78rem 1rem; border-radius:10px; font-weight:800; font-size:.84rem; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; box-shadow:none; }
                .eu-report-btn:hover { background:#f1d493; transform:translateY(-1px); }
                .eu-report-canvas { background:var(--eu-paper); padding:1.7rem; border-radius:22px; border:1px solid var(--eu-line); box-shadow:none; margin-bottom:2rem; }
                .eu-chart-panel { background:#fffdf9; padding:1.5rem; border-radius:16px; border:1px solid var(--eu-line); margin-bottom:2.25rem; }
                .eu-chart-panel h3, .eu-report-canvas > h3 { font-family:Georgia,'Times New Roman',serif; color:var(--eu-ink)!important; letter-spacing:-.01em; }
                .eu-report-canvas table thead tr { background:#ede7dc!important; border-color:var(--eu-line)!important; }
                .eu-report-canvas table th { color:#4a5364!important; letter-spacing:.065em; }
                .eu-report-canvas table tbody tr:hover { background:#fffdf9; }
                .eu-report-canvas .status-badge { border-radius:999px; font-size:.69rem; letter-spacing:.05em; }
                @media (max-width:760px) { .eu-report-hero { padding:1.45rem; align-items:flex-start; flex-direction:column; } .eu-report-controls { justify-content:flex-start; } .eu-report-canvas { padding:1rem; } }
            </style>
            <?php
                $selected_year = $_GET['year'] ?? date('Y');
                
                // Fetch bookings for the year
                $stmt_b = $pdo->prepare("SELECT b.*, u.name as user_name, u.email as user_email, b.user_phone, c_u.name as coach_name 
                                         FROM bookings b 
                                         JOIN users u ON b.user_id = u.id 
                                         JOIN coaches c ON b.coach_id = c.id 
                                         JOIN users c_u ON c.user_id = c_u.id 
                                         WHERE YEAR(b.booking_date) = ?
                                         ORDER BY b.booking_date DESC");
                $stmt_b->execute([$selected_year]);
                $yearly_bookings = $stmt_b->fetchAll();

                // Fetch orders for the year
                $stmt_o = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                         FROM orders o 
                                         JOIN users u ON o.user_id = u.id 
                                         WHERE YEAR(o.created_at) = ?
                                         ORDER BY o.created_at DESC");
                $stmt_o->execute([$selected_year]);
                $yearly_orders = $stmt_o->fetchAll();

                // Fetch monthly revenue for the chart
                $stmt_chart = $pdo->prepare("SELECT MONTH(o.created_at) as month, SUM(o.total_price) as revenue 
                                             FROM orders o
                                             WHERE YEAR(o.created_at) = ? AND o.status = 'completed'
                                             AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)
                                             GROUP BY MONTH(o.created_at)");
                $stmt_chart->execute([$selected_year]);
                $chart_data_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

                $monthly_revenue = array_fill(1, 12, 0);
                foreach($chart_data_raw as $row) {
                    $monthly_revenue[(int)$row['month']] = (int)round((float)$row['revenue'] * 25000);
                }
            ?>
                <?php $total_yearly_revenue = array_sum($monthly_revenue); ?>
                <!-- html2canvas & jsPDF for Image and PDF Export -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

                <div class="eu-report-shell">
                <div class="eu-report-hero">
                    <div>
                        <p class="eu-report-kicker">Gym Manager · Annual Overview</p>
                        <h2>Yearly Report — <?php echo htmlspecialchars($selected_year); ?></h2>
                        <p>Total revenue: <strong><?php echo number_format($total_yearly_revenue, 0, ',', '.'); ?> VND</strong></p>
                    </div>
                    <div class="eu-report-controls">
                        <button type="button" onclick="downloadYearlyReportPDF('<?php echo $selected_year; ?>', event)" 
                                class="eu-report-btn">
                            <i class="fas fa-file-pdf"></i> <?php echo $current_lang === 'vi' ? 'Tải Báo Cáo Năm (PDF)' : 'Download Yearly Report (PDF)'; ?>
                        </button>

                        <form method="GET">
                            <input type="hidden" name="page" value="yearly_report">
                            <label style="font-weight: 600;"><?php echo __("select_year"); ?></label>
                            <select name="year" onchange="this.form.submit()">
                                <?php 
                                $current_year = date('Y');
                                for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                    echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>
                </div>

                <div id="yearly-report-export-area" class="eu-report-canvas">

                    <!-- Chart Section -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <div class="eu-chart-panel">
                        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                            <?php echo __("monthly_revenue_chart"); ?>
                        </h3>
                        <div style="height: 350px; width: 100%;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('revenueChart');
                        if(canvas) {
                            const ctx = canvas.getContext('2d');
                            
                            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, 'rgba(124, 38, 55, 0.34)');
                            gradient.addColorStop(1, 'rgba(124, 38, 55, 0.02)');

                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                    datasets: [{
                                        label: 'Revenue',
                                        data: <?php echo json_encode(array_values($monthly_revenue)); ?>,
                                        backgroundColor: gradient,
                                        borderColor: '#7c2637',
                                        borderWidth: 3,
                                        pointBackgroundColor: '#ffffff',
                                        pointBorderColor: '#7c2637',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        fill: true,
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                            titleFont: { size: 13 },
                                            bodyFont: { size: 14, weight: 'bold' },
                                            padding: 12,
                                            displayColors: false,
                                            callbacks: {
                                                label: function(context) {
                                                    return context.parsed.y.toLocaleString('en-GB') + ' VND';
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: '#f1f5f9', drawBorder: false },
                                            ticks: {
                                                callback: function(value) { return value.toLocaleString('en-GB') + ' VND'; },
                                                font: { family: 'Inter, sans-serif', size: 12 },
                                                color: '#64748b'
                                            }
                                        },
                                        x: {
                                            grid: { display: false, drawBorder: false },
                                            ticks: {
                                                font: { family: 'Inter, sans-serif', size: 12 },
                                                color: '#64748b'
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                    </script>

                    <?php 
                    $bookings_by_coach = [];
                    foreach ($yearly_bookings as $b) {
                        $c_name = $b['coach_name'];
                        if (!isset($bookings_by_coach[$c_name])) {
                            $bookings_by_coach[$c_name] = [];
                        }
                        $bookings_by_coach[$c_name][] = $b;
                    }
                    ?>
                    
                    <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                        <i class="fas fa-users-cog" style="color: var(--primary);"></i> Coach Performance Summary (<?php echo htmlspecialchars($selected_year); ?>)
                    </h3>
                    
                    <?php if (empty($bookings_by_coach)): ?>
                        <p style="color: var(--text-muted); margin-bottom: 3rem;"><?php echo __("no_appointments"); ?></p>
                    <?php else: ?>
                        <div style="margin-bottom: 3rem;">
                        <?php foreach ($bookings_by_coach as $c_name => $c_bookings): ?>
                        <div style="background: white; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <div style="background: #f8fafc; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                                <h4 style="margin: 0; color: var(--primary); font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-user-tie"></i> Coach <?php echo htmlspecialchars($c_name); ?>
                                </h4>
                                <span style="background: var(--primary); color: white; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                                    Total Bookings: <?php echo count($c_bookings); ?>
                                </span>
                            </div>
                            
                            <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date_time"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($c_bookings as $b): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                            <td style="padding: 0.75rem 1rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($b['user_name']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; word-break: break-all; max-width: 200px;"><?php echo htmlspecialchars($b['user_email']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; white-space: nowrap;"><?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                                <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo strtoupper($b['status']); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shopping-cart"></i> Products Purchased in <?php echo htmlspecialchars($selected_year); ?>
                    </h3>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; border-radius: 12px; border: 1px solid var(--border);">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("order_id"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("total_price"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; min-width: 140px;"><?php echo __("items"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;" class="invoice-export-btn"><?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($yearly_orders as $o): 
                                    $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $stmt_items->execute([$o['id']]);
                                    $items = $stmt_items->fetchAll();
                                    if (empty($items)) continue;
                                    $items_str = array_map(function($i) { return $i['name'] . (!empty($i['variant']) ? ' (' . $i['variant'] . ')' : '') . ' x' . $i['quantity']; }, $items);
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: white; transition: background 0.2s;">
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 800; color: var(--primary); white-space: nowrap;">#<?php echo $o['id']; ?></td>
                                    <td style="padding: 0.75rem 0.8rem; min-width: 190px;">
                                        <strong style="display:block; color:#0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></strong>
                                        <span style="display:block; margin-top:3px; color:#64748b; font-size:.74rem; overflow-wrap:anywhere;"><i class="far fa-envelope"></i> <?php echo htmlspecialchars($o['user_email']); ?></span>
                                        <span style="display:block; margin-top:2px; color:#64748b; font-size:.74rem;"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; color: #10b981; font-weight: 800; white-space: nowrap;"><?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ</td>
                                    <td style="padding: 0.75rem 0.8rem; color: #334155; font-size: 0.8rem; line-height: 1.35; max-width: 220px; word-wrap: break-word;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                            <?php echo strtoupper($o['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <button type="button" class="invoice-export-btn" onclick="openInvoiceModal(<?php echo $o['id']; ?>)" 
                                                style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); transition: all 0.2s;">
                                            <i class="fas fa-eye" style="color: #ddc99c;"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?>
                                        </button>

                                        <!-- Invoice Modal for Order #<?php echo $o['id']; ?> -->
                                        <div id="invoice-modal-<?php echo $o['id']; ?>" onclick="if(event.target===this) closeInvoiceModal(<?php echo $o['id']; ?>)" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 1.5rem; overflow-y: auto;">
                                            <div style="background: white; border-radius: 24px; width: 100%; max-width: 720px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; font-family: 'Outfit', sans-serif;">
                                                <!-- Action Control Bar -->
                                                <div style="background: #0f172a; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại' : 'Back'; ?>
                                                        </button>
                                                        <span style="font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                                            <i class="fas fa-receipt" style="color: #f59e0b;"></i> <?php echo $current_lang === 'vi' ? 'Hóa Đơn #' : 'Invoice #'; ?><?php echo $o['id']; ?>
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <button type="button" onclick="downloadInvoicePDF(<?php echo $o['id']; ?>)" 
                                                                style="background: #ef4444; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'png')" 
                                                                style="background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> PNG
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'jpg')" 
                                                                style="background: #3b82f6; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> JPG
                                                        </button>
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;" title="Close">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Printable/Exportable Invoice Card -->
                                                <div style="padding: 2rem; background: #f8fafc;">
                                                    <div id="invoice-card-<?php echo $o['id']; ?>" style="background: #ffffff; padding: 2.5rem; border-radius: 16px; border: 1px solid #e2e8f0; color: #0f172a; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;">
                                                        <!-- Header -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 1.5rem; border-bottom: 2px solid #0062ff;">
                                                            <div>
                                                                <h2 style="margin: 0; color: #0062ff; font-weight: 900; font-size: 1.6rem; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
                                                                    <i class="fas fa-bolt" style="color: #f59e0b;"></i> GYM MANAGER
                                                                </h2>
                                                                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                                                                    <?php echo $current_lang === 'vi' ? 'HÓA ĐƠN THANH TOÁN CHÍNH THỨC' : 'OFFICIAL SALES INVOICE'; ?>
                                                                </p>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8;">
                                                                    123 Fitness Ave, District 1, HCMC | Hotline: 1900 8888
                                                                </p>
                                                            </div>
                                                            <div style="text-align: right;">
                                                                <span style="background: #eff6ff; color: #0062ff; font-weight: 800; font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; border: 1px solid #bfdbfe; display: inline-block;">
                                                                    INV-#<?php echo sprintf('%05d', $o['id']); ?>
                                                                </span>
                                                                <p style="margin: 6px 0 0; font-size: 0.8rem; color: #64748b;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Ngày:' : 'Date:'; ?></strong> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Customer Info -->
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; background: #f8fafc; padding: 1.2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Billed To'; ?></h4>
                                                                <p style="margin: 0; font-weight: 800; font-size: 1rem; color: #0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['user_email']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Payment Status'; ?></h4>
                                                                <span style="background: #dcfce7; color: #166534; font-weight: 800; font-size: 0.8rem; padding: 4px 10px; border-radius: 12px; display: inline-block; margin-bottom: 6px;">
                                                                    ✔ <?php echo strtoupper($o['status']); ?>
                                                                </span>
                                                                <p style="margin: 0; font-size: 0.85rem; color: #475569;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?></strong> Gym Online Store
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Item Table -->
                                                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.88rem;">
                                                            <thead>
                                                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; background: #f1f5f9;">
                                                                    <th style="padding: 10px; border-radius: 8px 0 0 8px; color: #475569; font-weight: 700;">#</th>
                                                                    <th style="padding: 10px; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Sản Phẩm' : 'Product Item'; ?></th>
                                                                    <th style="padding: 10px; text-align: center; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'SL' : 'Qty'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Đơn Giá' : 'Unit Price'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; border-radius: 0 8px 8px 0; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Thành Tiền' : 'Total'; ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $idx = 1;
                                                                foreach ($items as $item): 
                                                                    $item_total = $item['price'] * $item['quantity'];
                                                                ?>
                                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                    <td style="padding: 10px; font-weight: 600; color: #64748b;"><?php echo $idx++; ?></td>
                                                                    <td style="padding: 10px; font-weight: 700; color: #0f172a;">
                                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 400;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td style="padding: 10px; text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                                                    <td style="padding: 10px; text-align: right; color: #475569;"><?php echo number_format((int)round($item['price'] * 25000), 0, ',', '.'); ?>đ</td>
                                                                    <td style="padding: 10px; text-align: right; font-weight: 700; color: #0062ff;"><?php echo number_format((int)round($item_total * 25000), 0, ',', '.'); ?>đ</td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                        <!-- Summary -->
                                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: white; padding: 1.2rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                                                            <div>
                                                                <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'TỔNG CỘNG THANH TOÁN' : 'GRAND TOTAL'; ?></span>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Đã bao gồm thuế & phí' : 'Taxes & fees included'; ?></p>
                                                            </div>
                                                            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;">
                                                                <?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ
                                                            </h2>
                                                        </div>

                                                        <!-- Footer Stamp -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                                                            <div>
                                                                <div style="display: inline-flex; align-items: center; gap: 6px; border: 2px solid #16a34a; color: #16a34a; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; transform: rotate(-2deg);">
                                                                    <i class="fas fa-check-circle"></i> GYM MANAGER VERIFIED
                                                                </div>
                                                                <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                                                                    Thank you for your business!
                                                                </p>
                                                            </div>
                                                            <div style="text-align: center;">
                                                                <p style="margin: 0; font-size: 0.8rem; font-weight: 700; color: #475569;"><?php echo $current_lang === 'vi' ? 'Xác Nhận Quản Lý' : 'Authorized Signature'; ?></p>
                                                                <div style="height: 30px;"></div>
                                                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8;">(Signed Digitally)</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Back Button -->
                                                    <div style="margin-top: 1.5rem; text-align: center;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: #475569; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(71,85,105,0.25); transition: transform 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại danh sách' : 'Back to List'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($yearly_orders)): ?>
                                <tr><td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo __("no_orders"); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'monthly_report'): 
                /* The monthly view uses the same European editorial system as the annual report. */
            ?>
            <style>
                .eu-report-shell { --eu-ink:#172033; --eu-wine:#7c2637; --eu-gold:#b28a4a; --eu-paper:#f7f4ee; --eu-line:#ded8cf; --eu-green:#206451; }
                .eu-report-hero { background:linear-gradient(118deg,#172033 0%,#25324a 68%,#7c2637 170%); border-radius:24px; padding:2rem 2.2rem; margin:0 0 1.4rem; color:#fff; display:flex; justify-content:space-between; align-items:center; gap:1.5rem; box-shadow:0 18px 38px rgba(23,32,51,.15); }
                .eu-report-kicker { margin:0 0 .45rem; color:#ddc99c; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.16em; }
                .eu-report-hero h2 { margin:0; color:#fff; font-family:Georgia,'Times New Roman',serif; font-size:clamp(1.6rem,3vw,2.3rem); letter-spacing:-.025em; }
                .eu-report-hero p { margin:.5rem 0 0; color:#d9dfeb; font-size:.93rem; }.eu-report-hero strong{color:#f1d493;font-size:1.28rem;}
                .eu-report-controls { display:flex; align-items:center; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }.eu-report-controls form{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin:0;padding:.4rem;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(255,255,255,.08);}.eu-report-controls label{color:#f4f0e8;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;}.eu-report-controls select{appearance:none;background:#fff;color:var(--eu-ink);border:0;border-radius:8px;padding:.65rem .75rem;font-weight:700;}.eu-report-btn{background:#d9bc7c;color:#172033;border:0;padding:.78rem 1rem;border-radius:10px;font-weight:800;font-size:.84rem;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;}.eu-report-btn:hover{background:#f1d493;transform:translateY(-1px);}.eu-report-filter{background:#7c2637;color:#fff;border:0;padding:.68rem .9rem;border-radius:8px;font-weight:800;cursor:pointer;}.eu-report-canvas{background:var(--eu-paper);padding:1.7rem;border-radius:22px;border:1px solid var(--eu-line);box-shadow:none;margin-bottom:2rem;}.eu-summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2.25rem;}.eu-summary-card{background:#fffdf9!important;color:var(--eu-ink)!important;padding:1.35rem!important;border:1px solid var(--eu-line);border-radius:14px!important;box-shadow:none!important;border-top:4px solid var(--eu-wine);}.eu-summary-card:nth-child(2){border-top-color:#206451;}.eu-summary-card:nth-child(3){border-top-color:#b28a4a;}.eu-summary-card h3{color:var(--eu-ink)!important;font-family:Georgia,'Times New Roman',serif;}.eu-summary-card span,.eu-summary-card p{color:#687284!important;}.eu-chart-panel{background:#fffdf9;padding:1.5rem;border-radius:16px;border:1px solid var(--eu-line);margin-bottom:2.25rem;}.eu-chart-panel h3,.eu-report-canvas>h3{font-family:Georgia,'Times New Roman',serif;color:var(--eu-ink)!important;}.eu-report-canvas table thead tr{background:#ede7dc!important;border-color:var(--eu-line)!important;}.eu-report-canvas table th{color:#4a5364!important;letter-spacing:.065em;}.eu-report-canvas table tbody tr:hover{background:#fffdf9;}@media(max-width:760px){.eu-report-hero{padding:1.45rem;align-items:flex-start;flex-direction:column;}.eu-report-controls{justify-content:flex-start;}.eu-report-canvas{padding:1rem;}.eu-summary-grid{grid-template-columns:1fr;}}
            </style>
            <?php
                $selected_month = intval($_GET['month'] ?? date('n'));
                $selected_year = intval($_GET['year'] ?? date('Y'));
                
                $month_name_vi = [1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'];
                $month_name_en = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
                $display_month_title = ($current_lang === 'vi' ? $month_name_vi[$selected_month] : $month_name_en[$selected_month]) . " " . $selected_year;

                $days_in_month = date('t', strtotime("$selected_year-$selected_month-01"));

                // Fetch bookings for the month
                $stmt_mb = $pdo->prepare("SELECT b.*, u.name as user_name, u.email as user_email, b.user_phone, c_u.name as coach_name 
                                          FROM bookings b 
                                          JOIN users u ON b.user_id = u.id 
                                          JOIN coaches c ON b.coach_id = c.id 
                                          JOIN users c_u ON c.user_id = c_u.id 
                                          WHERE MONTH(b.booking_date) = ? AND YEAR(b.booking_date) = ?
                                          ORDER BY b.booking_date DESC");
                $stmt_mb->execute([$selected_month, $selected_year]);
                $monthly_bookings = $stmt_mb->fetchAll();

                $monthly_bookings_by_coach = [];
                foreach ($monthly_bookings as $mb) {
                    $monthly_bookings_by_coach[$mb['coach_name']][] = $mb;
                }

                // Fetch orders for the month
                $stmt_mo = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                          FROM orders o 
                                          JOIN users u ON o.user_id = u.id 
                                          WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
                                          ORDER BY o.created_at DESC");
                $stmt_mo->execute([$selected_month, $selected_year]);
                $monthly_orders = $stmt_mo->fetchAll();

                // Fetch daily revenue for chart
                $stmt_daily = $pdo->prepare("SELECT DAY(o.created_at) as day_num, SUM(o.total_price) as daily_revenue 
                                              FROM orders o
                                              WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ? AND o.status = 'completed'
                                              AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)
                                              GROUP BY DAY(o.created_at)");
                $stmt_daily->execute([$selected_month, $selected_year]);
                $daily_data_raw = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

                $daily_revenue = array_fill(1, $days_in_month, 0);
                foreach($daily_data_raw as $row) {
                    $daily_revenue[(int)$row['day_num']] = (int)round((float)$row['daily_revenue'] * 25000);
                }

                $total_monthly_revenue = array_sum($daily_revenue);
                $completed_orders_count = count(array_filter($monthly_orders, function($o) { return $o['status'] === 'completed'; }));
            ?>
                <!-- html2canvas & jsPDF for Image and PDF Export -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

                <div class="eu-report-shell">
                <div class="eu-report-hero">
                    <div>
                        <p class="eu-report-kicker">Gym Manager · Period Review</p>
                        <h2><?php echo $current_lang === 'vi' ? 'Báo Cáo Tháng' : 'Monthly Report'; ?> — <?php echo htmlspecialchars($display_month_title); ?></h2>
                        <p><?php echo $current_lang === 'vi' ? 'Tổng Doanh Thu Tháng:' : 'Total Monthly Revenue:'; ?> <strong><?php echo number_format($total_monthly_revenue, 0, ',', '.'); ?> VND</strong></p>
                    </div>
                    <div class="eu-report-controls">
                        <button type="button" onclick="downloadMonthlyReportPDF('<?php echo $selected_month; ?>', '<?php echo $selected_year; ?>', event)" 
                                class="eu-report-btn">
                            <i class="fas fa-file-pdf"></i> <?php echo $current_lang === 'vi' ? 'Tải Báo Cáo Tháng (PDF)' : 'Download Monthly Report (PDF)'; ?>
                        </button>

                        <form method="GET">
                            <input type="hidden" name="page" value="monthly_report">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Tháng:' : 'Month:'; ?></label>
                            <select name="month">
                                <?php 
                                for ($m = 1; $m <= 12; $m++) {
                                    $m_label = $current_lang === 'vi' ? 'Tháng ' . $m : date('F', mktime(0,0,0,$m,1));
                                    echo "<option value='$m' " . ($selected_month == $m ? 'selected' : '') . ">$m_label</option>";
                                }
                                ?>
                            </select>

                            <label style="font-weight: 600;"><?php echo __("select_year"); ?></label>
                            <select name="year">
                                <?php 
                                $curr_yr = date('Y');
                                for ($y = $curr_yr; $y >= $curr_yr - 5; $y--) {
                                    echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="eu-report-filter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </form>
                    </div>
                </div>
                </div>

                <div id="monthly-report-export-area" class="eu-report-canvas">
                    
                    <!-- Top Summary Stats Grid -->
                    <div class="eu-summary-grid">
                        <div class="eu-summary-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'DOANH THU THÁNG' : 'MONTHLY REVENUE'; ?></span>
                                <i class="fas fa-wallet" style="color: #10b981; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;"><?php echo number_format($total_monthly_revenue, 0, ',', '.'); ?>đ</h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $display_month_title; ?></p>
                        </div>

                        <div class="eu-summary-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'ĐƠN HÀNG THÀNH CÔNG' : 'COMPLETED ORDERS'; ?></span>
                                <i class="fas fa-shopping-bag" style="color: #60a5fa; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #60a5fa;"><?php echo $completed_orders_count; ?> / <?php echo count($monthly_orders); ?></h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Tổng đơn hàng sản phẩm' : 'Total store product orders'; ?></p>
                        </div>

                        <div class="eu-summary-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;"><?php echo $current_lang === 'vi' ? 'BUỔI TẬP HLV (PT)' : 'COACH BOOKINGS'; ?></span>
                                <i class="fas fa-dumbbell" style="color: #f59e0b; font-size: 1.2rem;"></i>
                            </div>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #f59e0b;"><?php echo count($monthly_bookings); ?></h3>
                            <p style="margin: 4px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Tổng lịch hẹn huấn luyện' : 'Total training sessions'; ?></p>
                        </div>
                    </div>

                    <!-- Daily Chart Section -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <div class="eu-chart-panel">
                        <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-chart-area" style="color: var(--primary);"></i>
                            <?php echo $current_lang === 'vi' ? 'Biểu Đồ Doanh Thu Theo Ngày' : 'Daily Revenue Growth Chart'; ?> (<?php echo $display_month_title; ?>)
                        </h3>
                        <div style="height: 350px; width: 100%;">
                            <canvas id="monthlyDailyChart"></canvas>
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('monthlyDailyChart');
                        if(canvas) {
                            const ctx = canvas.getContext('2d');
                            
                            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, 'rgba(32, 100, 81, 0.34)');
                            gradient.addColorStop(1, 'rgba(32, 100, 81, 0.02)');

                            const daysLabels = [];
                            for(let d = 1; d <= <?php echo $days_in_month; ?>; d++) {
                                daysLabels.push(String(d).padStart(2, '0'));
                            }

                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: daysLabels,
                                    datasets: [{
                                        label: 'Daily Revenue (VND)',
                                        data: <?php echo json_encode(array_values($daily_revenue)); ?>,
                                        backgroundColor: gradient,
                                        borderColor: '#206451',
                                        borderWidth: 3,
                                        pointBackgroundColor: '#ffffff',
                                        pointBorderColor: '#206451',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        fill: true,
                                        tension: 0.35
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: '#f1f5f9' },
                                            ticks: {
                                                callback: function(value) { return value.toLocaleString('en-GB') + ' VND'; },
                                                font: { family: "'Outfit', sans-serif", weight: '600' }
                                            }
                                        },
                                        x: {
                                            grid: { display: false },
                                            ticks: { font: { family: "'Outfit', sans-serif" } }
                                        }
                                    }
                                }
                            });
                        }
                    });
                    </script>

                    <!-- Coach Performance Summary for Month -->
                    <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                        <i class="fas fa-users-cog" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Tổng Quan HLV Trong Tháng' : 'Coach Performance Summary'; ?> (<?php echo $display_month_title; ?>)
                    </h3>
                    
                    <?php if (empty($monthly_bookings_by_coach)): ?>
                        <p style="color: var(--text-muted); margin-bottom: 3rem;"><?php echo $current_lang === 'vi' ? 'Không có lịch đặt tập nào trong tháng này.' : 'No training bookings recorded for this month.'; ?></p>
                    <?php else: ?>
                        <div style="margin-bottom: 3rem;">
                        <?php foreach ($monthly_bookings_by_coach as $c_name => $c_bookings): ?>
                        <div style="background: white; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <div style="background: #f8fafc; padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                                <h4 style="margin: 0; color: var(--primary); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-user-tie"></i> Coach <?php echo htmlspecialchars($c_name); ?>
                                </h4>
                                <span style="background: var(--primary); color: white; padding: 4px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                                    Bookings: <?php echo count($c_bookings); ?>
                                </span>
                            </div>
                            
                            <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date_time"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("email"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("phone"); ?></th>
                                            <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($c_bookings as $b): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                            <td style="padding: 0.75rem 1rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($b['booking_date'])); ?></td>
                                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #0f172a; white-space: nowrap;"><?php echo htmlspecialchars($b['user_name']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; word-break: break-all; max-width: 200px;"><?php echo htmlspecialchars($b['user_email']); ?></td>
                                            <td style="padding: 0.75rem 1rem; color: #475569; white-space: nowrap;"><?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                                <span class="status-badge status-<?php echo $b['status']; ?>"><?php echo strtoupper($b['status']); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Products Purchased in Month Table -->
                    <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shopping-cart"></i> <?php echo $current_lang === 'vi' ? 'Sản Phẩm Đã Bán Trong Tháng' : 'Products Purchased in Month'; ?> (<?php echo $display_month_title; ?>)
                    </h3>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; border-radius: 12px; border: 1px solid var(--border);">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("date"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("order_id"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("client_name"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("total_price"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: left; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; min-width: 140px;"><?php echo __("items"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;"><?php echo __("status"); ?></th>
                                    <th style="padding: 0.75rem 0.8rem; text-align: center; font-weight: 700; color: #475569; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap;" class="invoice-export-btn"><?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($monthly_orders as $o): 
                                    $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $stmt_items->execute([$o['id']]);
                                    $items = $stmt_items->fetchAll();
                                    if (empty($items)) continue;
                                    $items_str = array_map(function($i) { return $i['name'] . (!empty($i['variant']) ? ' (' . $i['variant'] . ')' : '') . ' x' . $i['quantity']; }, $items);
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: white; transition: background 0.2s;">
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 600; white-space: nowrap;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; font-weight: 800; color: var(--primary); white-space: nowrap;">#<?php echo $o['id']; ?></td>
                                    <td style="padding: 0.75rem 0.8rem; min-width: 190px;">
                                        <strong style="display:block; color:#0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></strong>
                                        <span style="display:block; margin-top:3px; color:#64748b; font-size:.74rem; overflow-wrap:anywhere;"><i class="far fa-envelope"></i> <?php echo htmlspecialchars($o['user_email']); ?></span>
                                        <span style="display:block; margin-top:2px; color:#64748b; font-size:.74rem;"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; color: #10b981; font-weight: 800; white-space: nowrap;"><?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ</td>
                                    <td style="padding: 0.75rem 0.8rem; color: #334155; font-size: 0.8rem; line-height: 1.35; max-width: 220px; word-wrap: break-word;"><?php echo htmlspecialchars(implode(', ', $items_str)); ?></td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <span class="status-badge" style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;">
                                            <?php echo strtoupper($o['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem 0.8rem; text-align: center; white-space: nowrap;">
                                        <button type="button" class="invoice-export-btn" onclick="openInvoiceModal(<?php echo $o['id']; ?>)" 
                                                style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); transition: all 0.2s;">
                                            <i class="fas fa-eye" style="color: #ddc99c;"></i> <?php echo $current_lang === 'vi' ? 'Chi Tiết' : 'Details'; ?>
                                        </button>

                                        <!-- Invoice Modal for Order #<?php echo $o['id']; ?> -->
                                        <div id="invoice-modal-<?php echo $o['id']; ?>" onclick="if(event.target===this) closeInvoiceModal(<?php echo $o['id']; ?>)" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 1.5rem; overflow-y: auto;">
                                            <div style="background: white; border-radius: 24px; width: 100%; max-width: 720px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; font-family: 'Outfit', sans-serif;">
                                                <!-- Action Control Bar -->
                                                <div style="background: #0f172a; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white;">
                                                    <div style="display: flex; align-items: center; gap: 12px;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại' : 'Back'; ?>
                                                        </button>
                                                        <span style="font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                                            <i class="fas fa-receipt" style="color: #f59e0b;"></i> <?php echo $current_lang === 'vi' ? 'Hóa Đơn #' : 'Invoice #'; ?><?php echo $o['id']; ?>
                                                        </span>
                                                    </div>
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <button type="button" onclick="downloadInvoicePDF(<?php echo $o['id']; ?>)" 
                                                                style="background: #ef4444; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'png')" 
                                                                style="background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> PNG
                                                        </button>
                                                        <button type="button" onclick="downloadInvoiceImage(<?php echo $o['id']; ?>, 'jpg')" 
                                                                style="background: #3b82f6; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                                            <i class="fas fa-download"></i> JPG
                                                        </button>
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: rgba(255,255,255,0.15); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;" title="Close">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Printable/Exportable Invoice Card -->
                                                <div style="padding: 2rem; background: #f8fafc;">
                                                    <div id="invoice-card-<?php echo $o['id']; ?>" style="background: #ffffff; padding: 2.5rem; border-radius: 16px; border: 1px solid #e2e8f0; color: #0f172a; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;">
                                                        <!-- Header -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 1.5rem; border-bottom: 2px solid #0062ff;">
                                                            <div>
                                                                <h2 style="margin: 0; color: #0062ff; font-weight: 900; font-size: 1.6rem; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;">
                                                                    <i class="fas fa-bolt" style="color: #f59e0b;"></i> GYM MANAGER
                                                                </h2>
                                                                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                                                                    <?php echo $current_lang === 'vi' ? 'HÓA ĐƠN THANH TOÁN CHÍNH THỨC' : 'OFFICIAL SALES INVOICE'; ?>
                                                                </p>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8;">
                                                                    123 Fitness Ave, District 1, HCMC | Hotline: 1900 8888
                                                                </p>
                                                            </div>
                                                            <div style="text-align: right;">
                                                                <span style="background: #eff6ff; color: #0062ff; font-weight: 800; font-size: 0.9rem; padding: 6px 14px; border-radius: 20px; border: 1px solid #bfdbfe; display: inline-block;">
                                                                    INV-#<?php echo sprintf('%05d', $o['id']); ?>
                                                                </span>
                                                                <p style="margin: 6px 0 0; font-size: 0.8rem; color: #64748b;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Ngày:' : 'Date:'; ?></strong> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Customer Info -->
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; background: #f8fafc; padding: 1.2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Khách Hàng' : 'Billed To'; ?></h4>
                                                                <p style="margin: 0; font-weight: 800; font-size: 1rem; color: #0f172a;"><?php echo htmlspecialchars($o['user_name']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['user_email']); ?></p>
                                                                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div>
                                                                <h4 style="margin: 0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Payment Status'; ?></h4>
                                                                <span style="background: #dcfce7; color: #166534; font-weight: 800; font-size: 0.8rem; padding: 4px 10px; border-radius: 12px; display: inline-block; margin-bottom: 6px;">
                                                                    ✔ <?php echo strtoupper($o['status']); ?>
                                                                </span>
                                                                <p style="margin: 0; font-size: 0.85rem; color: #475569;">
                                                                    <strong><?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?></strong> Gym Online Store
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <!-- Item Table -->
                                                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.88rem;">
                                                            <thead>
                                                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; background: #f1f5f9;">
                                                                    <th style="padding: 10px; border-radius: 8px 0 0 8px; color: #475569; font-weight: 700;">#</th>
                                                                    <th style="padding: 10px; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Sản Phẩm' : 'Product Item'; ?></th>
                                                                    <th style="padding: 10px; text-align: center; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'SL' : 'Qty'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Đơn Giá' : 'Unit Price'; ?></th>
                                                                    <th style="padding: 10px; text-align: right; border-radius: 0 8px 8px 0; color: #475569; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Thành Tiền' : 'Total'; ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $idx = 1;
                                                                foreach ($items as $item): 
                                                                    $item_total = $item['price'] * $item['quantity'];
                                                                ?>
                                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                    <td style="padding: 10px; font-weight: 600; color: #64748b;"><?php echo $idx++; ?></td>
                                                                    <td style="padding: 10px; font-weight: 700; color: #0f172a;">
                                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 400;">(<?php echo htmlspecialchars($item['variant']); ?>)</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td style="padding: 10px; text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                                                    <td style="padding: 10px; text-align: right; color: #475569;"><?php echo number_format((int)round($item['price'] * 25000), 0, ',', '.'); ?>đ</td>
                                                                    <td style="padding: 10px; text-align: right; font-weight: 700; color: #0062ff;"><?php echo number_format((int)round($item_total * 25000), 0, ',', '.'); ?>đ</td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                        <!-- Summary -->
                                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: white; padding: 1.2rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                                                            <div>
                                                                <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'TỔNG CỘNG THANH TOÁN' : 'GRAND TOTAL'; ?></span>
                                                                <p style="margin: 2px 0 0; font-size: 0.75rem; color: #cbd5e1;"><?php echo $current_lang === 'vi' ? 'Đã bao gồm thuế & phí' : 'Taxes & fees included'; ?></p>
                                                            </div>
                                                            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 900; color: #10b981;">
                                                                <?php echo number_format((int)round($o['total_price'] * 25000), 0, ',', '.'); ?>đ
                                                            </h2>
                                                        </div>

                                                        <!-- Footer Stamp -->
                                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                                                            <div>
                                                                <div style="display: inline-flex; align-items: center; gap: 6px; border: 2px solid #16a34a; color: #16a34a; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; transform: rotate(-2deg);">
                                                                    <i class="fas fa-check-circle"></i> GYM MANAGER VERIFIED
                                                                </div>
                                                                <p style="margin: 6px 0 0; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                                                                    Thank you for your business!
                                                                </p>
                                                            </div>
                                                            <div style="text-align: center;">
                                                                <p style="margin: 0; font-size: 0.8rem; font-weight: 700; color: #475569;"><?php echo $current_lang === 'vi' ? 'Xác Nhận Quản Lý' : 'Authorized Signature'; ?></p>
                                                                <div style="height: 30px;"></div>
                                                                <p style="margin: 0; font-size: 0.75rem; color: #94a3b8;">(Signed Digitally)</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Back Button -->
                                                    <div style="margin-top: 1.5rem; text-align: center;">
                                                        <button type="button" onclick="closeInvoiceModal(<?php echo $o['id']; ?>)" 
                                                                style="background: #475569; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(71,85,105,0.25); transition: transform 0.2s;">
                                                            <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay lại danh sách' : 'Back to List'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($monthly_orders)): ?>
                                <tr><td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-muted);"><?php echo $current_lang === 'vi' ? 'Không có đơn hàng nào trong tháng này.' : 'No orders found for this month.'; ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'member_subscriptions'): 
                // Fetch stats (Identical to Booking Management 4-card structure)
                $stat_total   = $pdo->query("SELECT COUNT(*) FROM member_subscriptions")->fetchColumn();
                $stat_pending = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status = 'pending'")->fetchColumn();
                $stat_active  = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status = 'active'")->fetchColumn();
                $stat_expired = $pdo->query("SELECT COUNT(*) FROM member_subscriptions WHERE status IN ('expired', 'cancelled')")->fetchColumn();

                // Filters & Search logic
                $search = trim($_GET['search'] ?? '');
                $status_filter = trim($_GET['status_filter'] ?? '');
                
                $query_str = "SELECT ms.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.avatar as user_avatar, 
                                     mp.name as plan_name, mp.name_vi as plan_name_vi, mp.badge, mp.badge_color, mp.price as plan_default_price, mp.duration_days as plan_duration_days
                              FROM member_subscriptions ms 
                              JOIN users u ON ms.user_id = u.id 
                              JOIN membership_plans mp ON ms.plan_id = mp.id";
                
                $params = [];
                $conditions = [];
                
                if ($search !== '') {
                    $conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR mp.name LIKE ? OR mp.name_vi LIKE ? OR ms.id LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                if ($status_filter !== '') {
                    if ($status_filter === 'expired_all') {
                        $conditions[] = "ms.status IN ('expired', 'cancelled')";
                    } elseif (in_array($status_filter, ['pending', 'active', 'expired', 'cancelled'])) {
                        $conditions[] = "ms.status = ?";
                        $params[] = $status_filter;
                    }
                }
                
                if (!empty($conditions)) {
                    $query_str .= " WHERE " . implode(" AND ", $conditions);
                }
                
                $query_str .= " ORDER BY ms.id DESC";
                
                $stmt = $pdo->prepare($query_str);
                $stmt->execute($params);
                $all_subs = $stmt->fetchAll();

                // Fetch all users for dropdown
                $all_users = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
                
                // Fetch all active plans for dropdown
                $all_plans = $pdo->query("SELECT id, name, name_vi, price, duration_days, badge FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

                // Check if editing a subscription
                $edit_sub = null;
                if (isset($_GET['edit_sub'])) {
                    $stmt_es = $pdo->prepare("SELECT ms.*, u.name as user_name, u.email as user_email FROM member_subscriptions ms JOIN users u ON ms.user_id = u.id WHERE ms.id = ?");
                    $stmt_es->execute([$_GET['edit_sub']]);
                    $edit_sub = $stmt_es->fetch();
                }
            ?>
                <!-- Section Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><?php echo __("membership_management"); ?></h2>
                </div>

                <!-- Statistics Cards (Exact Booking Management Grid Style) -->
                <div class="grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Tổng Thẻ Thành Viên' : 'Total Memberships'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 5px;"><?php echo $stat_total; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=pending" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #fff7ed; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #ffedd5; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(234,88,12,0.12)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #c2410c; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Chờ Xác Nhận' : 'Pending'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #ea580c; margin-top: 5px;"><?php echo $stat_pending; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=active" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid #dcfce7; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(22,163,74,0.12)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #15803d; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #16a34a; margin-top: 5px;"><?php echo $stat_active; ?></p>
                        </div>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=expired_all" style="text-decoration: none; color: inherit; display: block;">
                        <div style="background: #fafafa; padding: 1.5rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                            <h4 style="color: #4b5563; font-size: 0.8rem; text-transform: uppercase;"><?php echo $current_lang === 'vi' ? 'Hết Hạn / Đã Hủy' : 'Expired / Cancelled'; ?></h4>
                            <p style="font-size: 2rem; font-weight: 800; color: #4b5563; margin-top: 5px;"><?php echo $stat_expired; ?></p>
                        </div>
                    </a>
                </div>

                <!-- Interactive Status Tabs Bar (Trình bày hiện rõ trong Tab) -->
                <div class="membership-tabs-nav" style="display: flex; gap: 0.6rem; margin-bottom: 1.8rem; flex-wrap: wrap; background: #ffffff; padding: 0.6rem 0.8rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo empty($status_filter) ? 'background: #1a2744; color: white; box-shadow: 0 4px 12px rgba(26,39,68,0.2);' : 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;'; ?>">
                        <i class="fas fa-layer-group"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Tất Cả Thẻ' : 'All Cards'; ?></span>
                        <span style="background: <?php echo empty($status_filter) ? 'rgba(255,255,255,0.25)' : '#e2e8f0'; ?>; color: <?php echo empty($status_filter) ? '#fff' : '#475569'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_total; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=pending<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo $status_filter === 'pending' ? 'background: #ea580c; color: white; box-shadow: 0 4px 12px rgba(234,88,12,0.25);' : 'background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;'; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Chờ Duyệt' : 'Pending'; ?></span>
                        <span style="background: <?php echo $status_filter === 'pending' ? 'rgba(255,255,255,0.25)' : '#fed7aa'; ?>; color: <?php echo $status_filter === 'pending' ? '#fff' : '#9a3412'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_pending; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=active<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo $status_filter === 'active' ? 'background: #16a34a; color: white; box-shadow: 0 4px 12px rgba(22,163,74,0.25);' : 'background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;'; ?>">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?></span>
                        <span style="background: <?php echo $status_filter === 'active' ? 'rgba(255,255,255,0.25)' : '#bbf7d0'; ?>; color: <?php echo $status_filter === 'active' ? '#fff' : '#14532d'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_active; ?></span>
                    </a>
                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&status_filter=expired_all<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; text-decoration: none; transition: all 0.2s; <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? 'background: #ef4444; color: white; box-shadow: 0 4px 12px rgba(239,68,68,0.25);' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2;'; ?>">
                        <i class="fas fa-history"></i>
                        <span><?php echo $current_lang === 'vi' ? 'Hết Hạn / Đã Hủy' : 'Expired / Cancelled'; ?></span>
                        <span style="background: <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? 'rgba(255,255,255,0.25)' : '#fecaca'; ?>; color: <?php echo in_array($status_filter, ['expired', 'cancelled', 'expired_all']) ? '#fff' : '#991b1b'; ?>; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><?php echo $stat_expired; ?></span>
                    </a>
                </div>

                <?php if ($edit_sub): ?>
                <!-- Edit Subscription Form (Exact Booking Management Style) -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; border: 2px solid var(--primary); box-shadow: var(--shadow-premium);">
                    <input type="hidden" name="sub_id" value="<?php echo $edit_sub['id']; ?>">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-edit" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa Thẻ Thành Viên #' . $edit_sub['id'] : 'Edit Membership #' . $edit_sub['id']; ?> (<?php echo htmlspecialchars($edit_sub['user_name']); ?>)
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Gói Thành Viên' : 'Membership Plan'; ?></label>
                            <select name="plan_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $edit_sub['plan_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ - <?php echo $p['duration_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày' : 'days'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" value="<?php echo $edit_sub['start_date']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" value="<?php echo $edit_sub['end_date']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán (VNĐ)' : 'Price Paid (VNĐ)'; ?></label>
                            <input type="number" step="1000" name="price_paid" value="<?php echo $edit_sub['price_paid']; ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Phương Thức' : 'Payment Method'; ?></label>
                            <select name="payment_method" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="cod" <?php echo $edit_sub['payment_method'] == 'cod' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Tại quầy (COD)' : 'At counter (COD)'; ?></option>
                                <option value="momo" <?php echo $edit_sub['payment_method'] == 'momo' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Ví MoMo' : 'MoMo Wallet'; ?></option>
                                <option value="cash" <?php echo $edit_sub['payment_method'] == 'cash' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Tiền mặt' : 'Cash'; ?></option>
                                <option value="bank_transfer" <?php echo $edit_sub['payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>><?php echo $current_lang === 'vi' ? 'Chuyển khoản' : 'Bank Transfer'; ?></option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái Thẻ' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" <?php echo $edit_sub['status'] == 'active' ? 'selected' : ''; ?>>🟢 <?php echo $current_lang === 'vi' ? 'Active (Đang hoạt động)' : 'Active'; ?></option>
                                <option value="pending" <?php echo $edit_sub['status'] == 'pending' ? 'selected' : ''; ?>>🟡 <?php echo $current_lang === 'vi' ? 'Pending (Chờ duyệt)' : 'Pending'; ?></option>
                                <option value="expired" <?php echo $edit_sub['status'] == 'expired' ? 'selected' : ''; ?>>🔴 <?php echo $current_lang === 'vi' ? 'Expired (Đã hết hạn)' : 'Expired'; ?></option>
                                <option value="cancelled" <?php echo $edit_sub['status'] == 'cancelled' ? 'selected' : ''; ?>>⚪ <?php echo $current_lang === 'vi' ? 'Cancelled (Đã hủy)' : 'Cancelled'; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ghi Chú Quản Trị' : 'Admin Notes'; ?></label>
                        <input type="text" name="note" value="<?php echo htmlspecialchars($edit_sub['note'] ?? ''); ?>" placeholder="<?php echo $current_lang === 'vi' ? 'Nhập ghi chú thêm...' : 'Additional notes...'; ?>" style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_subscription" class="btn-auth" style="margin-top: 0; width: auto; padding: 1.1rem 2rem;"><?php echo $current_lang === 'vi' ? 'Lưu Thay Đổi' : 'Save Changes'; ?></button>
                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 15px; padding: 1.1rem 1.5rem; height: auto; display: flex; align-items: center; text-decoration: none; font-weight: 700;"><?php echo $current_lang === 'vi' ? 'Hủy Bỏ' : 'Cancel'; ?></a>
                    </div>
                </form>
                <?php else: ?>
                <!-- Create Direct Membership Form (Exact Booking Management Style) -->
                <form method="POST" style="background: white; padding: 2.5rem; border-radius: 25px; margin: 2rem 0; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên Trực Tiếp' : 'Create Direct Membership'; ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Học Viên' : 'Client'; ?></label>
                            <select name="user_id" required style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected><?php echo $current_lang === 'vi' ? '-- Chọn Học Viên --' : '-- Select Client --'; ?></option>
                                <?php foreach($all_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Gói Thành Viên' : 'Membership Plan'; ?></label>
                            <select name="plan_id" id="create_sub_plan_id" required onchange="handlePlanSelect(this)" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="" disabled selected><?php echo $current_lang === 'vi' ? '-- Chọn Gói --' : '-- Select Plan --'; ?></option>
                                <?php foreach($all_plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-days="<?php echo $p['duration_days']; ?>">
                                        <?php echo htmlspecialchars(($current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name']); ?> (<?php echo number_format($p['price'], 0, ',', '.'); ?>đ)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Bắt Đầu' : 'Start Date'; ?></label>
                            <input type="date" name="start_date" id="create_sub_start_date" value="<?php echo date('Y-m-d'); ?>" required onchange="calcEndDate()" style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?></label>
                            <select name="status" style="padding: 1.1rem; border-radius: 15px; background: #f1f5f9; border: none; width: 100%; outline: none; font-weight: 600;">
                                <option value="active" selected>🟢 <?php echo $current_lang === 'vi' ? 'Active (Kích hoạt ngay)' : 'Active (Immediate)'; ?></option>
                                <option value="pending">🟡 <?php echo $current_lang === 'vi' ? 'Pending (Chờ xác nhận)' : 'Pending (Requires Confirmation)'; ?></option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 1.5rem; margin-top: 1.5rem; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn' : 'End Date'; ?></label>
                            <input type="date" name="end_date" id="create_sub_end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Giá Thanh Toán' : 'Price Paid'; ?></label>
                            <input type="number" step="1000" name="price_paid" id="create_sub_price_paid" placeholder="899000" required style="background: #f1f5f9; padding: 1.1rem; border-radius: 15px; border: none; width: 100%; outline: none; font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px; height: 55px;">
                            <input type="checkbox" name="overwrite_existing" value="1" id="add_overwrite" checked style="width: 20px; height: 20px; accent-color: var(--primary);">
                            <label for="add_overwrite" style="font-weight: 600; cursor: pointer; color: #64748b; font-size: 0.9rem;">
                                <?php echo $current_lang === 'vi' ? 'Thay thế thẻ cũ của khách hàng nếu đang có thẻ hoạt động' : 'Overwrite previous active membership if user already has one'; ?>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="add_subscription" class="btn-auth" style="margin-top: 1.5rem; width: auto; padding: 1.1rem 2.5rem;"><?php echo $current_lang === 'vi' ? 'Tạo Thẻ Thành Viên' : 'Create Membership'; ?></button>
                </form>
                <?php endif; ?>

                <!-- Search & Filters (Exact Booking Management Style) -->
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem; background: #f8fafc; padding: 1.2rem; border-radius: 20px; border: 1px solid var(--border);">
                    <input type="hidden" name="page" value="member_subscriptions">
                    <div style="flex: 2; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $current_lang === 'vi' ? 'Tìm theo tên hội viên, email, số điện thoại, mã thẻ, gói tập...' : 'Search member name, email, phone, card ID, plan...'; ?>" style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-size: 0.95rem; outline: none;">
                    </div>
                    <div style="flex: 1;">
                        <select name="status_filter" style="width: 100%; padding: 1rem; background: white; border: 1px solid var(--border); border-radius: 15px; font-weight: 600; outline: none; font-size: 0.95rem;">
                            <option value=""><?php echo $current_lang === 'vi' ? '-- Tất Cả Trạng Thái --' : '-- All Statuses --'; ?></option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending (<?php echo $current_lang === 'vi' ? 'Chờ Duyệt' : 'Pending'; ?>)</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active (<?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active'; ?>)</option>
                            <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired (<?php echo $current_lang === 'vi' ? 'Hết Hạn' : 'Expired'; ?>)</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled (<?php echo $current_lang === 'vi' ? 'Đã Hủy' : 'Cancelled'; ?>)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-auth" style="margin-top: 0; width: auto; padding: 1rem 2rem; font-size: 0.95rem;"><?php echo __("search"); ?></button>
                    <?php if ($search !== '' || $status_filter !== ''): ?>
                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); padding: 1rem 1.5rem; border-radius: 15px; text-decoration: none; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center;"><?php echo $current_lang === 'vi' ? 'Đặt Lại Bộ Lọc' : 'Reset Filter'; ?></a>
                    <?php endif; ?>
                </form>

                <!-- Subscriptions Table (Exact Booking Management Table Structure) -->
                <?php if (empty($all_subs)): ?>
                    <div style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 20px; border: 1px solid var(--border); margin-top: 2rem;">
                        <i class="far fa-id-card" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;"><?php echo $current_lang === 'vi' ? 'Không tìm thấy thẻ thành viên nào phù hợp bộ lọc.' : 'No membership subscriptions found matching filters.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: 0;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 1.2rem; border-radius: 15px 0 0 15px;"><?php echo $current_lang === 'vi' ? 'HỘI VIÊN' : 'CLIENT'; ?></th>
                                    <th style="padding: 1.2rem;"><?php echo $current_lang === 'vi' ? 'GÓI THÀNH VIÊN' : 'MEMBERSHIP PLAN'; ?></th>
                                    <th style="padding: 1.2rem;"><?php echo $current_lang === 'vi' ? 'THỜI HẠN & HIỆU LỰC' : 'VALIDITY & DATES'; ?></th>
                                    <th style="padding: 1.2rem; border-radius: 0 15px 15px 0; text-align: center;">DETAILS & ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_subs as $s): 
                                    $p_name = ($current_lang === 'vi' && !empty($s['plan_name_vi'])) ? $s['plan_name_vi'] : $s['plan_name'];
                                    $b_bg   = !empty($s['badge_color']) ? $s['badge_color'] : '#2563eb';
                                    $b_txt  = !empty($s['badge']) ? $s['badge'] : mb_strtoupper($p_name);
                                    $end_ts = strtotime($s['end_date']);
                                    $today_ts = strtotime(date('Y-m-d'));
                                    $days_left = ceil(($end_ts - $today_ts) / 86400);
                                ?>
                                <tr>
                                    <td style="padding: 1.2rem; background: white; border-radius: 15px 0 0 15px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo $s['user_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($s['user_name']).'&background=0062ff&color=fff&size=40'; ?>" 
                                                 style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
                                            <div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <strong style="color: var(--text-main); font-weight: 700;"><?php echo htmlspecialchars($s['user_name']); ?></strong>
                                                    <span style="background: #f1f5f9; color: #475569; font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 6px;">#<?php echo $s['id']; ?></span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($s['user_email']); ?></div>
                                                <?php if (!empty($s['user_phone'])): ?>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                                        <i class="fas fa-phone-alt" style="font-size: 0.75rem; color: var(--primary);"></i> <?php echo htmlspecialchars($s['user_phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div>
                                            <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo htmlspecialchars($p_name); ?></strong>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 4px; align-items: center;">
                                                <span style="background: <?php echo $b_bg; ?>; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 7px; border-radius: 6px; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($b_txt); ?>
                                                </span>
                                                <?php if (!empty($s['is_renewal'])): ?>
                                                    <span style="background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 7px; border-radius: 6px; display: inline-flex; align-items: center; gap: 3px;">
                                                        <i class="fas fa-crown"></i> <?php echo $current_lang === 'vi' ? 'GIA HẠN' : 'RENEWAL'; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span style="font-size: 0.82rem; font-weight: 800; color: #ef4444; margin-left: 2px;">
                                                    <?php echo number_format($s['price_paid'], 0, ',', '.'); ?>đ
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white;">
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <i class="far fa-calendar-alt" style="color: var(--primary); margin-right: 5px;"></i>
                                            <?php echo date('d/m/Y', strtotime($s['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($s['end_date'])); ?>
                                        </div>
                                        <div style="display: flex; gap: 6px; align-items: center; margin-top: 4px;">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <?php if ($days_left > 0): ?>
                                                    <span style="font-size: 0.75rem; color: #16a34a; font-weight: 700; background: #dcfce7; padding: 2px 8px; border-radius: 10px;">
                                                        ⚡ <?php echo $current_lang === 'vi' ? "Còn $days_left ngày" : "$days_left days left"; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; background: #fee2e2; padding: 2px 8px; border-radius: 10px;">
                                                        <?php echo $current_lang === 'vi' ? 'Hôm nay hết hạn' : 'Expires today'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php elseif ($s['status'] === 'pending'): ?>
                                                <span style="font-size: 0.75rem; color: #d97706; font-weight: 700; background: #fef3c7; padding: 2px 8px; border-radius: 10px;">
                                                    ⏳ <?php echo $current_lang === 'vi' ? 'Chờ kích hoạt' : 'Pending'; ?>
                                                </span>
                                            <?php elseif ($s['status'] === 'expired'): ?>
                                                <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700; background: #fee2e2; padding: 2px 8px; border-radius: 10px;">
                                                    <?php echo $current_lang === 'vi' ? 'Đã hết hạn' : 'Expired'; ?>
                                                </span>
                                            <?php elseif ($s['status'] === 'cancelled'): ?>
                                                <span style="font-size: 0.75rem; color: #64748b; font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 10px;">
                                                    <?php echo $current_lang === 'vi' ? 'Đã hủy' : 'Cancelled'; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['bonus_days']) && $s['bonus_days'] > 0): ?>
                                                <span style="font-size: 0.75rem; color: #15803d; font-weight: 700;">+<?php echo $s['bonus_days']; ?>d free</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem; background: white; border-radius: 0 15px 15px 0; text-align: center;">
                                        <button type="button" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 7px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; background: linear-gradient(135deg, #1a2744, #2563eb); color: white; border: none;" onclick="showSubDetailsModal(<?php echo $s['id']; ?>)">
                                            <i class="fas fa-eye"></i> <?php echo $current_lang === 'vi' ? 'Xem Chi Tiết' : 'Details'; ?>
                                        </button>

                                        <!-- Hidden Modal Content for Subscription #<?php echo $s['id']; ?> -->
                                        <div id="sub-modal-content-<?php echo $s['id']; ?>" style="display:none;">
                                            <!-- Membership Header & Status Badge -->
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1.2rem; border-bottom: 1.5px solid #e2e8f0;">
                                                <div>
                                                    <h3 style="margin: 0; color: var(--primary); font-weight: 800; font-size: 1.2rem;">
                                                        <?php echo $current_lang === 'vi' ? 'Thẻ Thành Viên #' : 'Membership Card #'; ?><?php echo $s['id']; ?>
                                                    </h3>
                                                    <span style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                                                        📅 <?php echo date('d/m/Y H:i', strtotime($s['created_at'] ?? $s['start_date'])); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <?php
                                                    $st_styles = [
                                                        'active' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'border' => '#86efac', 'label' => '🟢 ACTIVE'],
                                                        'pending' => ['bg' => '#fef3c7', 'color' => '#d97706', 'border' => '#fde68a', 'label' => '🟡 PENDING'],
                                                        'expired' => ['bg' => '#fee2e2', 'color' => '#ef4444', 'border' => '#fca5a5', 'label' => '🔴 EXPIRED'],
                                                        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'border' => '#cbd5e1', 'label' => '⚪ CANCELLED'],
                                                    ];
                                                    $badge_curr = $st_styles[$s['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'border' => '#cbd5e1', 'label' => strtoupper($s['status'])];
                                                    ?>
                                                    <span style="display: inline-block; padding: 6px 14px; font-weight: 800; font-size: 0.8rem; border-radius: 20px; background: <?php echo $badge_curr['bg']; ?>; color: <?php echo $badge_curr['color']; ?>; border: 1px solid <?php echo $badge_curr['border']; ?>;">
                                                        <?php echo $badge_curr['label']; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Member & Plan Info Cards -->
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem; background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; text-align: left;">
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        👤 <?php echo $current_lang === 'vi' ? 'Hội Viên' : 'Member Info'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($s['user_name']); ?></div>
                                                    <div style="font-size: 0.82rem; color: #475569;"><?php echo htmlspecialchars($s['user_email']); ?></div>
                                                    <?php if (!empty($s['user_phone'])): ?>
                                                        <div style="font-size: 0.82rem; color: #334155; margin-top: 2px;">📞 <?php echo htmlspecialchars($s['user_phone']); ?></div>
                                                    <?php endif; ?>
                                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">User ID: #<?php echo $s['user_id']; ?></div>
                                                </div>
                                                <div>
                                                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                                        💳 <?php echo $current_lang === 'vi' ? 'Gói Tập & Chi Phí' : 'Plan & Payment'; ?>
                                                    </div>
                                                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;"><?php echo htmlspecialchars($p_name); ?></div>
                                                    <div style="font-size: 0.9rem; color: #ef4444; font-weight: 800;"><?php echo number_format($s['price_paid'], 0, ',', '.'); ?>đ</div>
                                                    <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;">
                                                        <?php echo $current_lang === 'vi' ? 'Phương thức:' : 'Method:'; ?> <strong><?php echo strtoupper($s['payment_method']); ?></strong>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: #334155; margin-top: 2px;">
                                                        📅 <?php echo date('d/m/Y', strtotime($s['start_date'])); ?> → <?php echo date('d/m/Y', strtotime($s['end_date'])); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!empty($s['is_renewal'])): ?>
                                            <!-- Loyalty & Renewal Privileges Card -->
                                            <div style="background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 16px; padding: 1.2rem; margin-bottom: 1.2rem; text-align: left; font-size: 0.88rem; color: #92400e;">
                                                <div style="font-weight: 800; display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                                    <i class="fas fa-crown" style="color: #d97706;"></i> <?php echo $current_lang === 'vi' ? 'ĐẶC QUYỀN GIA HẠN / HỘI VIÊN THÂN THIẾT' : 'RENEWAL & LOYALTY PRIVILEGES'; ?>
                                                </div>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                                    <?php if (!empty($s['discount_amount']) && $s['discount_amount'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Giảm giá tri ân:' : 'Discount:'; ?> <strong>-<?php echo number_format($s['discount_amount'], 0, ',', '.'); ?>đ</strong></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($s['bonus_days']) && $s['bonus_days'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Tặng thêm:' : 'Bonus:'; ?> <strong>+<?php echo $s['bonus_days']; ?> <?php echo $current_lang === 'vi' ? 'ngày tập free' : 'free days'; ?></strong></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($s['bonus_pt']) && $s['bonus_pt'] > 0): ?>
                                                        <div>• <?php echo $current_lang === 'vi' ? 'Tặng kèm:' : 'Bonus:'; ?> <strong>+<?php echo $s['bonus_pt']; ?> buổi PT 1:1</strong></div>
                                                    <?php endif; ?>
                                                    <div>• <?php echo $current_lang === 'vi' ? 'Quà tặng:' : 'Gift:'; ?> <strong>Bình GymX Shaker VIP Pro + 2 Guest Pass</strong></div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <!-- Notes Card -->
                                            <div style="background: #f1f5f9; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-sticky-note" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Ghi Chú Hệ Thống' : 'System Notes'; ?>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #334155; font-weight: 600; line-height: 1.4;">
                                                    <?php echo !empty($s['note']) ? htmlspecialchars($s['note']) : '<span style="color: #94a3b8; font-style: italic; font-weight: 500;">' . ($current_lang === 'vi' ? 'Chưa có ghi chú' : 'None') . '</span>'; ?>
                                                </div>
                                            </div>

                                            <!-- Action Controls inside Modal (Exact Booking Management Action Style) -->
                                            <div style="background: #f8fafc; padding: 1.2rem; border-radius: 16px; border: 1px solid #cbd5e1; text-align: left;">
                                                <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-cogs" style="color: var(--gold);"></i> <?php echo $current_lang === 'vi' ? 'Thao Tác Quản Lý Thẻ Thành Viên' : 'Membership Management Actions'; ?>
                                                </div>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                                    <?php if ($s['status'] === 'pending'): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&approve_sub=<?php echo $s['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Xác nhận KÍCH HOẠT thẻ thành viên này cho hội viên?' : 'Confirm activation of this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Kích Hoạt Thẻ' : 'Activate Membership'; ?>', '⚡');">
                                                            <i class="fas fa-check-circle"></i> <?php echo $current_lang === 'vi' ? 'Xác Nhận Kích Hoạt' : 'Confirm & Activate'; ?>
                                                        </a>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&reject_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn TỪ CHỐI yêu cầu đăng ký thẻ này?' : 'Are you sure you want to reject this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Từ Chối Thẻ' : 'Reject Membership'; ?>', '❌');">
                                                            <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Từ Chối' : 'Reject'; ?>
                                                        </a>
                                                    <?php elseif ($s['status'] === 'active'): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&reject_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn HỦY thẻ thành viên này?' : 'Are you sure you want to cancel this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Hủy Thẻ' : 'Cancel Membership'; ?>', '❌');">
                                                            <i class="fas fa-ban"></i> <?php echo $current_lang === 'vi' ? 'Hủy Thẻ' : 'Cancel'; ?>
                                                        </a>
                                                    <?php elseif (in_array($s['status'], ['expired', 'cancelled'])): ?>
                                                        <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&approve_sub=<?php echo $s['id']; ?>" class="action-btn btn-add" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Kích hoạt lại thẻ thành viên này?' : 'Re-activate this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Kích Hoạt Lại' : 'Re-activate'; ?>', '⚡');">
                                                            <i class="fas fa-redo"></i> <?php echo $current_lang === 'vi' ? 'Kích Hoạt Lại' : 'Re-activate'; ?>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&edit_sub=<?php echo $s['id']; ?>" class="action-btn btn-edit" style="font-size: 0.82rem; padding: 8px 16px; text-decoration: none;">
                                                        <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa' : 'Edit'; ?>
                                                    </a>

                                                    <a href="/Gym Manager/dashboards/manager.php?page=member_subscriptions&delete_sub=<?php echo $s['id']; ?>" class="action-btn btn-delete" style="font-size: 0.82rem; padding: 8px 16px; background: #fff1f2; color: #ef4444; text-decoration: none; margin-left: auto;" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'CẢNH BÁO: Bạn có chắc chắn muốn XÓA VĨNH VIỄN thẻ thành viên này?' : 'WARNING: Are you sure you want to PERMANENTLY DELETE this membership?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete Permanently'; ?>', '🗑️');">
                                                        <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'vi' ? 'Xóa Vĩnh Viễn' : 'Delete'; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Membership Details Modal Shell (Exact Booking Details Modal Shell Style) -->
                    <div id="subDetailsModalShell" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
                        <div style="background: #ffffff; width: 100%; max-width: 660px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden; animation: euModalPop 0.3s ease forwards;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.6rem; background: linear-gradient(135deg, #1a2744, #2563eb); color: white;">
                                <div style="font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-id-card" style="color: var(--gold);"></i>
                                    <span><?php echo $current_lang === 'vi' ? 'Chi Tiết & Quản Lý Thẻ Thành Viên' : 'Membership Details & Actions'; ?></span>
                                </div>
                                <button type="button" onclick="closeSubDetailsModal()" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                                    &times;
                                </button>
                            </div>
                            <div id="subDetailsModalBody" style="padding: 1.6rem; max-height: 80vh; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <script>
                    function showSubDetailsModal(subId) {
                        const src = document.getElementById('sub-modal-content-' + subId);
                        const body = document.getElementById('subDetailsModalBody');
                        const shell = document.getElementById('subDetailsModalShell');
                        if (src && body && shell) {
                            body.innerHTML = src.innerHTML;
                            shell.style.display = 'flex';
                        }
                    }
                    function closeSubDetailsModal() {
                        const shell = document.getElementById('subDetailsModalShell');
                        if (shell) shell.style.display = 'none';
                    }
                    window.addEventListener('click', function(e) {
                        const shell = document.getElementById('subDetailsModalShell');
                        if (e.target === shell) {
                            closeSubDetailsModal();
                        }
                    });

                    function handlePlanSelect(selectEl) {
                        const opt = selectEl.options[selectEl.selectedIndex];
                        if (opt) {
                            const price = opt.getAttribute('data-price');
                            const days = parseInt(opt.getAttribute('data-days')) || 30;
                            if (price) {
                                document.getElementById('create_sub_price_paid').value = price;
                            }
                            calcEndDate(days);
                        }
                    }

                    function calcEndDate(customDays) {
                        const startInput = document.getElementById('create_sub_start_date');
                        const endInput = document.getElementById('create_sub_end_date');
                        const planSelect = document.getElementById('create_sub_plan_id');
                        if (!startInput || !endInput) return;
                        
                        let days = customDays;
                        if (!days && planSelect) {
                            const opt = planSelect.options[planSelect.selectedIndex];
                            if (opt) days = parseInt(opt.getAttribute('data-days')) || 30;
                        }
                        if (!days) days = 30;
                        
                        const startDate = new Date(startInput.value || new Date());
                        startDate.setDate(startDate.getDate() + days);
                        endInput.value = startDate.toISOString().split('T')[0];
                    }

                    // Auto open modal if sub_id or confirm_action is passed in URL
                    document.addEventListener('DOMContentLoaded', function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const subId = urlParams.get('sub_id');
                        if (subId) {
                            showSubDetailsModal(subId);
                        }
                    });
                    </script>
                <?php endif; ?>

            <?php elseif ($page == 'equipment'): 
                $all_equipment = $pdo->query("SELECT * FROM equipment ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                $total_eq = count($all_equipment);
                $active_eq = count(array_filter($all_equipment, function($e) { return $e['status'] === 'active'; }));
                $maint_eq = count(array_filter($all_equipment, function($e) { return $e['status'] === 'maintenance'; }));

                $today_dt = new DateTime('today');
                $overdue_eq = count(array_filter($all_equipment, function($e) use ($today_dt) {
                    if ($e['status'] === 'maintenance') return true;
                    if (empty($e['next_maintenance_date'])) return false;
                    return new DateTime($e['next_maintenance_date']) < $today_dt;
                }));

                $upcoming_eq = count(array_filter($all_equipment, function($e) use ($today_dt) {
                    if ($e['status'] === 'maintenance' || empty($e['next_maintenance_date'])) return false;
                    $m_dt = new DateTime($e['next_maintenance_date']);
                    if ($m_dt < $today_dt) return false;
                    $diff = $today_dt->diff($m_dt);
                    return $diff->days <= 15;
                }));

                $in_warranty_eq = count(array_filter($all_equipment, function($e) use ($today_dt) {
                    if (empty($e['warranty_expiry_date'])) return false;
                    return new DateTime($e['warranty_expiry_date']) >= $today_dt;
                }));

                // Determine initially selected equipment for the Details Tab
                $selected_eq_id = intval($_GET['details_id'] ?? ($_GET['edit_equipment'] ?? 0));
                $selected_eq = null;
                if ($selected_eq_id > 0) {
                    foreach ($all_equipment as $eq_item) {
                        if (intval($eq_item['id']) === $selected_eq_id) {
                            $selected_eq = $eq_item;
                            break;
                        }
                    }
                }
                if (!$selected_eq && !empty($all_equipment)) {
                    $selected_eq = $all_equipment[0];
                }

                $initial_tab = (!empty($_GET['details_id']) || !empty($_GET['edit_equipment'])) ? 'details' : ((isset($_GET['action']) && $_GET['action'] === 'add') ? 'add' : 'overview');

                // JSON export for instant client-side reactivity
                $all_equipment_json = json_encode(array_map(function($e) {
                    return [
                        'id' => intval($e['id']),
                        'name' => $e['name'],
                        'discipline' => $e['discipline'],
                        'status' => $e['status'],
                        'image' => $e['image'] ?: '/Gym Manager/assets/img/equipment/treadmills.png',
                        'description' => $e['description'] ?? '',
                        'purchase_date' => $e['purchase_date'] ?? '',
                        'warranty_months' => intval($e['warranty_months'] ?? 12),
                        'warranty_expiry_date' => $e['warranty_expiry_date'] ?? '',
                        'next_maintenance_date' => $e['next_maintenance_date'] ?? '',
                        'notes' => $e['notes'] ?? ''
                    ];
                }, $all_equipment), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            ?>
                <!-- Equipment Management Header -->
                <div id="equipmentSectionWrapper" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-dumbbell" style="color: var(--primary);"></i>
                            <?php echo $lang['eq_mgmt_title'] ?? ($current_lang === 'vi' ? 'Quản Lý Thiết Bị Phòng Tập' : 'Gym Equipment Management'); ?>
                        </h2>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.95rem;">
                            <?php echo $current_lang === 'vi' 
                                ? 'Bảng tổng thể hiển thị thông tin sơ bộ. Bấm Details để xem và chỉnh sửa bảo hành, lịch bảo trì và ghi chú kỹ thuật.' 
                                : 'Overview table displays summary info. Click Details to view and edit warranty, maintenance schedule, and notes.'; ?>
                        </p>
                    </div>
                </div>

                <!-- Navigation Tabs Bar -->
                <div class="equipment-tabs-bar" style="display: flex; gap: 8px; margin-bottom: 2rem; border-bottom: 2px solid var(--border); padding-bottom: 4px; flex-wrap: wrap;">
                    <button type="button" class="eq-nav-tab <?php echo $initial_tab === 'overview' ? 'active' : ''; ?>" id="btn-tab-overview" onclick="switchEquipmentTab('overview')">
                        <i class="fas fa-list-ul"></i> <?php echo $lang['eq_tab_overview'] ?? ($current_lang === 'vi' ? 'Bảng Tổng Thể' : 'Overview Table'); ?>
                    </button>
                    <button type="button" class="eq-nav-tab <?php echo $initial_tab === 'details' ? 'active' : ''; ?>" id="btn-tab-details" onclick="switchEquipmentTab('details')">
                        <i class="fas fa-sliders-h"></i> <?php echo $lang['eq_tab_details'] ?? ($current_lang === 'vi' ? 'Chi Tiết & Bảo Hành' : 'Details & Warranty'); ?>
                        <span id="nav-selected-eq-badge" style="<?php echo $selected_eq ? '' : 'display:none;'; ?> font-size: 0.74rem; background: var(--primary); color: white; padding: 2px 8px; border-radius: 10px; font-weight: 700; margin-left: 4px;">
                            #<?php echo $selected_eq ? $selected_eq['id'] : ''; ?>
                        </span>
                    </button>
                    <button type="button" class="eq-nav-tab <?php echo $initial_tab === 'add' ? 'active' : ''; ?>" id="btn-tab-add" onclick="switchEquipmentTab('add')">
                        <i class="fas fa-plus-circle"></i> <?php echo $lang['eq_tab_add'] ?? ($current_lang === 'vi' ? 'Thêm Thiết Bị Mới' : 'Add New Equipment'); ?>
                    </button>
                </div>

                <!-- ==================== PANE 1: BẢNG TỔNG THỂ (OVERVIEW) ==================== -->
                <div id="eq-pane-overview" style="<?php echo $initial_tab === 'overview' ? '' : 'display:none;'; ?>">
                    <!-- Stats summary cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.2rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); display: flex; align-items: center; gap: 1.1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(37,99,235,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.35rem;">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div>
                                <span style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); line-height: 1;"><?php echo $total_eq; ?></span>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?php echo $current_lang === 'vi' ? 'Tổng Thiết Bị' : 'Total Equipment'; ?></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); display: flex; align-items: center; gap: 1.1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(22,163,74,0.1); color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.35rem;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <span style="font-size: 1.75rem; font-weight: 800; color: #16a34a; line-height: 1;"><?php echo $active_eq; ?></span>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?php echo $current_lang === 'vi' ? 'Đang Hoạt Động' : 'Active Status'; ?></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); display: flex; align-items: center; gap: 1.1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(217,119,6,0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.35rem;">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <span style="font-size: 1.75rem; font-weight: 800; color: #d97706; line-height: 1;"><?php echo $maint_eq; ?></span>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?php echo $current_lang === 'vi' ? 'Đang Sửa Chữa' : 'In Repair'; ?></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid <?php echo ($overdue_eq > 0 || $upcoming_eq > 0) ? '#fecaca' : 'var(--border)'; ?>; display: flex; align-items: center; gap: 1.1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.35rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <span style="font-size: 1.75rem; font-weight: 800; color: #ef4444; line-height: 1;"><?php echo ($overdue_eq + $upcoming_eq); ?></span>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?php echo $current_lang === 'vi' ? 'Cần Bảo Trì (Hạn ≤ 15d)' : 'Needs Maintenance'; ?></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); display: flex; align-items: center; gap: 1.1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(79,70,229,0.1); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.35rem;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <span style="font-size: 1.75rem; font-weight: 800; color: #4f46e5; line-height: 1;"><?php echo $in_warranty_eq; ?></span>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?php echo $current_lang === 'vi' ? 'Còn Bảo Hành' : 'In Warranty'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Filter & Search Toolbar -->
                    <div style="background: white; padding: 1.3rem; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
                        <div style="flex: 2; min-width: 240px; position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.95rem;"></i>
                            <input type="text" id="eqSearchInput" placeholder="<?php echo $lang['eq_search_placeholder'] ?? ($current_lang === 'vi' ? 'Tìm theo tên thiết bị, mã máy...' : 'Search equipment by name, ID...'); ?>" onkeyup="filterEquipmentTable()" style="width: 100%; padding: 0.8rem 1rem 0.8rem 2.6rem; border-radius: 12px; border: 1.5px solid var(--border); outline: none; font-size: 0.92rem;">
                        </div>
                        
                        <div style="display: flex; gap: 0.8rem; flex-wrap: wrap; flex: 3; justify-content: flex-end;">
                            <select id="eqDisciplineFilter" onchange="filterEquipmentTable()" style="padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); background: #f8fafc; font-weight: 600; outline: none; font-size: 0.88rem;">
                                <option value=""><?php echo $current_lang === 'vi' ? '🏋️ Tất Cả Bộ Môn' : '🏋️ All Disciplines'; ?></option>
                                <option value="gym">🏋️ Gym Fitness</option>
                                <option value="muay">🥊 Muay Thai</option>
                                <option value="yoga">🧘‍♀️ Yoga</option>
                            </select>

                            <select id="eqStatusFilter" onchange="filterEquipmentTable()" style="padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); background: #f8fafc; font-weight: 600; outline: none; font-size: 0.88rem;">
                                <option value=""><?php echo $current_lang === 'vi' ? '⚙️ Trạng Thái Máy' : '⚙️ Machine Status'; ?></option>
                                <option value="active">🟢 <?php echo $current_lang === 'vi' ? 'Hoạt Động' : 'Active'; ?></option>
                                <option value="maintenance">🟡 <?php echo $current_lang === 'vi' ? 'Đang Bảo Trì' : 'Maintenance'; ?></option>
                                <option value="retired">🔴 <?php echo $current_lang === 'vi' ? 'Ngưng Dùng' : 'Retired'; ?></option>
                            </select>

                            <select id="eqMaintFilter" onchange="filterEquipmentTable()" style="padding: 0.8rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); background: #f8fafc; font-weight: 600; outline: none; font-size: 0.88rem;">
                                <option value=""><?php echo $current_lang === 'vi' ? '🔔 Lịch Bảo Trì & Bảo Hành' : '🔔 Maintenance & Warranty'; ?></option>
                                <option value="overdue">⚠️ <?php echo $current_lang === 'vi' ? 'Quá Hạn Bảo Trì' : 'Overdue Maintenance'; ?></option>
                                <option value="upcoming">⏰ <?php echo $current_lang === 'vi' ? 'Sắp Tới Hạn (≤ 15 ngày)' : 'Upcoming (≤ 15 days)'; ?></option>
                                <option value="warranty_valid">🛡️ <?php echo $current_lang === 'vi' ? 'Còn Hạn Bảo Hành' : 'In Warranty'; ?></option>
                                <option value="warranty_expired">⚪ <?php echo $current_lang === 'vi' ? 'Hết Hạn Bảo Hành' : 'Warranty Expired'; ?></option>
                            </select>

                            <button type="button" onclick="resetEquipmentFilters()" style="padding: 0.8rem 1.1rem; border-radius: 12px; border: 1.5px solid var(--border); background: white; color: var(--text-muted); cursor: pointer; font-weight: 700; transition: all 0.2s;" title="<?php echo $current_lang === 'vi' ? 'Đặt lại bộ lọc' : 'Reset filters'; ?>">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Clean Overview Table (Thông Tin Sơ Bộ) -->
                    <div class="table-responsive" style="overflow-x: auto; width: 100%; border-radius: 20px; box-shadow: var(--shadow-premium);">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                            <thead>
                                <tr style="background: transparent;">
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none;">ID & <?php echo $current_lang === 'vi' ? 'Ảnh' : 'Image'; ?></th>
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none;"><?php echo $current_lang === 'vi' ? 'Thiết Bị & Bộ Môn' : 'Equipment & Discipline'; ?></th>
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none;"><?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?></th>
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none;"><?php echo $lang['eq_next_maintenance'] ?? ($current_lang === 'vi' ? 'Kỳ Bảo Trì Tiếp Theo' : 'Next Maintenance'); ?></th>
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none;"><?php echo $lang['eq_warranty'] ?? ($current_lang === 'vi' ? 'Bảo Hành' : 'Warranty'); ?></th>
                                    <th style="padding: 1rem 1.2rem; font-size: 0.82rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border: none; text-align: center;"><?php echo $current_lang === 'vi' ? 'Thao Tác' : 'Actions'; ?></th>
                                </tr>
                            </thead>
                            <tbody id="equipmentTableBody">
                                <?php foreach($all_equipment as $eq): 
                                    $disc_badge = [
                                        'gym' => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'label' => '🏋️‍♂️ GYM'],
                                        'muay' => ['bg' => '#fef2f2', 'color' => '#b91c1c', 'label' => '🥊 MUAY THAI'],
                                        'yoga' => ['bg' => '#f0fdf4', 'color' => '#15803d', 'label' => '🧘‍♀️ YOGA']
                                    ][$eq['discipline']] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => strtoupper($eq['discipline'])];

                                    $st_badge = [
                                        'active' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'label' => '🟢 ACTIVE'],
                                        'maintenance' => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => '🟡 MAINTENANCE'],
                                        'retired' => ['bg' => '#fee2e2', 'color' => '#ef4444', 'label' => '🔴 RETIRED']
                                    ][$eq['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => strtoupper($eq['status'])];

                                    // Warranty calculation
                                    $warranty_dt = !empty($eq['warranty_expiry_date']) ? new DateTime($eq['warranty_expiry_date']) : null;
                                    $maint_dt = !empty($eq['next_maintenance_date']) ? new DateTime($eq['next_maintenance_date']) : null;

                                    $is_warranty_valid = false;
                                    $concise_warranty_badge = '';
                                    if ($warranty_dt) {
                                        if ($warranty_dt >= $today_dt) {
                                            $is_warranty_valid = true;
                                            $concise_warranty_badge = '<span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.74rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-shield-alt"></i> ' . ($current_lang === 'vi' ? 'Còn BH' : 'In Warranty') . '</span>';
                                        } else {
                                            $concise_warranty_badge = '<span style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.74rem;">' . ($current_lang === 'vi' ? '⚪ Hết BH' : '⚪ Expired') . '</span>';
                                        }
                                    } else {
                                        $concise_warranty_badge = '<span style="color: var(--text-muted); font-size: 0.78rem;">N/A</span>';
                                    }

                                    // Maintenance alert
                                    $maint_filter_tag = 'ontime';
                                    $maint_display_html = '';
                                    if ($eq['status'] === 'maintenance') {
                                        $maint_filter_tag = 'overdue';
                                        $maint_display_html = '<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 0.74rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-tools"></i> ' . ($current_lang === 'vi' ? 'Đang sửa chữa' : 'In Repair') . '</span>';
                                    } elseif ($maint_dt) {
                                        $date_formatted = $maint_dt->format('d/m/Y');
                                        if ($maint_dt < $today_dt) {
                                            $maint_filter_tag = 'overdue';
                                            $overdue_days = $today_dt->diff($maint_dt)->days;
                                            $maint_display_html = '<div style="font-weight: 700; font-size: 0.88rem; color: #b91c1c;">' . $date_formatted . '</div><span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;"><i class="fas fa-exclamation-triangle"></i> ' . ($current_lang === 'vi' ? "Quá {$overdue_days} ngày" : "Overdue {$overdue_days}d") . '</span>';
                                        } elseif ($today_dt->diff($maint_dt)->days <= 15) {
                                            $maint_filter_tag = 'upcoming';
                                            $due_in_days = $today_dt->diff($maint_dt)->days;
                                            $maint_display_html = '<div style="font-weight: 700; font-size: 0.88rem; color: #d97706;">' . $date_formatted . '</div><span style="background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;"><i class="fas fa-clock"></i> ' . ($current_lang === 'vi' ? "Còn {$due_in_days} ngày" : "Due in {$due_in_days}d") . '</span>';
                                        } else {
                                            $maint_filter_tag = 'ontime';
                                            $maint_display_html = '<div style="font-weight: 700; font-size: 0.88rem; color: var(--text-main);">' . $date_formatted . '</div><span style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 2px 7px; border-radius: 6px; font-weight: 700; font-size: 0.7rem; margin-top: 2px; display: inline-block;">' . ($current_lang === 'vi' ? '🟢 Đúng lịch' : '🟢 On schedule') . '</span>';
                                        }
                                    } else {
                                        $maint_display_html = '<span style="color: var(--text-muted); font-size: 0.8rem;">N/A</span>';
                                    }

                                    $search_corpus = strtolower($eq['name'] . ' ' . ($eq['description'] ?? '') . ' #' . $eq['id']);
                                ?>
                                <tr class="eq-table-row" 
                                    data-search="<?php echo htmlspecialchars($search_corpus); ?>" 
                                    data-discipline="<?php echo htmlspecialchars($eq['discipline']); ?>" 
                                    data-status="<?php echo htmlspecialchars($eq['status']); ?>" 
                                    data-maint-status="<?php echo $maint_filter_tag; ?>" 
                                    data-warranty-valid="<?php echo $is_warranty_valid ? '1' : '0'; ?>"
                                    style="box-shadow: 0 2px 10px rgba(0,0,0,0.02); transition: transform 0.15s ease;">
                                    
                                    <!-- ID & Image -->
                                    <td style="padding: 1rem 1.2rem; background: white; border-radius: 16px 0 0 16px; white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="font-weight: 800; color: var(--text-muted); font-size: 0.85rem;">#<?php echo $eq['id']; ?></span>
                                            <img src="<?php echo htmlspecialchars($eq['image'] ?: '/Gym Manager/assets/img/equipment/treadmills.png'); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                                        </div>
                                    </td>

                                    <!-- Equipment Name & Discipline -->
                                    <td style="padding: 1rem 1.2rem; background: white; min-width: 200px;">
                                        <div style="font-weight: 800; color: var(--text-main); font-size: 0.98rem; margin-bottom: 3px;">
                                            <?php echo htmlspecialchars($eq['name']); ?>
                                        </div>
                                        <span style="background:<?php echo $disc_badge['bg']; ?>; color:<?php echo $disc_badge['color']; ?>; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.68rem;">
                                            <?php echo $disc_badge['label']; ?>
                                        </span>
                                    </td>

                                    <!-- Machine Status -->
                                    <td style="padding: 1rem 1.2rem; background: white; white-space: nowrap;">
                                        <span style="background:<?php echo $st_badge['bg']; ?>; color:<?php echo $st_badge['color']; ?>; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.72rem; letter-spacing: 0.3px;">
                                            <?php echo $st_badge['label']; ?>
                                        </span>
                                    </td>

                                    <!-- Scheduled Maintenance -->
                                    <td style="padding: 1rem 1.2rem; background: white; white-space: nowrap;">
                                        <?php echo $maint_display_html; ?>
                                    </td>

                                    <!-- Concise Warranty -->
                                    <td style="padding: 1rem 1.2rem; background: white; white-space: nowrap;">
                                        <?php echo $concise_warranty_badge; ?>
                                    </td>

                                    <!-- Actions: Details Button & Delete -->
                                    <td style="padding: 1rem 1.2rem; background: white; border-radius: 0 16px 16px 0; text-align: center; white-space: nowrap;">
                                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                            <!-- DETAILS BUTTON TO OPEN SEPARATE TAB -->
                                            <button type="button" 
                                                class="btn-eq-details" 
                                                onclick="openEquipmentDetailsTab(<?php echo $eq['id']; ?>)" 
                                                title="<?php echo $current_lang === 'vi' ? 'Xem chi tiết, chỉnh sửa bảo hành & ghi chú' : 'View details, edit warranty & notes'; ?>"
                                                style="background: linear-gradient(135deg, #1e40af, #2563eb); color: white; border: none; padding: 7px 15px; border-radius: 10px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(37,99,235,0.25); transition: all 0.2s ease;">
                                                <i class="fas fa-sliders-h"></i>
                                                <?php echo $lang['eq_btn_details'] ?? ($current_lang === 'vi' ? 'Chi Tiết' : 'Details'); ?>
                                            </button>

                                            <!-- Delete Equipment -->
                                            <a href="/Gym Manager/dashboards/manager.php?page=equipment&delete_equipment=<?php echo $eq['id']; ?>" class="action-btn btn-delete" onclick="return confirmLinkCustom(event, this, '<?php echo $current_lang === 'vi' ? 'Bạn có chắc chắn muốn XÓA thiết bị này khỏi hệ thống?' : 'Are you sure you want to DELETE this equipment?'; ?>', '<?php echo $current_lang === 'vi' ? 'Xóa Thiết Bị' : 'Delete Equipment'; ?>', '🗑️');" title="<?php echo $current_lang === 'vi' ? 'Xóa thiết bị' : 'Delete Equipment'; ?>" style="width: 32px; height: 32px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Empty row state -->
                                <tr id="eqEmptyRow" style="<?php echo empty($all_equipment) ? '' : 'display:none;'; ?>">
                                    <td colspan="6" style="text-align:center; padding: 3rem 1rem; color: var(--text-muted); background: white; border-radius: 16px;">
                                        <i class="fas fa-search" style="font-size: 2.2rem; color: #cbd5e1; margin-bottom: 0.8rem; display: block;"></i>
                                        <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">
                                            <?php echo $current_lang === 'vi' ? 'Không tìm thấy thiết bị phù hợp' : 'No matching equipment found'; ?>
                                        </div>
                                        <div style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
                                            <?php echo $current_lang === 'vi' ? 'Vui lòng kiểm tra lại từ khóa tìm kiếm hoặc các tiêu chí bộ lọc.' : 'Please try adjusting your search terms or filter criteria.'; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ==================== PANE 2: TAB RIÊNG - CHI TIẾT & BẢO HÀNH (DETAILS) ==================== -->
                <div id="eq-pane-details" style="<?php echo $initial_tab === 'details' ? '' : 'display:none;'; ?>">
                    <?php
                        $sel_warranty_dt = !empty($selected_eq['warranty_expiry_date']) ? new DateTime($selected_eq['warranty_expiry_date']) : null;
                        $sel_maint_dt = !empty($selected_eq['next_maintenance_date']) ? new DateTime($selected_eq['next_maintenance_date']) : null;
                        
                        $sel_warranty_badge_html = '';
                        if ($sel_warranty_dt) {
                            if ($sel_warranty_dt >= $today_dt) {
                                $diff_w = $today_dt->diff($sel_warranty_dt)->days;
                                $sel_warranty_badge_html = '<span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-shield-alt"></i> ' . ($current_lang === 'vi' ? "Còn hạn: {$diff_w} ngày" : "Valid: {$diff_w}d left") . '</span>';
                            } else {
                                $sel_warranty_badge_html = '<span style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;"><i class="fas fa-history"></i> ' . ($current_lang === 'vi' ? 'Hết hạn bảo hành' : 'Warranty Expired') . '</span>';
                            }
                        } else {
                            $sel_warranty_badge_html = '<span style="color: #94a3b8; font-size: 0.75rem;">N/A</span>';
                        }

                        $sel_maint_badge_html = '';
                        if (($selected_eq['status'] ?? '') === 'maintenance') {
                            $sel_maint_badge_html = '<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-tools"></i> ' . ($current_lang === 'vi' ? 'Đang sửa chữa' : 'In repair') . '</span>';
                        } elseif ($sel_maint_dt) {
                            $diff_m = $today_dt->diff($sel_maint_dt)->days;
                            if ($sel_maint_dt < $today_dt) {
                                $sel_maint_badge_html = '<span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-exclamation-triangle"></i> ' . ($current_lang === 'vi' ? "Quá hạn {$diff_m} ngày" : "Overdue {$diff_m}d") . '</span>';
                            } elseif ($diff_m <= 15) {
                                $sel_maint_badge_html = '<span style="background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-clock"></i> ' . ($current_lang === 'vi' ? "Sắp tới hạn ({$diff_m} ngày)" : "Due in {$diff_m}d") . '</span>';
                            } else {
                                $sel_maint_badge_html = '<span style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-check-circle"></i> ' . ($current_lang === 'vi' ? 'Đúng định kỳ' : 'On schedule') . '</span>';
                            }
                        } else {
                            $sel_maint_badge_html = '<span style="color: #94a3b8; font-size: 0.75rem;">N/A</span>';
                        }
                    ?>

                    <!-- Top control row: Back button, Quick switcher, Subview switchers -->
                    <div style="background: white; padding: 0.9rem 1.4rem; border-radius: 16px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <button type="button" onclick="switchEquipmentTab('overview')" style="background: #f1f5f9; color: var(--text-muted); border: 1.5px solid var(--border); padding: 0.65rem 1.2rem; border-radius: 10px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fas fa-arrow-left"></i> <?php echo $lang['eq_back_to_overview'] ?? ($current_lang === 'vi' ? 'Quay lại Bảng' : 'Back to Table'); ?>
                        </button>

                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <label style="font-weight: 700; font-size: 0.84rem; color: var(--text-muted);">
                                <i class="fas fa-random" style="color: var(--primary);"></i> <?php echo $current_lang === 'vi' ? 'Chọn máy:' : 'Select eq:'; ?>
                            </label>
                            <select id="detailsEquipmentSelector" onchange="openEquipmentDetailsTab(this.value)" style="padding: 0.6rem 0.9rem; border-radius: 10px; border: 1.5px solid var(--border); font-weight: 700; background: #f8fafc; font-size: 0.86rem; outline: none; min-width: 220px;">
                                <?php foreach($all_equipment as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" <?php echo ($selected_eq && $selected_eq['id'] == $item['id']) ? 'selected' : ''; ?>>
                                        #<?php echo $item['id']; ?> - <?php echo htmlspecialchars($item['name']); ?> (<?php echo strtoupper($item['discipline']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mode Switch Buttons -->
                        <div style="display: flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid var(--border);">
                            <button type="button" id="btn-mode-report" onclick="toggleDetailsSubMode('report')" style="padding: 0.5rem 1rem; border-radius: 8px; border: none; font-weight: 800; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; background: var(--primary); color: white; transition: all 0.2s;">
                                <i class="fas fa-camera"></i> <?php echo $current_lang === 'vi' ? 'Phiếu Chụp Word' : 'Word Report View'; ?>
                            </button>
                            <button type="button" id="btn-mode-edit" onclick="toggleDetailsSubMode('edit')" style="padding: 0.5rem 1rem; border-radius: 8px; border: none; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; background: transparent; color: var(--text-muted); transition: all 0.2s;">
                                <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa' : 'Edit Mode'; ?>
                            </button>
                        </div>
                    </div>

                    <!-- 1. WORD SCREENSHOT CARD: PHIẾU THÔNG TIN BẢO HÀNH & BẢO TRÌ (GỌN GÀNG, VỪA VẶN 1 MÀN HÌNH ĐỂ CHỤP WORD) -->
                    <div id="eq-subview-report" style="max-width: 840px; margin: 0 auto; background: #ffffff; border: 2px solid #334155; border-radius: 16px; padding: 1.8rem 2.2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.08); font-family: inherit;">
                        
                        <!-- Official Sheet Header -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 0.9rem; border-bottom: 2px solid #0f172a; margin-bottom: 1.2rem;">
                            <div>
                                <div style="font-size: 0.78rem; font-weight: 800; letter-spacing: 1.5px; color: #2563eb; text-transform: uppercase;">
                                    GYM MASTER FITNESS & MANAGEMENT
                                </div>
                                <h3 style="margin: 4px 0 0 0; font-size: 1.32rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">
                                    <?php echo $current_lang === 'vi' ? 'PHIẾU QUẢN LÝ BẢO HÀNH & BẢO TRÌ THIẾT BỊ' : 'EQUIPMENT WARRANTY & MAINTENANCE RECORD'; ?>
                                </h3>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 3px;">
                                    <?php echo $current_lang === 'vi' ? 'Hồ sơ kỹ thuật thiết bị phòng tập' : 'Gym Equipment Specification & Maintenance Sheet'; ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span id="rc_id_badge" style="background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe; padding: 4px 14px; border-radius: 8px; font-weight: 800; font-size: 0.95rem; display: inline-block;">
                                    #<?php echo $selected_eq['id'] ?? '0'; ?>
                                </span>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 5px;">
                                    <?php echo $current_lang === 'vi' ? 'Ngày xem:' : 'Date:'; ?> <strong><?php echo date('d/m/Y'); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment Identity Bar -->
                        <div style="display: flex; align-items: center; gap: 1.4rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.4rem; margin-bottom: 1.2rem;">
                            <img id="rc_img" src="<?php echo htmlspecialchars($selected_eq['image'] ?? '/Gym Manager/assets/img/equipment/treadmills.png'); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1.5px solid #cbd5e1; background: white;">
                            <div style="flex: 1;">
                                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px; flex-wrap: wrap;">
                                    <span id="rc_disc_badge" style="font-weight: 800; font-size: 0.75rem; padding: 3px 10px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; text-transform: uppercase;">
                                        <?php 
                                            $d = $selected_eq['discipline'] ?? 'gym';
                                            echo ($d === 'gym' ? '🏋️ GYM' : ($d === 'muay' ? '🥊 MUAY THAI' : '🧘 YOGA'));
                                        ?>
                                    </span>
                                    <span id="rc_status_badge" style="font-weight: 800; font-size: 0.75rem; padding: 3px 10px; border-radius: 6px; background: #dcfce7; color: #15803d; text-transform: uppercase;">
                                        <?php 
                                            $st = $selected_eq['status'] ?? 'active';
                                            echo ($st === 'active' ? '🟢 ' . ($current_lang === 'vi' ? 'HOẠT ĐỘNG' : 'ACTIVE') : ($st === 'maintenance' ? '🟡 ' . ($current_lang === 'vi' ? 'BẢO TRÌ' : 'MAINTENANCE') : '🔴 ' . ($current_lang === 'vi' ? 'NGƯNG DÙNG' : 'RETIRED')));
                                        ?>
                                    </span>
                                </div>
                                <h4 id="rc_name" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">
                                    <?php echo htmlspecialchars($selected_eq['name'] ?? ''); ?>
                                </h4>
                            </div>
                        </div>

                        <!-- 4 Core Parameters (Lưới 4 thông số cốt lõi) -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem;">
                            <!-- 1. Thời Gian Mua -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1.1rem; background: #ffffff;">
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-calendar-alt" style="color: #2563eb;"></i> <?php echo $current_lang === 'vi' ? 'Thời Gian Mua Máy' : 'Purchase Date'; ?>
                                </div>
                                <div id="rc_purchase_date" style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                    <?php echo !empty($selected_eq['purchase_date']) ? date('d/m/Y', strtotime($selected_eq['purchase_date'])) : 'N/A'; ?>
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 3px;">
                                    <?php echo $current_lang === 'vi' ? 'Ngày ghi nhận nhập kho' : 'Inventory entry date'; ?>
                                </div>
                            </div>

                            <!-- 2. Bảo Hành Chính Hãng -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1.1rem; background: #ffffff;">
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-shield-alt" style="color: #16a34a;"></i> <?php echo $current_lang === 'vi' ? 'Bảo Hành Chính Hãng' : 'Warranty Status'; ?>
                                </div>
                                <div style="display: flex; align-items: baseline; gap: 8px;">
                                    <span id="rc_warranty_period" style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                        <?php echo intval($selected_eq['warranty_months'] ?? 12); ?> <?php echo $current_lang === 'vi' ? 'Tháng' : 'Months'; ?>
                                    </span>
                                    <span style="font-size: 0.82rem; color: #64748b;">(HSD: <strong id="rc_warranty_expiry"><?php echo !empty($selected_eq['warranty_expiry_date']) ? date('d/m/Y', strtotime($selected_eq['warranty_expiry_date'])) : 'N/A'; ?></strong>)</span>
                                </div>
                                <div id="rc_warranty_badge_container" style="margin-top: 5px;">
                                    <?php echo $sel_warranty_badge_html; ?>
                                </div>
                            </div>

                            <!-- 3. Dự Kiến Ngày Bảo Trì -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1.1rem; background: #ffffff;">
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-tools" style="color: #d97706;"></i> <?php echo $current_lang === 'vi' ? 'Dự Kiến Ngày Bảo Trì' : 'Scheduled Maintenance'; ?>
                                </div>
                                <div id="rc_next_maintenance" style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                    <?php echo !empty($selected_eq['next_maintenance_date']) ? date('d/m/Y', strtotime($selected_eq['next_maintenance_date'])) : 'N/A'; ?>
                                </div>
                                <div id="rc_maint_badge_container" style="margin-top: 5px;">
                                    <?php echo $sel_maint_badge_html; ?>
                                </div>
                            </div>

                            <!-- 4. Tình Trạng Thiết Bị -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1.1rem; background: #ffffff;">
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-heartbeat" style="color: #e11d48;"></i> <?php echo $current_lang === 'vi' ? 'Tình Trạng Vận Hành' : 'Operation Condition'; ?>
                                </div>
                                <div id="rc_op_status" style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                    <?php 
                                    echo ($selected_eq['status'] ?? 'active') === 'active' 
                                        ? '<span style="color: #16a34a;">🟢 ' . ($current_lang === 'vi' ? 'Sẵn Sàng Phục Vụ' : 'Operational') . '</span>' 
                                        : (($selected_eq['status'] ?? '') === 'maintenance' 
                                            ? '<span style="color: #d97706;">🟡 ' . ($current_lang === 'vi' ? 'Đang Sửa Chữa / Bảo Trì' : 'In Repair') . '</span>' 
                                            : '<span style="color: #ef4444;">🔴 ' . ($current_lang === 'vi' ? 'Ngưng Sử Dụng' : 'Out of Order') . '</span>');
                                    ?>
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 3px;">
                                    <?php echo $current_lang === 'vi' ? 'Kiểm tra kỹ thuật định kỳ tại sàn tập' : 'Inspected periodically at gym floor'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Notes (Note) -->
                        <div style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 1rem 1.2rem; background: #f8fafc; margin-bottom: 1.2rem;">
                            <div style="font-size: 0.8rem; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-clipboard-list" style="color: #2563eb;"></i> <?php echo $current_lang === 'vi' ? 'Ghi Chú Kỹ Thuật, Số Serial & Phụ Tùng (Note):' : 'Technical Notes, Serial & Spare Parts:'; ?>
                            </div>
                            <div id="rc_notes_content" style="font-size: 0.92rem; color: #1e293b; line-height: 1.6; white-space: pre-wrap; font-family: inherit; min-height: 48px;">
                                <?php echo !empty($selected_eq['notes']) ? htmlspecialchars($selected_eq['notes']) : ($current_lang === 'vi' ? 'Chưa có ghi chú kỹ thuật cho thiết bị này.' : 'No notes recorded.'); ?>
                            </div>
                        </div>

                        <!-- Verification Footer -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.8rem; border-top: 1px dashed #cbd5e1; font-size: 0.76rem; color: #64748b;">
                            <div>
                                <i class="fas fa-check-circle" style="color: #16a34a;"></i> <?php echo $current_lang === 'vi' ? 'Xác nhận kiểm tra bởi Quản lý Thiết Bị' : 'Verified by Equipment Manager'; ?>
                            </div>
                            <div style="font-weight: 600; font-style: italic;">
                                Gym Manager System &bull; <?php echo date('Y'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar beneath the Word Report Card (Outside screenshot area) -->
                    <div style="max-width: 840px; margin: 1.2rem auto 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" onclick="toggleDetailsSubMode('edit')" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.4rem; border-radius: 12px; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(37,99,235,0.25);">
                                <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa Thông Tin & Note' : 'Edit Info & Notes'; ?>
                            </button>
                            <button type="button" onclick="openMaintenanceModalFromDetails()" style="background: #16a34a; color: white; border: none; padding: 0.75rem 1.4rem; border-radius: 12px; font-weight: 800; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(22,163,74,0.25);">
                                <i class="fas fa-wrench"></i> <?php echo $current_lang === 'vi' ? 'Ghi Nhận Bảo Trì Hôm Nay' : 'Log Maintenance Today'; ?>
                            </button>
                        </div>
                        <div>
                            <button type="button" onclick="switchEquipmentTab('overview')" style="background: #f1f5f9; color: var(--text-muted); border: 1.5px solid var(--border); padding: 0.75rem 1.4rem; border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                                <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'vi' ? 'Quay Lại Bảng' : 'Back to Table'; ?>
                            </button>
                        </div>
                    </div>

                    <!-- 2. EDIT VIEW (FORM CHỈNH SỬA GỌN NHẸ CHỈ VỚI NHỮNG TRƯỜNG QUAN TRỌNG) -->
                    <div id="eq-subview-edit" style="display: none; max-width: 840px; margin: 0 auto; background: white; border-radius: 18px; padding: 2rem; border: 1px solid var(--border); box-shadow: var(--shadow-premium);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.4rem; border-bottom: 1px solid var(--border); padding-bottom: 0.8rem;">
                            <h4 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-edit"></i> <?php echo $current_lang === 'vi' ? 'Chỉnh Sửa Bảo Hành, Bảo Trì & Ghi Chú' : 'Edit Warranty, Maintenance & Notes'; ?>
                            </h4>
                            <button type="button" onclick="toggleDetailsSubMode('report')" style="background: none; border: none; color: var(--text-muted); font-weight: 700; cursor: pointer; font-size: 0.95rem;">
                                <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
                            </button>
                        </div>

                        <form method="POST" id="detailsEquipmentForm" enctype="multipart/form-data">
                            <input type="hidden" name="equipment_id" id="det_eq_id" value="<?php echo $selected_eq['id'] ?? '0'; ?>">
                            <input type="hidden" name="description" id="det_description" value="<?php echo htmlspecialchars($selected_eq['description'] ?? ''); ?>">
                            <input type="hidden" name="custom_image_url" id="det_custom_image_url" value="<?php echo htmlspecialchars($selected_eq['image'] ?? ''); ?>">

                            <!-- Row 1: Name, Discipline, Status -->
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem;">
                                <div>
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                        <?php echo $current_lang === 'vi' ? 'Tên Thiết Bị' : 'Equipment Name'; ?>
                                    </label>
                                    <input type="text" name="name" id="det_name" value="<?php echo htmlspecialchars($selected_eq['name'] ?? ''); ?>" required style="width: 100%; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1.5px solid var(--border); font-weight: 700;">
                                </div>
                                <div>
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                        <?php echo $current_lang === 'vi' ? 'Bộ Môn' : 'Discipline'; ?>
                                    </label>
                                    <select name="discipline" id="det_discipline" style="width: 100%; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1.5px solid var(--border); font-weight: 700; background: #f8fafc;">
                                        <option value="gym" <?php echo ($selected_eq['discipline'] ?? '') == 'gym' ? 'selected' : ''; ?>>🏋️ Gym</option>
                                        <option value="muay" <?php echo ($selected_eq['discipline'] ?? '') == 'muay' ? 'selected' : ''; ?>>🥊 Muay</option>
                                        <option value="yoga" <?php echo ($selected_eq['discipline'] ?? '') == 'yoga' ? 'selected' : ''; ?>>🧘 Yoga</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                        <?php echo $current_lang === 'vi' ? 'Trạng Thái' : 'Status'; ?>
                                    </label>
                                    <select name="status" id="det_status" style="width: 100%; padding: 0.75rem 0.9rem; border-radius: 10px; border: 1.5px solid var(--border); font-weight: 700; background: #f8fafc;">
                                        <option value="active" <?php echo ($selected_eq['status'] ?? '') == 'active' ? 'selected' : ''; ?>>🟢 <?php echo $current_lang === 'vi' ? 'Hoạt Động' : 'Active'; ?></option>
                                        <option value="maintenance" <?php echo ($selected_eq['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>🟡 <?php echo $current_lang === 'vi' ? 'Bảo Trì' : 'Maintenance'; ?></option>
                                        <option value="retired" <?php echo ($selected_eq['status'] ?? '') == 'retired' ? 'selected' : ''; ?>>🔴 <?php echo $current_lang === 'vi' ? 'Ngưng Dùng' : 'Retired'; ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 2: Purchase, Warranty, Maintenance Dates -->
                            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 1.2rem; margin-bottom: 1.2rem;">
                                <div style="font-weight: 800; font-size: 0.85rem; color: var(--primary); margin-bottom: 0.8rem; text-transform: uppercase;">
                                    <i class="fas fa-calendar-alt"></i> <?php echo $current_lang === 'vi' ? 'Thông Tin Bảo Hành & Lịch Bảo Trì' : 'Warranty & Maintenance Schedule'; ?>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">
                                            <?php echo $current_lang === 'vi' ? 'Ngày Mua Máy' : 'Purchase Date'; ?>
                                        </label>
                                        <input type="date" name="purchase_date" id="det_purchase_date" value="<?php echo htmlspecialchars($selected_eq['purchase_date'] ?? date('Y-m-d')); ?>" onchange="calcWarrantyDates('detailsEquipmentForm')" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--border); background: white;">
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">
                                            <?php echo $current_lang === 'vi' ? 'Thời Hạn BH' : 'Warranty Period'; ?>
                                        </label>
                                        <select name="warranty_months" id="det_warranty_months" onchange="calcWarrantyDates('detailsEquipmentForm')" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--border); background: white; font-weight: 600;">
                                            <?php foreach([3, 6, 12, 18, 24, 36, 48, 60] as $m_cnt): ?>
                                                <option value="<?php echo $m_cnt; ?>" <?php echo intval($selected_eq['warranty_months'] ?? 12) === $m_cnt ? 'selected' : ''; ?>>
                                                    <?php echo $m_cnt; ?> <?php echo $current_lang === 'vi' ? 'tháng' : 'months'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">
                                            <?php echo $current_lang === 'vi' ? 'Ngày Hết Hạn BH' : 'Expiry Date'; ?>
                                        </label>
                                        <input type="date" name="warranty_expiry_date" id="det_warranty_expiry_date" value="<?php echo htmlspecialchars($selected_eq['warranty_expiry_date'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--border); background: white;">
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">
                                            <?php echo $current_lang === 'vi' ? 'Dự Kiến Bảo Trì' : 'Next Maintenance'; ?>
                                        </label>
                                        <input type="date" name="next_maintenance_date" id="det_next_maintenance_date" value="<?php echo htmlspecialchars($selected_eq['next_maintenance_date'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--border); background: white;">
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3: Technical Notes -->
                            <div style="margin-bottom: 1.4rem;">
                                <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">
                                    <?php echo $current_lang === 'vi' ? 'Ghi Chú Kỹ Thuật, Số Serial & Phụ Tùng (Note)' : 'Technical Notes & Spare Parts (Note)'; ?>
                                </label>
                                <textarea name="notes" id="det_notes" rows="4" placeholder="<?php echo $current_lang === 'vi' ? 'Nhập ghi chú kỹ thuật, serial, lưu ý bảo dưỡng...' : 'Enter technical notes, serial number, maintenance notes...'; ?>" style="width: 100%; padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); font-family: inherit; font-size: 0.9rem; line-height: 1.5; outline: none;"><?php echo htmlspecialchars($selected_eq['notes'] ?? ''); ?></textarea>
                            </div>

                            <!-- Save button bar -->
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="submit" name="update_equipment" style="background: var(--primary); color: white; border: none; padding: 0.85rem 2rem; border-radius: 12px; font-weight: 800; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(37,99,235,0.3);">
                                    <i class="fas fa-save"></i> <?php echo $current_lang === 'vi' ? 'Lưu Thay Đổi' : 'Save Changes'; ?>
                                </button>
                                <button type="button" onclick="toggleDetailsSubMode('report')" style="background: #f1f5f9; color: var(--text-muted); border: 1.5px solid var(--border); padding: 0.85rem 1.5rem; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer;">
                                    <?php echo $current_lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ==================== PANE 3: THÊM THIẾT BỊ MỚI (ADD) ==================== -->
                <div id="eq-pane-add" style="<?php echo $initial_tab === 'add' ? '' : 'display:none;'; ?>">
                    <form method="POST" id="addEquipmentForm" enctype="multipart/form-data" style="background: white; padding: 2.2rem; border-radius: 24px; margin-bottom: 2.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <h3 style="margin: 0; color: var(--primary); font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-plus-circle"></i> <?php echo $lang['eq_add_title'] ?? ($current_lang === 'vi' ? 'Thêm Thiết Bị Mới Vào Hệ Thống' : 'Add New Equipment'); ?>
                            </h3>
                            <button type="button" onclick="switchEquipmentTab('overview')" style="background: none; border: none; color: var(--text-muted); font-weight: 700; cursor: pointer;">
                                <i class="fas fa-times"></i> <?php echo $current_lang === 'vi' ? 'Đóng' : 'Close'; ?>
                            </button>
                        </div>
                        
                        <!-- Row 1: Basic Information -->
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                    <?php echo $current_lang === 'vi' ? 'Tên Thiết Bị' : 'Equipment Name'; ?> <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="text" name="name" required placeholder="<?php echo $current_lang === 'vi' ? 'Ví dụ: Máy Chạy Bộ Matrix T70, Ghế Đẩy Tạ Impulse...' : 'e.g. Matrix T70 Treadmill, Impulse Bench Press...'; ?>" style="padding: 0.9rem 1.1rem; border-radius: 14px; border: 1.5px solid var(--border); width: 100%; outline: none; font-size: 0.95rem;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                    <?php echo $current_lang === 'vi' ? 'Bộ Môn / Phân Loại' : 'Discipline'; ?> <span style="color: #ef4444;">*</span>
                                </label>
                                <select name="discipline" required style="padding: 0.9rem 1.1rem; border-radius: 14px; background: #f8fafc; border: 1.5px solid var(--border); width: 100%; outline: none; font-weight: 600;">
                                    <option value="gym">🏋️‍♂️ Gym Fitness</option>
                                    <option value="muay">🥊 Muay Thai / Boxing</option>
                                    <option value="yoga">🧘‍♀️ Yoga & Pilates</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                    <?php echo $current_lang === 'vi' ? 'Trạng Thái Máy' : 'Status'; ?> <span style="color: #ef4444;">*</span>
                                </label>
                                <select name="status" required style="padding: 0.9rem 1.1rem; border-radius: 14px; background: #f8fafc; border: 1.5px solid var(--border); width: 100%; outline: none; font-weight: 600;">
                                    <option value="active">🟢 <?php echo $current_lang === 'vi' ? 'Hoạt Động Tốt' : 'Active'; ?></option>
                                    <option value="maintenance">🟡 <?php echo $current_lang === 'vi' ? 'Đang Bảo Trì' : 'Under Maintenance'; ?></option>
                                    <option value="retired">🔴 <?php echo $current_lang === 'vi' ? 'Ngưng Sử Dụng' : 'Retired'; ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Purchase, Warranty & Maintenance Dates -->
                        <div style="background: #f8fafc; padding: 1.4rem; border-radius: 18px; border: 1px solid var(--border); margin-bottom: 1.2rem;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem; color: var(--primary); font-weight: 700; font-size: 0.95rem;">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo $current_lang === 'vi' ? 'Thời Gian Mua, Hạn Bảo Hành & Lịch Bảo Trì Dự Kiến' : 'Purchase, Warranty & Scheduled Maintenance'; ?>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.2rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block; color: var(--text-main);">
                                        <?php echo $lang['eq_purchase_date_label'] ?? ($current_lang === 'vi' ? 'Thời Gian Mua' : 'Purchase Date'); ?>
                                    </label>
                                    <input type="date" name="purchase_date" id="add_purchase_date" value="<?php echo date('Y-m-d'); ?>" onchange="calcWarrantyDates('addEquipmentForm')" style="padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none; background: white;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block; color: var(--text-main);">
                                        <?php echo $lang['eq_warranty_months'] ?? ($current_lang === 'vi' ? 'Thời Hạn Bảo Hành' : 'Warranty Period'); ?>
                                    </label>
                                    <select name="warranty_months" id="add_warranty_months" onchange="calcWarrantyDates('addEquipmentForm')" style="padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none; background: white; font-weight: 600;">
                                        <option value="3">3 <?php echo $current_lang === 'vi' ? 'tháng' : 'months'; ?></option>
                                        <option value="6">6 <?php echo $current_lang === 'vi' ? 'tháng' : 'months'; ?></option>
                                        <option value="12" selected>12 <?php echo $current_lang === 'vi' ? 'tháng (1 năm)' : 'months (1 yr)'; ?></option>
                                        <option value="18">18 <?php echo $current_lang === 'vi' ? 'tháng' : 'months'; ?></option>
                                        <option value="24">24 <?php echo $current_lang === 'vi' ? 'tháng (2 năm)' : 'months (2 yrs)'; ?></option>
                                        <option value="36">36 <?php echo $current_lang === 'vi' ? 'tháng (3 năm)' : 'months (3 yrs)'; ?></option>
                                        <option value="48">48 <?php echo $current_lang === 'vi' ? 'tháng (4 năm)' : 'months (4 yrs)'; ?></option>
                                        <option value="60">60 <?php echo $current_lang === 'vi' ? 'tháng (5 năm)' : 'months (5 yrs)'; ?></option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block; color: var(--text-main);">
                                        <?php echo $lang['eq_warranty_expiry'] ?? ($current_lang === 'vi' ? 'Ngày Hết Hạn Bảo Hành' : 'Warranty Expiry Date'); ?>
                                    </label>
                                    <input type="date" name="warranty_expiry_date" id="add_warranty_expiry_date" value="<?php echo date('Y-m-d', strtotime('+12 months')); ?>" style="padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none; background: white;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem; display: block; color: var(--text-main);">
                                        <?php echo $lang['eq_next_maintenance'] ?? ($current_lang === 'vi' ? 'Dự Kiến Ngày Bảo Trì' : 'Next Maintenance Date'); ?>
                                    </label>
                                    <input type="date" name="next_maintenance_date" id="add_next_maintenance_date" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" style="padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none; background: white;">
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Image -->
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                <?php echo $current_lang === 'vi' ? 'Hình Ảnh Thiết Bị' : 'Equipment Image'; ?>
                            </label>
                            <input type="file" name="image" accept="image/*" style="padding: 0.65rem; background: #f8fafc; border-radius: 12px; border: 1.5px solid var(--border); width: 100%;">
                            <input type="text" name="custom_image_url" placeholder="<?php echo $current_lang === 'vi' ? 'Hoặc dán URL hình ảnh trực tiếp (tùy chọn)...' : 'Or paste absolute image URL here (optional)...'; ?>" style="margin-top: 8px; padding: 0.75rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none;">
                        </div>

                        <!-- Row 4: Description & Notes -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                    <?php echo $current_lang === 'vi' ? 'Mô Tả Kỹ Thuật / Công Năng' : 'Description / Features'; ?> <span style="color: #ef4444;">*</span>
                                </label>
                                <textarea name="description" required placeholder="<?php echo $current_lang === 'vi' ? 'Nhập thông số, tính năng chính của máy...' : 'Enter equipment specifications, features, key exercises...'; ?>" style="width: 100%; padding: 0.9rem 1.1rem; border-radius: 14px; border: 1.5px solid var(--border); outline: none; min-height: 100px; resize: vertical;"></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                    <i class="fas fa-clipboard-list" style="color: var(--primary);"></i>
                                    <?php echo $lang['eq_notes'] ?? ($current_lang === 'vi' ? 'Ghi Chú & Nhật Ký Kỹ Thuật' : 'Notes & Technical Log'); ?>
                                </label>
                                <textarea name="notes" placeholder="<?php echo $current_lang === 'vi' ? 'Ghi chú số serial, nhà phân phối bảo hành, phụ tùng dự phòng...' : 'Enter serial number, warranty provider, spare parts, initial condition...'; ?>" style="width: 100%; padding: 0.9rem 1.1rem; border-radius: 14px; border: 1.5px solid var(--border); outline: none; min-height: 100px; resize: vertical;"></textarea>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="add_equipment" class="btn-auth" style="margin: 0; width: auto; padding: 0.95rem 2.2rem; border-radius: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-plus"></i>
                                <?php echo $current_lang === 'vi' ? 'Thêm Thiết Bị Vào Hệ Thống' : 'Add Equipment to System'; ?>
                            </button>
                            <button type="button" onclick="switchEquipmentTab('overview')" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 14px; padding: 0.95rem 1.6rem; height: auto; text-decoration: none; font-weight: 700; border: none; cursor: pointer;">
                                <?php echo $current_lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Log Maintenance Modal Shell -->
                <div id="maintenanceLogModalShell" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;" onclick="if(event.target===this) closeMaintenanceModal();">
                    <div style="background: #ffffff; width: 100%; max-width: 540px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: euModalPop 0.25s ease forwards;">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="equipment_id" id="maint_modal_eq_id" value="">
                            
                            <!-- Header -->
                            <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 1.3rem 1.6rem; color: white; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.15rem;">
                                    <i class="fas fa-tools"></i>
                                    <span><?php echo $lang['eq_log_maintenance_modal_title'] ?? ($current_lang === 'vi' ? 'Ghi Nhận Hoàn Tất Bảo Trì' : 'Log Maintenance Completion'); ?></span>
                                </div>
                                <button type="button" onclick="closeMaintenanceModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Body -->
                            <div style="padding: 1.6rem;">
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 1rem; margin-bottom: 1.2rem;">
                                    <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: #15803d;"><?php echo $current_lang === 'vi' ? 'Thiết Bị Đang Bảo Dưỡng' : 'Equipment under service'; ?></div>
                                    <div id="maint_modal_eq_name" style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-top: 2px;"></div>
                                    <div style="font-size: 0.82rem; color: #166534; margin-top: 4px;">
                                        <?php echo $current_lang === 'vi' ? 'Lịch bảo trì hiện tại:' : 'Current scheduled date:'; ?> 
                                        <strong id="maint_modal_current_date"></strong>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 1.2rem;">
                                    <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                        <i class="fas fa-calendar-plus" style="color: #10b981;"></i>
                                        <?php echo $lang['eq_maintenance_interval'] ?? ($current_lang === 'vi' ? 'Kỳ Bảo Trì Tiếp Theo' : 'Next Maintenance Cycle'); ?>
                                    </label>
                                    <select name="next_interval_months" style="padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); width: 100%; outline: none; background: white; font-weight: 600;">
                                        <option value="1">1 <?php echo $current_lang === 'vi' ? 'tháng tới' : 'month (+1 mo)'; ?></option>
                                        <option value="2">2 <?php echo $current_lang === 'vi' ? 'tháng tới' : 'months (+2 mos)'; ?></option>
                                        <option value="3" selected>3 <?php echo $current_lang === 'vi' ? 'tháng tới (Khuyến nghị định kỳ)' : 'months (Recommended)'; ?></option>
                                        <option value="6">6 <?php echo $current_lang === 'vi' ? 'tháng tới (Bán niên)' : 'months (Semi-annual)'; ?></option>
                                        <option value="12">12 <?php echo $current_lang === 'vi' ? 'tháng tới (1 năm)' : 'months (1 year)'; ?></option>
                                    </select>
                                    <small style="color: var(--text-muted); font-size: 0.78rem; margin-top: 4px; display: block;">
                                        <?php echo $current_lang === 'vi' ? 'Hệ thống sẽ tự động cập nhật ngày bảo trì kế tiếp và đổi trạng thái máy sang Hoạt Động Tốt.' : 'System will automatically update the next maintenance date and reset status to Active.'; ?>
                                    </small>
                                </div>

                                <div class="form-group" style="margin-bottom: 1.2rem;">
                                    <label style="font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; display: block; color: var(--text-main);">
                                        <i class="fas fa-clipboard-check" style="color: #10b981;"></i>
                                        <?php echo $current_lang === 'vi' ? 'Nội Dung Kiểm Tra & Bảo Dưỡng' : 'Maintenance Details & Replaced Parts'; ?>
                                    </label>
                                    <textarea name="maintenance_note" rows="3" placeholder="<?php echo $current_lang === 'vi' ? 'Ví dụ: Đã tra dầu bạc đạn, căng lại băng tải, kiểm tra sensor nhịp tim đạt chuẩn an toàn...' : 'e.g. Lubricated bearings, adjusted running belt tension, safety sensors checked...'; ?>" style="width: 100%; padding: 0.85rem 1rem; border-radius: 12px; border: 1.5px solid var(--border); outline: none; resize: vertical;"></textarea>
                                </div>

                                <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                                    <button type="button" onclick="closeMaintenanceModal()" class="action-btn" style="background: #f1f5f9; color: var(--text-muted); border-radius: 12px; padding: 0.8rem 1.4rem; height: auto; text-decoration: none; font-weight: 700; border: none; cursor: pointer;">
                                        <?php echo $current_lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
                                    </button>
                                    <button type="submit" name="log_maintenance" class="btn-auth" style="margin: 0; width: auto; padding: 0.8rem 1.8rem; border-radius: 12px; font-weight: 700; background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 4px 15px rgba(16,185,129,0.3);">
                                        <i class="fas fa-check" style="margin-right: 6px;"></i>
                                        <?php echo $current_lang === 'vi' ? 'Xác Nhận Hoàn Tất' : 'Confirm Complete'; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <style>
                .eq-nav-tab {
                    padding: 0.85rem 1.6rem;
                    border-radius: 14px;
                    font-weight: 700;
                    font-size: 0.95rem;
                    border: 1.5px solid transparent;
                    background: transparent;
                    color: var(--text-muted);
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.2s ease;
                }
                .eq-nav-tab:hover {
                    color: var(--primary);
                    background: rgba(37, 99, 235, 0.05);
                }
                .eq-nav-tab.active {
                    background: white;
                    color: var(--primary);
                    border-color: var(--border);
                    box-shadow: var(--shadow-premium);
                }
                .btn-eq-details:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 14px rgba(37,99,235,0.4) !important;
                }
                </style>

                <!-- Client-Side Scripts for Tabs, Details View, Warranty Calculation & Filtering -->
                <script>
                const allEquipmentData = <?php echo $all_equipment_json ?: '[]'; ?>;
                const currentLanguage = '<?php echo $current_lang; ?>';

                function switchEquipmentTab(tabName) {
                    const overviewPane = document.getElementById('eq-pane-overview');
                    const detailsPane = document.getElementById('eq-pane-details');
                    const addPane = document.getElementById('eq-pane-add');

                    if (overviewPane) overviewPane.style.display = (tabName === 'overview') ? 'block' : 'none';
                    if (detailsPane) detailsPane.style.display = (tabName === 'details') ? 'block' : 'none';
                    if (addPane) addPane.style.display = (tabName === 'add') ? 'block' : 'none';

                    document.querySelectorAll('.eq-nav-tab').forEach(btn => btn.classList.remove('active'));
                    const activeBtn = document.getElementById('btn-tab-' + tabName);
                    if (activeBtn) activeBtn.classList.add('active');

                    if (history.replaceState) {
                        history.replaceState(null, null, '#tab-' + tabName);
                    }
                }

                function toggleDetailsSubMode(mode) {
                    const reportView = document.getElementById('eq-subview-report');
                    const editView = document.getElementById('eq-subview-edit');
                    const btnReport = document.getElementById('btn-mode-report');
                    const btnEdit = document.getElementById('btn-mode-edit');

                    if (mode === 'edit') {
                        if (reportView) reportView.style.display = 'none';
                        if (editView) editView.style.display = 'block';
                        if (btnEdit) { btnEdit.style.background = 'var(--primary)'; btnEdit.style.color = 'white'; }
                        if (btnReport) { btnReport.style.background = 'transparent'; btnReport.style.color = 'var(--text-muted)'; }
                    } else {
                        if (reportView) reportView.style.display = 'block';
                        if (editView) editView.style.display = 'none';
                        if (btnReport) { btnReport.style.background = 'var(--primary)'; btnReport.style.color = 'white'; }
                        if (btnEdit) { btnEdit.style.background = 'transparent'; btnEdit.style.color = 'var(--text-muted)'; }
                    }
                }

                function openEquipmentDetailsTab(eqId) {
                    const eq = allEquipmentData.find(e => parseInt(e.id) === parseInt(eqId));
                    if (!eq) return;

                    // 1. Populate form fields (for edit subview)
                    if (document.getElementById('det_eq_id')) document.getElementById('det_eq_id').value = eq.id;
                    if (document.getElementById('det_name')) document.getElementById('det_name').value = eq.name || '';
                    if (document.getElementById('det_discipline')) document.getElementById('det_discipline').value = eq.discipline || 'gym';
                    if (document.getElementById('det_status')) document.getElementById('det_status').value = eq.status || 'active';
                    if (document.getElementById('det_purchase_date')) document.getElementById('det_purchase_date').value = eq.purchase_date || '';
                    if (document.getElementById('det_warranty_months')) document.getElementById('det_warranty_months').value = eq.warranty_months || 12;
                    if (document.getElementById('det_warranty_expiry_date')) document.getElementById('det_warranty_expiry_date').value = eq.warranty_expiry_date || '';
                    if (document.getElementById('det_next_maintenance_date')) document.getElementById('det_next_maintenance_date').value = eq.next_maintenance_date || '';
                    if (document.getElementById('det_notes')) document.getElementById('det_notes').value = eq.notes || '';
                    if (document.getElementById('det_description')) document.getElementById('det_description').value = eq.description || '';
                    if (document.getElementById('det_custom_image_url')) document.getElementById('det_custom_image_url').value = eq.image || '';

                    // 2. Update quick selector dropdown
                    const sel = document.getElementById('detailsEquipmentSelector');
                    if (sel) sel.value = eq.id;

                    // 3. Update Word Report Card fields
                    if (document.getElementById('rc_img')) document.getElementById('rc_img').src = eq.image || '/Gym Manager/assets/img/equipment/treadmills.png';
                    if (document.getElementById('rc_name')) document.getElementById('rc_name').textContent = eq.name || '';
                    if (document.getElementById('rc_id_badge')) document.getElementById('rc_id_badge').textContent = '#' + eq.id;
                    
                    // Discipline badge
                    const discBadge = document.getElementById('rc_disc_badge');
                    if (discBadge) {
                        discBadge.textContent = (eq.discipline === 'gym' ? '🏋️ GYM' : (eq.discipline === 'muay' ? '🥊 MUAY THAI' : '🧘 YOGA'));
                        if (eq.discipline === 'gym') {
                            discBadge.style.background = '#eff6ff'; discBadge.style.color = '#1d4ed8';
                        } else if (eq.discipline === 'muay') {
                            discBadge.style.background = '#fef2f2'; discBadge.style.color = '#b91c1c';
                        } else {
                            discBadge.style.background = '#f0fdf4'; discBadge.style.color = '#15803d';
                        }
                    }

                    // Status badge & operational status text
                    const stBadge = document.getElementById('rc_status_badge');
                    const opStatus = document.getElementById('rc_op_status');
                    if (stBadge) {
                        if (eq.status === 'active') {
                            stBadge.textContent = '🟢 ' + (currentLanguage === 'vi' ? 'HOẠT ĐỘNG' : 'ACTIVE');
                            stBadge.style.background = '#dcfce7'; stBadge.style.color = '#15803d';
                            if (opStatus) opStatus.innerHTML = '<span style="color: #16a34a;">🟢 ' + (currentLanguage === 'vi' ? 'Sẵn Sàng Phục Vụ' : 'Operational') + '</span>';
                        } else if (eq.status === 'maintenance') {
                            stBadge.textContent = '🟡 ' + (currentLanguage === 'vi' ? 'BẢO TRÌ' : 'MAINTENANCE');
                            stBadge.style.background = '#fef3c7'; stBadge.style.color = '#d97706';
                            if (opStatus) opStatus.innerHTML = '<span style="color: #d97706;">🟡 ' + (currentLanguage === 'vi' ? 'Đang Sửa Chữa / Bảo Trì' : 'In Repair') + '</span>';
                        } else {
                            stBadge.textContent = '🔴 ' + (currentLanguage === 'vi' ? 'NGƯNG DÙNG' : 'RETIRED');
                            stBadge.style.background = '#fee2e2'; stBadge.style.color = '#ef4444';
                            if (opStatus) opStatus.innerHTML = '<span style="color: #ef4444;">🔴 ' + (currentLanguage === 'vi' ? 'Ngưng Sử Dụng' : 'Out of Order') + '</span>';
                        }
                    }

                    // Purchase Date
                    if (document.getElementById('rc_purchase_date')) {
                        document.getElementById('rc_purchase_date').textContent = formatDateDisplay(eq.purchase_date);
                    }

                    // Warranty Period & Expiry
                    if (document.getElementById('rc_warranty_period')) {
                        document.getElementById('rc_warranty_period').textContent = (eq.warranty_months || 12) + (currentLanguage === 'vi' ? ' Tháng' : ' Mos');
                    }
                    if (document.getElementById('rc_warranty_expiry')) {
                        document.getElementById('rc_warranty_expiry').textContent = formatDateDisplay(eq.warranty_expiry_date);
                    }

                    // Next Maintenance Date
                    if (document.getElementById('rc_next_maintenance')) {
                        document.getElementById('rc_next_maintenance').textContent = formatDateDisplay(eq.next_maintenance_date);
                    }

                    // Notes
                    if (document.getElementById('rc_notes_content')) {
                        document.getElementById('rc_notes_content').textContent = eq.notes || (currentLanguage === 'vi' ? 'Chưa có ghi chú kỹ thuật cho thiết bị này.' : 'No notes recorded.');
                    }

                    // Update dynamic badges for warranty and maintenance
                    updateDetailsBadges(eq);

                    // Update Tab 2 badge in top tab bar
                    const navBadge = document.getElementById('nav-selected-eq-badge');
                    if (navBadge) {
                        navBadge.style.display = 'inline-block';
                        navBadge.textContent = '#' + eq.id;
                    }

                    // Always reset to report view when opening details
                    toggleDetailsSubMode('report');

                    // Switch to Details pane & smooth scroll
                    switchEquipmentTab('details');
                    const wrapper = document.getElementById('equipmentSectionWrapper');
                    if (wrapper) wrapper.scrollIntoView({ behavior: 'smooth' });
                }

                function updateDetailsBadges(eq) {
                    const today = new Date();
                    today.setHours(0,0,0,0);

                    // Warranty Badge in Report Card
                    const wContainer = document.getElementById('rc_warranty_badge_container');
                    if (wContainer) {
                        if (eq.warranty_expiry_date) {
                            const expDate = new Date(eq.warranty_expiry_date);
                            expDate.setHours(0,0,0,0);
                            if (expDate >= today) {
                                const diffDays = Math.round((expDate - today) / (1000 * 60 * 60 * 24));
                                wContainer.innerHTML = `<span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-shield-alt"></i> ${currentLanguage === 'vi' ? 'Còn hạn: ' + diffDays + ' ngày' : 'Valid: ' + diffDays + 'd left'}</span>`;
                            } else {
                                wContainer.innerHTML = `<span style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;"><i class="fas fa-history"></i> ${currentLanguage === 'vi' ? 'Hết hạn bảo hành' : 'Warranty Expired'}</span>`;
                            }
                        } else {
                            wContainer.innerHTML = '<span style="color: #94a3b8; font-size: 0.75rem;">N/A</span>';
                        }
                    }

                    // Maintenance Badge in Report Card
                    const mContainer = document.getElementById('rc_maint_badge_container');
                    if (mContainer) {
                        if (eq.status === 'maintenance') {
                            mContainer.innerHTML = `<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-tools"></i> ${currentLanguage === 'vi' ? 'Đang sửa chữa' : 'In repair'}</span>`;
                        } else if (eq.next_maintenance_date) {
                            const mDate = new Date(eq.next_maintenance_date);
                            mDate.setHours(0,0,0,0);
                            const diffDays = Math.round((mDate - today) / (1000 * 60 * 60 * 24));
                            if (mDate < today) {
                                mContainer.innerHTML = `<span style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-exclamation-triangle"></i> ${currentLanguage === 'vi' ? 'Quá hạn ' + Math.abs(diffDays) + ' ngày' : 'Overdue ' + Math.abs(diffDays) + 'd'}</span>`;
                            } else if (diffDays <= 15) {
                                mContainer.innerHTML = `<span style="background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-clock"></i> ${currentLanguage === 'vi' ? 'Sắp tới hạn (' + diffDays + ' ngày)' : 'Due in ' + diffDays + 'd'}</span>`;
                            } else {
                                mContainer.innerHTML = `<span style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-check-circle"></i> ${currentLanguage === 'vi' ? 'Đúng định kỳ' : 'On schedule'}</span>`;
                            }
                        } else {
                            mContainer.innerHTML = '<span style="color: #94a3b8; font-size: 0.75rem;">N/A</span>';
                        }
                    }
                }

                function formatDateDisplay(dStr) {
                    if (!dStr) return 'N/A';
                    const parts = dStr.split('-');
                    if (parts.length === 3) {
                        return `${parts[2]}/${parts[1]}/${parts[0]}`;
                    }
                    return dStr;
                }

                function openMaintenanceModalFromDetails() {
                    const eqId = document.getElementById('det_eq_id').value;
                    const eq = allEquipmentData.find(e => parseInt(e.id) === parseInt(eqId));
                    const eqName = eq ? eq.name : (document.getElementById('det_name') ? document.getElementById('det_name').value : '');
                    const nextDate = eq ? eq.next_maintenance_date : '';
                    openMaintenanceModal(eqId, eqName, formatDateDisplay(nextDate));
                }

                function openMaintenanceModal(eqId, eqName, currentDate) {
                    document.getElementById('maint_modal_eq_id').value = eqId;
                    document.getElementById('maint_modal_eq_name').textContent = eqName;
                    document.getElementById('maint_modal_current_date').textContent = currentDate || 'N/A';
                    const shell = document.getElementById('maintenanceLogModalShell');
                    if (shell) shell.style.display = 'flex';
                }

                function closeMaintenanceModal() {
                    const shell = document.getElementById('maintenanceLogModalShell');
                    if (shell) shell.style.display = 'none';
                }

                function calcWarrantyDates(formId) {
                    const form = document.getElementById(formId);
                    if (!form) return;
                    const pInput = form.querySelector('[name="purchase_date"]');
                    const mSelect = form.querySelector('[name="warranty_months"]');
                    const wExpInput = form.querySelector('[name="warranty_expiry_date"]');
                    const nextMaintInput = form.querySelector('[name="next_maintenance_date"]');

                    if (!pInput || !pInput.value) return;
                    const pDate = new Date(pInput.value);
                    if (isNaN(pDate.getTime())) return;

                    const months = parseInt(mSelect ? mSelect.value : 12) || 12;

                    // Compute Warranty Expiry
                    const expDate = new Date(pDate);
                    expDate.setMonth(expDate.getMonth() + months);
                    if (wExpInput) {
                        const y = expDate.getFullYear();
                        const m = String(expDate.getMonth() + 1).padStart(2, '0');
                        const d = String(expDate.getDate()).padStart(2, '0');
                        wExpInput.value = `${y}-${m}-${d}`;
                    }

                    // Compute Next Maintenance
                    if (nextMaintInput && (!nextMaintInput.value || formId === 'addEquipmentForm')) {
                        const mDate = new Date(pDate);
                        mDate.setMonth(mDate.getMonth() + 3);
                        const my = mDate.getFullYear();
                        const mm = String(mDate.getMonth() + 1).padStart(2, '0');
                        const md = String(mDate.getDate()).padStart(2, '0');
                        nextMaintInput.value = `${my}-${mm}-${md}`;
                    }

                    // If in details form, refresh badges
                    if (formId === 'detailsEquipmentForm') {
                        updateDetailsBadges({
                            status: form.querySelector('[name="status"]').value,
                            warranty_expiry_date: wExpInput ? wExpInput.value : '',
                            next_maintenance_date: nextMaintInput ? nextMaintInput.value : ''
                        });
                    }
                }

                function filterEquipmentTable() {
                    const query = (document.getElementById('eqSearchInput').value || '').trim().toLowerCase();
                    const discipline = (document.getElementById('eqDisciplineFilter').value || '').toLowerCase();
                    const status = (document.getElementById('eqStatusFilter').value || '').toLowerCase();
                    const maint = (document.getElementById('eqMaintFilter').value || '').toLowerCase();

                    const rows = document.querySelectorAll('#equipmentTableBody tr.eq-table-row');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const rSearch = row.getAttribute('data-search') || '';
                        const rDiscipline = row.getAttribute('data-discipline') || '';
                        const rStatus = row.getAttribute('data-status') || '';
                        const rMaintStatus = row.getAttribute('data-maint-status') || '';
                        const rWarrantyValid = row.getAttribute('data-warranty-valid') || '0';

                        let matches = true;

                        if (query && !rSearch.includes(query)) matches = false;
                        if (discipline && rDiscipline !== discipline) matches = false;
                        if (status && rStatus !== status) matches = false;
                        if (maint) {
                            if (maint === 'overdue' && rMaintStatus !== 'overdue') matches = false;
                            if (maint === 'upcoming' && rMaintStatus !== 'upcoming') matches = false;
                            if (maint === 'warranty_valid' && rWarrantyValid !== '1') matches = false;
                            if (maint === 'warranty_expired' && rWarrantyValid !== '0') matches = false;
                        }

                        if (matches) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    const emptyRow = document.getElementById('eqEmptyRow');
                    if (emptyRow) {
                        emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
                    }
                }

                function resetEquipmentFilters() {
                    document.getElementById('eqSearchInput').value = '';
                    document.getElementById('eqDisciplineFilter').value = '';
                    document.getElementById('eqStatusFilter').value = '';
                    document.getElementById('eqMaintFilter').value = '';
                    filterEquipmentTable();
                }

                // Initialize initial active equipment details if loaded on details tab
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($selected_eq): ?>
                        const initialEq = allEquipmentData.find(e => e.id === <?php echo intval($selected_eq['id']); ?>);
                        if (initialEq) {
                            updateDetailsBadges(initialEq);
                        }
                    <?php endif; ?>

                    // Handle URL hash if present
                    if (window.location.hash === '#tab-details') {
                        switchEquipmentTab('details');
                    } else if (window.location.hash === '#tab-add') {
                        switchEquipmentTab('add');
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeMaintenanceModal();
                    }
                });
                </script>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

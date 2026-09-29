<?php
// ============================================================
// Initiate MoMo QR Payment for Shop Orders
// Creates order record, stores pending info in session,
// redirects to QR payment page (momo_qr.php)
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$phone   = trim($input['phone']   ?? '');
$address = trim($input['address'] ?? '');
$user_id = $_SESSION['user_id'];

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ (10 chữ số).']);
    exit;
}
if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập địa chỉ giao hàng.']);
    exit;
}
if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống.']);
    exit;
}

// --- Calculate total from cart ---
$ids       = array_keys($_SESSION['cart']);
$base_ids  = array_unique(array_map(fn($k) => explode('_', $k)[0], $ids));
$placeholders = implode(',', array_fill(0, count($base_ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_values($base_ids));
$db_products = [];
foreach ($stmt->fetchAll() as $p) { $db_products[$p['id']] = $p; }

$total_vnd = 0;
$checkout_items = [];
foreach ($ids as $cart_key) {
    $parts  = explode('_', $cart_key);
    $p_id   = $parts[0];
    $weight = $parts[1] ?? '';
    if (!isset($db_products[$p_id])) continue;
    $p    = $db_products[$p_id];
    $price = $p['price'];
    if ($weight === '1kg')  $price += 10;
    elseif ($weight === '2kg') $price += 25;
    elseif ($weight === '5kg') $price += 60;
    $qty = $_SESSION['cart'][$cart_key];
    $total_vnd += (int)round($price * 25000) * $qty; // Convert USD→VND (approx)
    $checkout_items[] = ['id' => $p['id'], 'price' => $price, 'qty' => $qty, 'variant' => $weight];
}

// MoMo minimum 1000 VND
if ($total_vnd < 1000) $total_vnd = 1000;

// --- Create order record (status = pending until QR confirmed) ---
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, phone, address, payment_method, admin_seen) VALUES (?, ?, 'pending', ?, ?, 'momo', 0)");
    $stmt->execute([$user_id, array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $checkout_items)), $phone, $address]);
    $order_id = $pdo->lastInsertId();

    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, variant) VALUES (?,?,?,?,?)");
    foreach ($checkout_items as $item) {
        $stmt_item->execute([$order_id, $item['id'], $item['qty'], $item['price'], $item['variant']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi tạo đơn hàng: ' . $e->getMessage()]);
    exit;
}

// --- Store pending payment info in session ---
$_SESSION['momo_pending'] = [
    'type'     => 'shop',
    'order_id' => $order_id,
    'amount'   => $total_vnd,
    'items'    => $checkout_items,
    'created'  => time(),
];

// Clear cart after order creation
unset($_SESSION['cart']);

// Return QR payment page URL
echo json_encode([
    'success' => true,
    'payUrl'  => '/Gym Manager/pages/momo_qr.php',
]);

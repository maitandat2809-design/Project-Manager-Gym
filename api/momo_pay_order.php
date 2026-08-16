<?php
// ============================================================
// Initiate MoMo Payment for Shop Orders
// ============================================================

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/momo_helper.php';

header('Content-Type: application/json');

// ────────────────────────────────────────────────────────────
// TEST MODE: Set to true for local testing without real MoMo
// Set to false when you have real credentials from business.momo.vn
// ────────────────────────────────────────────────────────────
define('MOMO_TEST_MODE', true);

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

// MoMo requires minimum 1000 VND
if ($total_vnd < 1000) $total_vnd = 1000;

// --- Create order record (status = pending until IPN confirms) ---
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

// --- Build extraData ---
$extraData = base64_encode(json_encode([
    'type'    => 'shop_order',
    'id'      => $order_id,
    'user_id' => $user_id,
]));

$orderId   = 'SHOP_' . $order_id . '_' . time();
$orderInfo = "Gym Manager - Don hang #$order_id";

// ── TEST MODE: skip real MoMo, simulate success immediately ────────
if (defined('MOMO_TEST_MODE') && MOMO_TEST_MODE) {
    // Mark order as completed directly
    $pdo->prepare("UPDATE orders SET status='completed', payment_method='momo' WHERE id=?")->execute([$order_id]);

    // Reduce stock
    $stmt_sales = $pdo->prepare("UPDATE products SET sales_count=sales_count+?, stock=GREATEST(0,stock-?) WHERE id=?");
    foreach ($checkout_items as $item) {
        $stmt_sales->execute([$item['qty'], $item['qty'], $item['id']]);
    }

    // Store success data for the success page
    unset($_SESSION['cart']);
    $_SESSION['momo_success'] = [
        'order_id' => $order_id,
        'amount'   => $total_vnd,
    ];

    echo json_encode([
        'success' => true,
        'payUrl'  => '/gymmanager/pages/momo_success.php?type=shop&test=1',
    ]);
    exit;
}

// ── PRODUCTION: real MoMo API call ───────────────────────────
$result = createMoMoPayment($orderId, $total_vnd, $orderInfo, $extraData);

if (isset($result['payUrl']) && !empty($result['payUrl'])) {
    unset($_SESSION['cart']);
    echo json_encode(['success' => true, 'payUrl' => $result['payUrl']]);
} else {
    // Rollback order on MoMo failure
    $pdo->prepare("DELETE FROM order_items WHERE order_id=?")->execute([$order_id]);
    $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([$order_id]);
    $msg = $result['localMessage'] ?? $result['message'] ?? 'Không thể kết nối MoMo. Thử lại sau.';
    echo json_encode(['success' => false, 'message' => $msg]);
}

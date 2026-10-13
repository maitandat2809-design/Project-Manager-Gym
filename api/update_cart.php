<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$cart_key = trim($input['cart_key'] ?? '');
$action = $input['action'] ?? 'set'; // 'set', 'change', 'remove'
$quantity = intval($input['quantity'] ?? 1);

if (empty($cart_key) || !isset($_SESSION['cart'][$cart_key])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit();
}

require_once __DIR__ . '/../config/db.php';

$p_id = explode('_', $cart_key)[0];
$st_prod = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
$st_prod->execute([$p_id]);
$prod_data = $st_prod->fetch();
$stock_limit = $prod_data ? intval($prod_data['stock']) : 99;

$is_vi = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'vi');

if ($action === 'remove' || ($action === 'set' && $quantity <= 0)) {
    unset($_SESSION['cart'][$cart_key]);
} elseif ($action === 'change') {
    $target_qty = ($_SESSION['cart'][$cart_key] ?? 0) + $quantity;
    if ($target_qty > $stock_limit) {
        $msg = $is_vi ? "Rất tiếc! Số lượng hàng trong kho không đủ (chỉ còn {$stock_limit} sản phẩm)." : "Sorry! Stock limit reached (only {$stock_limit} items available).";
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['cart'][$cart_key] = max(1, min($stock_limit, $target_qty));
} else {
    if ($quantity > $stock_limit) {
        $msg = $is_vi ? "Rất tiếc! Số lượng hàng trong kho không đủ (chỉ còn {$stock_limit} sản phẩm)." : "Sorry! Stock limit reached (only {$stock_limit} items available).";
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['cart'][$cart_key] = max(1, min($stock_limit, $quantity));
}
$cart_keys = array_keys($_SESSION['cart'] ?? []);
$total = 0;
$item_subtotal = 0;
$current_qty = $_SESSION['cart'][$cart_key] ?? 0;

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
        if ($w === '1kg') $price += 10;
        elseif ($w === '2kg') $price += 25;
        elseif ($w === '5kg') $price += 60;
        
        $q = $_SESSION['cart'][$k];
        $sub = $price * $q;
        $total += $sub;
        if ($k === $cart_key) {
            $item_subtotal = $sub;
        }
    }
}

$cart_count = array_sum($_SESSION['cart'] ?? []);

require_once __DIR__ . '/../includes/membership_loyalty.php';
$member_discount = 0;
$discount_rate = 0;
if (isset($_SESSION['user_id'])) {
    $loyalty = getUserMembershipLoyaltyInfo($pdo, (int)$_SESSION['user_id']);
    if (!empty($loyalty['eligible_for_renewal_perks'])) {
        $discount_rate = 0.10;
        $member_discount = round($total * $discount_rate, 2);
    }
}
$final_total = max(0, $total - $member_discount);
$final_total_vnd = (int)round($final_total * 25000);

echo json_encode([
    'success' => true,
    'cart_empty' => empty($_SESSION['cart']),
    'cart_key' => $cart_key,
    'current_qty' => $current_qty,
    'item_subtotal_formatted' => number_format((int)round($item_subtotal * 25000), 0, ',', '.') . 'đ',
    'subtotal_formatted' => number_format((int)round($total * 25000), 0, ',', '.') . 'đ',
    'total_formatted' => number_format($final_total_vnd, 0, ',', '.') . 'đ',
    'member_discount' => $member_discount,
    'member_discount_formatted' => number_format((int)round($member_discount * 25000), 0, ',', '.') . 'đ',
    'total_vnd_formatted' => number_format($final_total_vnd, 0, ',', '.') . 'đ',
    'cart_count' => $cart_count
]);
?>

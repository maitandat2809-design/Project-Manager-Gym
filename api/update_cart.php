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

if ($action === 'remove' || ($action === 'set' && $quantity <= 0)) {
    unset($_SESSION['cart'][$cart_key]);
} elseif ($action === 'change') {
    $_SESSION['cart'][$cart_key] = max(1, min(99, $_SESSION['cart'][$cart_key] + $quantity));
} else {
    $_SESSION['cart'][$cart_key] = max(1, min(99, $quantity));
}

// Calculate updated totals
require_once __DIR__ . '/../config/db.php';
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
$total_vnd = (int)round($total * 25000);

echo json_encode([
    'success' => true,
    'cart_empty' => empty($_SESSION['cart']),
    'cart_key' => $cart_key,
    'current_qty' => $current_qty,
    'item_subtotal_formatted' => number_format($item_subtotal, 2),
    'total_formatted' => number_format($total, 2),
    'total_vnd_formatted' => number_format($total_vnd, 0, ',', '.') . 'đ',
    'cart_count' => $cart_count
]);
?>

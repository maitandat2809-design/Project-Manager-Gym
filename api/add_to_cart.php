<?php
// ============================================================
// API: Add Product to Cart via AJAX (No page reload)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$product_id = intval($input['product_id'] ?? 0);
$qty        = max(1, intval($input['qty'] ?? 1));
$weight     = trim($input['weight'] ?? '');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Check stock and product details
$stmt = $pdo->prepare("SELECT id, name, COALESCE(name_vi, name) as name_vi, stock, price FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$max_stock = intval($product['stock']);
$is_vi     = (!isset($_SESSION['lang']) || $_SESSION['lang'] === 'vi');

if ($max_stock <= 0) {
    $msg = $is_vi ? 'Sản phẩm này hiện đã hết hàng.' : 'This product is currently out of stock.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

$cart_key = $weight ? $product_id . '_' . $weight : (string)$product_id;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current_qty = $_SESSION['cart'][$cart_key] ?? 0;
$target_qty  = $current_qty + $qty;

if ($target_qty > $max_stock) {
    $msg = $is_vi 
        ? "Số lượng trong giỏ ({$target_qty}) vượt quá số lượng còn trong kho ({$max_stock})." 
        : "Total requested quantity ({$target_qty}) exceeds available stock ({$max_stock}).";
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

$_SESSION['cart'][$cart_key] = $target_qty;
$total_cart_items = array_sum($_SESSION['cart']);

$prod_display_name = ($is_vi && !empty($product['name_vi'])) ? $product['name_vi'] : $product['name'];
if ($weight) {
    $prod_display_name .= " ({$weight})";
}

echo json_encode([
    'success'      => true,
    'cart_count'   => $total_cart_items,
    'product_name' => $prod_display_name,
    'message'      => $is_vi ? 'Đã thêm sản phẩm vào giỏ hàng thành công!' : 'Product added to cart successfully!'
]);

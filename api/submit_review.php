<?php
// ============================================================
// API: Submit Product Review via AJAX
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$product_id = intval($input['product_id'] ?? 0);
$rating     = max(1, min(5, intval($input['rating'] ?? 5)));
$comment    = trim($input['comment'] ?? '');
$user_id    = (int)$_SESSION['user_id'];

$is_vi = (!isset($_SESSION['lang']) || $_SESSION['lang'] === 'vi');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Sản phẩm không hợp lệ' : 'Invalid product']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => $is_vi ? 'Vui lòng nhập nhận xét của bạn' : 'Please enter your review comment']);
    exit;
}

try {
    $chk = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $chk->execute([$user_id, $product_id]);
    $existing = $chk->fetch();

    if ($existing) {
        $upd = $pdo->prepare("UPDATE product_reviews SET rating = ?, comment = ? WHERE id = ?");
        $upd->execute([$rating, $comment, $existing['id']]);
    } else {
        $ins = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $ins->execute([$product_id, $user_id, $rating, $comment]);
    }

    echo json_encode([
        'success' => true,
        'message' => $is_vi ? 'Cảm ơn bạn đã gửi đánh giá sản phẩm!' : 'Thank you for submitting your review!',
        'rating'  => $rating,
        'comment' => htmlspecialchars($comment)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

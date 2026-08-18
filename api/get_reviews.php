<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lang.php';

if (!isset($_GET['product_id'])) exit;

$product_id = intval($_GET['product_id']);
$stmt = $pdo->prepare("SELECT pr.*, u.name as user_name FROM product_reviews pr JOIN users u ON pr.user_id = u.id WHERE pr.product_id = ? ORDER BY pr.id DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

if (empty($reviews)) {
    echo "<p style='color: #64748b; text-align: center; margin: 2rem 0; font-size: 0.95rem;'>" . __('no_reviews_yet') . "</p>";
} else {
    foreach ($reviews as $r) {
        echo "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0;'>";
        echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;'>";
        echo "<strong style='color: #0f172a; font-weight: 700;'>" . htmlspecialchars($r['user_name']) . "</strong>";
        echo "<div style='color: #fbbf24; font-size: 0.9rem;'>";
        for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? "★" : "☆";
        echo "</div>";
        echo "</div>";
        echo "<p style='color: #475569; margin: 0; font-size: 0.95rem; line-height: 1.5;'>" . htmlspecialchars($r['comment']) . "</p>";
        if (!empty($r['created_at'])) {
            echo "<span style='font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 6px; text-align: right;'>" . date('Y-m-d H:i', strtotime($r['created_at'])) . "</span>";
        }
        echo "</div>";
    }
}
?>

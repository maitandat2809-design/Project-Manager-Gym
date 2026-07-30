<?php
require_once __DIR__ . '/../config/db.php';
if (!isset($_GET['product_id'])) exit;

$product_id = $_GET['product_id'];
$stmt = $pdo->prepare("SELECT pr.*, u.name as user_name FROM product_reviews pr JOIN users u ON pr.user_id = u.id WHERE pr.product_id = ? ORDER BY pr.id DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

if (empty($reviews)) {
    echo "<p style='color: #64748b; text-align: center; margin-top: 2rem;'>No reviews yet. Be the first to review this product!</p>";
} else {
    foreach ($reviews as $r) {
        echo "<div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0;'>";
        echo "<div style='display: flex; justify-content: space-between; margin-bottom: 8px;'>";
        echo "<strong style='color: #0f172a;'>" . htmlspecialchars($r['user_name']) . "</strong>";
        echo "<div style='color: #fbbf24;'>";
        for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? "★" : "☆";
        echo "</div>";
        echo "</div>";
        echo "<p style='color: #475569; margin: 0; font-size: 0.95rem; line-height: 1.5;'>" . htmlspecialchars($r['comment']) . "</p>";
        echo "</div>";
    }
}
?>

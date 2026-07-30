<?php
require_once 'config/db.php';

$updates = [
    'Bộ Tạ Tay (Dumbbell Set)' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop',
    'Dây Kháng Lực (Resistance Bands)' => 'https://images.unsplash.com/photo-1598289431512-b97b0917a63e?q=80&w=2070&auto=format&fit=crop',
    'Tạ Ấm (Kettlebell)' => 'https://images.unsplash.com/photo-1583454155184-870a1f63aebc?q=80&w=2070&auto=format&fit=crop',
    'Dây Nhảy Tốc Độ' => 'https://images.unsplash.com/photo-1517042578500-7bbd5669d518?q=80&w=2070&auto=format&fit=crop',
    'Con Lăn Foam Roller' => 'https://images.unsplash.com/photo-1620188467120-ef82a01444f4?q=80&w=2070&auto=format&fit=crop',
    'Găng Tay Tập Gym' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=2071&auto=format&fit=crop',
    'Đai Lưng Cứng' => 'https://images.unsplash.com/photo-1590487988256-9ed24133863e?q=80&w=2070&auto=format&fit=crop',
    'Ghế Tập Bụng Đa Năng' => 'https://images.unsplash.com/photo-1594737625785-a6badccc31d2?q=80&w=2070&auto=format&fit=crop',
    'Xà Đơn Treo Tường' => 'https://images.unsplash.com/photo-1598971639058-fab3c0bc7bac?q=80&w=2070&auto=format&fit=crop',
    'Thảm Tập Yoga Cao Cấp' => 'https://images.unsplash.com/photo-1592432676556-269e830e19ad?q=80&w=2070&auto=format&fit=crop',
    'Whey Protein' => 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?q=80&w=2070&auto=format&fit=crop',
    'Yoga Mat' => 'https://images.unsplash.com/photo-1599447421416-3414500d18a5?q=80&w=2070&auto=format&fit=crop'
];

try {
    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE name = ?");
    foreach ($updates as $name => $url) {
        $stmt->execute([$url, $name]);
    }
    echo "<h1>Cập nhật hình ảnh thành công!</h1>";
    echo "<a href='shop.php'>Quay lại cửa hàng</a>";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>

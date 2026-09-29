<?php
require_once __DIR__ . '/../config/db.php';

$coach_avatars = [
    // Thanh Coach PT (Yoga & Pilates) - User ID 6
    6 => 'https://images.unsplash.com/photo-1545205597-3d9d02c29597?q=80&w=1000&auto=format&fit=crop',
    
    // Dat Coach (Gym & Fitness) - User ID 7
    7 => 'https://images.unsplash.com/photo-1567013127542-490d757e51fc?q=80&w=1000&auto=format&fit=crop',
    
    // Hung Muay Thai (Muay Thai) - User ID 10
    10 => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?q=80&w=1000&auto=format&fit=crop',
    
    // Phuc Gym (Gym & Fitness) - User ID 14
    14 => 'https://images.unsplash.com/photo-1534367507873-d2d7e24c797f?q=80&w=1000&auto=format&fit=crop',
    
    // Hana Gym (Gym & Fitness) - User ID 15
    15 => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=1000&auto=format&fit=crop',
    
    // Mai Tan (Gym) - User ID 16
    16 => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?q=80&w=1000&auto=format&fit=crop'
];

try {
    $stmt_user = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt_coach = $pdo->prepare("UPDATE coaches SET image = ? WHERE user_id = ?");

    foreach ($coach_avatars as $user_id => $url) {
        $stmt_user->execute([$url, $user_id]);
        $stmt_coach->execute([$url, $user_id]);
        echo "Successfully updated Coach User ID {$user_id} with avatar: {$url}\n";
    }

    echo "\nAll coach avatars updated successfully with 100% unique, high-resolution fitness photos!\n";

} catch (Exception $e) {
    echo "Error updating coach avatars: " . $e->getMessage() . "\n";
}

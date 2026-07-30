<?php
require_once 'config/db.php';

$products = [
    ['Dumbbell Set', 'Premium 10kg dumbbell set, non-slip design.', 55.00, 20, 'https://images.unsplash.com/photo-1638536532686-d610adfc8e5c?q=80&w=2070&auto=format&fit=crop'],
    ['Resistance Bands', 'Set of 5 resistance bands for leg and glute workouts.', 15.00, 100, 'https://images.unsplash.com/photo-1598289431512-b97b0917a63e?q=80&w=2070&auto=format&fit=crop'],
    ['Kettlebell', '12kg cast iron kettlebell, competition standard.', 42.50, 15, 'https://images.unsplash.com/photo-1583454155184-870a1f63aebc?q=80&w=2070&auto=format&fit=crop'],
    ['Speed Jump Rope', 'Ball bearing jump rope for ultra-smooth 360-degree rotation.', 12.00, 50, 'https://images.unsplash.com/photo-1517042578500-7bbd5669d518?q=80&w=2070&auto=format&fit=crop'],
    ['Foam Roller', 'Support muscle recovery after workouts, reduces soreness.', 25.00, 30, 'https://images.unsplash.com/photo-1620188467120-ef82a01444f4?q=80&w=2070&auto=format&fit=crop'],
    ['Gym Gloves', 'Sheepskin gloves for better grip and palm protection.', 18.00, 40, 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?q=80&w=2071&auto=format&fit=crop'],
    ['Weightlifting Belt', 'Supports spine during heavy Squats and Deadlifts.', 35.00, 25, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop'],
    ['Multifunctional Ab Bench', 'Adjustable incline bench, sturdy steel frame.', 145.00, 10, 'https://images.unsplash.com/photo-1594737625785-a6badccc31d2?q=80&w=2070&auto=format&fit=crop'],
    ['Wall-Mounted Pull-Up Bar', 'Versatile pull-up bar, easy home installation.', 38.00, 15, 'https://images.unsplash.com/photo-1598971639058-fab3c0bc7bac?q=80&w=2070&auto=format&fit=crop'],
    ['Premium Yoga Mat', '2-layer TPE material, absolute slip resistance.', 28.00, 60, 'https://images.unsplash.com/photo-1592432676556-269e830e19ad?q=80&w=2070&auto=format&fit=crop']
];

try {
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->execute($p);
    }
    echo "<h1>Successfully added 10 pieces of equipment!</h1>";
    echo "<a href='shop.php'>View Shop</a>";
} catch (PDOException $e) {
    echo "<h1>Error:</h1> " . $e->getMessage();
}
?>

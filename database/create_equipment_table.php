<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        discipline VARCHAR(100) NOT NULL DEFAULT 'gym',
        description TEXT NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'equipment' created or already exists successfully!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM equipment");
    if ($stmt->fetchColumn() == 0) {
        $initial = [
            ['Treadmills', 'gym', 'Ideal for cardiovascular health, fat burning, and improving stamina.', 'active', '/Gym Manager/assets/img/equipment/treadmills.png'],
            ['Dumbbells', 'gym', 'Versatile free weights for building muscle mass, strength, and fixing imbalances.', 'active', '/Gym Manager/assets/img/equipment/dumbbells.png'],
            ['Barbells', 'gym', 'Essential for heavy compound lifts like squats, deadlifts, and bench presses.', 'active', '/Gym Manager/assets/img/equipment/barbells.png'],
            ['Cable Machines', 'gym', 'Offers constant tension for isolation exercises and functional training.', 'active', '/Gym Manager/assets/img/equipment/cable_machine.png'],
            ['Smith Machine', 'gym', 'Provides guided barbell movements for safer heavy lifting and targeted hypertrophy.', 'active', '/Gym Manager/assets/img/equipment/smith_machine.png'],
            ['Leg Press', 'gym', 'Heavy-duty lower body isolation machine for building massive leg strength.', 'active', '/Gym Manager/assets/img/equipment/leg_press.png'],
            ['Heavy Punching Bags', 'muay', 'Durable leather bags engineered for high-impact striking, kicking, and endurance.', 'active', '/Gym Manager/assets/img/equipment/heavy_bags.png'],
            ['Professional Boxing Gloves', 'muay', 'Multi-layered foam padding for hand protection during intense sparring.', 'active', '/Gym Manager/assets/img/equipment/boxing_gloves.png'],
            ['Focus Mitts', 'muay', 'Precision targets for speed, combination drills, and counter-punching.', 'active', '/Gym Manager/assets/img/equipment/focus_mitts.png'],
            ['Shin Guards', 'muay', 'High-density foam guards protecting shin and instep during kicking drills.', 'active', '/Gym Manager/assets/img/equipment/shin_guards.png'],
            ['Thai Kick Pads', 'muay', 'Heavy-duty curved pads designed to absorb powerful roundhouse kicks and knees.', 'active', '/Gym Manager/assets/img/equipment/thai_kick_pads.png'],
            ['Belly Pad', 'muay', 'Reinforced body protection for coaches during teep and knee strike training.', 'active', '/Gym Manager/assets/img/equipment/belly_pad.png'],
            ['Eco Premium Yoga Mats', 'yoga', 'High-density TPE mats providing non-slip grip, joint cushioning, and stability.', 'active', '/Gym Manager/assets/img/equipment/yoga_mats.png'],
            ['High-Density Foam Blocks', 'yoga', 'Lightweight support blocks assisting alignment, flexibility, and deep stretches.', 'active', '/Gym Manager/assets/img/equipment/yoga_blocks.png'],
            ['Resistance Stretch Bands', 'yoga', 'Elastic bands enhancing mobility, flexibility, and rehabilitation exercises.', 'active', '/Gym Manager/assets/img/equipment/yoga_bands.png'],
            ['Balance Balls (Bosu)', 'yoga', 'Core stability and balance training equipment for mindful movement control.', 'active', '/Gym Manager/assets/img/equipment/balance_balls.png'],
            ['Yoga Wheel', 'yoga', 'Back-opening and flexibility enhancement wheel for chest opening poses.', 'active', '/Gym Manager/assets/img/equipment/yoga_wheel.png'],
            ['Support Bolsters', 'yoga', 'Firm cushioned bolsters for restorative yoga and deep relaxation practices.', 'active', '/Gym Manager/assets/img/equipment/yoga_bolster.png']
        ];
        
        $ins = $pdo->prepare("INSERT INTO equipment (name, discipline, description, status, image) VALUES (?, ?, ?, ?, ?)");
        foreach ($initial as $item) {
            $ins->execute($item);
        }
        echo "Inserted initial 18 equipment items successfully!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

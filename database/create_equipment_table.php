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
            ['Treadmills', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/treadmills.png'],
            ['Dumbbells', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/dumbbells.png'],
            ['Barbells', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/barbells.png'],
            ['Cable Machines', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/cable_machine.png'],
            ['Smith Machine', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/smith_machine.png'],
            ['Leg Press', 'gym', 'Working well', 'active', '/Gym Manager/assets/img/equipment/leg_press.png'],
            ['Heavy Punching Bags', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/heavy_bags.png'],
            ['Professional Boxing Gloves', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/boxing_gloves.png'],
            ['Focus Mitts', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/focus_mitts.png'],
            ['Shin Guards', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/shin_guards.png'],
            ['Thai Kick Pads', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/thai_kick_pads.png'],
            ['Belly Pad', 'muay', 'Working well', 'active', '/Gym Manager/assets/img/equipment/belly_pad.png'],
            ['Eco Premium Yoga Mats', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/yoga_mats.png'],
            ['High-Density Foam Blocks', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/yoga_blocks.png'],
            ['Resistance Stretch Bands', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/yoga_bands.png'],
            ['Balance Balls (Bosu)', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/balance_balls.png'],
            ['Yoga Wheel', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/yoga_wheel.png'],
            ['Support Bolsters', 'yoga', 'Working well', 'active', '/Gym Manager/assets/img/equipment/yoga_bolster.png']
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

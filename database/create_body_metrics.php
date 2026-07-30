<?php
require_once 'config/db.php';

try {
    // Create Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS body_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            recorded_date DATE NOT NULL,
            weight DECIMAL(5,2) DEFAULT NULL,
            muscle_mass DECIMAL(5,2) DEFAULT NULL,
            fat_mass DECIMAL(5,2) DEFAULT NULL,
            bmi DECIMAL(4,2) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'body_metrics' created successfully!<br>";

    // Let's seed mock data for testing if there's a logged-in user or standard users.
    // We will seed mock data for user_id = 1 (if exists) or other users, or we can seed for all users so whoever logs in can see a pretty chart.
    $stmt = $pdo->query("SELECT id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($users as $uid) {
        // Check if data already exists for this user
        $check = $pdo->prepare("SELECT COUNT(*) FROM body_metrics WHERE user_id = ?");
        $check->execute([$uid]);
        if ($check->fetchColumn() == 0) {
            $stmt_insert = $pdo->prepare("
                INSERT INTO body_metrics (user_id, recorded_date, weight, muscle_mass, fat_mass, bmi, notes) VALUES
                (?, '2026-03-01', 75.0, 32.5, 18.0, 24.5, 'Starting phase'),
                (?, '2026-04-01', 74.2, 33.0, 17.2, 24.2, 'Inbody test 1'),
                (?, '2026-05-01', 73.0, 33.8, 15.5, 23.8, 'Progressing nicely'),
                (?, '2026-06-01', 72.1, 34.2, 14.0, 23.5, 'Consistent training'),
                (?, '2026-07-01', 71.5, 34.8, 12.8, 23.3, 'Goal achieved')
            ");
            $stmt_insert->execute([$uid, $uid, $uid, $uid, $uid]);
            echo "Seeded mock body metrics for user ID: $uid<br>";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

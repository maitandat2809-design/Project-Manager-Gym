<?php
require_once __DIR__ . '/../config/db.php';

$sqls = [];

// Create membership_plans table
$sqls[] = "CREATE TABLE IF NOT EXISTS `membership_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_vi` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_vi` text DEFAULT NULL,
  `price` decimal(12,0) NOT NULL,
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `features` text DEFAULT NULL,
  `features_vi` text DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `badge_color` varchar(20) DEFAULT '#2563eb',
  `is_popular` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Create member_subscriptions table
$sqls[] = "CREATE TABLE IF NOT EXISTS `member_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_paid` decimal(12,0) NOT NULL,
  `payment_method` enum('cod','bank_transfer') DEFAULT 'cod',
  `status` enum('pending','active','expired','cancelled') DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `admin_seen` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Add FK constraints
$sqls[] = "ALTER TABLE `member_subscriptions` ADD CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE";
$sqls[] = "ALTER TABLE `member_subscriptions` ADD CONSTRAINT FOREIGN KEY (`plan_id`) REFERENCES `membership_plans`(`id`) ON DELETE CASCADE";

// Insert default plans
$sqls[] = "INSERT INTO `membership_plans` (`name`, `name_vi`, `description`, `description_vi`, `price`, `duration_days`, `features`, `features_vi`, `badge`, `badge_color`, `is_popular`, `sort_order`) VALUES
('Basic Gym', 'Gym Cơ Bản', 'Perfect starter package for fitness beginners.', 'Gói tập lý tưởng cho người mới bắt đầu.', 5000, 30, 'Unlimited gym access|Free InBody analysis|Locker room & shower|1 PT trial session', 'Vào gym không giới hạn|Đo InBody miễn phí|Phòng thay đồ & tắm|1 buổi tập thử PT', 'STARTER', '#64748b', 0, 1),
('Premium Gym + PT', 'Premium Gym + PT', 'Best value with personal trainer sessions.', 'Gói giá trị nhất kèm buổi tập cùng HLV cá nhân.', 8000, 30, 'Unlimited gym access|4 PT sessions included|InBody + nutrition plan|Priority class booking|Towel & water included', 'Vào gym không giới hạn|4 buổi PT 1-1|InBody + lộ trình dinh dưỡng|Ưu tiên đặt lớp|Khăn & nước uống', 'POPULAR', '#2563eb', 1, 2),
('Elite Annual', 'Elite Hằng Năm', 'Full year unlimited access with maximum benefits.', 'Trọn năm không giới hạn với đầy đủ quyền lợi.', 10000, 365, 'Unlimited gym + all classes|12 PT sessions per year|Quarterly InBody check|Exclusive member lounge|Priority booking + guest pass', 'Vào gym + tất cả lớp không giới hạn|12 buổi PT cả năm|Đo InBody theo quý|Lounge hội viên cao cấp|Ưu tiên tuyệt đối + vé khách', 'BEST VALUE', '#d97706', 0, 3),
('Yoga & Wellness', 'Yoga & Sức Khỏe', 'Dedicated yoga, meditation and mobility package.', 'Gói Yoga, thiền định và tăng tính linh hoạt.', 6000, 30, 'Unlimited yoga classes|Mat & props included|Meditation sessions|Stress relief program', 'Lớp yoga không giới hạn|Thảm & dụng cụ miễn phí|Buổi thiền định|Chương trình giảm căng thẳng', 'WELLNESS', '#10b981', 0, 4),
('Fat Burn Intensive', 'Đốt Mỡ Chuyên Sâu', '8-week scientific fat loss transformation.', '8 tuần biến đổi đốt mỡ theo chuẩn y khoa.', 7000, 60, 'HIIT & cardio unlimited|Weekly metrics review|Custom meal plan|2 PT sessions/week|Body composition tracking', 'HIIT & cardio không giới hạn|Kiểm tra chỉ số hàng tuần|Thực đơn cá nhân hóa|2 buổi PT/tuần|Theo dõi cấu trúc cơ thể', 'TRANSFORM', '#ef4444', 0, 5)";

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ OK: " . substr($sql, 0, 80) . "...</p>";
    } catch(PDOException $e) {
        echo "<p style='color:orange'>⚠️ " . substr($sql, 0, 80) . "... → " . $e->getMessage() . "</p>";
    }
}

echo "<br><h2 style='color:green'>✅ Membership tables created! <a href='/Gym Manager/pages/membership.php'>View Membership Page</a></h2>";
?>

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
('Basic Gym Package', 'Gói Gym Cơ Bản', 'Flexible monthly basic gym package for all fitness levels.', 'Gói tập Gym cơ bản linh hoạt theo tháng cho mọi đối tượng.', 899000, 30, 'Unlimited gym training access|Free InBody metrics analysis|1 free PT trial session|Locker room & hot showers', 'Vào gym không giới hạn giờ tập|Đo InBody miễn phí|1 buổi tập thử PT 1-1|Tủ đồ locker & phòng tắm nóng lạnh', 'STARTER', '#64748b', 0, 1),
('Yoga & Wellness', 'Gói Yoga & Sức Khỏe', 'Comprehensive yoga, meditation and mobility package.', 'Gói Yoga, thiền định và phục hồi năng lượng toàn diện.', 699000, 30, 'Unlimited yoga group classes|Premium mat & props included|Guided meditation sessions|Quiet studio & aromatherapy', 'Lớp Yoga không giới hạn|Thảm tập & dụng cụ cao cấp|Buổi thiền định hướng dẫn|Phòng tập yên tĩnh & tinh dầu thảo mộc', 'WELLNESS', '#10b981', 0, 2),
('Kickboxing & GroupX', 'Gói Kickboxing & GroupX', 'High-energy combat & group workout classes package.', 'Gói tập các lớp nhóm sôi động, xả stress và rèn luyện thể lực.', 1190000, 30, 'Unlimited Kickboxing & GroupX classes|Pro coach guidance each session|Standard gloves & heavy bags|Boost endurance & stress relief', 'Không giới hạn lớp Kickboxing & GroupX|HLV hướng dẫn trực tiếp từng buổi|Dụng cụ găng & bao cát tiêu chuẩn|Tăng sức bền & xả stress hiệu quả', 'ATHLETIC', '#8b5cf6', 0, 3),
('8-Week Fat Loss', 'Gói Giảm Mỡ 8 Tuần', '8-week intensive medical-grade fat loss transformation.', 'Khóa huấn luyện biến đổi vóc dáng & đốt mỡ chuẩn y khoa 8 tuần.', 2290000, 60, 'High-intensity fat burn training|Weekly body metrics review|Customized medical nutrition plan|24/7 shape consultation', 'Lịch tập HIIT & Cardio chuyên sâu|Kiểm tra chỉ số hàng tuần|Thực đơn dinh dưỡng cá nhân hóa|Cố vấn vóc dáng & tư vấn 24/7', 'TRANSFORM', '#ef4444', 0, 4),
('Personal Trainer 1:1', 'Gói PT 1:1 Cao Cấp', '1-on-1 personal coaching with certified personal trainer.', 'Huấn luyện viên cá nhân 1 kèm 1 đạt chuẩn quốc tế.', 3490000, 30, '10 1-on-1 personal trainer sessions|Customized workout & meal plan|Unlimited monthly gym access|Guaranteed results contract', '10 buổi tập 1 kèm 1 với HLV riêng|Lộ trình dinh dưỡng & bài tập riêng|Vào gym không giới hạn trọn tháng|Cam kết hiệu quả theo hợp đồng', 'POPULAR', '#2563eb', 1, 5),
('Elite Annual', 'Gói Elite Hằng Năm', 'Full-year VIP unlimited access with maximum privileges.', 'Thẻ thành viên VIP trọn gói cả năm với tối đa đặc quyền.', 6890000, 365, 'Unlimited gym & all class access|12 free 1-on-1 PT sessions/year|Dedicated locker & free towel service|Monthly InBody tracking|VIP priority + 1 guest pass/month', 'Vào gym & tất cả các lớp không giới hạn|12 buổi PT 1-1 miễn phí cả năm|Tủ đồ cá nhân cố định + Khăn tắm|Đo InBody định kỳ hàng tháng|Ưu tiên tuyệt đối + Vé dẫn 1 người thân/tháng', 'BEST VALUE', '#d97706', 0, 6)";

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

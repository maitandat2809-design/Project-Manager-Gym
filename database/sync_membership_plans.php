<?php
require_once __DIR__ . '/../config/db.php';

$plans = [
    [
        'name' => 'Basic Gym Package',
        'name_vi' => 'Gói Gym Cơ Bản',
        'description' => 'Flexible monthly basic gym package for all fitness levels.',
        'description_vi' => 'Gói tập Gym cơ bản linh hoạt theo tháng cho mọi đối tượng.',
        'price' => 899000,
        'duration_days' => 30,
        'features' => 'Unlimited gym training access|Free InBody metrics analysis|1 free PT trial session|Locker room & hot showers',
        'features_vi' => 'Vào gym không giới hạn giờ tập|Đo InBody miễn phí|1 buổi tập thử PT 1-1|Tủ đồ locker & phòng tắm nóng lạnh',
        'badge' => 'STARTER',
        'badge_color' => '#64748b',
        'is_popular' => 0,
        'sort_order' => 1
    ],
    [
        'name' => 'Yoga & Wellness',
        'name_vi' => 'Gói Yoga & Sức Khỏe',
        'description' => 'Comprehensive yoga, meditation and mobility package.',
        'description_vi' => 'Gói Yoga, thiền định và phục hồi năng lượng toàn diện.',
        'price' => 699000,
        'duration_days' => 30,
        'features' => 'Unlimited yoga group classes|Premium mat & props included|Guided meditation sessions|Quiet studio & aromatherapy',
        'features_vi' => 'Lớp Yoga không giới hạn|Thảm tập & dụng cụ cao cấp|Buổi thiền định hướng dẫn|Phòng tập yên tĩnh & tinh dầu thảo mộc',
        'badge' => 'WELLNESS',
        'badge_color' => '#10b981',
        'is_popular' => 0,
        'sort_order' => 2
    ],
    [
        'name' => 'Kickboxing & GroupX',
        'name_vi' => 'Gói Kickboxing & GroupX',
        'description' => 'High-energy combat & group workout classes package.',
        'description_vi' => 'Gói tập các lớp nhóm sôi động, xả stress và rèn luyện thể lực.',
        'price' => 1190000,
        'duration_days' => 30,
        'features' => 'Unlimited Kickboxing & GroupX classes|Pro coach guidance each session|Standard gloves & heavy bags|Boost endurance & stress relief',
        'features_vi' => 'Không giới hạn lớp Kickboxing & GroupX|HLV hướng dẫn trực tiếp từng buổi|Dụng cụ găng & bao cát tiêu chuẩn|Tăng sức bền & xả stress hiệu quả',
        'badge' => 'ATHLETIC',
        'badge_color' => '#8b5cf6',
        'is_popular' => 0,
        'sort_order' => 3
    ],
    [
        'name' => '8-Week Fat Loss',
        'name_vi' => 'Gói Giảm Mỡ 8 Tuần',
        'description' => '8-week intensive medical-grade fat loss transformation.',
        'description_vi' => 'Khóa huấn luyện biến đổi vóc dáng & đốt mỡ chuẩn y khoa 8 tuần.',
        'price' => 2290000,
        'duration_days' => 60,
        'features' => 'High-intensity fat burn training|Weekly body metrics review|Customized medical nutrition plan|24/7 shape consultation',
        'features_vi' => 'Lịch tập HIIT & Cardio chuyên sâu|Kiểm tra chỉ số hàng tuần|Thực đơn dinh dưỡng cá nhân hóa|Cố vấn vóc dáng & tư vấn 24/7',
        'badge' => 'TRANSFORM',
        'badge_color' => '#ef4444',
        'is_popular' => 0,
        'sort_order' => 4
    ],
    [
        'name' => 'Personal Trainer 1:1',
        'name_vi' => 'Gói PT 1:1 Cao Cấp',
        'description' => '1-on-1 personal coaching with certified personal trainer.',
        'description_vi' => 'Huấn luyện viên cá nhân 1 kèm 1 đạt chuẩn quốc tế.',
        'price' => 3490000,
        'duration_days' => 30,
        'features' => '10 1-on-1 personal trainer sessions|Customized workout & meal plan|Unlimited monthly gym access|Guaranteed results contract',
        'features_vi' => '10 buổi tập 1 kèm 1 với HLV riêng|Lộ trình dinh dưỡng & bài tập riêng|Vào gym không giới hạn trọn tháng|Cam kết hiệu quả theo hợp đồng',
        'badge' => 'POPULAR',
        'badge_color' => '#2563eb',
        'is_popular' => 1,
        'sort_order' => 5
    ],
    [
        'name' => 'Elite Annual',
        'name_vi' => 'Gói Elite Hằng Năm',
        'description' => 'Full-year VIP unlimited access with maximum privileges.',
        'description_vi' => 'Thẻ thành viên VIP trọn gói cả năm với tối đa đặc quyền.',
        'price' => 6890000,
        'duration_days' => 365,
        'features' => 'Unlimited gym & all class access|12 free 1-on-1 PT sessions/year|Dedicated locker & free towel service|Monthly InBody tracking|VIP priority + 1 guest pass/month',
        'features_vi' => 'Vào gym & tất cả các lớp không giới hạn|12 buổi PT 1-1 miễn phí cả năm|Tủ đồ cá nhân cố định + Khăn tắm|Đo InBody định kỳ hàng tháng|Ưu tiên tuyệt đối + Vé dẫn 1 người thân/tháng',
        'badge' => 'BEST VALUE',
        'badge_color' => '#d97706',
        'is_popular' => 0,
        'sort_order' => 6
    ]
];

try {
    // Clear old membership plans or update them
    foreach ($plans as $p) {
        $stmt_check = $pdo->prepare("SELECT id FROM membership_plans WHERE name = ? OR name_vi = ?");
        $stmt_check->execute([$p['name'], $p['name_vi']]);
        $existing = $stmt_check->fetch();

        if ($existing) {
            $stmt_u = $pdo->prepare("
                UPDATE membership_plans SET
                    name = ?, name_vi = ?, description = ?, description_vi = ?,
                    price = ?, duration_days = ?, features = ?, features_vi = ?,
                    badge = ?, badge_color = ?, is_popular = ?, sort_order = ?, is_active = 1
                WHERE id = ?
            ");
            $stmt_u->execute([
                $p['name'], $p['name_vi'], $p['description'], $p['description_vi'],
                $p['price'], $p['duration_days'], $p['features'], $p['features_vi'],
                $p['badge'], $p['badge_color'], $p['is_popular'], $p['sort_order'],
                $existing['id']
            ]);
            echo "Updated plan ID: " . $existing['id'] . " (" . $p['name_vi'] . ")\n";
        } else {
            $stmt_i = $pdo->prepare("
                INSERT INTO membership_plans 
                (name, name_vi, description, description_vi, price, duration_days, features, features_vi, badge, badge_color, is_popular, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt_i->execute([
                $p['name'], $p['name_vi'], $p['description'], $p['description_vi'],
                $p['price'], $p['duration_days'], $p['features'], $p['features_vi'],
                $p['badge'], $p['badge_color'], $p['is_popular'], $p['sort_order']
            ]);
            echo "Inserted new plan: " . $p['name_vi'] . "\n";
        }
    }

    // Clean up outdated test plans if any
    $valid_names = array_column($plans, 'name');
    $in_clause = implode(',', array_fill(0, count($valid_names), '?'));
    $stmt_del = $pdo->prepare("UPDATE membership_plans SET is_active = 0 WHERE name NOT IN ($in_clause)");
    $stmt_del->execute($valid_names);

    echo "SUCCESS: All 6 membership plans updated with Vietnam market pricing!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

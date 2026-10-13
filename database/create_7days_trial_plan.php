<?php
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT id FROM membership_plans WHERE duration_days = 7 OR name = '7 Days Free Trial' LIMIT 1");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt_up = $pdo->prepare("
            UPDATE membership_plans SET
                name = '7 Days Free Trial',
                name_vi = '7 Ngày Tập Miễn Phí',
                description = '7 days free training trial with full gym access and privileges.',
                description_vi = 'Gói trải nghiệm 7 ngày tập luyện miễn phí với đầy đủ tiện ích phòng gym.',
                price = 0,
                duration_days = 7,
                features = 'Full Gym Access for 7 Days|Free Locker & Shower|1 InBody Analysis|1-on-1 PT Consultation',
                features_vi = 'Tập luyện toàn bộ khu vực trong 7 ngày|Tủ locker & phòng tắm nóng lạnh|1 lần đo chỉ số InBody miễn phí|Tư vấn lộ trình 1-1 cùng HLV',
                badge = '7 DAYS FREE',
                badge_color = '#00b4ff',
                is_popular = 0,
                is_active = 0,
                sort_order = 0,
                pt_sessions = 1
            WHERE id = ?
        ");
        $stmt_up->execute([$existing['id']]);
        echo "Updated existing 7 Days Free Trial plan ID: " . $existing['id'] . "\n";
    } else {
        $stmt_in = $pdo->prepare("
            INSERT INTO membership_plans (
                name, name_vi, description, description_vi, price, duration_days,
                features, features_vi, badge, badge_color, is_popular, is_active, sort_order, pt_sessions
            ) VALUES (
                '7 Days Free Trial',
                '7 Ngày Tập Miễn Phí',
                '7 days free training trial with full gym access and privileges.',
                'Gói trải nghiệm 7 ngày tập luyện miễn phí với đầy đủ tiện ích phòng gym.',
                0,
                7,
                'Full Gym Access for 7 Days|Free Locker & Shower|1 InBody Analysis|1-on-1 PT Consultation',
                'Tập luyện toàn bộ khu vực trong 7 ngày|Tủ locker & phòng tắm nóng lạnh|1 lần đo chỉ số InBody miễn phí|Tư vấn lộ trình 1-1 cùng HLV',
                '7 DAYS FREE',
                '#00b4ff',
                0,
                0,
                0,
                1
            )
        ");
        $stmt_in->execute();
        echo "Created new 7 Days Free Trial plan ID: " . $pdo->lastInsertId() . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

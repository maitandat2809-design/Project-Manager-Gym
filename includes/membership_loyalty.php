<?php
/**
 * GymX Membership Loyalty & Renewal Helper
 * Handles loyalty verification, renewal perks, discounts, bonus days, and PT perks
 * for members who currently hold or previously purchased memberships.
 */

if (!function_exists('getUserMembershipLoyaltyInfo')) {
    function getUserMembershipLoyaltyInfo($pdo, $user_id) {
        $info = [
            'is_logged_in'          => ($user_id > 0),
            'has_active'            => false,
            'has_history'           => false,
            'is_eligible'           => false,
            'active_sub'            => null,
            'pending_sub'           => null,
            'history_count'         => 0,
            'tier_badge'            => 'STANDARD',
            'tier_name_vi'          => 'Khách Hàng',
            'tier_name_en'          => 'Standard Client',
            'shop_discount_percent' => 0
        ];

        if (!$user_id) {
            return $info;
        }

        try {
            // Check currently active subscription
            $stmt_act = $pdo->prepare("
                SELECT ms.*, mp.name, mp.name_vi, mp.duration_days as plan_duration, 
                       mp.price as plan_price, mp.badge, mp.badge_color,
                       DATEDIFF(ms.end_date, CURDATE()) as days_left
                FROM member_subscriptions ms
                JOIN membership_plans mp ON ms.plan_id = mp.id
                WHERE ms.user_id = ? 
                  AND ms.status = 'active' 
                  AND ms.end_date >= CURDATE()
                ORDER BY ms.end_date DESC 
                LIMIT 1
            ");
            $stmt_act->execute([$user_id]);
            $active_sub = $stmt_act->fetch(PDO::FETCH_ASSOC);

            // Check pending subscription
            $stmt_pen = $pdo->prepare("
                SELECT ms.*, mp.name, mp.name_vi 
                FROM member_subscriptions ms
                JOIN membership_plans mp ON ms.plan_id = mp.id
                WHERE ms.user_id = ? AND ms.status = 'pending'
                ORDER BY ms.id DESC LIMIT 1
            ");
            $stmt_pen->execute([$user_id]);
            $pending_sub = $stmt_pen->fetch(PDO::FETCH_ASSOC);

            // Count previous completed or paid subscriptions
            $stmt_cnt = $pdo->prepare("
                SELECT COUNT(*) FROM member_subscriptions 
                WHERE user_id = ? AND (status IN ('active', 'expired') OR price_paid > 0)
            ");
            $stmt_cnt->execute([$user_id]);
            $history_count = (int)$stmt_cnt->fetchColumn();

            $has_active  = !empty($active_sub);
            $has_pending = !empty($pending_sub);
            $has_history = ($history_count > 0);
            $is_eligible = ($has_active || $has_history || $has_pending);

            $tier_badge = 'STANDARD';
            $tier_name_vi = 'Khách Hàng';
            $tier_name_en = 'Standard Client';
            if ($is_eligible) {
                if ($history_count >= 2 || ($active_sub && (float)$active_sub['price_paid'] >= 2000000)) {
                    $tier_badge = 'VIP GOLD';
                    $tier_name_vi = 'Hội Viên VIP Gold';
                    $tier_name_en = 'VIP Gold Member';
                } elseif ($has_active) {
                    $tier_badge = 'LOYAL MEMBER';
                    $tier_name_vi = 'Hội Viên Thân Thiết';
                    $tier_name_en = 'Loyal Member';
                } elseif ($has_pending) {
                    $tier_badge = 'PENDING VIP';
                    $tier_name_vi = 'Hội Viên Đang Mua Thẻ';
                    $tier_name_en = 'Pending Member';
                } else {
                    $tier_badge = 'RETURNING MEMBER';
                    $tier_name_vi = 'Hội Viên Đã Từng Mua';
                    $tier_name_en = 'Returning Member';
                }
            }

            $info['has_active']            = $has_active;
            $info['has_pending']           = $has_pending;
            $info['has_history']           = $has_history;
            $info['is_eligible']           = $is_eligible;
            $info['eligible_for_renewal_perks'] = $is_eligible;
            $info['is_currently_active']   = $has_active;
            $info['is_currently_pending']  = $has_pending;
            $info['is_previously_purchased'] = ($has_history && !$has_active);
            $info['has_membership']        = $is_eligible;
            $info['status_label_vi']       = $tier_name_vi;
            $info['status_label_en']       = $tier_name_en;
            $info['active_sub']            = $active_sub;
            $info['pending_sub']           = $pending_sub;
            $info['history_count']         = $history_count;
            $info['tier_badge']            = $tier_badge;
            $info['tier_name_vi']          = $tier_name_vi;
            $info['tier_name_en']          = $tier_name_en;
            $info['shop_discount_percent'] = $is_eligible ? 10 : 0;

        } catch (Exception $e) {
            error_log("getUserMembershipLoyaltyInfo error: " . $e->getMessage());
        }

        return $info;
    }
}

if (!function_exists('getPlanLoyaltyPerks')) {
    function getPlanLoyaltyPerks($plan, $loyalty_info) {
        $price = (float)$plan['price'];
        $duration = (int)$plan['duration_days'];
        $base_pt = (int)($plan['pt_sessions'] ?? 0);

        if (empty($loyalty_info['is_eligible']) && empty($loyalty_info['eligible_for_renewal_perks'])) {
            return [
                'is_eligible'               => false,
                'is_renewal'                => false,
                'renewal_type'              => 'new',
                'is_same_plan_renewal'      => false,
                'discount_percent'          => 0,
                'discount_amount'           => 0,
                'orig_price'                => $price,
                'orig_price_formatted'      => number_format($price, 0, ',', '.') . 'đ',
                'discount_amount_formatted' => '0đ',
                'final_price'               => $price,
                'final_price_formatted'     => number_format($price, 0, ',', '.') . 'đ',
                'bonus_days'                => 0,
                'total_days'                => $duration,
                'bonus_pt'                  => 0,
                'total_pt'                  => $base_pt,
                'gifts'                     => [],
                'start_date'                => date('Y-m-d'),
                'end_date'                  => date('Y-m-d', strtotime("+{$duration} days")),
                'calc_start_date'           => date('Y-m-d'),
                'calc_end_date'             => date('Y-m-d', strtotime("+{$duration} days"))
            ];
        }

        // User is eligible (currently has active membership OR previously purchased)
        $is_same_plan = ($loyalty_info['has_active'] && (int)$loyalty_info['active_sub']['plan_id'] === (int)$plan['id']);
        
        // 15% off for annual plans, 10% off for other plans
        $discount_percent = ($duration >= 365) ? 15 : 10;
        $discount_amount  = round(($price * $discount_percent) / 100);
        $final_price      = max(0, $price - $discount_amount);

        // Bonus extension days
        if ($duration >= 365) {
            $bonus_days = 30; // +1 month VIP
        } elseif ($duration >= 60) {
            $bonus_days = 14; // +2 weeks
        } else {
            $bonus_days = 7;  // +1 week
        }
        $total_days = $duration + $bonus_days;

        // Bonus PT sessions
        if ($base_pt > 0 || (int)$plan['id'] === 9 || (int)$plan['id'] === 3) {
            $bonus_pt = 2; // +2 extra PT 1:1 sessions
        } else {
            $bonus_pt = 1; // +1 complimentary PT 1:1 orientation session
        }
        $total_pt = $base_pt + $bonus_pt;

        // Renewal type & Start date calculation
        if ($is_same_plan) {
            $renewal_type = 'extend';
            // Start from existing active subscription end_date, so user NEVER loses their remaining days
            $curr_end = $loyalty_info['active_sub']['end_date'];
            $start_date = ($curr_end >= date('Y-m-d')) ? $curr_end : date('Y-m-d');
        } elseif ($loyalty_info['has_active']) {
            $renewal_type = 'upgrade';
            $start_date = date('Y-m-d');
        } else {
            $renewal_type = 'reactivate';
            $start_date = date('Y-m-d');
        }

        $end_date = date('Y-m-d', strtotime("+{$total_days} days", strtotime($start_date)));

        $gifts = [
            [
                'icon' => 'fa-bottle-water',
                'vi'   => 'Bình nước thể thao GymX Shaker VIP Pro',
                'en'   => 'GymX VIP Pro Sports Shaker'
            ],
            [
                'icon' => 'fa-ticket-alt',
                'vi'   => '02 Vé mời bạn bè / người thân tập cùng (Guest Pass)',
                'en'   => '2 Free Guest Passes for Friends'
            ],
            [
                'icon' => 'fa-heartbeat',
                'vi'   => 'Đo chỉ số InBody & Phác đồ dinh dưỡng định kỳ miễn phí',
                'en'   => 'Free InBody Scan & Nutrition Consultation'
            ],
            [
                'icon' => 'fa-tags',
                'vi'   => 'Đặc quyền Hội viên: Giảm thêm 10% đơn hàng tại GymX Shop',
                'en'   => 'Loyalty Privilege: 10% OFF at GymX Shop'
            ]
        ];

        return [
            'is_eligible'               => true,
            'is_renewal'                => true,
            'renewal_type'              => $renewal_type,
            'is_same_plan_renewal'      => $is_same_plan,
            'discount_percent'          => $discount_percent,
            'discount_amount'           => $discount_amount,
            'discount_amount_formatted' => number_format($discount_amount, 0, ',', '.') . 'đ',
            'orig_price'                => $price,
            'orig_price_formatted'      => number_format($price, 0, ',', '.') . 'đ',
            'final_price'               => $final_price,
            'final_price_formatted'     => number_format($final_price, 0, ',', '.') . 'đ',
            'bonus_days'                => $bonus_days,
            'total_days'                => $total_days,
            'bonus_pt'                  => $bonus_pt,
            'total_pt'                  => $total_pt,
            'gifts'                     => $gifts,
            'start_date'                => $start_date,
            'end_date'                  => $end_date,
            'calc_start_date'           => $start_date,
            'calc_end_date'             => $end_date
        ];
    }
}

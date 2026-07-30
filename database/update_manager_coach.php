<?php
$files = ['manager.php', 'admin.php'];

$search_start = '<h3 style="margin-top: 1rem; margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-calendar-check"></i> Appointments in <?php echo htmlspecialchars($selected_year); ?>
                </h3>';

$search_end = '                    </table>
                </div>

                <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Products Purchased in';

$new_code = <<<HTML
                <?php 
                \$bookings_by_coach = [];
                foreach (\$yearly_bookings as \$b) {
                    \$c_name = \$b['coach_name'];
                    if (!isset(\$bookings_by_coach[\$c_name])) {
                        \$bookings_by_coach[\$c_name] = [];
                    }
                    \$bookings_by_coach[\$c_name][] = \$b;
                }
                ?>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-weight: 800;">
                    <i class="fas fa-users-cog" style="color: var(--primary);"></i> Coach Performance Summary (<?php echo htmlspecialchars(\$selected_year); ?>)
                </h3>
                
                <?php if (empty(\$bookings_by_coach)): ?>
                    <p style="color: var(--text-muted); margin-bottom: 3rem;"><?php echo __("no_appointments"); ?></p>
                <?php else: ?>
                    <div style="margin-bottom: 3rem;">
                    <?php foreach (\$bookings_by_coach as \$c_name => \$c_bookings): ?>
                    <div style="background: white; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                        <div style="background: #f8fafc; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                            <h4 style="margin: 0; color: var(--primary); font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-user-tie"></i> HLV <?php echo htmlspecialchars(\$c_name); ?>
                            </h4>
                            <span style="background: var(--primary); color: white; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                                Tổng lịch đặt: <?php echo count(\$c_bookings); ?>
                            </span>
                        </div>
                        
                        <div class="table-responsive" style="overflow-x: auto; width: 100%; max-width: calc(100vw - 320px);">
                            <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("date_time"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("client_name"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("email"); ?></th>
                                        <th style="padding: 1.2rem; white-space: nowrap; text-align: left; border-bottom: 1px solid #e2e8f0;"><?php echo __("phone"); ?></th>
                                        <th style="padding: 1.2rem; text-align: center; white-space: nowrap; border-bottom: 1px solid #e2e8f0;"><?php echo __("status"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(\$c_bookings as \$b): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td style="padding: 1.2rem; font-weight: 600; border-bottom: 1px solid #f1f5f9;"><?php echo date('d/m/Y H:i', strtotime(\$b['booking_date'])); ?></td>
                                        <td style="padding: 1.2rem; border-bottom: 1px solid #f1f5f9;"><strong><?php echo htmlspecialchars(\$b['user_name']); ?></strong></td>
                                        <td style="padding: 1.2rem; word-break: break-all; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars(\$b['user_email']); ?></td>
                                        <td style="padding: 1.2rem; border-bottom: 1px solid #f1f5f9;"><?php echo htmlspecialchars(\$b['user_phone'] ?? 'N/A'); ?></td>
                                        <td style="padding: 1.2rem; text-align: center; border-bottom: 1px solid #f1f5f9;">
                                            <span class="status-badge status-<?php echo \$b['status']; ?>"><?php echo strtoupper(\$b['status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Products Purchased in
HTML;

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $start_pos = strpos($content, '<h3 style="margin-top: 1rem; margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">');
        $end_pos = strpos($content, '<h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Products Purchased in');
                    
        if ($start_pos !== false && $end_pos !== false && $end_pos > $start_pos) {
            $new_content = substr($content, 0, $start_pos) . $new_code . substr($content, $end_pos + strlen('<h3 style="margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Products Purchased in'));
            file_put_contents($file, $new_content);
            echo "Updated $file successfully.\n";
        } else {
            echo "Pattern not found in $file.\n";
        }
    }
}

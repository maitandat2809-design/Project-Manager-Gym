<?php
$files = ['manager.php', 'admin.php'];

$chart_php_code = <<<PHP
                // Fetch monthly revenue for the chart
                \$stmt_chart = \$pdo->prepare("SELECT MONTH(created_at) as month, SUM(total_price) as revenue 
                                             FROM orders 
                                             WHERE YEAR(created_at) = ? AND status = 'completed'
                                             GROUP BY MONTH(created_at)");
                \$stmt_chart->execute([\$selected_year]);
                \$chart_data_raw = \$stmt_chart->fetchAll(PDO::FETCH_ASSOC);

                \$monthly_revenue = array_fill(1, 12, 0);
                foreach(\$chart_data_raw as \$row) {
                    \$monthly_revenue[(int)\$row['month']] = (float)\$row['revenue'];
                }
PHP;

$chart_html_code = <<<HTML
                <!-- Chart Section -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <div style="background: white; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 3rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                        <?php echo __("monthly_revenue_chart"); ?>
                    </h3>
                    <div style="height: 350px; width: 100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const canvas = document.getElementById('revenueChart');
                    if(canvas) {
                        const ctx = canvas.getContext('2d');
                        
                        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(0, 98, 255, 0.4)');
                        gradient.addColorStop(1, 'rgba(0, 98, 255, 0.0)');

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                datasets: [{
                                    label: 'Revenue',
                                    data: <?php echo json_encode(array_values(\$monthly_revenue)); ?>,
                                    backgroundColor: gradient,
                                    borderColor: '#0062ff',
                                    borderWidth: 3,
                                    pointBackgroundColor: '#ffffff',
                                    pointBorderColor: '#0062ff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        titleFont: { size: 13 },
                                        bodyFont: { size: 14, weight: 'bold' },
                                        padding: 12,
                                        displayColors: false,
                                        callbacks: {
                                            label: function(context) {
                                                return '$' + context.parsed.y.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: '#f1f5f9', drawBorder: false },
                                        ticks: {
                                            callback: function(value) { return '$' + value; },
                                            font: { family: 'Inter, sans-serif', size: 12 },
                                            color: '#64748b'
                                        }
                                    },
                                    x: {
                                        grid: { display: false, drawBorder: false },
                                        ticks: {
                                            font: { family: 'Inter, sans-serif', size: 12 },
                                            color: '#64748b'
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
                </script>
HTML;

foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        
        // Find injection point for PHP code
        if (strpos($content, '$yearly_orders = $stmt_o->fetchAll();') !== false && strpos($content, 'monthly_revenue_chart') === false) {
            $content = str_replace(
                '$yearly_orders = $stmt_o->fetchAll();', 
                '$yearly_orders = $stmt_o->fetchAll();' . "\n\n" . $chart_php_code, 
                $content
            );
        }
        
        // Find injection point for HTML code
        if (strpos($content, '</form>') !== false && strpos($content, 'id="revenueChart"') === false) {
            $content = str_replace(
                '</form>' . "\n" . '                </div>', 
                '</form>' . "\n" . '                </div>' . "\n\n" . $chart_html_code, 
                $content
            );
        }
        
        file_put_contents($f, $content);
        echo "Updated $f with chart.\n";
    }
}

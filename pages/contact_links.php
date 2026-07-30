<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php';

$type = $_GET['type'] ?? 'zalo';

// Config
$config = [
    'zalo' => [
        'title' => 'GymX Zalo Official',
        'subtitle' => 'Gym & Fitness Center',
        'desc' => 'Thank you for your interest in GymX Zalo OA. We look forward to accompanying you on your fitness journey. We invite you to check out our current promotions at GymX. Connect with us now!',
        'button' => 'Message via Zalo',
        'icon' => 'https://img.icons8.com/color/48/zalo.png',
        'btn_color' => '#0068ff',
        'qr' => 'assets/images/zalo_qr.png',
        'link' => 'https://zalo.me/gymxofficial' // Placeholder if they click the button instead of scanning
    ],
    'messenger' => [
        'title' => 'GymX Facebook Page',
        'subtitle' => 'Official Fanpage & Community',
        'desc' => 'Thank you for following GymX on Facebook. We are here to support and advise you 24/7. Send us a message or join our community to receive a free 7-day trial voucher!',
        'button' => 'Visit Facebook Page',
        'icon' => 'https://img.icons8.com/color/48/facebook-messenger--v1.png',
        'btn_color' => '#0084ff',
        'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://www.facebook.com/share/g/18NdyiYrdj/?mibextid=wwXIfr',
        'link' => 'mock_fanpage.php'
    ],
    'phone' => [
        'title' => 'GymX Hotline',
        'subtitle' => 'Customer Care Support',
        'desc' => 'GymX is always ready to listen to your inquiries and support requests. Please call our direct hotline for the fastest assistance.',
        'button' => 'Call Now: 0888.236.757',
        'icon' => 'https://img.icons8.com/color/48/phone.png',
        'btn_color' => '#22c55e',
        'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=tel:0888236757',
        'link' => 'tel:0888236757'
    ]
];

$current = $config[$type] ?? $config['zalo'];
?>

<style>
    .oa-container { background-color: #f1f5f9; min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .oa-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; max-width: 800px; display: flex; flex-direction: column; overflow: hidden; }
    .oa-body { display: flex; flex-wrap: wrap; padding: 2rem; }
    .oa-left { flex: 1; min-width: 300px; padding-right: 2rem; border-right: 1px solid #e2e8f0; }
    .oa-right { width: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-left: 2rem; }
    @media (max-width: 768px) {
        .oa-left { padding-right: 0; border-right: none; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; margin-bottom: 2rem; }
        .oa-right { padding-left: 0; width: 100%; }
    }
</style>

<div class="oa-container">
    <div class="oa-card">
        <div class="oa-body">
            <!-- Left side details -->
            <div class="oa-left">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 60px; height: 60px; background: <?php echo $current['btn_color']; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 900;">
                        GX
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem; color: #0f172a; display: flex; align-items: center; gap: 5px;">
                            <?php echo $current['title']; ?>
                            <i class="fas fa-check-circle" style="color: #fbbf24; font-size: 1rem;"></i>
                        </h2>
                        <p style="margin: 5px 0 0 0; color: #64748b; font-size: 0.9rem;"><?php echo $current['subtitle']; ?></p>
                    </div>
                </div>

                <a href="<?php echo $current['link']; ?>" <?php echo $type == 'messenger' ? '' : 'target="_blank"'; ?> style="display: block; width: 100%; background: <?php echo $current['btn_color']; ?>; color: white; text-align: center; padding: 0.8rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 2rem; transition: opacity 0.2s;">
                    <img src="<?php echo $current['icon']; ?>" style="width: 20px; vertical-align: middle; margin-right: 8px; filter: brightness(0) invert(1);"> 
                    <?php echo $current['button']; ?>
                </a>

                <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; color: #0f172a;">Details Information</h3>
                <p style="color: #475569; line-height: 1.6; font-size: 0.95rem;">
                    <?php echo $current['desc']; ?>
                </p>
            </div>

            <!-- Right side QR -->
            <div class="oa-right">
                <?php if ($type == 'zalo'): ?>
                <div style="width: 250px; height: 250px; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; position: relative; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <img src="<?php echo $current['qr']; ?>" style="position: absolute; width: 127%; left: -13.5%; top: -43%; max-width: none;" alt="QR Code">
                </div>
                <?php else: ?>
                <div style="border: 1px solid #e2e8f0; padding: 1rem; border-radius: 12px; background: white; position: relative;">
                    <img src="<?php echo $current['qr']; ?>" style="width: 100%; height: auto; display: block;" alt="QR Code">
                    <!-- Overlay logo in center of QR -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 4px; border-radius: 50%;">
                        <img src="<?php echo $current['icon']; ?>" style="width: 32px; height: 32px; display: block;">
                    </div>
                </div>
                <?php endif; ?>
                
                <p style="text-align: center; font-size: 0.85rem; color: #64748b; margin-top: 1rem;">
                    Open <?php echo ucfirst($type); ?> on your phone,<br>scan the QR code to connect.
                </p>
            </div>
        </div>
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 1.5rem; text-align: center;">
            <a href="/Gym Manager/index.php" style="display: inline-block; padding: 0.8rem 2rem; border-radius: 30px; background: white; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Website
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

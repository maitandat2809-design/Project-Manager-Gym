<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php';
?>

<div class="news-page-container" style="padding: 120px 2rem 4rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h1 style="font-size: 3.5rem; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900; letter-spacing: -1px; margin-bottom: 1rem;"><?php echo __('news_title'); ?></h1>
        <div style="width: 80px; height: 6px; background: var(--grad-primary); margin: 0 auto; border-radius: 10px;"></div>
        <p style="color: var(--text-muted); margin-top: 1.5rem; font-size: 1.2rem;"><?php echo __('news_subtitle'); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2.5rem;">
        <!-- Card 1 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column;">
            <div style="height: 220px; background: url('https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;"></div>
            <div style="padding: 2rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="color: var(--primary); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;"><?php echo __('news_date1'); ?></span>
                    <h3 style="color: var(--text-main); font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.3;"><?php echo __('news_title1'); ?></h3>
                    <p style="color: var(--text-muted); line-height: 1.7; font-size: 1rem; margin-bottom: 1.5rem;"><?php echo __('news_desc1'); ?></p>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column;">
            <div style="height: 220px; background: url('https://images.unsplash.com/photo-1498837167922-ddd27525d352?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;"></div>
            <div style="padding: 2rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="color: var(--primary); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;"><?php echo __('news_date2'); ?></span>
                    <h3 style="color: var(--text-main); font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.3;"><?php echo __('news_title2'); ?></h3>
                    <p style="color: var(--text-muted); line-height: 1.7; font-size: 1rem; margin-bottom: 1.5rem;"><?php echo __('news_desc2'); ?></p>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="card reveal" style="background: white; border-radius: 20px; box-shadow: var(--shadow-premium); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column;">
            <div style="height: 220px; background: url('https://images.unsplash.com/photo-1599058917212-d750089bc07e?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;"></div>
            <div style="padding: 2rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="color: var(--primary); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.5rem;"><?php echo __('news_date3'); ?></span>
                    <h3 style="color: var(--text-main); font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.3;"><?php echo __('news_title3'); ?></h3>
                    <p style="color: var(--text-muted); line-height: 1.7; font-size: 1rem; margin-bottom: 1.5rem;"><?php echo __('news_desc3'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

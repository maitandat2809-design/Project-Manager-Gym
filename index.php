<?php include 'includes/header.php'; ?>


<!-- Ambient Orbs -->
<div class="ambient-orb orb-1"></div>
<div class="ambient-orb orb-2"></div>

<!-- Marquee Ticker -->
<div class="marquee-wrapper">
    <div class="marquee-track">
        <span>GymX Premium</span>
        <span class="highlight">✦</span>
        <span><?php echo __('expert_team'); ?></span>
        <span class="highlight">✦</span>
        <span>European Standard</span>
        <span class="highlight">✦</span>
        <span><?php echo __('premium_facility'); ?></span>
        <span class="highlight">✦</span>
        <span>Personal Training</span>
        <span class="highlight">✦</span>
        <span>Gym · Yoga · Muay Thai</span>
        <span class="highlight">✦</span>
        <span>Booking Online 24/7</span>
        <span class="highlight">✦</span>
        <!-- duplicate for seamless loop -->
        <span>GymX Premium</span>
        <span class="highlight">✦</span>
        <span><?php echo __('expert_team'); ?></span>
        <span class="highlight">✦</span>
        <span>European Standard</span>
        <span class="highlight">✦</span>
        <span><?php echo __('premium_facility'); ?></span>
        <span class="highlight">✦</span>
        <span>Personal Training</span>
        <span class="highlight">✦</span>
        <span>Gym · Yoga · Muay Thai</span>
        <span class="highlight">✦</span>
        <span>Booking Online 24/7</span>
        <span class="highlight">✦</span>
    </div>
</div>

<!-- Hero -->
<header class="hero">
    <div class="hero-bg-video">
        <iframe src="https://www.youtube.com/embed/gC_L9qAHVJ8?autoplay=1&mute=1&controls=0&showinfo=0&loop=1&playlist=gC_L9qAHVJ8&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <div class="hero-badge" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(8px); color: rgba(255,255,255,0.8); border: 1px solid rgba(201,168,76,0.35);">
            <span class="dot" style="background: var(--gold);"></span>
            <?php echo __('hero_badge'); ?>
        </div>
        <h1><?php echo __('hero_title'); ?></h1>
        <p><?php echo __('hero_subtitle'); ?></p>
        <div style="display: flex; gap: 1.2rem; animation: fadeInUp 1s ease 0.6s backwards; flex-wrap: wrap; margin-top: 2.5rem;">
            <?php 
            $hero_target = '/Gym Manager/auth/register.php';
            if (isset($_SESSION['role'])) {
                if ($_SESSION['role'] === 'coach') {
                    $hero_target = '/Gym Manager/dashboards/coach_dashboard.php';
                } elseif ($_SESSION['role'] === 'manager') {
                    $hero_target = '/Gym Manager/dashboards/manager.php';
                } elseif ($_SESSION['role'] === 'admin') {
                    $hero_target = '/Gym Manager/dashboards/admin.php';
                } else {
                    $hero_target = '/Gym Manager/pages/coaches.php';
                }
            }
            ?>
            <a href="<?php echo $hero_target; ?>" class="cta-btn" style="margin: 0; padding: 1rem 2.4rem; font-size: 0.82rem;"><?php echo __('start_journey'); ?></a>
            <a href="#features" class="cta-btn" style="margin: 0; padding: 1rem 2.4rem; font-size: 0.82rem; background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.18); box-shadow: none;"><?php echo __('explore_more'); ?></a>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="scroll-cue">
        <div class="scroll-line"></div>
        <span>Scroll</span>
    </div>
</header>

<?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
<!-- Promo Strip -->
<div class="promo-section">
    <a href="/Gym Manager/auth/register.php" class="promo-btn trial">
        <span class="label"><?php echo __('register'); ?></span>
        <span class="main-text"><?php echo __('train_free'); ?></span>
    </a>
    <a href="/Gym Manager/auth/register.php" class="promo-btn offer">
        <span class="label"><?php echo __('register_now'); ?></span>
        <span class="main-text"><?php echo __('special_offer'); ?></span>
    </a>
</div>
<?php endif; ?>

<!-- Stats Band -->
<div class="stats-band">
    <div class="stat-block reveal">
        <span class="num" data-target="500">0</span><span class="unit">+</span>
        <span class="lbl"><?php echo __('active_members'); ?></span>
        <div class="bar"></div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.1s">
        <span class="num" data-target="12">0</span><span class="unit">+</span>
        <span class="lbl"><?php echo __('coaches'); ?></span>
        <div class="bar"></div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.2s">
        <span class="num" data-target="8">0</span><span class="unit"></span>
        <span class="lbl"><?php echo __('years_experience'); ?></span>
        <div class="bar"></div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.3s">
        <span class="num" data-target="98">0</span><span class="unit">%</span>
        <span class="lbl"><?php echo __('satisfaction_rate'); ?></span>
        <div class="bar"></div>
    </div>
</div>

<!-- Services Section -->
<section id="features">
    <div class="section-title reveal">
        <div class="eu-eyebrow"><?php echo __('tag_training'); ?></div>
        <h2><?php echo __('our_services'); ?></h2>
        <div class="jewel-divider"><span>◆ ◆ ◆</span></div>
        <p style="color: var(--text-muted); font-size: 1.05rem; max-width: 550px; margin: 0 auto;"><?php echo __('services_subtitle'); ?></p>
    </div>

    <div class="grid">
        <div class="card reveal">
            <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop" class="card-img" alt="Gym">
            <div class="card-content">
                <div class="card-tag"><?php echo __('tag_training'); ?></div>
                <h3 class="card-title"><?php echo __('card_title_facilities'); ?></h3>
                <p style="color: var(--text-muted);"><?php echo __('card_desc_facilities'); ?></p>
                <div class="card-footer">
                    <a href="/Gym Manager/pages/equipment.php" class="btn-card"><?php echo __('view_equipment'); ?></a>
                </div>
            </div>
        </div>

        <div class="card reveal" style="transition-delay: 0.15s;">
            <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=2070&auto=format&fit=crop" class="card-img" alt="Coach">
            <div class="card-content">
                <div class="card-tag"><?php echo __('tag_coaching'); ?></div>
                <h3 class="card-title"><?php echo __('card_title_mentors'); ?></h3>
                <p style="color: var(--text-muted);"><?php echo __('card_desc_mentors'); ?></p>
                <div class="card-footer">
                    <a href="/Gym Manager/pages/coaches.php" class="btn-card"><?php echo __('find_coach'); ?></a>
                </div>
            </div>
        </div>

        <div class="card reveal" style="transition-delay: 0.3s;">
            <img src="https://images.unsplash.com/photo-1593095948071-474c5cc2989d?q=80&w=2070&auto=format&fit=crop" class="card-img" alt="Products">
            <div class="card-content">
                <div class="card-tag"><?php echo __('tag_products'); ?></div>
                <h3 class="card-title"><?php echo __('card_title_fuel'); ?></h3>
                <p style="color: var(--text-muted);"><?php echo __('card_desc_fuel'); ?></p>
                <div class="card-footer">
                    <a href="/Gym Manager/pages/shop.php" class="btn-card"><?php echo __('shop_premium'); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
<!-- Featured Services Section -->
<section id="featured-services" style="padding: 6rem 2rem; background: #ffffff; position: relative; z-index: 2;">
    <div class="section-title reveal">
        <div class="eu-eyebrow" style="color: var(--primary); letter-spacing: 3px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;"><?php echo __('our_services'); ?></div>
        <h2 style="font-family: 'Playfair Display', serif; font-weight: 800; font-size: clamp(2.2rem, 4vw, 3.2rem); color: var(--text-main); margin-top: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('fs_title'); ?></h2>
        <div class="jewel-divider" style="margin-bottom: 1.2rem;"><span>◆ ◆ ◆</span></div>
        <p style="color: var(--text-muted); font-size: 1.05rem; max-width: 600px; margin: 0 auto; line-height: 1.6; font-weight: 500; font-family: 'Outfit', sans-serif;"><?php echo __('fs_subtitle'); ?></p>
    </div>

    <div class="fs-grid reveal" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; max-width: 1200px; margin: 3.5rem auto 0; padding: 0 1rem; font-family: 'Outfit', sans-serif;">
        <!-- Card 1: Gym -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1540206351-d6465b3ac5c1?q=80&w=600&auto=format&fit=crop" alt="Gym" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: rgba(30, 41, 59, 0.85); color: #fff; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-dumbbell" style="margin-right: 5px;"></i> Gym</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_gym_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">1.200.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">899.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / tháng</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-clock" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_gym_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-heartbeat" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_gym_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-gift" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_gym_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('gym'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('gym'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>

        <!-- Card 2: PT 1:1 -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=600&auto=format&fit=crop" alt="PT 1:1" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: #e2e8f0; color: #1e293b; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 800; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-user-shield" style="margin-right: 5px; color: #2563eb;"></i> PT 1:1</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_pt_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">4.500.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">3.490.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / khóa</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-user-friends" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_pt_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-utensils" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_pt_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-file-contract" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_pt_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('pt'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('pt'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>

        <!-- Card 3: Fat Loss -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1518310383802-640c2de311b2?q=80&w=600&auto=format&fit=crop" alt="Fat Loss" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: rgba(30, 41, 59, 0.85); color: #fff; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-fire" style="margin-right: 5px; color: #ef4444;"></i> Burn Fat</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_fatloss_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">3.000.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">2.290.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / khóa</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-tachometer-alt" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_fatloss_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-chart-line" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_fatloss_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-book" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_fatloss_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('fatloss'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('fatloss'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>

        <!-- Card 4: Yoga -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=600&auto=format&fit=crop" alt="Yoga" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: rgba(30, 41, 59, 0.85); color: #fff; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-yin-yang" style="margin-right: 5px; color: #10b981;"></i> Yoga</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_yoga_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">900.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">699.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / tháng</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-users" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_yoga_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-sparkles" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_yoga_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-hands-helping" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_yoga_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('yoga'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('yoga'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>

        <!-- Card 5: Body Pump -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?q=80&w=600&auto=format&fit=crop" alt="Body Pump" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: rgba(30, 41, 59, 0.85); color: #fff; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-heartbeat" style="margin-right: 5px; color: #e11d48;"></i> Strength</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_bodypump_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">850.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">649.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / tháng</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-users" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_bodypump_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-shield-alt" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_bodypump_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-burn" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_bodypump_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('bodypump'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('bodypump'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>

        <!-- Card 6: Kickboxing -->
        <div class="fs-card" style="background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;">
            <div class="fs-card-img-wrapper" style="position: relative; height: 210px; overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1599058917212-d750089bc07e?q=80&w=600&auto=format&fit=crop" alt="Kickboxing" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                <div style="position: absolute; top: 15px; left: 15px; background: rgba(30, 41, 59, 0.85); color: #fff; padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; backdrop-filter: blur(5px); text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-bolt" style="margin-right: 5px; color: #f59e0b;"></i> Combat</div>
            </div>
            <div class="fs-card-body" style="padding: 1.8rem; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0 0 1rem; font-family: 'Playfair Display', serif;"><?php echo __('fs_kickboxing_title'); ?></h3>
                
                <div class="fs-price-container" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                    <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.95rem; font-weight: 600;">950.000đ</span>
                    <span style="color: #2563eb; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">749.000đ<span style="font-size: 0.8rem; font-weight: 600; color: #64748b;"> / tháng</span></span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-shield-alt" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_kickboxing_feature1'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-running" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_kickboxing_feature2'); ?></li>
                    <li style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 500;"><i class="fas fa-hands" style="color: #2563eb; font-size: 0.95rem; width: 18px;"></i> <?php echo __('fs_kickboxing_feature3'); ?></li>
                </ul>

                <div style="display: flex; gap: 0.8rem; margin-top: auto;">
                    <a href="#" onclick="openServiceDetails('kickboxing'); return false;" class="fs-btn fs-btn-secondary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; background: transparent; text-decoration: none;"><?php echo __('fs_view_details'); ?></a>
                    <a href="#" onclick="openServiceRegistration('kickboxing'); return false;" class="fs-btn fs-btn-primary" style="flex: 1; text-align: center; padding: 0.85rem; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); border: none; text-decoration: none;"><?php echo __('fs_register_now'); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Add elegant styling & zoom on hover interactions for Featured Services -->
<style>
.fs-card {
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.fs-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 45px rgba(0,0,0,0.12) !important;
    border-color: #cbd5e1 !important;
}
.fs-card:hover img {
    transform: scale(1.08);
}
.fs-btn-secondary:hover {
    background: #f8fafc !important;
    border-color: #94a3b8 !important;
    color: #1e293b !important;
}
.fs-btn-primary:hover {
    background: #1d4ed8 !important;
    box-shadow: 0 6px 16px rgba(29, 78, 216, 0.3) !important;
}
</style>

<!-- Features Strip -->
<div class="features-strip">
    <div class="feature-strip-item reveal">
        <div class="fi-icon"><i class="fas fa-dumbbell"></i></div>
        <h4><?php echo __('feat_equip_title'); ?></h4>
        <p><?php echo __('feat_equip_desc'); ?></p>
    </div>
    <div class="feature-strip-item reveal" style="transition-delay: 0.12s;">
        <div class="fi-icon"><i class="fas fa-user-shield"></i></div>
        <h4><?php echo __('feat_coach_title'); ?></h4>
        <p><?php echo __('feat_coach_desc'); ?></p>
    </div>
    <div class="feature-strip-item reveal" style="transition-delay: 0.24s;">
        <div class="fi-icon"><i class="fas fa-calendar-check"></i></div>
        <h4><?php echo __('feat_book_title'); ?></h4>
        <p><?php echo __('feat_book_desc'); ?></p>
    </div>
</div>

<!-- Smart Workout & Fitness Advisor Section -->
<section id="workout-advisor" style="padding: 6rem 2rem; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); position: relative; z-index: 2;">
    <div class="section-title reveal" style="text-align: center; max-width: 800px; margin: 0 auto 3rem;">
        <div class="eu-eyebrow" style="color: var(--primary); letter-spacing: 3px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">
            💡 <?php echo __('advisor_eyebrow'); ?>
        </div>
        <h2 style="font-family: 'Playfair Display', serif; font-weight: 800; font-size: clamp(2rem, 4vw, 3rem); color: var(--text-main); margin-top: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase;">
            <?php echo __('advisor_title'); ?>
        </h2>
        <div class="jewel-divider" style="margin-bottom: 1.2rem;"><span>◆ ◆ ◆</span></div>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; font-weight: 500;">
            <?php echo __('advisor_subtitle'); ?>
        </p>
    </div>

    <!-- Category Tabs Selector -->
    <div style="max-width: 1100px; margin: 0 auto; background: #ffffff; border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="display: flex; border-bottom: 2px solid #f1f5f9; background: #f8fafc; flex-wrap: wrap;">
            <button id="tabBtnBodyAdvisor" onclick="switchAdvisorTab('body')" style="flex: 1; min-width: 280px; padding: 1.4rem; font-size: 1.05rem; font-weight: 800; border: none; background: #ffffff; color: #2563eb; cursor: pointer; border-bottom: 3.5px solid #2563eb; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="fas fa-calculator" style="font-size: 1.2rem;"></i> <?php echo __('advisor_tab_body'); ?>
            </button>
            <button id="tabBtnMuayThai" onclick="switchAdvisorTab('muaythai')" style="flex: 1; min-width: 280px; padding: 1.4rem; font-size: 1.05rem; font-weight: 800; border: none; background: transparent; color: #64748b; cursor: pointer; border-bottom: 3.5px solid transparent; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="fas fa-bolt" style="font-size: 1.2rem; color: #ef4444;"></i> <?php echo __('advisor_tab_muaythai'); ?>
            </button>
        </div>

        <!-- TAB 1: BODY ADVISOR (Gym & Yoga) -->
        <div id="advisorBodySection" style="padding: 2.5rem;">
            <form id="bodyMetricsForm" onsubmit="calculateBodyAdvisor(event)">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Gender -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_gender'); ?></label>
                        <select id="advGender" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; background: #f8fafc; outline: none;">
                            <option value="female"><?php echo __('adv_female'); ?></option>
                            <option value="male"><?php echo __('adv_male'); ?></option>
                        </select>
                    </div>

                    <!-- Height -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_height'); ?></label>
                        <input type="number" id="advHeight" placeholder="165" min="100" max="230" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; outline: none;">
                    </div>

                    <!-- Weight -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_weight'); ?></label>
                        <input type="number" id="advWeight" placeholder="55" step="0.1" min="30" max="200" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; outline: none;">
                    </div>

                    <!-- Bust / Vòng 1 -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_bust'); ?></label>
                        <input type="number" id="advBust" placeholder="86" min="50" max="150" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; outline: none;">
                    </div>

                    <!-- Waist / Vòng 2 -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_waist'); ?></label>
                        <input type="number" id="advWaist" placeholder="64" min="40" max="150" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; outline: none;">
                    </div>

                    <!-- Hip / Vòng 3 -->
                    <div>
                        <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_hip'); ?></label>
                        <input type="number" id="advHip" placeholder="92" min="50" max="160" required style="width: 100%; padding: 0.9rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 600; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px; display: block;"><?php echo __('adv_goal'); ?></label>
                    <select id="advGoal" style="width: 100%; padding: 0.95rem; border-radius: 14px; border: 1.5px solid #cbd5e1; font-weight: 700; background: #f8fafc; color: #2563eb; outline: none;">
                        <option value="waist_fatloss"><?php echo __('adv_goal_waist'); ?></option>
                        <option value="glute_growth"><?php echo __('adv_goal_glute'); ?></option>
                        <option value="flexibility_balance"><?php echo __('adv_goal_flexibility'); ?></option>
                        <option value="overall_toning"><?php echo __('adv_goal_overall'); ?></option>
                    </select>
                </div>

                <button type="submit" class="cta-btn" style="width: 100%; margin: 0; padding: 1.1rem; font-size: 1rem; border-radius: 16px; font-weight: 800; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; border: none; box-shadow: 0 10px 25px rgba(37,99,235,0.25);">
                    <i class="fas fa-magic" style="margin-right: 8px;"></i> <?php echo __('adv_btn_submit'); ?>
                </button>
            </form>

            <!-- Results Output Display -->
            <div id="advResultsContainer" style="display: none; margin-top: 3rem; padding-top: 2.5rem; border-top: 2px dashed #e2e8f0;">
                <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 2rem; color: #fff; margin-bottom: 2.5rem; box-shadow: 0 12px 30px rgba(0,0,0,0.15);">
                    <h3 style="color: #60a5fa; font-size: 1.3rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-pie"></i> <?php echo __('adv_res_title'); ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; text-align: center;">
                        <div style="background: rgba(255,255,255,0.06); padding: 1.2rem; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><?php echo __('adv_bmi'); ?></div>
                            <div id="resBMIVal" style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin: 4px 0;">--</div>
                            <div id="resBMIText" style="font-size: 0.85rem; font-weight: 600; color: #cbd5e1;">--</div>
                        </div>

                        <div style="background: rgba(255,255,255,0.06); padding: 1.2rem; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><?php echo __('adv_whr'); ?></div>
                            <div id="resWHRVal" style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin: 4px 0;">--</div>
                            <div id="resWHRText" style="font-size: 0.85rem; font-weight: 600; color: #cbd5e1;">--</div>
                        </div>

                        <div style="background: rgba(255,255,255,0.06); padding: 1.2rem; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><?php echo __('adv_body_shape'); ?></div>
                            <div id="resBodyShapeVal" style="font-size: 1.3rem; font-weight: 800; color: #38bdf8; margin: 6px 0;">--</div>
                            <div id="resBodyShapeText" style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1; line-height: 1.4;">--</div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                    <!-- Gym Recommendations -->
                    <div style="background: #ffffff; border-radius: 20px; padding: 2rem; border: 1.5px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.04);">
                        <h4 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.8rem;">
                            <i class="fas fa-dumbbell" style="color: #2563eb;"></i> <?php echo __('adv_gym_rec_title'); ?>
                        </h4>
                        <div id="gymRecContent" style="color: #334155; line-height: 1.6; font-size: 0.93rem;">
                        </div>
                    </div>

                    <!-- Yoga Recommendations -->
                    <div style="background: #ffffff; border-radius: 20px; padding: 2rem; border: 1.5px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.04);">
                        <h4 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.8rem;">
                            <i class="fas fa-yin-yang" style="color: #10b981;"></i> <?php echo __('adv_yoga_rec_title'); ?>
                        </h4>
                        <div id="yogaRecContent" style="color: #334155; line-height: 1.6; font-size: 0.93rem;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: MUAY THAI GUIDE -->
        <div id="advisorMuayThaiSection" style="display: none; padding: 2.5rem;">
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <h3 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem;">
                    <?php echo __('mt_title'); ?>
                </h3>
                <p style="color: #64748b; font-size: 0.95rem;"><?php echo __('mt_subtitle'); ?></p>
            </div>

            <!-- Muay Thai Level Selector -->
            <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 2.5rem; flex-wrap: wrap;">
                <button onclick="showMuayThaiLevel('beginner')" id="mtBtnBeginner" style="padding: 0.8rem 1.8rem; border-radius: 30px; font-weight: 800; border: none; background: #ef4444; color: #fff; cursor: pointer; box-shadow: 0 4px 14px rgba(239,68,68,0.3); transition: all 0.2s;">
                    <?php echo __('mt_beginner'); ?>
                </button>
                <button onclick="showMuayThaiLevel('intermediate')" id="mtBtnInter" style="padding: 0.8rem 1.8rem; border-radius: 30px; font-weight: 800; border: 1.5px solid #cbd5e1; background: transparent; color: #475569; cursor: pointer; transition: all 0.2s;">
                    <?php echo __('mt_inter'); ?>
                </button>
                <button onclick="showMuayThaiLevel('advanced')" id="mtBtnAdv" style="padding: 0.8rem 1.8rem; border-radius: 30px; font-weight: 800; border: 1.5px solid #cbd5e1; background: transparent; color: #475569; cursor: pointer; transition: all 0.2s;">
                    <?php echo __('mt_adv'); ?>
                </button>
            </div>

            <!-- Muay Thai Level Content Containers -->
            <div id="mtLevelBeginner" style="display: block;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.8rem;">
                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #ef4444; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-shoe-prints"></i> 1. <?php echo ($current_lang === 'en') ? 'Stance & Footwork Techniques' : 'Kỹ Thuật Di Chuyển & Bộ Thể (Stance & Footwork)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Fighting Stance:</strong> Feet shoulder-width apart, knees soft, chin tucked, guard up protecting face.</li>
                                <li>✔️ <strong>Forward & Backward Step:</strong> Step with front foot first to advance, rear foot first to retreat.</li>
                                <li>✔️ <strong>Basic Evasion:</strong> Slide feet 45° off-angle to dodge linear punches.</li>
                            <?php else: ?>
                                <li>✔️ <strong>Tư thế đứng chuẩn:</strong> Giữ khoảng cách 2 chân rộng bằng vai, gối chùng nhẹ, cằm thu sâu guard cao che mặt.</li>
                                <li>✔️ <strong>Di chuyển bước tiến/lùi:</strong> Bước chân trước trước khi tiến, bước chân sau trước khi lùi.</li>
                                <li>✔️ <strong>Né gạt cơ bản:</strong> Trượt chân 45 độ tránh đòn đấm trực diện.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #ef4444; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-fist-raised"></i> 2. <?php echo ($current_lang === 'en') ? 'Basic Punch & Kick Strikes' : 'Đòn Đấm & Đá Cơ Bản (Strikes)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Jab - Cross (1-2 Combo):</strong> Rotate hips to drive kinetic power from legs to shoulders. (4 rounds x 3 mins)</li>
                                <li>✔️ <strong>Teep Kick (Front Push Kick):</strong> Distance management to stop opponent's forward charge.</li>
                                <li>✔️ <strong>Low Kick (Thigh Slice):</strong> Pivot supporting heel, drive shin bone into opponent's outer thigh.</li>
                            <?php else: ?>
                                <li>✔️ <strong>Jab - Cross (Đấm thẳng 1-2):</strong> Đấm xoay hông dồn lực từ chân lên vai. (4 hiệp x 3 phút)</li>
                                <li>✔️ <strong>Teep Kick (Đá tống trước):</strong> Giữ khoảng cách và cản bước tiến đối thủ.</li>
                                <li>✔️ <strong>Low Kick (Đá xẻ đùi):</strong> Xoay cổ chân trụ, dùng xương cẳng chân quét mạnh đùi ngoài đối thủ.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #ef4444; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-running"></i> 3. <?php echo ($current_lang === 'en') ? 'Conditioning & Stamina' : 'Thể Lực & Sức Nhẫn Nại (Conditioning)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Jump Rope:</strong> 3 rounds x 3 mins (increases cardiovascular threshold & ankle stability).</li>
                                <li>✔️ <strong>Burpees & Push-ups:</strong> 3 sets x 15 reps (builds shoulder press & core explosiveness).</li>
                                <li>✔️ <strong>Plank & Sit-ups:</strong> 3 sets x 60s (strengthens abdominal wall for blow conditioning).</li>
                            <?php else: ?>
                                <li>✔️ <strong>Nhảy dây (Jump Rope):</strong> 3 hiệp x 3 phút (tăng nhịp tim và khớp cổ chân).</li>
                                <li>✔️ <strong>Burpees & Push-ups:</strong> 3 hiệp x 15 reps (xây dựng thể lực vai và ngực).</li>
                                <li>✔️ <strong>Plank & Sit-ups:</strong> 3 hiệp x 60s (siết cơ bụng chịu đòn).</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="mtLevelInter" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.8rem;">
                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #d97706; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-hand-rock"></i> 1. <?php echo ($current_lang === 'en') ? 'Signature Elbows & Knees' : 'Chỏ & Gối Đặc Trưng (Elbows & Knees)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Horizontal & Diagonal Elbows:</strong> Slice inside opponent guard at close pocket range.</li>
                                <li>✔️ <strong>Flying Knee / Spear Knee:</strong> Drive straight knee bone into opponent solar plexus.</li>
                                <li>✔️ <strong>Clinch Entry:</strong> Lock crown of opponent head (Plum Grip) to break balance.</li>
                            <?php else: ?>
                                <li>✔️ <strong>Horizontal & Diagonal Elbow:</strong> Đánh chỏ ngang và chỏ xéo ở cự cự áp sát.</li>
                                <li>✔️ <strong>Flying Knee / Straight Knee:</strong> Đánh gối bay hoặc gối thẳng cắm thẳng bụng đối thủ.</li>
                                <li>✔️ <strong>Clinch Entry:</strong> Khóa gáy đối thủ (Plum Grip) kiểm soát trọng tâm.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #d97706; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-bullseye"></i> 2. <?php echo ($current_lang === 'en') ? 'Heavy Bag Drills' : 'Tập Bao Cát Nặng (Heavy Bag Drills)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Strike Combinations:</strong> Jab-Cross-Low Kick x 100 reps per session.</li>
                                <li>✔️ <strong>Body Kicks Power:</strong> 50 consecutive roundhouse body kicks per leg on heavy bag.</li>
                                <li>✔️ <strong>Close-Range Bag Clinch Work:</strong> 3 rounds x 3 mins maintaining continuous power output.</li>
                            <?php else: ?>
                                <li>✔️ <strong>Combo Đấm - Đá liên hoàn:</strong> Jab-Cross-Low Kick x 100 lần/buổi.</li>
                                <li>✔️ <strong>Body Kicks Power:</strong> Đá tầm trung 50 cú liên tục mỗi chân lên bao cát heavy.</li>
                                <li>✔️ <strong>Chỏ gối cận chiến bao cát:</strong> 3 hiệp x 3 phút duy trì lực tối đa.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #d97706; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-shield-alt"></i> 3. <?php echo ($current_lang === 'en') ? 'Defense & Counter Striking' : 'Thủ Phản Công & Đột Phá (Defense & Counters)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Check Kick:</strong> Raise shin shield at 45° angle to absorb incoming leg kicks.</li>
                                <li>✔️ <strong>Catch & Sweep:</strong> Catch mid-section kick, pivot and sweep supporting post leg.</li>
                                <li>✔️ <strong>Technical Sparring:</strong> Controlled reflex sparring with partner (3 rounds x 3 mins).</li>
                            <?php else: ?>
                                <li>✔️ <strong>Check Kick (Giơ cẳng chân đỡ đá):</strong> Đỡ cú đá đùi của đối thủ bằng xương ống chân.</li>
                                <li>✔️ <strong>Catch & Sweep (Bắt chân quét ngã):</strong> Bắt cú đá giữa lưng đối thủ và đạp chân trụ đối phương.</li>
                                <li>✔️ <strong>Sparring kỹ thuật nhẹ (Light Sparring):</strong> Tập phản xạ cùng bạn tập 3 hiệp x 3 phút.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="mtLevelAdv" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.8rem;">
                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #2563eb; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-fire-alt"></i> 1. <?php echo ($current_lang === 'en') ? 'Pro 1-on-1 Pad Work' : 'Pad Work 1:1 Cùng HLV Chuyên Nghiệp'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>5 High-Intensity Pad Rounds:</strong> Trainer feeds dynamic targets with relentless pressure.</li>
                                <li>✔️ <strong>6-8 Strike Flow Combos:</strong> Seamless transition between punches, knees, elbows, and kicks.</li>
                                <li>✔️ <strong>Footwork & Counter Reaction:</strong> Absorbing and returning counter strikes on the move.</li>
                            <?php else: ?>
                                <li>✔️ <strong>5 Hiệp Pad Work cường độ cao:</strong> HLV đưa đích phản xạ liên tục không ngắt quãng.</li>
                                <li>✔️ <strong>Combo 6-8 đòn đấm chỏ gối đá:</strong> Kết hợp biến hóa tốc độ và uy lực.</li>
                                <li>✔️ <strong>Phản xạ nhặt đòn trong khi di chuyển:</strong> Chịu áp lực đòn dồn dập.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #2563eb; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-trophy"></i> 2. <?php echo ($current_lang === 'en') ? 'Clinch Dominance & Takedowns' : 'Clinch Dominance & Takedowns'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>Side Clinch Break & Knees:</strong> Off-balance opponent to unleash knees to floating ribs.</li>
                                <li>✔️ <strong>Spinning Back Elbow:</strong> Surprising rotational counter elbow strike for KO victory.</li>
                                <li>✔️ <strong>Hard Sparring:</strong> Full-contact protective equipment sparring (5 rounds x 3 mins).</li>
                            <?php else: ?>
                                <li>✔️ <strong>Khóa gáy & Vật bẻ cổ (Side Clinch Break):</strong> Tạo khoảng trống tung gối vào mạn sườn.</li>
                                <li>✔️ <strong>Spinning Back Elbow (Chỏ quay lưng):</strong> Đòn xoay chỏ bất ngờ đoạt knock-out.</li>
                                <li>✔️ <strong>Hard Sparring (Đối kháng thực tế có bảo hộ):</strong> 5 hiệp x 3 phút chuẩn luật Muay Thái chuyên nghiệp.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div style="background: #fff; padding: 1.8rem; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 8px 25px rgba(0,0,0,0.04);">
                        <h4 style="color: #2563eb; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;"><i class="fas fa-weight-hanging"></i> 3. <?php echo ($current_lang === 'en') ? 'Iron Body Fighter Conditioning' : 'Thể Lực Võ Sĩ Thép (Pro Fighter Conditioning)'; ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.92rem;">
                            <?php if ($current_lang === 'en'): ?>
                                <li>✔️ <strong>100 Unbroken Kicks / Leg:</strong> Forges iron shins and extreme aerobic endurance.</li>
                                <li>✔️ <strong>Sledgehammer Slams & Tire Hitting:</strong> 15kg hammer slams to build lats & explosive shoulders.</li>
                                <li>✔️ <strong>5km Interval Roadwork:</strong> 30s sprint / 30s jog heart-rate conditioning.</li>
                            <?php else: ?>
                                <li>✔️ <strong>100 Kicks/leg không nghỉ:</strong> Xây dựng cẳng chân sắt và sức bền phổi cực hạn.</li>
                                <li>✔️ <strong>Tập búa tạ Slams & Lốp xe:</strong> Đập búa 15kg xây cơ xô và sức bùng nổ vai.</li>
                                <li>✔️ <strong>Chạy cardio interval 5km:</strong> 30s nước rút / 30s chạy chậm duy trì nhịp tim võ sĩ.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonial-section">
    <div class="section-title" style="text-align: left; margin-bottom: 0;">
        <div class="eu-eyebrow" style="color: rgba(201,168,76,0.8);"><?php echo __('eyebrow_reviews'); ?></div>
        <h2 style="color: white; font-family: 'Cormorant Garamond', serif; font-size: clamp(2.5rem,5vw,3.8rem); font-weight: 600;">
            <?php echo __('reviews_title'); ?> <em style="font-style: italic; color: var(--gold-light);">GymX</em>
        </h2>
    </div>

    <div class="testimonial-grid">
        <div class="t-card reveal">
            <div class="t-stars">★★★★★</div>
            <p class="t-text"><?php echo __('test_author_1_text'); ?></p>
            <div class="t-author">
                <div class="t-avatar">M</div>
                <div>
                    <div class="t-name"><?php echo __('test_author_1_name'); ?></div>
                    <div class="t-role"><?php echo __('test_author_1_role'); ?></div>
                </div>
            </div>
        </div>

        <div class="t-card reveal" style="transition-delay: 0.12s;">
            <div class="t-stars">★★★★★</div>
            <p class="t-text"><?php echo __('test_author_2_text'); ?></p>
            <div class="t-author">
                <div class="t-avatar">L</div>
                <div>
                    <div class="t-name"><?php echo __('test_author_2_name'); ?></div>
                    <div class="t-role"><?php echo __('test_author_2_role'); ?></div>
                </div>
            </div>
        </div>

        <div class="t-card reveal" style="transition-delay: 0.24s;">
            <div class="t-stars">★★★★★</div>
            <p class="t-text"><?php echo __('test_author_3_text'); ?></p>
            <div class="t-author">
                <div class="t-avatar">H</div>
                <div>
                    <div class="t-name"><?php echo __('test_author_3_name'); ?></div>
                    <div class="t-role"><?php echo __('test_author_3_role'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


<?php if(!isset($_SESSION['user_id'])): ?>
<!-- Home Promo Modal -->
<div id="homePromoModal" class="promo-modal">
    <div class="promo-modal-content">
        <span class="promo-close" id="closeHomePromo">&times;</span>
        <div class="promo-image-container">
            <div class="promo-text-overlay">
                <h2><?php echo __('promo_get_your'); ?></h2>
                <span class="huge-text"><?php echo __('promo_days'); ?></span>
                <span class="free-text"><?php echo __('promo_free'); ?></span>
            </div>
        </div>
        <div class="promo-form-container">
            <input type="text" placeholder="<?php echo __('promo_name'); ?>" class="promo-input home-promo-input">
            <input type="text" placeholder="<?php echo __('promo_phone'); ?>" class="promo-input home-promo-input">
            <button class="promo-submit-btn" id="submitHomePromo"><?php echo __('promo_register_btn'); ?></button>
            <p class="promo-note"><?php echo __('promo_note'); ?></p>
        </div>
    </div>
</div>

<style>
/* Promo Modal CSS */
.promo-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}

.promo-modal-content {
    background-color: #fff;
    margin: auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 450px;
    position: relative;
    box-shadow: 0 4px 25px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: modalOpen 0.5s ease-out forwards;
}

@keyframes modalOpen {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.promo-close {
    position: absolute;
    top: 10px;
    right: 15px;
    color: #333;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    z-index: 5;
    background: rgba(255,255,255,0.8);
    width: 28px;
    height: 28px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 5px;
    border: 1px solid #ccc;
    transition: 0.2s;
}

.promo-close:hover {
    background: white;
    color: red;
}

.promo-image-container {
    position: relative;
    width: 100%;
    height: 280px;
    background-image: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=800&auto=format&fit=crop');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
}

.promo-image-container::after {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(0,180,255,0.85) 0%, rgba(0,84,209,0.7) 100%);
}

.promo-text-overlay {
    position: relative;
    z-index: 1;
    color: white;
    text-align: center;
    padding: 20px;
    width: 100%;
}

.promo-text-overlay h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    letter-spacing: 1px;
}

.promo-text-overlay .huge-text {
    font-size: 36px;
    font-weight: 900;
    display: block;
    margin-top: 5px;
    text-transform: uppercase;
    text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
    line-height: 1.1;
}

.promo-text-overlay .free-text {
    font-size: 55px;
    font-weight: 900;
    color: #fff;
    display: block;
    text-transform: uppercase;
    text-shadow: 3px 3px 0px #0054d1, 4px 4px 10px rgba(0,0,0,0.5);
    margin-top: 0px;
    letter-spacing: 2px;
}

.promo-form-container {
    padding: 25px 30px;
    background: white;
}

.promo-input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 1px solid #00a8ff;
    border-radius: 6px;
    font-size: 15px;
    box-sizing: border-box;
    outline: none;
    color: #333;
}

.promo-input::placeholder {
    color: #999;
}

.promo-input:focus {
    box-shadow: 0 0 0 3px rgba(0,168,255,0.2);
}

.promo-submit-btn {
    width: 100%;
    background: #00b4ff;
    color: white;
    border: none;
    padding: 14px;
    border-radius: 6px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 4px 10px rgba(0, 180, 255, 0.3);
}

.promo-submit-btn:hover {
    background: #009ce0;
    transform: translateY(-1px);
}

.promo-note {
    text-align: center;
    font-size: 12px;
    color: #65676b;
    margin-top: 15px;
    margin-bottom: 0;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById("homePromoModal");
    var closeBtn = document.getElementById("closeHomePromo");

    if (modal) {
        // Only show if not shown in this session
        if (!sessionStorage.getItem('homePromoShown')) {
            setTimeout(function() {
                modal.style.display = "flex";
            }, 800);
        }

        closeBtn.onclick = function() {
            modal.style.display = "none";
            sessionStorage.setItem('homePromoShown', 'true');
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                sessionStorage.setItem('homePromoShown', 'true');
            }
        }
        
        document.getElementById('submitHomePromo').addEventListener('click', function(e) {
            e.preventDefault();
            var inputs = document.querySelectorAll('.home-promo-input');
            if(inputs[0].value.trim() === '' || inputs[1].value.trim() === '') {
                alert('<?php echo addslashes(__('promo_fill_info')); ?>');
                return;
            }
            alert('<?php echo addslashes(__('promo_thank_you')); ?> ' + inputs[0].value + ' <?php echo addslashes(__('promo_contact_soon')); ?>');
            modal.style.display = "none";
            sessionStorage.setItem('homePromoShown', 'true');
            inputs[0].value = '';
            inputs[1].value = '';
        });
    }
});
</script>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {



        // === Reveal Animations on Scroll ===
        const reveals = document.querySelectorAll('.reveal, .t-card, .feature-strip-item, .stat-block');
        const revealObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
        reveals.forEach(el => revealObserver.observe(el));


        // === Counter Animation ===
        const counters = document.querySelectorAll('.stat-block .num[data-target]');
        const counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el     = entry.target;
                const target = parseInt(el.getAttribute('data-target'));
                const duration = 1800;
                const step   = target / (duration / 16);
                let current  = 0;

                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        el.textContent = target;
                        clearInterval(timer);
                    } else {
                        el.textContent = Math.floor(current);
                    }
                }, 16);

                counterObserver.unobserve(el);
            });
        }, { threshold: 0.5 });

        counters.forEach(el => counterObserver.observe(el));

    });
</script>

<?php
// Fetch coaches list to populate the select options in the registration modal
$coaches_list = [];
try {
    $stmt_coaches = $pdo->query("SELECT c.id, u.name as coach_name, c.specialty FROM coaches c JOIN users u ON c.user_id = u.id ORDER BY u.name ASC");
    $coaches_list = $stmt_coaches->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching coaches: " . $e->getMessage());
}
?>

<!-- ============================================== -->
<!-- SERVICE DETAILS MODAL (PREMIUM GLASSMORPHISM) -->
<!-- ============================================== -->
<div id="serviceDetailsModal" class="premium-modal-overlay" style="display: none;">
    <div class="premium-modal-card">
        <button type="button" class="premium-modal-close" onclick="closeServiceDetails()">&times;</button>
        <div class="premium-modal-image-header">
            <img id="detailModalImg" src="" alt="Service Image">
            <div class="premium-modal-tag" id="detailModalTag">Gym</div>
        </div>
        <div class="premium-modal-body">
            <h2 class="premium-modal-title" id="detailModalTitle">Package Name</h2>
            <div class="premium-modal-price-box">
                <span class="price-old" id="detailModalPriceOld">0đ</span>
                <span class="price-new" id="detailModalPriceNew">0đ</span>
                <span class="price-period" id="detailModalPricePeriod">/ tháng</span>
            </div>
            <div class="premium-modal-features-title"><i class="fas fa-star" style="color: var(--primary);"></i> <?php echo __('fs_title'); ?></div>
            <ul class="premium-modal-features-list" id="detailModalFeatures">
                <!-- Dynamically populated -->
            </ul>
            <div class="premium-modal-actions">
                <button type="button" class="premium-action-btn-secondary" onclick="closeServiceDetails()"><?php echo __('fs_modal_close'); ?></button>
                <button type="button" class="premium-action-btn-primary" id="detailModalRegisterBtn"><i class="fas fa-check-circle" style="margin-right: 5px;"></i> <?php echo __('fs_register_now'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- SERVICE REGISTRATION MODAL (PREMIUM STYLED) -->
<!-- ============================================== -->
<div id="serviceRegisterModal" class="premium-modal-overlay" style="display: none;">
    <div class="premium-modal-card">
        <button type="button" class="premium-modal-close" onclick="closeServiceRegistration()">&times;</button>
        
        <!-- Registration Form Content -->
        <div id="registerFormContent">
            <div style="padding: 2.2rem 2.2rem 0.5rem;">
                <h2 style="font-family: 'Playfair Display', serif; font-weight: 800; font-size: 1.8rem; color: var(--text-main); margin: 0 0 5px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-edit" style="color: var(--primary);"></i> <?php echo __('fs_register_now'); ?>
                </h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0; font-weight: 500;"><?php echo __('fs_subtitle'); ?></p>
            </div>
            
            <form id="serviceRegisterForm" style="padding: 1.2rem 2.2rem 2.2rem; display: flex; flex-direction: column; gap: 1.2rem;">
                <input type="hidden" name="package_id" id="registerPackageId">
                
                <!-- Package Label Display -->
                <div class="form-group-custom" style="background: rgba(37,99,235,0.05); border: 1px solid rgba(37,99,235,0.15); padding: 0.8rem 1.2rem; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;"><?php echo __('fs_modal_package'); ?>:</span>
                    <strong style="color: var(--primary); font-size: 1.05rem;" id="registerPackageName">Gym</strong>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Full Name -->
                    <div class="form-group-custom">
                        <label><?php echo __('fs_modal_name'); ?> *</label>
                        <div class="input-wrapper-custom">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" required placeholder="<?php echo __('promo_name'); ?>" 
                                   value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="form-group-custom">
                        <label><?php echo __('fs_modal_phone'); ?> *</label>
                        <div class="input-wrapper-custom">
                            <i class="fas fa-phone-alt"></i>
                            <input type="text" name="phone" required placeholder="090x xxx xxx" pattern="[0-9]{10}"
                                   value="<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group-custom">
                    <label><?php echo __('fs_modal_email'); ?> *</label>
                    <div class="input-wrapper-custom">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="example@email.com"
                               value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Preferred Coach -->
                    <div class="form-group-custom">
                        <label><?php echo __('fs_modal_coach'); ?></label>
                        <div class="input-wrapper-custom select-wrapper-custom">
                            <i class="fas fa-user-shield"></i>
                            <select name="coach_id" id="registerCoachSelect" style="padding-right: 30px;">
                                <option value=""><?php echo __('fs_modal_coach_any'); ?></option>
                                <?php foreach($coaches_list as $coach): ?>
                                    <option value="<?php echo $coach['id']; ?>" data-specialty="<?php echo htmlspecialchars($coach['specialty']); ?>">
                                        Coach <?php echo htmlspecialchars($coach['coach_name']); ?> (<?php echo htmlspecialchars($coach['specialty']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div class="form-group-custom">
                        <label><?php echo __('fs_modal_start_date'); ?></label>
                        <div class="input-wrapper-custom">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\T08:00', strtotime('+1 day')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group-custom">
                    <label><?php echo __('fs_modal_note'); ?></label>
                    <div class="input-wrapper-custom">
                        <i class="fas fa-comment-dots" style="top: 15px; transform: none;"></i>
                        <textarea name="note" placeholder="<?php echo __('comment_placeholder'); ?>" style="height: 70px; resize: none; padding-top: 10px; padding-left: 35px;"></textarea>
                    </div>
                </div>

                <!-- Action buttons -->
                <div style="display: flex; gap: 0.8rem; margin-top: 0.5rem;">
                    <button type="button" class="premium-action-btn-secondary" style="flex: 1;" onclick="closeServiceRegistration()"><?php echo __('fs_modal_close'); ?></button>
                    <button type="submit" class="premium-action-btn-primary" style="flex: 2;"><i class="fas fa-paper-plane" style="margin-right: 5px;"></i> <?php echo __('fs_modal_submit'); ?></button>
                </div>
            </form>
        </div>

        <!-- Success Screen Content -->
        <div id="registerSuccessContent" style="display: none; padding: 3.5rem 2.2rem; text-align: center; flex-direction: column; align-items: center; gap: 1.5rem; width: 100%;">
            <div class="success-icon-wrapper">
                <i class="fas fa-check"></i>
            </div>
            <h2 style="font-family: 'Playfair Display', serif; font-weight: 800; font-size: 2rem; color: var(--text-main); margin: 0;"><?php echo __('fs_modal_success_title'); ?></h2>
            <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; max-width: 420px; margin: 0;" id="successDescText"><?php echo __('fs_modal_success_desc'); ?></p>
            <button type="button" class="premium-action-btn-primary" style="padding: 0.9rem 2.5rem; width: auto;" onclick="closeServiceRegistration()"><?php echo __('fs_modal_close'); ?></button>
        </div>

        <!-- Loading overlay inside card -->
        <div id="registerLoadingOverlay" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.85); border-radius: 20px; align-items: center; justify-content: center; flex-direction: column; gap: 15px; z-index: 10;">
            <div class="modal-spinner"></div>
            <span style="font-weight: 700; color: var(--text-main); font-size: 1rem;"><?php echo __('fs_modal_loading'); ?></span>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL STYLES -->
<!-- ============================================== -->
<style>
.premium-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    animation: modalOverlayFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.premium-modal-card {
    background: #ffffff;
    border-radius: 24px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
    animation: modalCardScaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    font-family: 'Outfit', sans-serif;
}

@keyframes modalOverlayFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalCardScaleIn {
    from { transform: scale(0.9) translateY(20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}

.premium-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    border: none;
    background: rgba(15, 23, 42, 0.05);
    color: #64748b;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 1.4rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 100;
}

.premium-modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    transform: rotate(90deg);
}

.premium-modal-image-header {
    height: 220px;
    position: relative;
    overflow: hidden;
}

.premium-modal-image-header img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.premium-modal-tag {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(30, 41, 59, 0.85);
    color: #fff;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 800;
    backdrop-filter: blur(5px);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.premium-modal-body {
    padding: 2.2rem;
}

.premium-modal-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0 0 1rem;
}

.premium-modal-price-box {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 1.5rem;
    padding-bottom: 1.2rem;
    border-bottom: 1px solid #f1f5f9;
}

.premium-modal-price-box .price-old {
    text-decoration: line-through;
    color: #94a3b8;
    font-size: 1.05rem;
    font-weight: 600;
}

.premium-modal-price-box .price-new {
    color: #2563eb;
    font-size: 1.8rem;
    font-weight: 800;
}

.premium-modal-price-box .price-period {
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
}

.premium-modal-features-title {
    font-weight: 800;
    color: var(--text-main);
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.premium-modal-features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 2.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.premium-modal-features-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #475569;
    font-size: 0.95rem;
    font-weight: 500;
}

.premium-modal-features-list li i {
    color: #2563eb;
    font-size: 1rem;
}

.premium-modal-actions {
    display: flex;
    gap: 1rem;
}

.premium-action-btn-primary {
    flex: 1;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 0.85rem 1.5rem;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.premium-action-btn-primary:hover {
    background: #1d4ed8;
    box-shadow: 0 6px 16px rgba(29, 78, 216, 0.3);
    transform: translateY(-2px);
}

.premium-action-btn-secondary {
    flex: 1;
    background: transparent;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 0.85rem 1.5rem;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
}

.premium-action-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #1e293b;
}

/* Custom form elements */
.form-group-custom {
    display: flex;
    flex-direction: column;
    gap: 6px;
    text-align: left;
}

.form-group-custom label {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-main);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.input-wrapper-custom {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper-custom i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.95rem;
    transition: color 0.2s;
}

.input-wrapper-custom input,
.input-wrapper-custom select,
.input-wrapper-custom textarea {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: var(--text-main);
    font-size: 0.95rem;
    font-weight: 600;
    outline: none;
    transition: all 0.2s;
}

.input-wrapper-custom input:focus,
.input-wrapper-custom select:focus,
.input-wrapper-custom textarea:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
}

.input-wrapper-custom input:focus + i,
.input-wrapper-custom select:focus + i {
    color: #2563eb;
}

.select-wrapper-custom::after {
    content: '\f078';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
    font-size: 0.8rem;
    display: block;
}

.input-wrapper-custom select {
    appearance: none;
    padding-right: 35px;
}

/* Success Screen Stylings */
.success-icon-wrapper {
    width: 80px;
    height: 80px;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.2);
    border: 2px solid rgba(16, 185, 129, 0.15);
    animation: successIconBounce 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

@keyframes successIconBounce {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.1); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

/* Loading Spinner */
.modal-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(37, 99, 235, 0.1);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: modalSpin 0.8s linear infinite;
}

@keyframes modalSpin {
    to { transform: rotate(360deg); }
}
</style>

<!-- ============================================== -->
<!-- MODAL INTERACTIVITY JAVASCRIPT -->
<!-- ============================================== -->
<script>
const packagesData = {
    gym: {
        title: "<?php echo addslashes(__('fs_gym_title')); ?>",
        price: "899.000đ",
        originalPrice: "1.200.000đ",
        period: "<?php echo addslashes(__('/ tháng')); ?>",
        image: "https://images.unsplash.com/photo-1540206351-d6465b3ac5c1?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_gym_feature1')); ?>",
            "<?php echo addslashes(__('fs_gym_feature2')); ?>",
            "<?php echo addslashes(__('fs_gym_feature3')); ?>"
        ],
        icon: "fa-dumbbell",
        specialty: "Gym"
    },
    pt: {
        title: "<?php echo addslashes(__('fs_pt_title')); ?>",
        price: "3.490.000đ",
        originalPrice: "4.500.000đ",
        period: "<?php echo addslashes(__('/ khóa')); ?>",
        image: "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_pt_feature1')); ?>",
            "<?php echo addslashes(__('fs_pt_feature2')); ?>",
            "<?php echo addslashes(__('fs_pt_feature3')); ?>"
        ],
        icon: "fa-user-shield",
        specialty: "Gym"
    },
    fatloss: {
        title: "<?php echo addslashes(__('fs_fatloss_title')); ?>",
        price: "2.290.000đ",
        originalPrice: "3.000.000đ",
        period: "<?php echo addslashes(__('/ khóa')); ?>",
        image: "https://images.unsplash.com/photo-1518310383802-640c2de311b2?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_fatloss_feature1')); ?>",
            "<?php echo addslashes(__('fs_fatloss_feature2')); ?>",
            "<?php echo addslashes(__('fs_fatloss_feature3')); ?>"
        ],
        icon: "fa-fire",
        specialty: "Gym"
    },
    yoga: {
        title: "<?php echo addslashes(__('fs_yoga_title')); ?>",
        price: "699.000đ",
        originalPrice: "900.000đ",
        period: "<?php echo addslashes(__('/ tháng')); ?>",
        image: "https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_yoga_feature1')); ?>",
            "<?php echo addslashes(__('fs_yoga_feature2')); ?>",
            "<?php echo addslashes(__('fs_yoga_feature3')); ?>"
        ],
        icon: "fa-yin-yang",
        specialty: "Yoga"
    },
    bodypump: {
        title: "<?php echo addslashes(__('fs_bodypump_title')); ?>",
        price: "649.000đ",
        originalPrice: "850.000đ",
        period: "<?php echo addslashes(__('/ tháng')); ?>",
        image: "https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_bodypump_feature1')); ?>",
            "<?php echo addslashes(__('fs_bodypump_feature2')); ?>",
            "<?php echo addslashes(__('fs_bodypump_feature3')); ?>"
        ],
        icon: "fa-heartbeat",
        specialty: "Gym"
    },
    kickboxing: {
        title: "<?php echo addslashes(__('fs_kickboxing_title')); ?>",
        price: "749.000đ",
        originalPrice: "950.000đ",
        period: "<?php echo addslashes(__('/ tháng')); ?>",
        image: "https://images.unsplash.com/photo-1599058917212-d750089bc07e?q=80&w=600&auto=format&fit=crop",
        features: [
            "<?php echo addslashes(__('fs_kickboxing_feature1')); ?>",
            "<?php echo addslashes(__('fs_kickboxing_feature2')); ?>",
            "<?php echo addslashes(__('fs_kickboxing_feature3')); ?>"
        ],
        icon: "fa-bolt",
        specialty: "Muay"
    }
};

let currentDetailPackageId = '';

function openServiceDetails(packageId) {
    const pkg = packagesData[packageId];
    if (!pkg) return;
    
    currentDetailPackageId = packageId;
    
    document.getElementById('detailModalImg').src = pkg.image;
    document.getElementById('detailModalTag').innerHTML = `<i class="fas ${pkg.icon}" style="margin-right: 5px;"></i> ${pkg.specialty}`;
    document.getElementById('detailModalTitle').innerText = pkg.title;
    document.getElementById('detailModalPriceOld').innerText = pkg.originalPrice;
    document.getElementById('detailModalPriceNew').innerText = pkg.price;
    document.getElementById('detailModalPricePeriod').innerText = pkg.period;
    
    const featuresUl = document.getElementById('detailModalFeatures');
    featuresUl.innerHTML = '';
    pkg.features.forEach(f => {
        const li = document.createElement('li');
        li.innerHTML = `<i class="fas fa-check-circle"></i> <span>${f}</span>`;
        featuresUl.appendChild(li);
    });
    
    // Wire register button inside details modal
    document.getElementById('detailModalRegisterBtn').onclick = function() {
        closeServiceDetails();
        openServiceRegistration(packageId);
    };
    
    document.getElementById('serviceDetailsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Lock background scroll
}

function closeServiceDetails() {
    document.getElementById('serviceDetailsModal').style.display = 'none';
    if (document.getElementById('serviceRegisterModal').style.display !== 'flex') {
        document.body.style.overflow = '';
    }
}

function openServiceRegistration(packageId) {
    const pkg = packagesData[packageId];
    if (!pkg) return;
    
    document.getElementById('registerPackageId').value = packageId;
    document.getElementById('registerPackageName').innerText = pkg.title;
    
    // Filter the coaches dropdown to match the specialty of the package!
    const coachSelect = document.getElementById('registerCoachSelect');
    const options = coachSelect.querySelectorAll('option');
    let firstMatchIndex = 0;
    
    options.forEach((opt, idx) => {
        if (idx === 0) return; // Skip "Any Available Coach"
        const spec = opt.getAttribute('data-specialty') || '';
        // If package specialty is Gym, match coaches with Specialty containing Gym or PT or Body Pump
        // If package specialty is Yoga, match Yoga
        // If package specialty is Muay, match Muay or Kickboxing
        let isMatch = false;
        if (pkg.specialty === 'Gym' && (spec.toLowerCase().includes('gym') || spec.toLowerCase().includes('fitness') || spec.toLowerCase().includes('trainer') || spec.toLowerCase().includes('pump'))) {
            isMatch = true;
        } else if (pkg.specialty === 'Yoga' && spec.toLowerCase().includes('yoga')) {
            isMatch = true;
        } else if (pkg.specialty === 'Muay' && (spec.toLowerCase().includes('muay') || spec.toLowerCase().includes('kick') || spec.toLowerCase().includes('box') || spec.toLowerCase().includes('combat'))) {
            isMatch = true;
        }
        
        if (isMatch) {
            opt.style.display = '';
            if (firstMatchIndex === 0) firstMatchIndex = idx;
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Select the "Any Available Coach" by default
    coachSelect.value = '';
    
    // Reset view states
    document.getElementById('registerFormContent').style.display = 'block';
    document.getElementById('registerSuccessContent').style.display = 'none';
    document.getElementById('registerLoadingOverlay').style.display = 'none';
    
    document.getElementById('serviceRegisterModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeServiceRegistration() {
    document.getElementById('serviceRegisterModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Handle AJAX submission
document.getElementById('serviceRegisterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    
    const loadingOverlay = document.getElementById('registerLoadingOverlay');
    loadingOverlay.style.display = 'flex';
    
    fetch('register_service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingOverlay.style.display = 'none';
        if (data.success) {
            // Show Success Screen
            document.getElementById('registerFormContent').style.display = 'none';
            document.getElementById('registerSuccessContent').style.display = 'flex';
            document.getElementById('successDescText').innerText = data.message;
            form.reset();
        } else {
            alert(data.message || '<?php echo addslashes(__('fs_modal_error')); ?>');
        }
    })
    .catch(error => {
        loadingOverlay.style.display = 'none';
        console.error('Error submitting form:', error);
        alert('<?php echo addslashes(__('fs_modal_error')); ?>');
    });
});

// Smart Workout Advisor JS Logic
function switchAdvisorTab(tab) {
    const btnBody = document.getElementById('tabBtnBodyAdvisor');
    const btnMT = document.getElementById('tabBtnMuayThai');
    const secBody = document.getElementById('advisorBodySection');
    const secMT = document.getElementById('advisorMuayThaiSection');

    if (tab === 'body') {
        btnBody.style.background = '#ffffff';
        btnBody.style.color = '#2563eb';
        btnBody.style.borderBottom = '3.5px solid #2563eb';

        btnMT.style.background = 'transparent';
        btnMT.style.color = '#64748b';
        btnMT.style.borderBottom = '3.5px solid transparent';

        secBody.style.display = 'block';
        secMT.style.display = 'none';
    } else {
        btnMT.style.background = '#ffffff';
        btnMT.style.color = '#ef4444';
        btnMT.style.borderBottom = '3.5px solid #ef4444';

        btnBody.style.background = 'transparent';
        btnBody.style.color = '#64748b';
        btnBody.style.borderBottom = '3.5px solid transparent';

        secMT.style.display = 'block';
        secBody.style.display = 'none';
    }
}

function showMuayThaiLevel(level) {
    const b1 = document.getElementById('mtBtnBeginner');
    const b2 = document.getElementById('mtBtnInter');
    const b3 = document.getElementById('mtBtnAdv');

    const l1 = document.getElementById('mtLevelBeginner');
    const l2 = document.getElementById('mtLevelInter');
    const l3 = document.getElementById('mtLevelAdv');

    [b1, b2, b3].forEach(b => {
        if(b) {
            b.style.background = 'transparent';
            b.style.color = '#475569';
            b.style.border = '1.5px solid #cbd5e1';
            b.style.boxShadow = 'none';
        }
    });

    [l1, l2, l3].forEach(l => { if(l) l.style.display = 'none'; });

    if (level === 'beginner') {
        b1.style.background = '#ef4444';
        b1.style.color = '#fff';
        b1.style.border = 'none';
        b1.style.boxShadow = '0 4px 14px rgba(239,68,68,0.3)';
        l1.style.display = 'block';
    } else if (level === 'intermediate') {
        b2.style.background = '#d97706';
        b2.style.color = '#fff';
        b2.style.border = 'none';
        b2.style.boxShadow = '0 4px 14px rgba(217,119,6,0.3)';
        l2.style.display = 'block';
    } else if (level === 'advanced') {
        b3.style.background = '#2563eb';
        b3.style.color = '#fff';
        b3.style.border = 'none';
        b3.style.boxShadow = '0 4px 14px rgba(37,99,235,0.3)';
        l3.style.display = 'block';
    }
}

function calculateBodyAdvisor(e) {
    e.preventDefault();

    const isEN = "<?php echo $current_lang; ?>" === "en";
    const gender = document.getElementById('advGender').value;
    const height = parseFloat(document.getElementById('advHeight').value);
    const weight = parseFloat(document.getElementById('advWeight').value);
    const bust   = parseFloat(document.getElementById('advBust').value);
    const waist  = parseFloat(document.getElementById('advWaist').value);
    const hip    = parseFloat(document.getElementById('advHip').value);
    const goal   = document.getElementById('advGoal').value;

    if (!height || !weight || !waist || !hip) return;

    // BMI
    const hMeter = height / 100;
    const bmi = (weight / (hMeter * hMeter)).toFixed(1);

    let bmiText = '';
    if (bmi < 18.5) bmiText = isEN ? 'Underweight / Muscle Gain Focus' : 'Thiếu cân / Cần tăng cơ';
    else if (bmi < 23) bmiText = isEN ? 'Balanced Asian Standard' : 'Cân đối chuẩn chuẩn ASIAN';
    else if (bmi < 25) bmiText = isEN ? 'Slightly Overweight / Toning Required' : 'Thừa cân nhẹ / Cần siết mỡ';
    else bmiText = isEN ? 'Overweight / Accelerated Fat Loss' : 'Béo phì / Cần giảm mỡ cấp tốc';

    // WHR (Waist to Hip Ratio)
    const whr = (waist / hip).toFixed(2);
    let whrText = '';
    if (gender === 'female') {
        if (whr <= 0.75) whrText = isEN ? 'Ideal Hourglass Ratio (≤ 0.75)' : 'Tỷ lệ Eo/Mông cực chuẩn (Đồng Hồ Cát)';
        else if (whr <= 0.85) whrText = isEN ? 'Healthy Average Ratio' : 'Tỷ lệ trung bình cân đối';
        else whrText = isEN ? 'Elevated Abdominal Fat / Waist Focus' : 'Tích mỡ vòng eo / Cần siết mỡ bụng';
    } else {
        if (whr <= 0.85) whrText = isEN ? 'Optimal Male WHR (≤ 0.85)' : 'Tỷ lệ Eo/Mông lý tưởng cho nam';
        else if (whr <= 0.95) whrText = isEN ? 'Average Range' : 'Tỷ lệ trung bình';
        else whrText = isEN ? 'High Abdominal Fat Risk' : 'Tích mỡ bụng nguy cơ cao';
    }

    // Body Shape
    let shapeName = '';
    let shapeDesc = '';
    if (gender === 'female') {
        if (bust - hip > 5 && waist < bust * 0.75) {
            shapeName = isEN ? 'Inverted Triangle / Apple Shape' : 'Dáng Quả Táo / Dáng Tam Giác Ngược';
            shapeDesc = isEN ? 'Broader bust than hips. Focus on shoulder/chest firmness and glute expansion.' : 'Phần trên ngực rộng hơn mông. Tập trung săn chắc ngực vai & phát triển vòng 3.';
        } else if (hip - bust > 5 && waist < hip * 0.75) {
            shapeName = isEN ? 'Pear Shape' : 'Dáng Quả Lê (Pear Shape)';
            shapeDesc = isEN ? 'Curvier hips than bust. Focus on upper body hypertrophy & lean lower body.' : 'Vòng 3 nảy nở hơn vòng 1. Tập trung giảm nhẹ đùi & tập thêm vai ngực cân đối.';
        } else if (Math.abs(bust - hip) <= 5 && waist <= bust * 0.75) {
            shapeName = isEN ? 'Hourglass Physique' : 'Dáng Đồng Hồ Cát (Hourglass)';
            shapeDesc = isEN ? 'Slim waist with perfectly balanced bust and glute ratios!' : 'Thắt eo thon gọn, số đo vòng 1 và vòng 3 cân đối hoàn hảo!';
        } else {
            shapeName = isEN ? 'Rectangle Athletic Shape' : 'Dáng Thước Kẻ (Rectangle Shape)';
            shapeDesc = isEN ? 'Uniform measurements. Focus on waist tightening & glute activation.' : 'Số đo 3 vòng khá đều nhau. Tập trung siết eo và nâng vòng 3 tạo đường cong.';
        }
    } else {
        if (bust > waist + 15) {
            shapeName = isEN ? 'V-Taper Athletic Frame' : 'Dáng Chữ V (V-Taper Athletic)';
            shapeDesc = isEN ? 'Broad shoulders, full chest, slim waist. Classical athletic frame!' : 'Vai rộng ngực nở, eo thon gọn. Chuẩn phom võ sĩ và gymer!';
        } else {
            shapeName = isEN ? 'Regular Straight Frame' : 'Dáng Thang Thẳng (Regular)';
            shapeDesc = isEN ? 'Focus on lats/deltoid expansion & waist trimming for V-taper.' : 'Cần tập trung phát triển vai xô và giảm mỡ vòng eo để tạo chữ V.';
        }
    }

    // Gym & Yoga Recommendation Content
    let gymHTML = '';
    let yogaHTML = '';

    if (goal === 'waist_fatloss' || whr > 0.8) {
        gymHTML = isEN ? `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Recommended Gym Plan: Waist Trimming & Full-Body Fat Loss (3-4 Days/wk)</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>High Incline Treadmill Walk:</strong> 10-12% incline, 4.5-5.0 km/h speed for 25 mins post-workout.</li>
                <li><strong>Russian Twists & Cable Woodchoppers:</strong> 4 sets x 20 reps for waist cinch & obliques.</li>
                <li><strong>Romanian Deadlift (RDL):</strong> 4 sets x 12 reps targeting hamstrings & glutes without waist bulk.</li>
                <li><strong>Plank Hold to Knee Tucks:</strong> 3 sets x 60s for deep abdominal core strength.</li>
            </ul>
        ` : `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Lộ trình Gym đề xuất: Giảm mỡ eo & Toàn thân (3-4 buổi/tuần)</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>High Incline Treadmill Walk:</strong> Đi bộ dốc 10-12%, tốc độ 4.5-5.0km/h trong 25 phút sau mỗi buổi tập.</li>
                <li><strong>Russian Twists & Cable Woodchoppers:</strong> 4 set x 20 reps siết eo thon gọn và đốt mỡ hai bên sườn.</li>
                <li><strong>Romanian Deadlift (RDL):</strong> 4 set x 12 reps tập đùi sau và săn cơ mông mà không to eo.</li>
                <li><strong>Plank Hold to Knee Tucks:</strong> 3 set x 60s tăng cường sức bền cơ bụng core.</li>
            </ul>
        `;
        yogaHTML = isEN ? `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Recommended Yoga Routine: Waist Twists & Core Detoxification</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Cobra Pose (Bhujangasana):</strong> Hold 45s x 4 sets, stretches abdominal wall & tones waistline.</li>
                <li><strong>Half Lord of the Fishes (Ardha Matsyendrasana):</strong> Stimulates digestion & burns intercostal fat.</li>
                <li><strong>Boat Pose (Navasana):</strong> Engages deep rectus abdominis & pelvic floor stability.</li>
            </ul>
        ` : `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Lộ trình Yoga đề xuất: Vặn xoắn thắt eo & Giải độc ruột</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Tư thế Rắn Hổ Mang (Bhujangasana):</strong> Giữ 45 giây x 4 lần, kéo giãn cơ bụng, săn chắc vùng eo.</li>
                <li><strong>Tư thế Vặn Lưng (Ardha Matsyendrasana):</strong> Kích thích tiêu hóa, đốt mỡ thừa hai bên liên sườn.</li>
                <li><strong>Tư thế Con Thuyền (Navasana):</strong> Siết chặt nhóm cơ bụng dưới và cơ lõi trung tâm.</li>
            </ul>
        `;
    } else if (goal === 'glute_growth' || (hip < 90 && gender === 'female')) {
        gymHTML = isEN ? `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Recommended Gym Plan: Glute Hypertrophy & Lower Body Toning</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Barbell Hip Thrust:</strong> 4 sets x 10-12 reps progressive overload, gold standard for glute max.</li>
                <li><strong>Bulgarian Split Squat:</strong> 3 sets x 12 reps/leg targeting upper glute shelf & hips.</li>
                <li><strong>Cable Glute Kickbacks:</strong> 4 sets x 15 reps to shape rounded glute contours.</li>
                <li><strong>Goblet Squat (Kettlebell/Dumbbell):</strong> 4 sets x 12 reps for firm quads and glutes.</li>
            </ul>
        ` : `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Lộ trình Gym đề xuất: Phát triển Nở Vòng 3 & Đùi Săn Chắc</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Barbell Hip Thrust:</strong> 4 set x 10-12 reps (tăng dần mức tạ), bài tập số 1 giúp tăng cơ mông cực nhanh.</li>
                <li><strong>Bulgarian Split Squat:</strong> 3 set x 12 reps/chân nhắm trực tiếp vào phần mông trên và mông ngoài.</li>
                <li><strong>Cable Glute Kickbacks:</strong> 4 set x 15 reps tạo độ cao tròn đầy cho vòng 3.</li>
                <li><strong>Goblet Squat (Tạ ấm/Tạ đơn):</strong> 4 set x 12 reps săn chắc đùi và mông.</li>
            </ul>
        `;
        yogaHTML = isEN ? `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Recommended Yoga Routine: Hip Opening & Glute Elevation Poses</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Warrior III (Virabhadrasana III):</strong> Activates gluteus maximus while maintaining spinal neutral.</li>
                <li><strong>Bridge Pose (Setu Bandhasana):</strong> Hold 60s x 4 sets, squeeze glutes and posterior chain.</li>
                <li><strong>Pigeon Pose (Eka Pada Rajakapotasana):</strong> Deep hip flexor release for elegant posture.</li>
            </ul>
        ` : `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Lộ trình Yoga đề xuất: Mở khớp hông & Nâng cơ mông</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Tư thế Chiến Binh III (Virabhadrasana III):</strong> Kích hoạt cơ mông lớn và giữ thắt lưng thẳng.</li>
                <li><strong>Tư thế Cây Cầu (Setu Bandhasana):</strong> Giữ 60 giây x 4 lần, siết chặt cơ mông và đùi sau.</li>
                <li><strong>Tư thế Bồ Câu (Eka Pada Rajakapotasana):</strong> Mở rộng khớp hông, kéo giãn vóc dáng thanh thoát.</li>
            </ul>
        `;
    } else if (goal === 'flexibility_balance') {
        gymHTML = isEN ? `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Recommended Light Gym Plan: Posture Correction & Muscle Tone</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Lat Pulldown (Wide Grip):</strong> 4 sets x 12 reps expands upper back, rectifying rounded shoulders.</li>
                <li><strong>Dumbbell Shoulder Press:</strong> 3 sets x 15 reps sculpts deltoids and elegant neckline posture.</li>
                <li><strong>Bodyweight Lunges & Step-ups:</strong> 3 sets x 15 reps builds leg stability & mobility.</li>
            </ul>
        ` : `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Lộ trình Gym nhẹ nhàng: Cân bằng khung xương & Tone cơ</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Lat Pulldown (Kéo xô rộng tay):</strong> 4 set x 12 reps mở rộng lưng, sửa dáng lưng gù.</li>
                <li><strong>Dumbbell Shoulder Press:</strong> 3 set x 15 reps giúp vai thon và cổ cao thanh tú.</li>
                <li><strong>Bodyweight Lunges & Step-ups:</strong> 3 set x 15 reps tăng thăng bằng và sức dẻo đôi chân.</li>
            </ul>
        `;
        yogaHTML = isEN ? `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Recommended Advanced Yoga Routine: Full-Body Flexibility & Energy Alignment</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Downward-Facing Dog (Adho Mukha Svanasana):</strong> Elongates spine, hamstrings & calves.</li>
                <li><strong>Camel Pose (Ustrasana):</strong> Opens thoracic cage, extends abdominal wall & lung capacity.</li>
                <li><strong>Wheel Pose (Chakrasana):</strong> Maximum back extension & full-body energy opening.</li>
            </ul>
        ` : `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Lộ trình Yoga chuyên sâu: Dẻo dai toàn thân & Cân bằng năng lượng</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Tư thế Chó Úp Mặt (Adho Mukha Svanasana):</strong> Duỗi thẳng toàn bộ cột sống, đùi sau và bắp chân.</li>
                <li><strong>Tư thế Con Lạc Đà (Ustrasana):</strong> Mở lồng ngực, kéo giãn cơ bụng và tăng dung tích phổi.</li>
                <li><strong>Tư thế Bánh Xe (Chakrasana):</strong> Tăng độ uốn dẻo tuyệt đối cho lưng và tăng dẻo dai toàn thân.</li>
            </ul>
        `;
    } else {
        gymHTML = isEN ? `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Recommended All-Rounder Gym Plan: Lean Mass & Muscular Endurance</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Bench Press / Push-ups:</strong> 4 sets x 12 reps for firm upper chest development.</li>
                <li><strong>Barbell Squats:</strong> 4 sets x 10 reps for total leg & glute activation.</li>
                <li><strong>Cable Face Pulls:</strong> 4 sets x 15 reps rear deltoids to keep shoulders square.</li>
            </ul>
        ` : `
            <div style="background:#eff6ff;padding:12px 16px;border-radius:12px;border-left:4px solid #2563eb;margin-bottom:1rem;">
                <strong>Lộ trình Gym tổng hợp: Tăng cơ nén & Tăng sức bền toàn thân</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Bench Press / Push-up:</strong> 4 set x 12 reps phát triển Vòng 1 đầy đặn săn chắc.</li>
                <li><strong>Barbell Squats:</strong> 4 set x 10 reps kích hoạt toàn bộ nhóm cơ chân và mông.</li>
                <li><strong>Cable Face Pulls:</strong> 4 set x 15 reps vai sau giúp giữ tư thế vai mở vuông vức.</li>
            </ul>
        `;
        yogaHTML = isEN ? `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Recommended Yoga Maintenance Routine: Recovery & Proportions</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Triangle Pose (Trikonasana):</strong> Stretches lateral obliques & improves hip mobility.</li>
                <li><strong>Cat-Cow Pose (Marjaryasana-Bitilasana):</strong> Restores intervertebral disc fluidity.</li>
                <li><strong>Child Pose (Balasana):</strong> Calms heart rate & relaxes nervous system.</li>
            </ul>
        ` : `
            <div style="background:#ecfdf5;padding:12px 16px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:1rem;">
                <strong>Lộ trình Yoga Phục Hồi & Duy Trì Số Đo Chuẩn</strong>
            </div>
            <ul style="padding-left:18px;margin-bottom:1rem;line-height:1.7;">
                <li><strong>Tư thế Tam Giác (Trikonasana):</strong> Kéo giãn lườn eo, tăng linh hoạt hông.</li>
                <li><strong>Tư thế Mèo - Bò (Marjaryasana-Bitilasana):</strong> Linh hoạt đĩa đệm cột sống.</li>
                <li><strong>Tư thế Em Bé (Balasana):</strong> Phục hồi nhịp tim và thư giãn thần kinh.</li>
            </ul>
        `;
    }

    document.getElementById('resBMIVal').innerText = bmi;
    document.getElementById('resBMIText').innerText = bmiText;
    document.getElementById('resWHRVal').innerText = whr;
    document.getElementById('resWHRText').innerText = whrText;
    document.getElementById('resBodyShapeVal').innerText = shapeName;
    document.getElementById('resBodyShapeText').innerText = shapeDesc;

    document.getElementById('gymRecContent').innerHTML = gymHTML;
    document.getElementById('yogaRecContent').innerHTML = yogaHTML;

    const resBox = document.getElementById('advResultsContainer');
    resBox.style.display = 'block';
    resBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>



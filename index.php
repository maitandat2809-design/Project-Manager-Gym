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
            <a href="<?php echo isset($_SESSION['user_id']) ? '/Gym Manager/pages/coaches.php' : '/Gym Manager/auth/register.php'; ?>" class="cta-btn" style="margin: 0; padding: 1rem 2.4rem; font-size: 0.82rem;"><?php echo __('start_journey'); ?></a>
            <a href="#features" class="cta-btn" style="margin: 0; padding: 1rem 2.4rem; font-size: 0.82rem; background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.18); box-shadow: none;"><?php echo __('explore_more'); ?></a>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="scroll-cue">
        <div class="scroll-line"></div>
        <span>Scroll</span>
    </div>
</header>

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

        // Check for Consultation Wizard redirect parameters
        const urlParams = new URLSearchParams(window.location.search);
        const registerPkg = urlParams.get('register_pkg');
        if (registerPkg) {
            setTimeout(() => {
                if (typeof openServiceRegistration === 'function') {
                    openServiceRegistration(registerPkg);
                    // Pre-fill notes field if available
                    const goal = urlParams.get('wizard_goal') || '';
                    const budget = urlParams.get('wizard_budget') || '';
                    const promo = urlParams.get('promo_claimed') === '1' ? 'YES (10%)' : 'NO';
                    const noteArea = document.querySelector('#serviceRegisterForm textarea[name="note"]');
                    if (noteArea) {
                        noteArea.value = `[Consultation Wizard Recommendation]\nGoal: ${goal.toUpperCase()}\nBudget: ${budget.toUpperCase()}\nDiscount Applied: ${promo}`;
                    }
                }
                // Clear the query parameters from URL without reloading page
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 500);
        }

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

// Close modals when clicking outside the card
window.addEventListener('click', function(e) {
    const detailsModal = document.getElementById('serviceDetailsModal');
    const registerModal = document.getElementById('serviceRegisterModal');
    if (e.target === detailsModal) {
        closeServiceDetails();
    }
    if (e.target === registerModal) {
        closeServiceRegistration();
    }
});
</script>



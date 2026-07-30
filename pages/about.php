<?php include_once __DIR__ . '/../includes/header.php'; ?>

<section style="padding: 100px 0 60px;">
    <div class="section-title">
        <h2 style="font-size: 3rem; font-weight: 900; background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">ABOUT GYM<span>X</span></h2>
        <div class="underline"></div>
        <p style="margin-top: 15px; color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto;">Revolutionizing fitness management since our inception.</p>
    </div>

    <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center; margin-top: 4rem;">
        <div>
            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop" 
                 style="width: 100%; border-radius: 30px; box-shadow: var(--shadow-premium); border: 2px solid var(--primary);" alt="GymX Facility">
        </div>
        <div>
            <h3 style="color: var(--primary); font-size: 2rem; margin-bottom: 1.5rem; font-weight: 800;">Our Legacy</h3>
            <p style="color: var(--text-main); font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
                Founded in <strong>March 2024</strong>, <strong>GymX</strong> emerged with a single vision: to bridge the gap between world-class fitness coaching and seamless digital management. We believe that everyone deserves a high-performance environment to transform their body and mind.
            </p>
            <p style="color: var(--text-main); font-size: 1.1rem; line-height: 1.8;">
                Initially starting as a local boutique gym management tool, we have evolved into a premium platform that connects thousands of fitness enthusiasts with professional coaches and top-tier nutritional supplements.
            </p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem;">
                <div style="background: white; padding: 2rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); box-shadow: var(--shadow-small);">
                    <h4 style="color: var(--primary); font-size: 2rem; font-weight: 900; margin-bottom: 5px;">2+</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Years Success</p>
                </div>
                <div style="background: white; padding: 2rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); box-shadow: var(--shadow-small);">
                    <h4 style="color: var(--primary); font-size: 2rem; font-weight: 900; margin-bottom: 5px;">500+</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Active Members</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Get In Touch Section -->
    <div style="margin-top: 8rem; text-align: center;">
        <h3 style="color: var(--primary); font-size: 2.5rem; margin-bottom: 1.5rem; font-weight: 800;">Get in Touch</h3>
        <p style="color: var(--text-main); font-size: 1.1rem; margin-bottom: 3.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">We'd love to hear from you. Reach out to our support team through any of the following channels.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1000px; margin: 0 auto;">
            <!-- Phone -->
            <a href="/Gym Manager/pages/contact_links.php?type=phone" style="text-decoration: none; display: block; background: white; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); box-shadow: var(--shadow-small); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 70px; height: 70px; background: #22c55e; color: white; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 1.5rem; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h4 style="color: var(--text-main); font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Phone</h4>
                <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">0888.236.757</p>
            </a>

            <!-- Zalo -->
            <a href="/Gym Manager/pages/contact_links.php?type=zalo" style="text-decoration: none; display: block; background: white; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); box-shadow: var(--shadow-small); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 70px; height: 70px; background: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
                    <img src="https://img.icons8.com/color/48/zalo.png" style="width: 45px; height: 45px;" alt="Zalo">
                </div>
                <h4 style="color: var(--text-main); font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Zalo</h4>
                <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">GymX Zalo Official</p>
            </a>

            <!-- Messenger -->
            <a href="/Gym Manager/pages/contact_links.php?type=messenger" style="text-decoration: none; display: block; background: white; padding: 2.5rem 2rem; border-radius: 20px; text-align: center; border: 1px solid var(--border); box-shadow: var(--shadow-small); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 70px; height: 70px; background: #0084ff; color: white; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 1.5rem; box-shadow: 0 4px 15px rgba(0, 132, 255, 0.3);">
                    <i class="fab fa-facebook-messenger"></i>
                </div>
                <h4 style="color: var(--text-main); font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Messenger</h4>
                <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">GymX Facebook Page</p>
            </a>
        </div>
    </div>

    <div style="margin-top: 8rem; background: var(--grad-primary); padding: 5rem; border-radius: 40px; color: white; position: relative; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 98, 255, 0.4);">
        <div style="position: relative; z-index: 2; text-align: center;">
            <h3 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 1.5rem;">The Future of Fitness</h3>
            <p style="font-size: 1.2rem; max-width: 800px; margin: 0 auto 3rem; opacity: 0.9; line-height: 1.8;">
                Our mission is to continue innovating, providing our community with the latest in exercise science and digital convenience. Whether you are a beginner or an elite athlete, GymX is your ultimate partner in health.
            </p>
            <a href="/Gym Manager/auth/register.php" class="cta-btn" style="background: white; color: var(--primary); font-weight: 800; padding: 1.2rem 3rem;">Join Our Movement</a>
        </div>
        <!-- Decorative Circle -->
        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

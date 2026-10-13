<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYM X | Facebook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; padding-bottom: 70px; }
        /* FB Navbar */
        .fb-nav { background: white; height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .fb-nav-left { display: flex; align-items: center; gap: 10px; }
        .fb-logo { color: #0866ff; font-size: 40px; }
        .fb-search { background: #f0f2f5; border: none; padding: 10px 16px; border-radius: 20px; outline: none; font-size: 15px; }
        .fb-nav-center { display: flex; gap: 10px; flex: 1; justify-content: center; max-width: 600px; }
        .fb-nav-icon { width: 110px; height: 50px; display: flex; justify-content: center; align-items: center; border-radius: 8px; color: #65676b; font-size: 24px; cursor: pointer; transition: 0.2s; }
        .fb-nav-icon:hover { background: #f0f2f5; }
        .fb-nav-icon.active { color: #0866ff; border-bottom: 3px solid #0866ff; border-radius: 0; }
        .fb-nav-right { display: flex; gap: 10px; }
        .fb-circle-btn { width: 40px; height: 40px; border-radius: 50%; background: #e4e6eb; display: flex; justify-content: center; align-items: center; font-size: 18px; cursor: pointer; color: #050505; transition: 0.2s; }
        .fb-circle-btn:hover { background: #d8dadf; }
        
        /* Main Layout */
        .fb-container { display: flex; max-width: 1920px; margin: 0 auto; }
        
        /* Sidebar */
        .fb-sidebar { width: 360px; background: white; height: calc(100vh - 56px); position: sticky; top: 56px; overflow-y: auto; padding: 16px; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .sidebar-item { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 8px; cursor: pointer; }
        .sidebar-item:hover { background: #f0f2f5; }
        .sidebar-icon { width: 36px; height: 36px; background: #e4e6eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #050505;}
        
        /* Content */
        .fb-content { flex: 1; max-width: 1000px; padding: 20px; margin: 0 auto; }
        
        /* Cover */
        .fb-cover { width: 100%; height: 350px; background-image: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop'); background-size: cover; background-position: center; border-radius: 12px; position: relative; }
        .fb-group-header { background: white; padding: 20px; border-radius: 0 0 12px 12px; display: flex; justify-content: space-between; align-items: flex-end; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .fb-group-title h1 { margin: 0 0 5px 0; font-size: 28px; color: #050505; }
        .fb-group-meta { color: #65676b; font-size: 15px; display: flex; align-items: center; gap: 5px; }
        .fb-join-btn { background: #0866ff; color: white; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s; }
        .fb-join-btn:hover { background: #0054d1; }
        .fb-msg-btn { background: #e4e6eb; color: #050505; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 8px; cursor: pointer; margin-left: 10px; transition: 0.2s;}
        .fb-msg-btn:hover { background: #d8dadf; }

        /* Posts */
        .fb-post { background: white; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .post-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .post-avatar { width: 40px; height: 40px; border-radius: 50%; background: #0866ff; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .post-author { font-weight: 600; color: #050505; text-decoration: none; }
        .post-time { font-size: 13px; color: #65676b; }
        .post-text { font-size: 15px; color: #050505; margin-bottom: 12px; line-height: 1.5; }
        .post-img { width: 100%; border-radius: 8px; margin-bottom: 12px; }
        .post-actions { display: flex; border-top: 1px solid #ced0d4; padding-top: 4px; }
        .post-action { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 6px; color: #65676b; font-weight: 600; cursor: pointer; border-radius: 4px; }
        .post-action:hover { background: #f0f2f5; }

        /* BACK TO WEBSITE BAR - Fixed at bottom */
        .back-to-web-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #1e293b;
            color: white;
            padding: 12px;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }
        .back-to-web-btn {
            background: #22c55e;
            color: white;
            padding: 8px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 2px solid white;
        }
        .back-to-web-btn:hover {
            background: #16a34a;
            transform: scale(1.05);
        }

        @media (max-width: 900px) {
            .fb-sidebar { display: none; }
            .fb-nav-center { display: none; }
            .fb-content { padding: 10px; }
        }
        
        @media (max-width: 600px) {
            .fb-cover { height: 180px; border-radius: 0; }
            .fb-group-header { border-radius: 0; padding: 15px 10px; margin-bottom: 10px; justify-content: center; text-align: center; }
            .fb-group-meta { justify-content: center; }
            .fb-group-header > div:first-child > div:last-child { justify-content: center; } /* Centers the avatar overlapping list */
            .fb-group-header > div:last-child { width: 100%; justify-content: center; margin-top: 10px; }
            
            .fb-join-btn, .fb-msg-btn { font-size: 13px; padding: 6px 10px; }
            .fb-search { display: none; }
            .fb-nav-left .fas.fa-search { background: #e4e6eb; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: static; cursor: pointer; }
            .fb-logo { font-size: 32px; }
            .post-action { font-size: 13px; gap: 5px; }
            
            .back-to-web-text { display: none; }
            .back-to-web-bar { padding: 10px; }
            .back-to-web-btn { font-size: 14px; padding: 8px 15px; }
        }
        
        @media (min-width: 601px) {
            .back-to-web-text { display: inline; font-size: 0.95rem; }
        }

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
</head>
<body>
    <!-- FB Header -->
    <div class="fb-nav">
        <div class="fb-nav-left">
            <i class="fab fa-facebook fb-logo"></i>
            <div style="position: relative; display: flex; align-items: center;">
                <i class="fas fa-search" style="position: absolute; left: 10px; color: #65676b;"></i>
                <input type="text" class="fb-search" placeholder="<?php echo __('fb_search'); ?>" style="padding-left: 30px;">
            </div>
        </div>
        <div class="fb-nav-center">
            <div class="fb-nav-icon"><i class="fas fa-home"></i></div>
            <div class="fb-nav-icon"><i class="fas fa-user-friends"></i></div>
            <div class="fb-nav-icon active"><i class="fas fa-users"></i></div>
        </div>
        <div class="fb-nav-right">
            <div class="fb-circle-btn"><i class="fas fa-th"></i></div>
            <div class="fb-circle-btn"><i class="fab fa-facebook-messenger"></i></div>
            <div class="fb-circle-btn"><i class="fas fa-bell"></i></div>
            <div class="fb-circle-btn" style="background: #e7f3ff; color: #0866ff;"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="fb-container">
        <!-- Sidebar -->
        <div class="fb-sidebar">
            <h3 style="margin: 0 0 15px 0; font-size: 24px;"><?php echo __('fb_community'); ?></h3>
            <div class="sidebar-item" style="background: #e7f3ff; color: #0866ff;">
                <div class="sidebar-icon" style="background: #1877f2; color: white;"><i class="fas fa-users"></i></div>
                <div style="font-weight: 600;"><?php echo __('fb_group_home'); ?></div>
            </div>
            <div class="sidebar-item">
                <div class="sidebar-icon"><i class="fas fa-compass"></i></div>
                <div style="font-weight: 600;"><?php echo __('fb_discover'); ?></div>
            </div>
            <hr style="border: none; border-top: 1px solid #ced0d4; margin: 15px 0;">
            <h4 style="color: #65676b; margin-bottom: 10px;"><?php echo __('fb_joined_groups'); ?></h4>
            <div class="sidebar-item">
                <img src="https://images.unsplash.com/photo-1593095948071-474c5cc2989d?q=80&w=100&auto=format&fit=crop" style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover;">
                <div style="font-weight: 600;">GYM X OFFICIAL</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="fb-content">
            <div class="fb-cover"></div>
            <div class="fb-group-header">
                <div>
                    <div class="fb-group-title">
                        <h1>GYM X OFFICIAL</h1>
                    </div>
                    <div class="fb-group-meta">
                        <i class="fas fa-lock"></i> <?php echo __('fb_private_group'); ?> · 15.2K <?php echo __('fb_members'); ?>
                    </div>
                    <div style="display: flex; margin-top: 15px;">
                        <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=40&auto=format&fit=crop" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid white; margin-left: -5px; z-index: 3;">
                        <img src="https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=40&auto=format&fit=crop" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid white; margin-left: -10px; z-index: 2;">
                        <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&auto=format&fit=crop" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid white; margin-left: -10px; z-index: 1;">
                    </div>
                </div>
                <div style="display: flex; align-items: center;">
                    <button class="fb-join-btn"><i class="fas fa-plus"></i> <?php echo __('fb_join_group'); ?></button>
                    <button class="fb-msg-btn"><i class="fab fa-facebook-messenger"></i> <?php echo __('fb_message'); ?></button>
                    <button class="fb-msg-btn"><i class="fas fa-share"></i> <?php echo __('fb_share'); ?></button>
                </div>
            </div>

            <!-- Posts -->
            <div style="max-width: 680px; margin: 0 auto;">
                <!-- Create Post -->
                <div class="fb-post" style="display: flex; gap: 10px; align-items: center;">
                    <img src="https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff" style="width: 40px; height: 40px; border-radius: 50%;">
                    <input type="text" placeholder="<?php echo __('fb_write_post'); ?>" style="flex: 1; background: #f0f2f5; border: none; padding: 12px 16px; border-radius: 20px; outline: none; cursor: pointer;">
                </div>

                <!-- Post 1 -->
                <div class="fb-post">
                    <div class="post-header">
                        <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=100&auto=format&fit=crop" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <a href="#" class="post-author"><?php echo __('fb_admin'); ?></a>
                            <div class="post-time">2 <?php echo __('fb_hours_ago'); ?> · <i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="post-text"><?php echo __('fb_post_content'); ?></div>
                    <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop" class="post-img">
                    <div class="post-actions">
                        <div class="post-action"><i class="far fa-thumbs-up"></i> <?php echo __('fb_like'); ?></div>
                        <div class="post-action"><i class="far fa-comment"></i> <?php echo __('fb_comment'); ?></div>
                        <div class="post-action"><i class="fas fa-share"></i> <?php echo __('fb_share'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BACK TO WEBSITE BAR -->
    <div class="back-to-web-bar">
        <span class="back-to-web-text"><?php echo __('fb_viewing_mockup'); ?></span>
        <a href="/Gym Manager/index.php" class="back-to-web-btn" id="real-back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo __('fb_back_web'); ?>
        </a>
    </div>

    <!-- Promo Modal -->
    <div id="promoModal" class="promo-modal">
        <div class="promo-modal-content">
            <span class="promo-close">&times;</span>
            <div class="promo-image-container">
                <div class="promo-text-overlay">
                    <h2>GET YOUR</h2>
                    <span class="huge-text">7 DAYS TRAINING</span>
                    <span class="free-text">FOR FREE</span>
                </div>
            </div>
            <div class="promo-form-container">
                <input type="text" placeholder="Name" class="promo-input">
                <input type="text" placeholder="Phone Number" class="promo-input">
                <button class="promo-submit-btn">Register Now!</button>
                <p class="promo-note">*Valid for first-time members at GYM X only</p>
            </div>
        </div>
    </div>

    <script>
        // Prevent default actions for mockup elements
        document.querySelectorAll('a[href="#"], button:not(.promo-submit-btn)').forEach(el => {
            el.addEventListener('click', e => {
                if(el.id !== 'real-back-btn' && !el.classList.contains('back-to-web-btn') && !el.closest('#promoModal')) {
                    e.preventDefault();
                    alert('<?php echo __("fb_mockup_alert"); ?>');
                }
            });
        });

        // Promo Modal Logic
        document.addEventListener("DOMContentLoaded", function() {
            var modal = document.getElementById("promoModal");
            var closeBtn = document.querySelector(".promo-close");

            // Automatically show modal on page load
            setTimeout(function() {
                modal.style.display = "flex";
            }, 800); // Small delay before showing

            closeBtn.onclick = function() {
                modal.style.display = "none";
            }

            // Click outside to close
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
            
            // Submit button
            document.querySelector('.promo-submit-btn').addEventListener('click', function(e) {
                e.preventDefault();
                var inputs = document.querySelectorAll('.promo-input');
                var name = inputs[0] ? inputs[0].value.trim() : '';
                var phone = inputs[1] ? inputs[1].value.trim() : '';
                if(name === '' || phone === '') {
                    alert('<?php echo __('fill_all_info'); ?>');
                    return;
                }
                
                modal.style.display = "none";
                window.location.href = '/Gym Manager/auth/register.php?name=' + encodeURIComponent(name) + '&phone=' + encodeURIComponent(phone) + '&promo=7days_free';
            });
        });
    </script>
</body>
</html>

    <footer class="main-footer">
        <div class="footer-top">
            <div class="footer-container">
                <a href="/Gym Manager/index.php" class="footer-logo">GYM<span>X</span></a>
            </div>
        </div>
        
        <div class="footer-middle">
            <div class="footer-container footer-grid">
                <div class="footer-col">
                    <h4><?php echo __('information'); ?></h4>
                    <ul>
                        <li><a href="/Gym Manager/pages/about.php"><?php echo __('about_us'); ?></a></li>
                        <?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
                        <li><a href="/Gym Manager/pages/membership.php"><?php echo __('membership_rates'); ?></a></li>
                        <li><a href="/Gym Manager/pages/coaches.php"><?php echo __('gym_system'); ?></a></li>
                        <?php endif; ?>
                        <li><a href="/Gym Manager/pages/news.php"><?php echo __('news_updates'); ?></a></li>
                        <li><a href="/Gym Manager/pages/careers.php"><?php echo __('careers'); ?></a></li>
                        <li><a href="/Gym Manager/pages/about.php#contact"><?php echo __('contact_us'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4><?php echo __('gym_network'); ?></h4>
                    <ul>
                        <?php if(!isset($_SESSION['role']) || $_SESSION['role'] === 'user'): ?>
                        <li><a href="/Gym Manager/pages/coaches.php"><?php echo __('gym_network'); ?></a></li>
                        <?php endif; ?>
                        <li><a href="/Gym Manager/pages/equipment.php"><?php echo __('premium_facilities'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4><?php echo __('policies'); ?></h4>
                    <ul>
                        <li><a href="/Gym Manager/pages/privacy.php"><?php echo __('privacy_policy'); ?></a></li>
                        <li><a href="/Gym Manager/pages/terms.php"><?php echo __('terms_conditions'); ?></a></li>
                        <li><a href="/Gym Manager/pages/support.php"><?php echo __('customer_care'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-col footer-lang">
                    <div class="lang-selector-mini">
                        <img src="https://flagcdn.com/w40/gb.png" alt="EN">
                        <span>English</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-container footer-bottom-flex">
                <p>&copy; <?php echo date('Y'); ?> GymX Fitness. <?php echo __('all_rights'); ?></p>
            </div>
        </div>

        <!-- Single Rotating Contact Button -->
        <div class="eu-dock" id="euDock">
            <div class="eu-spin-btn" id="euSpinBtn">
                <span class="eu-spin-icon" id="euSpinIcon"><i class="fas fa-robot"></i></span>
                <span class="eu-spin-label" id="euSpinLabel">Assistant</span>
                <span class="eu-spin-pulse"></span>
            </div>
        </div>

        <!-- AI Chatbox -->
        <div class="eu-chatbox" id="euChatbox">
            <div class="eu-chat-header">
                <div class="eu-chat-header-left">
                    <div class="eu-chat-avatar">
                        <i class="fas fa-robot"></i>
                        <span class="eu-online-status"></span>
                    </div>
                    <div>
                        <div class="eu-chat-name">MAISON GYMX</div>
                        <div class="eu-chat-subtitle">MEMBERS CONCIERGE</div>
                    </div>
                </div>
                <div class="eu-header-contacts">
                    <a href="/Gym Manager/pages/contact_links.php?type=phone" class="eu-header-contact-btn phone-btn" title="Call Us"><i class="fas fa-phone-alt"></i></a>
                    <a href="/Gym Manager/pages/contact_links.php?type=zalo" class="eu-header-contact-btn zalo-btn" title="Zalo"><img src="https://img.icons8.com/color/48/zalo.png" alt="Zalo" class="eu-gold-img" style="width:16px;height:16px;"></a>
                    <a href="/Gym Manager/pages/contact_links.php?type=messenger" class="eu-header-contact-btn messenger-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
                <button class="eu-chat-close" id="aiChatClose"><i class="fas fa-times"></i></button>
            </div>
            <div class="eu-chat-messages" id="euMessages">
                <div class="eu-msg eu-msg-bot">
                    <div class="eu-msg-bubble">Welcome to <strong>GymX</strong>! 👋<br>How can I help you today?</div>
                </div>
                <div class="eu-quick-replies" id="euQuickReplies">
                    <div class="eu-qr-title">SELECT AN INQUIRY</div>
                    <button class="eu-qr-btn" data-topic="register">Create an Account</button>
                    <button class="eu-qr-btn" data-topic="login">Member Login</button>
                    <button class="eu-qr-btn" data-topic="shop">Shopping Guide</button>
                    <button class="eu-qr-btn" data-topic="booking">Book a Coach</button>
                    <button class="eu-qr-btn" data-topic="membership">Membership Rates</button>
                    <button class="eu-qr-btn" data-topic="contact">Direct Support</button>
                </div>
            </div>
            <div class="eu-chat-input-row">
                <input type="text" id="euChatInput" class="eu-chat-input" placeholder="Type your question...">
                <button class="eu-chat-send" id="euChatSend"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </footer>

    <script>
        // Language Mini Toggle (Visual only)
        const langMini = document.querySelector('.lang-selector-mini');
        if(langMini) {
            langMini.addEventListener('click', () => {
                showGymXAlert(GYMX_LANG === 'vi' ? 'Chuyển đổi ngôn ngữ có sẵn ở menu phía trên.' : 'Language switching is available in the top menu.', null, '🌐');
            });
        }
    </script>
    <script>
        // Trigger background task for reminders
        fetch('/Gym Manager/notifications/cron_reminders.php').catch(() => {});
    </script>

    <script>
    /* ============================================================
       EUROPEAN LUXURY INTERACTION SYSTEM
       Restrained, elegant, premium — inspired by maison design
    ============================================================ */

    (function() {

        /* 1. REFINED INK-DROP RIPPLE on buttons & cards
           — not flashy, just a subtle press wave */
        function addRipple(el) {
            el.style.position = 'relative';
            el.style.overflow = 'hidden';
            el.addEventListener('click', function(e) {
                const existing = this.querySelector('.eu-ripple');
                if (existing) existing.remove();

                const r = document.createElement('span');
                r.className = 'eu-ripple';
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height) * 1.5;
                r.style.cssText = `
                    position:absolute; border-radius:50%; pointer-events:none;
                    width:${size}px; height:${size}px;
                    top:${e.clientY - rect.top - size/2}px;
                    left:${e.clientX - rect.left - size/2}px;
                    background: rgba(201,168,76,0.18);
                    transform: scale(0); opacity:1;
                    animation: euRipple 0.7s cubic-bezier(0.4,0,0.2,1) forwards;
                    z-index:10;
                `;
                this.appendChild(r);
                setTimeout(() => r.remove(), 800);
            });
        }

        document.querySelectorAll('.cta-btn, .btn-card, .btn-auth, .action-btn').forEach(addRipple);

        /* 2. MAGNETIC BUTTON EFFECT — button drifts gently toward cursor */
        document.querySelectorAll('.cta-btn, .btn-card').forEach(btn => {
            btn.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                const dx = (e.clientX - cx) * 0.18;
                const dy = (e.clientY - cy) * 0.18;
                this.style.transform = `translate(${dx}px, ${dy}px) translateY(-4px)`;
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.transition = 'transform 0.5s cubic-bezier(0.25,1,0.5,1)';
            });
        });

        /* 3. ELEGANT PAGE TRANSITION — thin gold line sweeps before navigation */
        const progressBar = document.createElement('div');
        progressBar.id = 'eu-progress';
        progressBar.style.cssText = `
            position:fixed; top:0; left:0; height:2px; width:0%;
            background: linear-gradient(90deg, #c9a84c, #e8c97a, #c9a84c);
            z-index:99999; transition:width 0.3s ease; pointer-events:none;
            box-shadow: 0 0 8px rgba(201,168,76,0.5);
        `;
        document.body.appendChild(progressBar);

        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto') || href.startsWith('tel') || link.target === '_blank' || link.hasAttribute('onclick')) return;
            link.addEventListener('click', function(e) {
                if (e.defaultPrevented) return;
                e.preventDefault();
                const dest = this.href;
                progressBar.style.width = '70%';
                setTimeout(() => {
                    progressBar.style.width = '100%';
                    setTimeout(() => { window.location.href = dest; }, 200);
                }, 200);
            });
        });

        /* 4. STAGGERED SCROLL REVEAL — cards & sections drift up elegantly */
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    const delay = entry.target.dataset.delay || (Array.from(entry.target.parentNode?.children || []).indexOf(entry.target) * 80);
                    setTimeout(() => entry.target.classList.add('active'), parseInt(delay));
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        // Auto-apply reveal to cards and grid items
        document.querySelectorAll('.card, .stat-item, .feature-card').forEach((el, i) => {
            if (!el.classList.contains('reveal')) {
                el.classList.add('reveal');
                el.dataset.delay = i * 70;
            }
            revealObserver.observe(el);
        });
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => revealObserver.observe(el));


        /* 6. NAVBAR — becomes more opaque and bordered on scroll */
        const nav = document.querySelector('nav');
        if (nav) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    nav.style.background = 'rgba(250,249,246,0.97)';
                    nav.style.borderBottomColor = 'rgba(201,168,76,0.25)';
                } else {
                    nav.style.background = 'rgba(250,249,246,0.92)';
                    nav.style.borderBottomColor = 'rgba(201,168,76,0.12)';
                }
            });
        }

    })();
    </script>
<style>
/* ─── EU DOCK ─── */
.eu-dock{position:fixed;right:26px;bottom:9vh;z-index:9999;display:flex;flex-direction:column;gap:12px;align-items:flex-end;}

/* single-button spin states */
.eu-spin-btn{display:flex;align-items:center;gap:10px;padding:0 22px 0 10px;height:54px;border-radius:100px;border:1.5px solid #c9a84c;cursor:pointer;font-family:'DM Serif Display',serif;font-weight:400;transition:all .4s ease;box-shadow:0 8px 28px rgba(28,35,64,.18),0 2px 6px rgba(0,0,0,.1);position:relative;white-space:nowrap;overflow:hidden;background:#1c2340;color:#c9a84c;animation:glowGold 3s infinite;}
.eu-spin-btn::after{content:'';position:absolute;top:0;left:-150%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(201,168,76,.35),transparent);transform:skewX(-20deg);transition:left .85s cubic-bezier(0.25,1,0.5,1);}
.eu-spin-btn:hover::after{left:150%;}
.eu-spin-btn:hover{transform:translateX(-5px) scale(1.04);box-shadow:0 14px 40px rgba(28,35,64,.3);border-color:#e8c97a;color:#e8c97a;}
.eu-spin-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;background:rgba(201,168,76,.15);color:#c9a84c;transition:transform 0.4s ease;}
.eu-spin-icon img{width:26px;height:26px;}
.eu-spin-label{pointer-events:none;transition:transform 0.4s ease, opacity 0.4s ease;text-transform:uppercase;letter-spacing:1.5px;font-size:0.72rem;font-family:'Inter',sans-serif;font-weight:550;}
.eu-spin-pulse{position:absolute;top:9px;right:13px;width:8px;height:8px;border-radius:50%;background:#c9a84c;animation:pulseOnline 2s infinite;}
@keyframes pulseOnline{0%{box-shadow:0 0 0 0 rgba(201,168,76,.6);}70%{box-shadow:0 0 0 7px rgba(201,168,76,0);}100%{box-shadow:0 0 0 0 rgba(201,168,76,0);}}

.eu-spin-btn.state-phone{background:#1c2340;color:#c9a84c;border-color:#c9a84c;animation:glowGold 3s infinite;}
.eu-spin-btn.state-zalo{background:#1c2340;color:#c9a84c;border-color:#c9a84c;animation:glowGold 3s infinite;}
.eu-spin-btn.state-messenger{background:#1c2340;color:#c9a84c;border-color:#c9a84c;animation:glowGold 3s infinite;}
.eu-spin-btn.state-ai{background:#1c2340;color:#c9a84c;border-color:#c9a84c;animation:glowGold 3s infinite;}

@keyframes glowGold {
  0%, 100% { box-shadow: 0 8px 28px rgba(28,35,64,.18), 0 0 0 0 rgba(201,168,76,.45); }
  50% { box-shadow: 0 8px 28px rgba(28,35,64,.18), 0 0 0 12px rgba(201,168,76,0); }
}

.eu-fade .eu-spin-icon { animation: iconSpinChange 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes iconSpinChange {
  0% { transform: scale(0.3) rotate(-360deg); opacity: 0; }
  100% { transform: scale(1) rotate(0); opacity: 1; }
}

.eu-fade .eu-spin-label { animation: labelSlideChange 0.5s cubic-bezier(0.25, 1, 0.5, 1); }
@keyframes labelSlideChange {
  0% { transform: translateY(16px); opacity: 0; filter: blur(1.5px); }
  100% { transform: translateY(0); opacity: 1; filter: blur(0); }
}

@media(max-width:640px){.eu-chatbox{width:calc(100vw - 30px);right:15px;bottom:calc(5vh + 160px);}.eu-dock{right:15px;bottom:5vh;}.eu-spin-btn{padding:0 14px 0 8px;height:46px;font-size:.74rem;}}

/* ─── EU CHATBOX ─── */
.eu-chatbox{position:fixed;bottom:calc(9vh + 70px);right:26px;width:360px;max-height:520px;background:#faf9f6;border:1px solid rgba(201,168,76,0.3);border-top:3px solid #1c2340;box-shadow:0 24px 80px rgba(28,35,64,.18),0 6px 24px rgba(0,0,0,.08);z-index:10000;display:flex;flex-direction:column;font-family:'Inter',sans-serif;transform-origin:bottom right;transition:transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease, visibility 0.35s;opacity:0;transform:translateY(30px) scale(0.85);visibility:hidden;pointer-events:none;}
.eu-chatbox::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#1c2340,#c9a84c,#1c2340);}
.eu-chatbox.open{opacity:1;transform:translateY(0) scale(1);visibility:visible;pointer-events:auto;}
.eu-chat-header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#faf9f6;color:#1c2340;border-bottom:1px solid rgba(201,168,76,0.25);}
.eu-chat-header-left{display:flex;align-items:center;gap:12px;}
.eu-chat-avatar{width:38px;height:38px;border-radius:50%;background:#1c2340;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#c9a84c;position:relative;}
.eu-online-status{position:absolute;bottom:0;right:0;width:9px;height:9px;border-radius:50%;background:#22c55e;border:1.5px solid #faf9f6;}
.eu-chat-name{font-family:'DM Serif Display',serif;font-size:1.15rem;font-weight:400;color:#1c2340;letter-spacing:0.8px;text-transform:uppercase;}
.eu-chat-subtitle{font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:600;letter-spacing:1.2px;color:#c9a84c;text-transform:uppercase;margin-top:2px;}
.eu-chat-close{background:transparent;border:none;color:#7c7267;width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-size:.82rem;}
.eu-chat-close:hover{background:rgba(28,35,64,0.06);color:#1c2340;}
.eu-chat-messages{flex:1;overflow-y:auto;padding:16px 14px 8px;display:flex;flex-direction:column;gap:10px;background:#fdfcfb;max-height:320px;}
.eu-chat-messages::-webkit-scrollbar{width:4px;}.eu-chat-messages::-webkit-scrollbar-thumb{background:#e8e2d8;border-radius:4px;}
.eu-msg{display:flex;animation:bubblePopIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;transform-origin:bottom left;}
.eu-msg-user{transform-origin:bottom right;}
@keyframes bubblePopIn{0%{transform:scale(0.8) translateY(12px);opacity:0;}100%{transform:scale(1) translateY(0);opacity:1;}}
.eu-msg-bot{justify-content:flex-start;}
.eu-msg-user{justify-content:flex-end;}
.eu-msg-bubble{max-width:84%;padding:11px 15px;font-size:.88rem;line-height:1.6;font-family:'Inter',sans-serif;border-radius:8px;}
.eu-msg-bot .eu-msg-bubble{background:#fcfbf9;color:#1c2340;border:1px solid rgba(201,168,76,0.25);box-shadow:0 2px 8px rgba(28,35,64,0.03);}
.eu-msg-user .eu-msg-bubble{background:#1c2340;color:#faf9f6;border:1px solid #1c2340;box-shadow:0 2px 8px rgba(28,35,64,0.15);}
.eu-quick-replies{display:flex;flex-direction:column;gap:6px;margin-top:4px;}
.eu-qr-title{font-size:0.65rem;letter-spacing:1.5px;color:#c9a84c;font-family:'Inter',sans-serif;font-weight:600;text-transform:uppercase;margin-bottom:8px;margin-top:10px;opacity:0.85;}
.eu-qr-btn{background:#faf9f6;border:1px solid rgba(201,168,76,0.3);color:#1c2340;padding:9px 14px;font-size:.78rem;font-family:'Inter',sans-serif;font-weight:550;border-radius:4px;cursor:pointer;text-align:left;transition:all .2s ease;text-transform:uppercase;letter-spacing:0.5px;}
.eu-qr-btn:hover{background:#1c2340;color:#c9a84c;border-color:#1c2340;transform:translateX(3px);}
.eu-chat-input-row{display:flex;border-top:1px solid rgba(201,168,76,0.2);background:#faf9f6;}
.eu-chat-input{flex:1;border:none;outline:none;padding:14px 16px;font-family:'Inter',sans-serif;font-size:.88rem;color:#1c2340;background:transparent;}
.eu-chat-input::placeholder{color:#7c7267;}
.eu-chat-send{background:transparent;border:none;color:#1c2340;width:48px;cursor:pointer;font-size:.9rem;transition:all .2s;}
.eu-chat-send:hover{color:#c9a84c;}
.eu-typing{display:flex;gap:4px;align-items:center;padding:6px 2px;}
.eu-typing span{width:7px;height:7px;border-radius:50%;background:#c9a84c;animation:euDot 1.2s infinite;}
.eu-typing span:nth-child(2){animation-delay:.2s;}.eu-typing span:nth-child(3){animation-delay:.4s;}
@keyframes euDot{0%,80%,100%{transform:scale(.6);opacity:.4;}40%{transform:scale(1);opacity:1;}}

/* Header Contacts style */
.eu-header-contacts{display:flex;gap:8px;margin-right:12px;align-items:center;}
.eu-header-contact-btn{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.88rem;color:#c9a84c;border:1px solid rgba(201,168,76,0.3);text-decoration:none;transition:all 0.3s cubic-bezier(0.25,1,0.5,1);background:rgba(255,255,255,0.05);box-shadow:0 2px 5px rgba(0,0,0,0.1);opacity:0;transform:scale(0);}
.eu-header-contact-btn.phone-btn{color:#c9a84c;}
.eu-header-contact-btn.zalo-btn{color:#c9a84c;}
.eu-header-contact-btn.messenger-btn{color:#c9a84c;}

.eu-gold-img {
  filter: invert(72%) sepia(21%) saturate(928%) hue-rotate(9deg) brightness(92%) contrast(85%);
  transition: filter 0.3s;
}
.eu-header-contact-btn:hover .eu-gold-img {
  filter: invert(11%) sepia(23%) saturate(2200%) hue-rotate(210deg) brightness(90%) contrast(90%);
}

/* Staggered Pop-in animation when Chatbox is open */
.eu-chatbox.open .eu-header-contact-btn {
  animation: bounceInContacts 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.eu-chatbox.open .eu-header-contact-btn:nth-child(1) { animation-delay: 0.2s; }
.eu-chatbox.open .eu-header-contact-btn:nth-child(2) { animation-delay: 0.3s; }
.eu-chatbox.open .eu-header-contact-btn:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounceInContacts {
  0% { transform: scale(0); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

.eu-header-contact-btn:hover{background:#c9a84c;color:#1c2340 !important;border-color:#c9a84c;transform:scale(1.2) translateY(-2px);box-shadow: 0 4px 10px rgba(201,168,76,0.25);}
</style>

<script>
(function(){
  /* ── ROTATING BUTTON ── */
  var states = [
    { cls:'state-ai',        icon:'<i class="fas fa-robot"></i>',                                                label:'Maison Assistant',  action:'chat' },
    { cls:'state-phone',     icon:'<i class="fas fa-phone-alt"></i>',                                           label:'Direct Line',       action:'phone' },
    { cls:'state-zalo',      icon:'<img src="https://img.icons8.com/color/48/zalo.png" alt="Zalo" class="eu-gold-img">',            label:'Zalo Concierge',    action:'zalo' },
    { cls:'state-messenger', icon:'<i class="fab fa-facebook-f"></i>',                                  label:'Facebook Desk',    action:'messenger' }
  ];
  var cur = 0, spinTimer;

  function applyState(s, animate){
    var btn  = document.getElementById('euSpinBtn');
    var icon = document.getElementById('euSpinIcon');
    var lbl  = document.getElementById('euSpinLabel');
    if(!btn) return;
    btn.className = 'eu-spin-btn ' + s.cls + (animate ? ' eu-fade' : '');
    icon.innerHTML = s.icon;
    lbl.textContent = s.label;
    btn.onclick = function(e){
      e.stopPropagation();
      if(s.action === 'chat'){
        var box = document.getElementById('euChatbox');
        if(box){
          box.classList.toggle('open');
          if(box.classList.contains('open')){
            clearInterval(spinTimer);
          } else {
            startRotation();
          }
        }
      } else {
        window.location.href = '/Gym Manager/pages/contact_links.php?type=' + s.action;
      }
    };
    if(animate) setTimeout(function(){ btn.classList.remove('eu-fade'); }, 380);
  }

  function startRotation(){
    clearInterval(spinTimer);
    spinTimer = setInterval(function(){
      cur = (cur + 1) % states.length;
      applyState(states[cur], true);
    }, 2500);
  }

  document.addEventListener('DOMContentLoaded', function(){
    applyState(states[0], false);
    startRotation();

    var btn = document.getElementById('euSpinBtn');
    if(btn){
      btn.addEventListener('mouseenter', function(){
        var box = document.getElementById('euChatbox');
        if(!box || !box.classList.contains('open')){
          clearInterval(spinTimer);
        }
      });
      btn.addEventListener('mouseleave', function(){
        var box = document.getElementById('euChatbox');
        if(!box || !box.classList.contains('open')){
          startRotation();
        }
      });
    }

  /* ── AI CHATBOX ── */
  var answers = {
    register: [
      '📝 <strong>Create a GymX Account</strong><br><br>1. Click <strong>"Sign Up"</strong> in the top-right corner.<br>2. Fill in your full name, email, and password.<br>3. Click <strong>"Create Account"</strong> and verify your email.<br><br>✅ You can then log in and start your fitness journey!',
      '💡 Tip: Use a real email address to receive session reminders and exclusive GymX offers!'
    ],
    login: [
      '🔐 <strong>Logging into GymX</strong><br><br>1. Click <strong>"Log In"</strong> in the top-right corner.<br>2. Enter your registered email and password.<br>3. Click <strong>"Log In"</strong>.<br><br>🔑 Forgot your password? Click <em>"Forgot Password"</em> to reset it via email!'
    ],
    shop: [
      '🛍️ <strong>Shopping Guide</strong><br><br>1. Go to <strong>Shop</strong> from the navigation menu.<br>2. Select a product → click <strong>"Add to Cart"</strong>.<br>3. Review your cart and proceed to checkout.<br>4. Choose payment: <strong>COD</strong> or <strong>Bank Transfer</strong>.<br>5. Confirm your order.<br><br>📦 Orders are processed within 1–2 business days!'
    ],
    booking: [
      '📅 <strong>Book a Personal Training Session</strong><br><br>1. Log in to your account.<br>2. Go to <strong>Coaches</strong> and choose a trainer.<br>3. Click <strong>"Book Now"</strong> on their profile.<br>4. Select a date and time, then confirm.<br><br>📬 You will receive a confirmation email shortly after booking!'
    ],
    membership: [
      '💳 <strong>GymX Membership Plans</strong><br><br>🥉 <strong>Basic</strong> — Unlimited gym access<br>🥈 <strong>Premium</strong> — Gym + Yoga + Muay Thai<br>🥇 <strong>Elite</strong> — All-access + PT sessions<br><br>Visit the <strong>Shop</strong> page for full details and sign-up!'
    ],
    contact: [
      '📞 <strong>GymX Support</strong><br><br>📱 Hotline: <strong>0966.456.789</strong><br>💬 Zalo: Tap the rotating button → Zalo<br>📘 Facebook: Tap the rotating button → Messenger<br>📧 Email: support@gymx.com<br><br>⏰ Support hours: 6:00 AM – 10:00 PM daily!'
    ]
  };

  function addMsg(html, isUser){
    var d=document.getElementById('euMessages');
    var m=document.createElement('div'); m.className='eu-msg '+(isUser?'eu-msg-user':'eu-msg-bot');
    var b=document.createElement('div'); b.className='eu-msg-bubble'; b.innerHTML=html;
    m.appendChild(b); d.appendChild(m); d.scrollTop=d.scrollHeight;
  }
  function showTyping(){
    var d=document.getElementById('euMessages');
    var t=document.createElement('div'); t.className='eu-msg eu-msg-bot'; t.id='euTyping';
    t.innerHTML='<div class="eu-msg-bubble" style="background:white;border:1px solid #e8e2d8;"><div class="eu-typing"><span></span><span></span><span></span></div></div>';
    d.appendChild(t); d.scrollTop=d.scrollHeight;
  }
  function removeTyping(){ var t=document.getElementById('euTyping'); if(t) t.remove(); }
  function reply(topic){
    var lines=answers[topic]||['I\'m not sure about that. Please call our hotline <strong>0966.456.789</strong> for direct support!'];
    var qr=document.getElementById('euQuickReplies'); if(qr) qr.style.display='none';
    showTyping();
    lines.forEach(function(line,i){ setTimeout(function(){ if(i===lines.length-1) removeTyping(); addMsg(line,false); },900*(i+1)); });
    setTimeout(function(){ addMsg('Any other questions? 😊',false); },900*(lines.length+1));
  }
  function handleInput(){
    var inp=document.getElementById('euChatInput'); var val=inp.value.trim(); if(!val) return;
    addMsg(val,true); inp.value='';
    var l=val.toLowerCase(), topic=null;
    if(/register|sign.?up|account|create/i.test(l)) topic='register';
    else if(/log.?in|login|password|forgot/i.test(l)) topic='login';
    else if(/shop|buy|cart|order|product|purchase/i.test(l)) topic='shop';
    else if(/book|pt|personal|trainer|coach|session/i.test(l)) topic='booking';
    else if(/member|plan|package|subscription/i.test(l)) topic='membership';
    else if(/contact|phone|zalo|hotline|email|support/i.test(l)) topic='contact';
    if(topic){ reply(topic); }
    else{ showTyping(); setTimeout(function(){ removeTyping(); addMsg('I understand you\'re asking about <em>"'+val+'"</em>. Please choose a topic below or call <strong>0966.456.789</strong> for live support!',false); },900); }
  }

  var close=document.getElementById('aiChatClose');
  var send=document.getElementById('euChatSend');
  var inp=document.getElementById('euChatInput');
  if(close) close.addEventListener('click',function(){
    document.getElementById('euChatbox').classList.remove('open');
    startRotation();
  });
  if(send)  send.addEventListener('click',handleInput);
  if(inp)   inp.addEventListener('keydown',function(e){ if(e.key==='Enter') handleInput(); });
  document.querySelectorAll('.eu-qr-btn').forEach(function(b){
    b.addEventListener('click',function(){ addMsg(this.textContent,true); reply(this.dataset.topic); });
  });

  }); // end DOMContentLoaded
})();
</script>

<!-- GymX Global Custom Alert & Confirm Modal -->
<div id="gymx-custom-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:999999;align-items:center;justify-content:center;backdrop-filter:blur(8px);padding:1rem;">
    <div style="background:#fff;border-radius:24px;width:100%;max-width:440px;box-shadow:0 30px 80px rgba(0,0,0,0.35);overflow:hidden;animation:gymxModalPop 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;border:1px solid rgba(255,255,255,0.2);">
        <!-- Header -->
        <div id="gymx-modal-header" style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:1.3rem 1.6rem;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div id="gymx-modal-icon-bg" style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                    <span id="gymx-modal-icon">⚠️</span>
                </div>
                <h4 id="gymx-modal-title" style="color:#fff;margin:0;font-size:1.08rem;font-weight:800;font-family:inherit;">Notification</h4>
            </div>
            <button onclick="closeGymXModal(false)" style="background:rgba(255,255,255,0.12);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">×</button>
        </div>
        <!-- Body -->
        <div style="padding:1.6rem 1.8rem;text-align:center;">
            <p id="gymx-modal-message" style="color:#334155;font-size:0.98rem;line-height:1.6;margin:0;white-space:pre-line;font-weight:600;font-family:inherit;"></p>
        </div>
        <!-- Footer / Actions -->
        <div style="padding:0 1.6rem 1.6rem;display:flex;gap:12px;justify-content:center;">
            <button id="gymx-modal-btn-cancel" onclick="closeGymXModal(false)" style="display:none;flex:1;padding:0.9rem;background:#f1f5f9;color:#64748b;border:none;border-radius:14px;font-weight:700;font-size:0.92rem;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Cancel
            </button>
            <button id="gymx-modal-btn-confirm" onclick="closeGymXModal(true)" style="flex:1;padding:0.9rem;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:14px;font-weight:700;font-size:0.92rem;cursor:pointer;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                Got It
            </button>
        </div>
    </div>
</div>

<style>
@keyframes gymxModalPop {
    from { opacity: 0; transform: scale(0.9) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
let gymxModalResolver = null;
const GYMX_LANG = <?php echo json_encode($_SESSION['lang'] ?? 'en'); ?>;

// English is the default UI language. Keep the Vietnamese dong marker only
// for an explicit Vietnamese session; English sessions display the ISO code.
function normaliseEnglishCurrency(root = document.body) {
    if (GYMX_LANG !== 'en' || !root) return;

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let node;
    while ((node = walker.nextNode())) {
        const parentTag = node.parentElement ? node.parentElement.tagName : '';
        if (!['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parentTag) && /\dđ/.test(node.nodeValue)) {
            textNodes.push(node);
        }
    }
    textNodes.forEach((textNode) => {
        textNode.nodeValue = textNode.nodeValue.replace(/(\d)đ/g, '$1 VND');
    });
}

if (GYMX_LANG === 'en') {
    normaliseEnglishCurrency();
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((addedNode) => {
                if (addedNode.nodeType === Node.ELEMENT_NODE) {
                    normaliseEnglishCurrency(addedNode);
                } else if (addedNode.nodeType === Node.TEXT_NODE && /\dđ/.test(addedNode.nodeValue)) {
                    addedNode.nodeValue = addedNode.nodeValue.replace(/(\d)đ/g, '$1 VND');
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
}

function showGymXAlert(message, title = null, icon = '⚠️') {
    if (!title) title = GYMX_LANG === 'vi' ? 'Thông Báo' : 'Notification';
    return new Promise((resolve) => {
        gymxModalResolver = resolve;
        document.getElementById('gymx-modal-icon').textContent = icon;
        document.getElementById('gymx-modal-title').textContent = title;
        document.getElementById('gymx-modal-message').textContent = message;
        document.getElementById('gymx-modal-btn-cancel').style.display = 'none';
        const confirmBtn = document.getElementById('gymx-modal-btn-confirm');
        confirmBtn.textContent = GYMX_LANG === 'vi' ? 'Đã Hiểu' : 'Got It';
        confirmBtn.style.background = 'linear-gradient(135deg,#2563eb,#1d4ed8)';
        confirmBtn.style.boxShadow = '0 4px 15px rgba(37,99,235,0.3)';
        document.getElementById('gymx-custom-modal').style.display = 'flex';
    });
}

function showGymXConfirm(message, title = null, icon = '❓') {
    if (!title) title = GYMX_LANG === 'vi' ? 'Xác Nhận Thao Tác' : 'Confirmation';
    return new Promise((resolve) => {
        gymxModalResolver = resolve;
        document.getElementById('gymx-modal-icon').textContent = icon;
        document.getElementById('gymx-modal-title').textContent = title;
        document.getElementById('gymx-modal-message').textContent = message;
        
        const cancelBtn = document.getElementById('gymx-modal-btn-cancel');
        cancelBtn.textContent = GYMX_LANG === 'vi' ? 'Hủy Bỏ' : 'Cancel';
        cancelBtn.style.display = 'block';

        const confirmBtn = document.getElementById('gymx-modal-btn-confirm');
        confirmBtn.textContent = GYMX_LANG === 'vi' ? 'Xác Nhận' : 'Confirm';
        confirmBtn.style.background = 'linear-gradient(135deg,#2563eb,#1d4ed8)';
        confirmBtn.style.boxShadow = '0 4px 15px rgba(37,99,235,0.3)';
        document.getElementById('gymx-custom-modal').style.display = 'flex';
    });
}

function closeGymXModal(result = false) {
    document.getElementById('gymx-custom-modal').style.display = 'none';
    if (gymxModalResolver) {
        gymxModalResolver(result);
        gymxModalResolver = null;
    }
}

// Close custom modal when clicking backdrop
document.getElementById('gymx-custom-modal').addEventListener('click', function(e) {
    if (e.target === this) closeGymXModal(false);
});

function confirmOrderCustom(event, form, message, title = null, icon = '❓') {
    if (event) {
        if (typeof event.preventDefault === 'function') event.preventDefault();
        if (typeof event.stopPropagation === 'function') event.stopPropagation();
    }
    const submitter = (event && event.submitter) ? event.submitter : null;
    showGymXConfirm(message, title, icon).then(confirmed => {
        if (confirmed && form) {
            if (submitter && submitter.name && !form.querySelector(`input[name="${submitter.name}"]`)) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = submitter.name;
                hidden.value = submitter.value || '1';
                form.appendChild(hidden);
            }
            form.submit();
        }
    });
    return false;
}

function confirmLinkCustom(event, link, message, title = null, icon = '❓') {
    if (event) {
        if (typeof event.preventDefault === 'function') event.preventDefault();
        if (typeof event.stopPropagation === 'function') event.stopPropagation();
    }
    const href = link ? link.getAttribute('href') : null;
    showGymXConfirm(message, title, icon).then(confirmed => {
        if (confirmed && href) {
            window.location.href = href;
        }
    });
    return false;
}

// Global override of native alert to custom GymX modal
window.alert = function(msg) {
    showGymXAlert(msg);
};
</script>

</body>
</html>


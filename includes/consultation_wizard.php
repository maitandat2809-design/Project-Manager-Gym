<?php
// Consultation Wizard Modal & Interactivity
// Fetch coaches list to populate the select options or display in recommendation
$coaches_list = [];
try {
    $stmt_coaches = $pdo->query("SELECT c.id, u.name as coach_name, c.specialty FROM coaches c JOIN users u ON c.user_id = u.id ORDER BY u.name ASC");
    $coaches_list = $stmt_coaches->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching coaches: " . $e->getMessage());
}
?>

<!-- ============================================== -->
<!-- CUSTOMER SUPPORT WIZARD MODAL (OVERLAY) -->
<!-- ============================================== -->
<div id="consultationWizardModal" class="wizard-modal-overlay" style="display: none;">
    <div class="wizard-modal-container">
        <!-- Close Button -->
        <button type="button" class="wizard-modal-close" onclick="closeConsultationWizard()">&times;</button>
        
        <!-- Header -->
        <div class="wizard-header">
            <div class="wizard-badge"><i class="fas fa-headset" style="margin-right: 5px;"></i> <?php echo __('nav_consultation'); ?></div>
            <h1 class="wizard-main-title"><?php echo __('wizard_title'); ?></h1>
            <p class="wizard-subtitle"><?php echo __('wizard_subtitle'); ?></p>
        </div>

        <!-- 4 Columns Grid -->
        <div class="wizard-columns-grid">
            
            <!-- COLUMN 1: ĐẶT LỊCH TẬP THỬ -->
            <div class="wizard-column-card">
                <div class="wizard-column-header">
                    <span class="column-num">01</span>
                    <h3><?php echo __('wizard_tab1_title'); ?></h3>
                    <p><?php echo __('wizard_tab1_desc'); ?></p>
                </div>
                <div class="wizard-column-body">
                    <form id="wizardTrialForm" class="wizard-form">
                        <div class="form-group-custom">
                            <label><?php echo __('fs_modal_name'); ?> *</label>
                            <div class="input-wrapper-custom">
                                <i class="fas fa-user"></i>
                                <input type="text" name="name" required placeholder="<?php echo __('promo_name'); ?>" 
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label><?php echo __('fs_modal_phone'); ?> *</label>
                            <div class="input-wrapper-custom">
                                <i class="fas fa-phone-alt"></i>
                                <input type="text" name="phone" required placeholder="090x xxx xxx" pattern="[0-9]{10}"
                                       value="<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label><?php echo __('fs_modal_email'); ?> *</label>
                            <div class="input-wrapper-custom">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" required placeholder="example@email.com"
                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label><?php echo __('wizard_branch'); ?></label>
                            <div class="input-wrapper-custom select-wrapper-custom">
                                <i class="fas fa-map-marker-alt"></i>
                                <select name="branch" required>
                                    <option value="GymX District 1">GymX District 1 (HQ)</option>
                                    <option value="GymX District 3">GymX District 3</option>
                                    <option value="GymX District 7">GymX District 7</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label><?php echo __('wizard_timeslot'); ?></label>
                            <div class="input-wrapper-custom select-wrapper-custom">
                                <i class="fas fa-clock"></i>
                                <select name="timeslot" required>
                                    <option value="Monday - Friday: 08:00 - 10:00">Mon - Fri: 08:00 - 10:00</option>
                                    <option value="Monday - Friday: 14:00 - 16:00">Mon - Fri: 14:00 - 16:00</option>
                                    <option value="Monday - Friday: 18:00 - 20:00">Mon - Fri: 18:00 - 20:00</option>
                                    <option value="Saturday - Sunday: 10:00 - 12:00">Sat - Sun: 10:00 - 12:00</option>
                                    <option value="Saturday - Sunday: 16:00 - 18:00">Sat - Sun: 16:00 - 18:00</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="wizard-submit-btn">
                            <i class="fas fa-calendar-check" style="margin-right: 5px;"></i> <?php echo __('wizard_book_now'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- COLUMN 2: CHỌN GÓI PHÙ HỢP -->
            <div class="wizard-column-card">
                <div class="wizard-column-header">
                    <span class="column-num">02</span>
                    <h3><?php echo __('wizard_tab2_title'); ?></h3>
                    <p><?php echo __('wizard_tab2_desc'); ?></p>
                </div>
                <div class="wizard-column-body">
                    <div class="wizard-options-vertical">
                        <div class="wizard-selectable-option active" data-goal="fat" onclick="selectWizardGoal('fat')">
                            <div class="option-icon-box bg-red"><i class="fas fa-fire"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_goal_fat'); ?></span>
                                <span class="option-desc"><?php echo __('wizard_goal_fat_desc'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option" data-goal="muscle" onclick="selectWizardGoal('muscle')">
                            <div class="option-icon-box bg-blue"><i class="fas fa-dumbbell"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_goal_muscle'); ?></span>
                                <span class="option-desc"><?php echo __('wizard_goal_muscle_desc'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option" data-goal="stamina" onclick="selectWizardGoal('stamina')">
                            <div class="option-icon-box bg-green"><i class="fas fa-heartbeat"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_goal_stamina'); ?></span>
                                <span class="option-desc"><?php echo __('wizard_goal_stamina_desc'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option" data-goal="recover" onclick="selectWizardGoal('recover')">
                            <div class="option-icon-box bg-purple"><i class="fas fa-yin-yang"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_goal_recover'); ?></span>
                                <span class="option-desc"><?php echo __('wizard_goal_recover_desc'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMN 3: ƯỚC TÍNH CHI PHÍ -->
            <div class="wizard-column-card">
                <div class="wizard-column-header">
                    <span class="column-num">03</span>
                    <h3><?php echo __('wizard_tab3_title'); ?></h3>
                    <p><?php echo __('wizard_tab3_desc'); ?></p>
                </div>
                <div class="wizard-column-body">
                    <div class="wizard-options-vertical">
                        <div class="wizard-selectable-option" data-budget="under2" onclick="selectWizardBudget('under2')">
                            <div class="option-icon-box bg-slate"><i class="fas fa-piggy-bank"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_budget_under2'); ?></span>
                                <span class="option-desc"><?php echo __('promo_save_more'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option active" data-budget="2to4" onclick="selectWizardBudget('2to4')">
                            <div class="option-icon-box bg-slate"><i class="fas fa-coins"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_budget_2to4'); ?></span>
                                <span class="option-desc"><?php echo __('fs_modal_coach_any'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option" data-budget="4to7" onclick="selectWizardBudget('4to7')">
                            <div class="option-icon-box bg-slate"><i class="fas fa-wallet"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_budget_4to7'); ?></span>
                                <span class="option-desc"><?php echo __('careers_pos1_title'); ?></span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>

                        <div class="wizard-selectable-option" data-budget="above7" onclick="selectWizardBudget('above7')">
                            <div class="option-icon-box bg-slate"><i class="fas fa-gem"></i></div>
                            <div class="option-details">
                                <span class="option-title"><?php echo __('wizard_budget_above7'); ?></span>
                                <span class="option-desc">VIP Access</span>
                            </div>
                            <div class="option-check-circle"><i class="fas fa-check"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMN 4: CHAT TƯ VẤN 24/7 -->
            <div class="wizard-column-card">
                <div class="wizard-column-header">
                    <span class="column-num">04</span>
                    <h3><?php echo __('wizard_tab4_title'); ?></h3>
                    <p><?php echo __('wizard_tab4_desc'); ?></p>
                </div>
                <div class="wizard-column-body chat-column-body">
                    <!-- Assistant Contact Profile Header -->
                    <div class="chat-agent-bar">
                        <div class="agent-avatar">
                            <img src="https://ui-avatars.com/api/?name=GymX+Assistant&background=0062ff&color=fff&size=36" alt="Agent">
                            <span class="online-indicator"></span>
                        </div>
                        <div class="agent-info">
                            <h4><?php echo __('wizard_assistant_title'); ?></h4>
                            <span>Online</span>
                        </div>
                    </div>

                    <!-- Chat Message Area -->
                    <div class="chat-message-area" id="wizardChatMessageArea">
                        <div class="chat-message bot">
                            <p>Hi! How can GymX support you today? Please choose one of the options below or ask me anything.</p>
                        </div>
                    </div>

                    <!-- Chat Clickable Suggestions -->
                    <div class="chat-suggestions" id="wizardChatSuggestions">
                        <button type="button" onclick="sendWizardChatPrompt('I want to lose fat, what package fits me?')">Lose Fat package recommendation?</button>
                        <button type="button" onclick="sendWizardChatPrompt('How much does PT 1:1 cost?')">PT 1:1 Price?</button>
                        <button type="button" onclick="sendWizardChatPrompt('How can I book a free trial session?')">Book a free trial?</button>
                    </div>

                    <!-- Chat Input form -->
                    <form id="wizardChatForm" class="chat-input-form" onsubmit="submitWizardChat(event)">
                        <input type="text" id="wizardChatInput" placeholder="<?php echo __('wizard_chat_placeholder'); ?>" required autocomplete="off">
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>

        </div>

        <!-- RECOMMENDED RESULT PANEL (DYNAMIC) -->
        <div class="wizard-recommendation-panel">
            <div class="panel-badge"><?php echo __('wizard_recommend_title'); ?></div>
            <p class="panel-intro"><?php echo __('wizard_recommend_desc'); ?></p>
            
            <div class="recommended-card-wrapper">
                <!-- Recommended Card Details -->
                <div class="recommended-package-card">
                    <div class="pkg-header-icon" id="recommendPkgIcon"><i class="fas fa-fire"></i></div>
                    <div class="pkg-main-info">
                        <h4 id="recommendPkgTitle">8-Week Fat Loss Package</h4>
                        <div class="pkg-tags">
                            <span class="pkg-tag-badge bg-blue" id="recommendPkgSpecialty">Gym</span>
                            <span class="pkg-tag-badge bg-slate" id="recommendPkgDuration">8 Weeks</span>
                        </div>
                        <ul class="pkg-features-bullets" id="recommendPkgFeatures">
                            <li>Medical-grade fat burning routines</li>
                            <li>Weekly body analysis and plan adjustments</li>
                        </ul>
                    </div>
                </div>

                <!-- Price and Action CTA -->
                <div class="recommended-action-box">
                    <div class="pricing-badge-row">
                        <div class="budget-chosen">
                            <span><?php echo __('wizard_recommend_budget'); ?></span>
                            <strong id="recommendChosenBudget">2.000.000đ - 4.000.000đ</strong>
                        </div>
                        <div class="price-suggested">
                            <span><?php echo __('wizard_recommend_price'); ?></span>
                            <strong id="recommendPrice">2.290.000đ</strong>
                        </div>
                    </div>
                    
                    <!-- Special offer label -->
                    <div class="special-promo-tag" id="wizardPromoBanner">
                        <i class="fas fa-percentage"></i>
                        <span><?php echo __('wizard_recommend_desc'); ?></span>
                        <button type="button" class="promo-claim-btn" onclick="claimWizardPromo()"><?php echo __('wizard_get_promo'); ?></button>
                    </div>

                    <button type="button" class="register-pkg-btn" onclick="registerRecommendedPackage()">
                        <i class="fas fa-check-circle" style="margin-right: 5px;"></i> <?php echo __('wizard_register_pkg'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Toast Notification -->
        <div class="wizard-toast-success" id="wizardSuccessToast" style="display: none;">
            <i class="fas fa-check-circle"></i>
            <span>Registration submitted successfully! Our manager will call you soon.</span>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- CONSULTATION WIZARD STYLES -->
<!-- ============================================== -->
<style>
.wizard-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(15px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    animation: wizardFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Outfit', sans-serif;
}

.wizard-modal-container {
    background: #0f172a; /* Premium Dark Theme background */
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 28px;
    width: 100%;
    max-width: 1200px;
    height: 90vh;
    box-shadow: 0 35px 80px -15px rgba(0,0,0,0.6);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: wizardScaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes wizardFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes wizardScaleIn {
    from { transform: scale(0.95) translateY(30px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}

.wizard-modal-close {
    position: absolute;
    top: 25px;
    right: 25px;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: #94a3b8;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.6rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s;
    z-index: 100;
}

.wizard-modal-close:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    transform: rotate(90deg);
}

/* Header styling */
.wizard-header {
    padding: 2rem 2.5rem 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.wizard-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(37, 99, 235, 0.15);
    border: 1px solid rgba(37, 99, 235, 0.3);
    color: #3b82f6;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.8rem;
}

.wizard-main-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    font-weight: 900;
    color: #ffffff;
    margin: 0 0 5px;
    letter-spacing: -0.5px;
}

.wizard-subtitle {
    color: #94a3b8;
    font-size: 0.95rem;
    margin: 0;
    font-weight: 500;
}

/* Columns Grid */
.wizard-columns-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    flex: 1;
    overflow-y: auto;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.wizard-column-card {
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    flex-direction: column;
    padding: 1.8rem;
    background: rgba(30, 41, 59, 0.2);
}

.wizard-column-card:last-child {
    border-right: none;
}

.wizard-column-header {
    margin-bottom: 1.2rem;
}

.column-num {
    font-size: 0.75rem;
    font-weight: 800;
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.wizard-column-header h3 {
    font-size: 1.1rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 4px;
}

.wizard-column-header p {
    color: #64748b;
    font-size: 0.82rem;
    margin: 0;
    line-height: 1.4;
}

.wizard-column-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Scrollable message columns */
.chat-column-body {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

/* Options */
.wizard-options-vertical {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.wizard-selectable-option {
    background: rgba(30, 41, 59, 0.4);
    border: 1.5px solid rgba(255, 255, 255, 0.05);
    border-radius: 14px;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.wizard-selectable-option:hover {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateX(3px);
}

.wizard-selectable-option.active {
    background: rgba(37, 99, 235, 0.1);
    border-color: #3b82f6;
    box-shadow: 0 0 15px rgba(59, 130, 246, 0.15);
}

.option-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.bg-red { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.bg-blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.bg-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.bg-purple { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.bg-slate { background: rgba(100, 116, 139, 0.15); color: #94a3b8; }

.option-details {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.option-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #ffffff;
}

.option-desc {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 1px;
}

.option-check-circle {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    color: transparent;
    transition: all 0.2s;
}

.wizard-selectable-option.active .option-check-circle {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

/* Forms inside column 1 */
.wizard-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.wizard-form .form-group-custom {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.wizard-form label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
}

.wizard-form .input-wrapper-custom i {
    color: #64748b;
}

.wizard-form input,
.wizard-form select {
    background: rgba(15, 23, 42, 0.6);
    border: 1.5px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-size: 0.88rem;
    padding: 8px 12px 8px 34px;
    font-weight: 500;
}

.wizard-form input:focus,
.wizard-form select:focus {
    border-color: #3b82f6;
    background: rgba(15, 23, 42, 0.9);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.wizard-submit-btn {
    background: #3b82f6;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 0.75rem;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.4rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.wizard-submit-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

/* Chat UI Panel */
.chat-agent-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    margin-bottom: 0.5rem;
}

.agent-avatar {
    position: relative;
    width: 36px;
    height: 36px;
}

.agent-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
}

.online-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background: #10b981;
    border: 2px solid #0f172a;
    border-radius: 50%;
}

.agent-info h4 {
    margin: 0;
    font-size: 0.88rem;
    color: #ffffff;
    font-weight: 700;
}

.agent-info span {
    font-size: 0.72rem;
    color: #10b981;
    font-weight: 600;
}

.chat-message-area {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    padding: 0.5rem 0.2rem;
    max-height: 160px;
}

.chat-message {
    padding: 8px 12px;
    border-radius: 12px;
    max-width: 85%;
    font-size: 0.82rem;
    line-height: 1.4;
    word-break: break-word;
}

.chat-message.bot {
    background: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
    align-self: flex-start;
    border-top-left-radius: 2px;
}

.chat-message.user {
    background: #3b82f6;
    color: #ffffff;
    align-self: flex-end;
    border-top-right-radius: 2px;
}

.chat-suggestions {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 0.8rem;
}

.chat-suggestions button {
    background: rgba(255, 255, 255, 0.04);
    border: 1px dashed rgba(255, 255, 255, 0.15);
    color: #94a3b8;
    text-align: left;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.chat-suggestions button:hover {
    background: rgba(37, 99, 235, 0.08);
    border-color: #3b82f6;
    color: #3b82f6;
}

.chat-input-form {
    display: flex;
    gap: 8px;
}

.chat-input-form input {
    flex: 1;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 8px 12px;
    color: #ffffff;
    font-size: 0.85rem;
    outline: none;
}

.chat-input-form input:focus {
    border-color: #3b82f6;
    background: rgba(255, 255, 255, 0.08);
}

.chat-input-form button {
    background: rgba(255, 255, 255, 0.08);
    color: #3b82f6;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chat-input-form button:hover {
    background: #3b82f6;
    color: #ffffff;
}

/* Dynamic Recommendation Area at bottom */
.wizard-recommendation-panel {
    background: rgba(30, 41, 59, 0.4);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1.5rem 2.5rem;
}

.panel-badge {
    display: inline-block;
    background: #e11d48;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.panel-intro {
    color: #94a3b8;
    font-size: 0.88rem;
    margin: 5px 0 1rem;
}

.recommended-card-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.recommended-package-card {
    flex: 1.3;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1.2rem;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.pkg-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.pkg-main-info h4 {
    margin: 0 0 4px;
    font-size: 1.2rem;
    color: #ffffff;
    font-weight: 800;
}

.pkg-tags {
    display: flex;
    gap: 8px;
    margin-bottom: 0.6rem;
}

.pkg-tag-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
}

.pkg-tag-badge.bg-blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.pkg-tag-badge.bg-slate { background: rgba(100, 116, 139, 0.15); color: #94a3b8; }
.pkg-tag-badge.bg-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }

.pkg-features-bullets {
    margin: 0;
    padding-left: 18px;
    list-style-type: square;
    color: #94a3b8;
    font-size: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.recommended-action-box {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.pricing-badge-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    padding: 0.8rem 1.2rem;
    border-radius: 12px;
}

.budget-chosen, .price-suggested {
    display: flex;
    flex-direction: column;
}

.budget-chosen span, .price-suggested span {
    font-size: 0.72rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
}

.budget-chosen strong {
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 700;
}

.price-suggested strong {
    color: #3b82f6;
    font-size: 1.3rem;
    font-weight: 800;
}

.special-promo-tag {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 10px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #10b981;
    font-weight: 600;
}

.special-promo-tag.claimed {
    background: rgba(16, 185, 129, 0.25);
    border-color: #10b981;
}

.promo-claim-btn {
    background: #10b981;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.promo-claim-btn:hover {
    background: #059669;
}

.register-pkg-btn {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 0.85rem;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.register-pkg-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
}

/* Toast Success style */
.wizard-toast-success {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #10b981;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: toastSlideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes toastSlideUp {
    from { transform: translateX(-50%) translateY(30px); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}

/* Mobile responsive stacked layout */
@media(max-width: 1024px) {
    .wizard-columns-grid {
        grid-template-columns: 1fr 1fr;
    }
    .wizard-column-card {
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .wizard-column-card:nth-child(even) {
        border-right: none;
    }
    .recommended-card-wrapper {
        flex-direction: column;
        gap: 1rem;
    }
    .wizard-modal-container {
        height: 95vh;
    }
}

@media(max-width: 640px) {
    .wizard-columns-grid {
        grid-template-columns: 1fr;
    }
    .wizard-column-card {
        border-right: none;
    }
    .wizard-modal-container {
        height: 100%;
        border-radius: 0;
    }
}
</style>

<!-- ============================================== -->
<!-- WIZARD INTERACTIVITY JAVASCRIPT -->
<!-- ============================================== -->
<script>
// Recommendations Data Source
const wizardRecommendations = {
    // format: 'goal_budget'
    'fat_under2': {
        packageId: 'gym',
        title: "<?php echo addslashes(__('fs_gym_title')); ?>",
        specialty: "Gym",
        duration: "1 Month",
        price: 899000,
        features: [
            "<?php echo addslashes(__('fs_gym_feature1')); ?>",
            "<?php echo addslashes(__('fs_gym_feature2')); ?>",
            "<?php echo addslashes(__('fs_gym_feature3')); ?>"
        ],
        icon: "fa-dumbbell"
    },
    'fat_2to4': {
        packageId: 'fatloss',
        title: "<?php echo addslashes(__('fs_fatloss_title')); ?>",
        specialty: "Gym",
        duration: "8 Weeks",
        price: 2290000,
        features: [
            "<?php echo addslashes(__('fs_fatloss_feature1')); ?>",
            "<?php echo addslashes(__('fs_fatloss_feature2')); ?>",
            "<?php echo addslashes(__('fs_fatloss_feature3')); ?>"
        ],
        icon: "fa-fire"
    },
    'fat_4to7': {
        packageId: 'pt',
        title: "<?php echo addslashes(__('fs_pt_title')); ?>",
        specialty: "Gym / PT",
        duration: "1 Course",
        price: 3490000,
        features: [
            "<?php echo addslashes(__('fs_pt_feature1')); ?>",
            "<?php echo addslashes(__('fs_pt_feature2')); ?>",
            "<?php echo addslashes(__('fs_pt_feature3')); ?>"
        ],
        icon: "fa-user-shield"
    },
    'fat_above7': {
        packageId: 'pt',
        title: "VIP PT 1:1 Elite Program",
        specialty: "Gym / VIP",
        duration: "3 Courses",
        price: 9900000,
        features: [
            "Premium HLV VIP coach choice & body metrics check daily",
            "Dedicated private locker and custom nutrition package",
            "Guaranteed body transformation results with insurance"
        ],
        icon: "fa-gem"
    },
    
    // Muscle
    'muscle_under2': {
        packageId: 'gym',
        title: "<?php echo addslashes(__('fs_gym_title')); ?>",
        specialty: "Gym",
        duration: "1 Month",
        price: 899000,
        features: [
            "<?php echo addslashes(__('fs_gym_feature1')); ?>",
            "<?php echo addslashes(__('fs_gym_feature2')); ?>",
            "<?php echo addslashes(__('fs_gym_feature3')); ?>"
        ],
        icon: "fa-dumbbell"
    },
    'muscle_2to4': {
        packageId: 'bodypump',
        title: "<?php echo addslashes(__('fs_bodypump_title')); ?>",
        specialty: "Gym",
        duration: "1 Month",
        price: 649000,
        features: [
            "<?php echo addslashes(__('fs_bodypump_feature1')); ?>",
            "<?php echo addslashes(__('fs_bodypump_feature2')); ?>",
            "<?php echo addslashes(__('fs_bodypump_feature3')); ?>"
        ],
        icon: "fa-heartbeat"
    },
    'muscle_4to7': {
        packageId: 'pt',
        title: "<?php echo addslashes(__('fs_pt_title')); ?>",
        specialty: "Gym / PT",
        duration: "1 Course",
        price: 3490000,
        features: [
            "<?php echo addslashes(__('fs_pt_feature1')); ?>",
            "<?php echo addslashes(__('fs_pt_feature2')); ?>",
            "<?php echo addslashes(__('fs_pt_feature3')); ?>"
        ],
        icon: "fa-dumbbell"
    },
    'muscle_above7': {
        packageId: 'pt',
        title: "VIP Bodybuilder Extreme PT",
        specialty: "Gym / VIP",
        duration: "3 Courses",
        price: 9900000,
        features: [
            "Extreme hypertrophy programming by champion coach",
            "Free premium whey protein shakes after every workout",
            "Daily metabolic check and advanced bodybuilding metrics"
        ],
        icon: "fa-trophy"
    },

    // Stamina
    'stamina_under2': {
        packageId: 'kickboxing',
        title: "<?php echo addslashes(__('fs_kickboxing_title')); ?>",
        specialty: "Muay",
        duration: "1 Month",
        price: 749000,
        features: [
            "<?php echo addslashes(__('fs_kickboxing_feature1')); ?>",
            "<?php echo addslashes(__('fs_kickboxing_feature2')); ?>",
            "<?php echo addslashes(__('fs_kickboxing_feature3')); ?>"
        ],
        icon: "fa-bolt"
    },
    'stamina_2to4': {
        packageId: 'kickboxing',
        title: "Muay Thai & Kickboxing Advanced",
        specialty: "Muay / Combat",
        duration: "3 Months",
        price: 2100000,
        features: [
            "Unlimited sparring and advanced pad-work sessions",
            "Cardiovascular endurance training from real Muay champions",
            "All-access gear library (free wraps and premium gloves)"
        ],
        icon: "fa-fist-raised"
    },
    'stamina_4to7': {
        packageId: 'kickboxing',
        title: "Personal Combat Trainer 1:1",
        specialty: "Muay / PT",
        duration: "1 Course",
        price: 4500000,
        features: [
            "1-on-1 private fighter coach dedicated to you",
            "Professional fight techniques, defense and strength",
            "Custom endurance test schedule & heart monitor support"
        ],
        icon: "fa-user-shield"
    },
    'stamina_above7': {
        packageId: 'kickboxing',
        title: "VIP Professional Muay Thai Camp",
        specialty: "Muay / Camp",
        duration: "6 Months VIP",
        price: 8900000,
        features: [
            "Unlimited access to exclusive professional ring camp",
            "Daily 1-on-1 focus mitt sessions and fight strategies",
            "Exclusive fighter gear bundle + custom nutrition plan"
        ],
        icon: "fa-medal"
    },

    // Recovery
    'recover_under2': {
        packageId: 'yoga',
        title: "<?php echo addslashes(__('fs_yoga_title')); ?>",
        specialty: "Yoga",
        duration: "1 Month",
        price: 699000,
        features: [
            "<?php echo addslashes(__('fs_yoga_feature1')); ?>",
            "<?php echo addslashes(__('fs_yoga_feature2')); ?>",
            "<?php echo addslashes(__('fs_yoga_feature3')); ?>"
        ],
        icon: "fa-yin-yang"
    },
    'recover_2to4': {
        packageId: 'yoga',
        title: "Yoga & Sound Healing Therapy",
        specialty: "Yoga",
        duration: "3 Months",
        price: 1990000,
        features: [
            "Restorative yoga with live Tibetan sound bowls",
            "Deep stretching to relieve chronic joint and muscle tension",
            "Free herbal tea lounge access before and after classes"
        ],
        icon: "fa-leaf"
    },
    'recover_4to7': {
        packageId: 'yoga',
        title: "Private Yoga Master 1:1",
        specialty: "Yoga / PT",
        duration: "1 Course",
        price: 4900000,
        features: [
            "1-on-1 yoga therapy with certified Indian Master",
            "Personalized alignments, breathing and recovery",
            "Premium organic mat and custom aromatherapy session"
        ],
        icon: "fa-spa"
    },
    'recover_above7': {
        packageId: 'yoga',
        title: "VIP Holistic Wellness Retreat Program",
        specialty: "Yoga / Wellness",
        duration: "6 Courses VIP",
        price: 9900000,
        features: [
            "Fully private boutique wellness room access",
            "Master class coaching + daily body stretch relief sessions",
            "Premium therapeutic oils, organic juices, & premium services"
        ],
        icon: "fa-water"
    }
};

let currentWizardGoal = 'fat';
let currentWizardBudget = '2to4';
let isPromoApplied = false;

function openConsultationWizard() {
    document.getElementById('consultationWizardModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    updateWizardRecommendation();
}

function closeConsultationWizard() {
    document.getElementById('consultationWizardModal').style.display = 'none';
    document.body.style.overflow = '';
}

function selectWizardGoal(goal) {
    currentWizardGoal = goal;
    document.querySelectorAll('[data-goal]').forEach(el => {
        el.classList.toggle('active', el.getAttribute('data-goal') === goal);
    });
    updateWizardRecommendation();
    
    // Auto add chat response for interactivity
    const goalsFriendly = {
        fat: "Lose Fat",
        muscle: "Gain Muscle",
        stamina: "Build Stamina",
        recover: "Yoga & Recovery"
    };
    triggerBotResponse(`I see you are interested in **${goalsFriendly[goal]}**. What is your budget range for monthly training?`);
}

function selectWizardBudget(budget) {
    currentWizardBudget = budget;
    document.querySelectorAll('[data-budget]').forEach(el => {
        el.classList.toggle('active', el.getAttribute('data-budget') === budget);
    });
    updateWizardRecommendation();
    
    const budgetsFriendly = {
        under2: "Under 2M VND",
        '2to4': "2M to 4M VND",
        '4to7': "4M to 7M VND",
        above7: "Above 7M VND"
    };
    triggerBotResponse(`Excellent budget selection: **${budgetsFriendly[budget]}**. Based on this, I've calculated your optimal package suggestions below!`);
}

function updateWizardRecommendation() {
    const key = `${currentWizardGoal}_${currentWizardBudget}`;
    const rec = wizardRecommendations[key];
    if (!rec) return;

    // Update icon
    const iconBox = document.getElementById('recommendPkgIcon');
    iconBox.className = 'pkg-header-icon';
    // set color classes based on goal
    if (currentWizardGoal === 'fat') iconBox.classList.add('bg-red');
    else if (currentWizardGoal === 'muscle') iconBox.classList.add('bg-blue');
    else if (currentWizardGoal === 'stamina') iconBox.classList.add('bg-green');
    else iconBox.classList.add('bg-purple');
    iconBox.innerHTML = `<i class="fas ${rec.icon}"></i>`;

    // Update text
    document.getElementById('recommendPkgTitle').innerText = rec.title;
    document.getElementById('recommendPkgSpecialty').innerText = rec.specialty;
    document.getElementById('recommendPkgDuration').innerText = rec.duration;
    
    // Update budget string text
    const budgetsTxt = {
        under2: "<?php echo addslashes(__('wizard_budget_under2')); ?>",
        '2to4': "<?php echo addslashes(__('wizard_budget_2to4')); ?>",
        '4to7': "<?php echo addslashes(__('wizard_budget_4to7')); ?>",
        above7: "<?php echo addslashes(__('wizard_budget_above7')); ?>"
    };
    document.getElementById('recommendChosenBudget').innerText = budgetsTxt[currentWizardBudget];

    // Update features list
    const featuresUl = document.getElementById('recommendPkgFeatures');
    featuresUl.innerHTML = '';
    rec.features.forEach(f => {
        const li = document.createElement('li');
        li.innerText = f;
        featuresUl.appendChild(li);
    });

    // Update price
    let priceToShow = rec.price;
    if (isPromoApplied) {
        priceToShow = Math.round(rec.price * 0.9);
    }
    document.getElementById('recommendPrice').innerText = priceToShow.toLocaleString('vi-VN') + 'đ';
}

function claimWizardPromo() {
    if (isPromoApplied) return;
    isPromoApplied = true;
    
    const banner = document.getElementById('wizardPromoBanner');
    banner.classList.add('claimed');
    banner.querySelector('span').innerText = "10% Promo Code APPLIED! 🎉";
    banner.querySelector('button').style.display = 'none';
    
    updateWizardRecommendation();
    triggerBotResponse("Wow! You claimed the **10% special discount**. Make sure to register right away to secure this deal! 💰");
}

function registerRecommendedPackage() {
    const key = `${currentWizardGoal}_${currentWizardBudget}`;
    const rec = wizardRecommendations[key];
    if (!rec) return;
    
    // Close the consultation wizard and open the register modal with the package id!
    closeConsultationWizard();
    
    // Wait a brief millisecond for smooth modal transitions
    setTimeout(() => {
        if (typeof openServiceRegistration === 'function') {
            openServiceRegistration(rec.packageId);
            // Pre-fill notes with recommendations
            const noteArea = document.querySelector('#serviceRegisterForm textarea[name="note"]');
            if (noteArea) {
                noteArea.value = `[Consultation Wizard Recommendation]\nGoal: ${currentWizardGoal.toUpperCase()}\nBudget: ${currentWizardBudget.toUpperCase()}\nPackage: ${rec.title}\nDiscount Applied: ${isPromoApplied ? 'YES (10%)' : 'NO'}`;
            }
        } else {
            // Redirect to index.php with parameters
            window.location.href = `index.php?register_pkg=${rec.packageId}&wizard_goal=${currentWizardGoal}&wizard_budget=${currentWizardBudget}&promo_claimed=${isPromoApplied ? '1' : '0'}`;
        }
    }, 300);
}

// Column 1: Trial booking submit via AJAX
document.getElementById('wizardTrialForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    
    // Add additional fields for register_service.php
    formData.append('package_id', 'gym'); // default gym package
    const branch = form.querySelector('select[name="branch"]').value;
    const timeslot = form.querySelector('select[name="timeslot"]').value;
    formData.append('note', `[TRIAL BOOKING] Branch: ${branch} | Preferred Time Slot: ${timeslot}`);
    
    // Add dummy start date
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    formData.append('start_date', tomorrow.toISOString().slice(0, 16));

    // Submit
    const btn = form.querySelector('.wizard-submit-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';

    fetch('register_service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            // Show custom toast notification
            showWizardToast(data.message || "Trial Session Booked Successfully! 🎉");
            form.reset();
            triggerBotResponse("Awesome! Your free trial session has been registered. Our manager will call you shortly to confirm! 📞");
        } else {
            alert(data.message || "An error occurred.");
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error(err);
        alert("Registration failed.");
    });
});

function showWizardToast(text) {
    const toast = document.getElementById('wizardSuccessToast');
    toast.querySelector('span').innerText = text;
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 4500);
}

// Column 4: Chat logic
function submitWizardChat(e) {
    e.preventDefault();
    const input = document.getElementById('wizardChatInput');
    const message = input.value.trim();
    if (!message) return;
    
    appendChatMessage(message, 'user');
    input.value = '';
    
    // Simple automated response mapping
    setTimeout(() => {
        let reply = "I'm processing your inquiry. For direct assistance, you can also fill out the Trial Booking form in Column 1!";
        
        const lowerMsg = message.toLowerCase();
        if (lowerMsg.includes('price') || lowerMsg.includes('cost') || lowerMsg.includes('giá') || lowerMsg.includes('nhiêu')) {
            reply = "Our monthly rates range from **649,000 VND** for basic access to **3,490,000 VND** for personal training. Choose your target budget in Column 3 to see customized recommendation cards!";
        } else if (lowerMsg.includes('lose fat') || lowerMsg.includes('giảm mỡ') || lowerMsg.includes('béo')) {
            reply = "To burn fat quickly, we highly recommend our **8-Week Fat Loss Program** (2.290.000đ) or a personal coach. Click 'Giảm mỡ' in Column 2 to update the suggest panel!";
        } else if (lowerMsg.includes('trial') || lowerMsg.includes('tập thử') || lowerMsg.includes('miễn phí')) {
            reply = "You can register a free trial instantly using the quick booking form in **Column 1**! Just fill in your details and select a branch.";
        } else if (lowerMsg.includes('coach') || lowerMsg.includes('hlv') || lowerMsg.includes('thầy')) {
            reply = "All our coaches are internationally certified (NASM, ISSA) and have at least 3 years of competitive experience. You can select your preferred coach in the final checkout form!";
        }
        
        appendChatMessage(reply, 'bot');
    }, 800);
}

function sendWizardChatPrompt(text) {
    appendChatMessage(text, 'user');
    
    // Auto responses
    setTimeout(() => {
        let reply = "";
        if (text.includes('lose fat')) {
            reply = "For fat loss, the **8-Week Fat Loss Package** is perfect. It includes HIIT training, custom diets, and body checks. Select 'Giảm mỡ' in Column 2 to see details!";
        } else if (text.includes('PT 1:1')) {
            reply = "Our PT 1:1 course starts at **3,490,000 VND** with 100% personalized plans, nutrition tracking, and guaranteed target results.";
        } else if (text.includes('free trial')) {
            reply = "Yes, you can register for a free trial right now! Just enter your Name & Phone in the form in **Column 1**.";
        }
        appendChatMessage(reply, 'bot');
    }, 700);
}

function appendChatMessage(text, sender) {
    const area = document.getElementById('wizardChatMessageArea');
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-message ${sender}`;
    // Simple markdown support
    msgDiv.innerHTML = `<p>${text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')}</p>`;
    area.appendChild(msgDiv);
    area.scrollTop = area.scrollHeight;
}

function triggerBotResponse(text) {
    // delay bot response slightly for natural feel
    setTimeout(() => {
        appendChatMessage(text, 'bot');
    }, 600);
}

// Close modal on click outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('consultationWizardModal');
    if (e.target === modal) {
        closeConsultationWizard();
    }
});
</script>

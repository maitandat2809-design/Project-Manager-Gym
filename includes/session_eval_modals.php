<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/lang.php';
$curr_lang = $_SESSION['lang'] ?? 'en';
?>
<!-- User Evaluation Modal & Coach Session Notes Modal -->
<div id="userSessionEvalModal" class="gymx-custom-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div style="background: white; border-radius: 24px; padding: 2.2rem; width: 92%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; animation: modalFadeIn 0.3s ease;">
        <button type="button" onclick="closeUserEvalModal()" style="position: absolute; top: 18px; right: 18px; background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; font-size: 1rem; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
            <i class="fas fa-times"></i>
        </button>

        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 0.8rem auto; box-shadow: 0 8px 20px rgba(217, 119, 6, 0.2);">
                <i class="fas fa-star"></i>
            </div>
            <h3 style="margin: 0 0 0.3rem 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?php echo __('eval_session_title'); ?></h3>
            <p id="evalModalSessionInfo" style="color: #64748b; font-size: 0.9rem; margin: 0; font-weight: 500;"></p>
        </div>

        <form id="userEvalForm" onsubmit="submitUserSessionEval(event)">
            <input type="hidden" name="action" value="submit_user_review">
            <input type="hidden" name="booking_id" id="evalBookingId" value="">
            
            <!-- Star Rating Selection -->
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px; font-size: 0.95rem;"><?php echo __('eval_satisfaction_level'); ?></label>
                <div class="star-rating-select" style="display: inline-flex; gap: 8px; font-size: 2.2rem; cursor: pointer;">
                    <i class="fas fa-star star-btn" data-value="1" style="color: #f59e0b; transition: transform 0.2s, color 0.2s;" onclick="setRating(1)"></i>
                    <i class="fas fa-star star-btn" data-value="2" style="color: #f59e0b; transition: transform 0.2s, color 0.2s;" onclick="setRating(2)"></i>
                    <i class="fas fa-star star-btn" data-value="3" style="color: #f59e0b; transition: transform 0.2s, color 0.2s;" onclick="setRating(3)"></i>
                    <i class="fas fa-star star-btn" data-value="4" style="color: #f59e0b; transition: transform 0.2s, color 0.2s;" onclick="setRating(4)"></i>
                    <i class="fas fa-star star-btn" data-value="5" style="color: #f59e0b; transition: transform 0.2s, color 0.2s;" onclick="setRating(5)"></i>
                </div>
                <input type="hidden" name="rating" id="evalRatingInput" value="5">
                <span id="ratingText" style="display: block; font-size: 0.9rem; color: #d97706; font-weight: 700; margin-top: 6px;">⭐⭐⭐⭐⭐ 5/5 - <?php echo $curr_lang === 'en' ? 'Excellent' : 'Tuyệt vời'; ?></span>
            </div>

            <!-- Review Comment Textarea -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px; font-size: 0.9rem;"><?php echo $curr_lang === 'en' ? 'Detailed Feedback on Coach & Session:' : 'Nhận xét chi tiết về HLV & buổi tập:'; ?></label>
                <textarea name="review" id="evalReviewText" rows="3" placeholder="<?php echo __('eval_review_placeholder'); ?>" style="width: 100%; padding: 0.9rem 1.1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #cbd5e1; outline: none; font-size: 0.95rem; resize: vertical; box-sizing: border-box;"></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem;">
                <button type="button" onclick="closeUserEvalModal()" style="flex: 1; padding: 0.9rem; background: #f1f5f9; color: #475569; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer;"><?php echo __('cancel'); ?></button>
                <button type="submit" id="btnSubmitEval" style="flex: 2; padding: 0.9rem; background: linear-gradient(135deg, #0062ff, #004ed8); color: white; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 14px rgba(0, 98, 255, 0.35);"><?php echo __('eval_submit_btn'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Coach Session Notes Modal -->
<div id="coachSessionNotesModal" class="gymx-custom-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div style="background: white; border-radius: 24px; padding: 2.2rem; width: 92%; max-width: 540px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; animation: modalFadeIn 0.3s ease;">
        <button type="button" onclick="closeCoachNotesModal()" style="position: absolute; top: 18px; right: 18px; background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; font-size: 1rem; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
            <i class="fas fa-times"></i>
        </button>

        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 0.8rem auto; box-shadow: 0 8px 20px rgba(22, 163, 74, 0.2);">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 style="margin: 0 0 0.3rem 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?php echo __('coach_notes_modal_title'); ?></h3>
            <p id="coachNotesModalInfo" style="color: #64748b; font-size: 0.9rem; margin: 0; font-weight: 500;"></p>
        </div>

        <form id="coachNotesForm" onsubmit="submitCoachSessionNotes(event)">
            <input type="hidden" name="action" value="submit_coach_notes">
            <input type="hidden" name="booking_id" id="notesBookingId" value="">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px; font-size: 0.9rem;"><?php echo __('coach_notes_modal_label'); ?></label>
                <textarea name="coach_session_notes" id="coachSessionNotesText" rows="4" placeholder="<?php echo __('coach_notes_placeholder'); ?>" style="width: 100%; padding: 0.9rem 1.1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #cbd5e1; outline: none; font-size: 0.95rem; resize: vertical; box-sizing: border-box;" required></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem;">
                <button type="button" onclick="closeCoachNotesModal()" style="flex: 1; padding: 0.9rem; background: #f1f5f9; color: #475569; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer;"><?php echo __('cancel'); ?></button>
                <button type="submit" id="btnSubmitNotes" style="flex: 2; padding: 0.9rem; background: linear-gradient(135deg, #16a34a, #15803d); color: white; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);"><?php echo __('coach_notes_submit_btn'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
const currentLang = <?php echo json_encode($curr_lang); ?>;

const ratingLabels = currentLang === 'en' ? {
    1: '⭐ 1/5 - Needs significant improvement',
    2: '⭐⭐ 2/5 - Fair',
    3: '⭐⭐⭐ 3/5 - Good',
    4: '⭐⭐⭐⭐ 4/5 - Very Good',
    5: '⭐⭐⭐⭐⭐ 5/5 - Excellent'
} : {
    1: '⭐ 1/5 - Cần cải thiện nhiều',
    2: '⭐⭐ 2/5 - Tạm ổn',
    3: '⭐⭐⭐ 3/5 - Khá tốt',
    4: '⭐⭐⭐⭐ 4/5 - Rất tốt',
    5: '⭐⭐⭐⭐⭐ 5/5 - Tuyệt vời'
};

function setRating(val) {
    document.getElementById('evalRatingInput').value = val;
    document.getElementById('ratingText').innerText = ratingLabels[val] || (val + '/5');
    
    const stars = document.querySelectorAll('.star-rating-select .star-btn');
    stars.forEach((star, index) => {
        if (index < val) {
            star.style.color = '#f59e0b';
        } else {
            star.style.color = '#e2e8f0';
        }
    });
}

function openUserEvalModal(bookingId, sessionText, currentRating = 5, currentReview = '') {
    document.getElementById('evalBookingId').value = bookingId;
    document.getElementById('evalModalSessionInfo').innerText = sessionText || ((currentLang === 'en' ? 'Session ID: #' : 'Mã buổi tập: #') + bookingId);
    document.getElementById('evalReviewText').value = currentReview || '';
    setRating(currentRating || 5);
    
    const modal = document.getElementById('userSessionEvalModal');
    if (modal) modal.style.display = 'flex';
}

function closeUserEvalModal() {
    const modal = document.getElementById('userSessionEvalModal');
    if (modal) modal.style.display = 'none';
}

function openCoachNotesModal(bookingId, sessionText, currentNotes = '') {
    document.getElementById('notesBookingId').value = bookingId;
    document.getElementById('coachNotesModalInfo').innerText = sessionText || ((currentLang === 'en' ? 'Session ID: #' : 'Mã buổi tập: #') + bookingId);
    document.getElementById('coachSessionNotesText').value = currentNotes || '';
    
    const modal = document.getElementById('coachSessionNotesModal');
    if (modal) modal.style.display = 'flex';
}

function closeCoachNotesModal() {
    const modal = document.getElementById('coachSessionNotesModal');
    if (modal) modal.style.display = 'none';
}

function submitUserSessionEval(e) {
    e.preventDefault();
    const form = document.getElementById('userEvalForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSubmitEval');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (currentLang === 'en' ? 'Submitting...' : 'Đang gửi...');

    fetch('/Gym Manager/features/process_session_eval.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            closeUserEvalModal();
            if (typeof showGymXAlert === 'function') {
                showGymXAlert(data.message, currentLang === 'en' ? 'Review Submitted' : 'Đánh Giá Thành Công', '⭐');
            } else {
                alert(data.message);
            }
            if (typeof notifBell !== 'undefined' && notifBell) {
                location.reload();
            }
        } else {
            if (typeof showGymXAlert === 'function') {
                showGymXAlert(data.message, currentLang === 'en' ? 'Error' : 'Lỗi Đánh Giá', '❌');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert(currentLang === 'en' ? 'Connection error occurred. Please try again!' : 'Có lỗi kết nối xảy ra. Vui lòng thử lại!');
    });
}

function submitCoachSessionNotes(e) {
    e.preventDefault();
    const form = document.getElementById('coachNotesForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSubmitNotes');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (currentLang === 'en' ? 'Saving...' : 'Đang lưu...');

    fetch('/Gym Manager/features/process_session_eval.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            closeCoachNotesModal();
            if (typeof showGymXAlert === 'function') {
                showGymXAlert(data.message, currentLang === 'en' ? 'Notes Saved' : 'Ghi Chú Thành Công', '📝');
            } else {
                alert(data.message);
            }
            if (typeof notifBell !== 'undefined' && notifBell) {
                location.reload();
            }
        } else {
            if (typeof showGymXAlert === 'function') {
                showGymXAlert(data.message, currentLang === 'en' ? 'Error' : 'Lỗi Ghi Chú', '❌');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert(currentLang === 'en' ? 'Connection error occurred. Please try again!' : 'Có lỗi kết nối xảy ra. Vui lòng thử lại!');
    });
}
</script>

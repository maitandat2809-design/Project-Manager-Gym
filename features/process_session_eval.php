<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_eval_mailer.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'submit_user_review') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn số sao đánh giá hợp lệ (1 - 5 sao).']);
        exit;
    }

    if ($booking_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mã buổi tập không hợp lệ.']);
        exit;
    }

    // Verify booking belongs to this user
    $stmt = $pdo->prepare("SELECT b.*, c.id as coach_table_id FROM bookings b JOIN coaches c ON b.coach_id = c.id WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin buổi tập.']);
        exit;
    }

    if ($booking['user_id'] != $user_id && $role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền đánh giá buổi tập này.']);
        exit;
    }

    $end_ts = strtotime($booking['booking_date']) + 3600;
    $is_ended = (time() >= $end_ts || $booking['attendance'] === 'present' || $booking['status'] === 'completed');
    if (!$is_ended) {
        $msg = ($_SESSION['lang'] ?? 'vi') === 'en' 
            ? 'Session has not ended yet. You can only evaluate after the session time finishes!' 
            : 'Buổi tập chưa kết thúc. Bạn chỉ có thể đánh giá sau khi hết giờ tập!';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update booking with user evaluation
        $upd = $pdo->prepare("
            UPDATE bookings 
            SET user_rating = ?, user_review = ?, user_reviewed_at = NOW(), review_seen_by_coach = 0 
            WHERE id = ?
        ");
        $upd->execute([$rating, $review, $booking_id]);

        // Insert into coach_reviews table as well for coach overall rating
        $ins_review = $pdo->prepare("
            INSERT INTO coach_reviews (coach_id, user_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ins_review->execute([$booking['coach_id'], $booking['user_id'], $rating, $review]);

        $pdo->commit();

        // Send email to both User and Coach
        $email_sent = sendSessionReviewEmail($booking_id);

        echo json_encode([
            'success' => true, 
            'message' => 'Đánh giá buổi tập thành công! Thông báo đã được gửi qua email cho bạn và HLV.',
            'email_sent' => $email_sent
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu đánh giá: ' . $e->getMessage()]);
        exit;
    }
}

if ($action === 'submit_coach_notes') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $notes = trim($_POST['coach_session_notes'] ?? '');

    if (empty($notes)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung ghi chú buổi tập.']);
        exit;
    }

    if ($booking_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Mã buổi tập không hợp lệ.']);
        exit;
    }

    // Verify booking belongs to this coach or admin/manager
    $stmt = $pdo->prepare("
        SELECT b.*, c.user_id as coach_user_id 
        FROM bookings b 
        JOIN coaches c ON b.coach_id = c.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy buổi tập.']);
        exit;
    }

    if ($booking['coach_user_id'] != $user_id && !in_array($role, ['admin', 'manager'])) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền ghi chú cho buổi tập này.']);
        exit;
    }

    $end_ts = strtotime($booking['booking_date']) + 3600;
    $is_ended = (time() >= $end_ts || $booking['attendance'] === 'present' || $booking['status'] === 'completed');
    if (!$is_ended) {
        $msg = ($_SESSION['lang'] ?? 'vi') === 'en' 
            ? 'Session has not ended yet. You can only add notes after the session finishes!' 
            : 'Buổi tập chưa kết thúc. Bạn chỉ có thể ghi chú sau khi hết giờ tập!';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    try {
        $upd = $pdo->prepare("
            UPDATE bookings 
            SET coach_session_notes = ?, coach_notes_at = NOW(), notes_seen_by_user = 0 
            WHERE id = ?
        ");
        $upd->execute([$notes, $booking_id]);

        // Send email to both User and Coach
        $email_sent = sendCoachSessionNotesEmail($booking_id);

        echo json_encode([
            'success' => true, 
            'message' => 'Lưu ghi chú buổi tập thành công! Thông báo email đã được gửi đến Học viên và HLV.',
            'email_sent' => $email_sent
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu ghi chú: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
exit;

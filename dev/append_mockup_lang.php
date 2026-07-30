<?php
$en_file = 'languages/en.php';
$vi_file = 'languages/vi.php';

$en_content = file_get_contents($en_file);
$vi_content = file_get_contents($vi_file);

$en_keys = [
    "'fb_search' => 'Search on Facebook',",
    "'fb_community' => 'Community',",
    "'fb_group_home' => 'Group Home',",
    "'fb_discover' => 'Discover',",
    "'fb_joined_groups' => 'Groups you joined',",
    "'fb_private_group' => 'Private Group',",
    "'fb_members' => 'members',",
    "'fb_join_group' => 'Join Group',",
    "'fb_message' => 'Message',",
    "'fb_share' => 'Share',",
    "'fb_write_post' => 'Write something...',",
    "'fb_admin' => 'GymX Admin',",
    "'fb_hours_ago' => 'hours ago',",
    "'fb_post_content' => 'Welcome to the GymX Private community! 🔥<br><br>This is a virtual Fanpage (Mockup) created specifically on the Gym Manager system to keep users from being redirected away from the website. Thanks to this, you can easily manage the user flow and allow them to click the \"Back\" button conveniently!',",
    "'fb_like' => 'Like',",
    "'fb_comment' => 'Comment',",
    "'fb_viewing_mockup' => 'You are viewing the internal GymX Fanpage',",
    "'fb_back_web' => 'BACK TO WEBSITE',",
    "'fb_mockup_alert' => 'This feature is in Mockup interface. Please click the green \"BACK TO WEBSITE\" button at the bottom of the screen to return to the main website.',"
];

$vi_keys = [
    "'fb_search' => 'Tìm kiếm trên Facebook',",
    "'fb_community' => 'Cộng đồng',",
    "'fb_group_home' => 'Trang chủ nhóm',",
    "'fb_discover' => 'Khám phá',",
    "'fb_joined_groups' => 'Nhóm bạn đã tham gia',",
    "'fb_private_group' => 'Nhóm Riêng tư',",
    "'fb_members' => 'thành viên',",
    "'fb_join_group' => 'Tham gia nhóm',",
    "'fb_message' => 'Nhắn tin',",
    "'fb_share' => 'Chia sẻ',",
    "'fb_write_post' => 'Bạn viết gì đi...',",
    "'fb_admin' => 'Ban Quản Trị GymX',",
    "'fb_hours_ago' => 'giờ trước',",
    "'fb_post_content' => 'Chào mừng bạn đến với cộng đồng GymX Private! 🔥<br><br>Đây là trang Fanpage ảo (Mockup) được tạo riêng trên hệ thống của Gym Manager để giữ chân người dùng không bị điều hướng ra khỏi website. Nhờ vậy, bạn có thể dễ dàng quản lý luồng người dùng và cho phép họ bấm nút \"Quay lại\" một cách thuận tiện nhất!',",
    "'fb_like' => 'Thích',",
    "'fb_comment' => 'Bình luận',",
    "'fb_viewing_mockup' => 'Bạn đang xem trang Fanpage nội bộ của GymX',",
    "'fb_back_web' => 'QUAY LẠI TRANG WEB',",
    "'fb_mockup_alert' => 'Tính năng này đang trong giao diện mô phỏng (Mockup). Vui lòng nhấn nút màu xanh \"QUAY LẠI TRANG WEB\" ở dưới cùng màn hình để quay về website chính.',"
];

// Simple check to prevent double appending
if (strpos($en_content, 'fb_search') === false) {
    $en_content = preg_replace('/];\s*$/', "    " . implode("\n    ", $en_keys) . "\n];", $en_content);
    file_put_contents($en_file, $en_content);
    echo "Appended EN.\n";
}
if (strpos($vi_content, 'fb_search') === false) {
    $vi_content = preg_replace('/];\s*$/', "    " . implode("\n    ", $vi_keys) . "\n];", $vi_content);
    file_put_contents($vi_file, $vi_content);
    echo "Appended VI.\n";
}

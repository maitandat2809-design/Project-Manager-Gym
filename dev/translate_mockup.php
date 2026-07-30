<?php
$file = 'mock_fanpage.php';
$content = file_get_contents($file);

$replacements = [
    'lang="vi"' => 'lang="<?php echo $_SESSION[\'lang\']; ?>"',
    'Tìm kiếm trên Facebook' => '<?php echo __(\'fb_search\'); ?>',
    '<h3 style="margin: 0 0 15px 0; font-size: 24px;">Cộng đồng</h3>' => '<h3 style="margin: 0 0 15px 0; font-size: 24px;"><?php echo __(\'fb_community\'); ?></h3>',
    '<div style="font-weight: 600;">Trang chủ nhóm</div>' => '<div style="font-weight: 600;"><?php echo __(\'fb_group_home\'); ?></div>',
    '<div style="font-weight: 600;">Khám phá</div>' => '<div style="font-weight: 600;"><?php echo __(\'fb_discover\'); ?></div>',
    '<h4 style="color: #65676b; margin-bottom: 10px;">Nhóm bạn đã tham gia</h4>' => '<h4 style="color: #65676b; margin-bottom: 10px;"><?php echo __(\'fb_joined_groups\'); ?></h4>',
    'Nhóm Riêng tư' => '<?php echo __(\'fb_private_group\'); ?>',
    'thành viên' => '<?php echo __(\'fb_members\'); ?>',
    'Tham gia nhóm' => '<?php echo __(\'fb_join_group\'); ?>',
    '> Nhắn tin</button>' => '> <?php echo __(\'fb_message\'); ?></button>',
    '> Chia sẻ</button>' => '> <?php echo __(\'fb_share\'); ?></button>',
    'Bạn viết gì đi...' => '<?php echo __(\'fb_write_post\'); ?>',
    'Ban Quản Trị GymX' => '<?php echo __(\'fb_admin\'); ?>',
    'giờ trước' => '<?php echo __(\'fb_hours_ago\'); ?>',
    '> Thích</div>' => '> <?php echo __(\'fb_like\'); ?></div>',
    '> Bình luận</div>' => '> <?php echo __(\'fb_comment\'); ?></div>',
    '> Chia sẻ</div>' => '> <?php echo __(\'fb_share\'); ?></div>',
    'Bạn đang xem trang Fanpage nội bộ của GymX' => '<?php echo __(\'fb_viewing_mockup\'); ?>',
    'QUAY LẠI TRANG WEB' => '<?php echo __(\'fb_back_web\'); ?>',
    "Tính năng này đang trong giao diện mô phỏng (Mockup). Vui lòng nhấn nút màu xanh \"QUAY LẠI TRANG WEB\" ở dưới cùng màn hình để quay về website chính." => "<?php echo __(\'fb_mockup_alert\'); ?>"
];

// Add the include lang.php at the top
if (strpos($content, 'includes/lang.php') === false) {
    $content = str_replace(
        "    session_start();\n}",
        "    session_start();\n}\ninclude_once __DIR__ . '/includes/lang.php';",
        $content
    );
}

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Regex replace for the post content because of dynamic whitespaces
$content = preg_replace(
    '/<div class="post-text">\s*Chào mừng bạn đến với cộng đồng GymX Private! 🔥<br><br>\s*Đây là trang Fanpage ảo \(Mockup\) được tạo riêng trên hệ thống của Gym Manager để giữ chân người dùng không bị điều hướng ra khỏi website\. Nhờ vậy, bạn có thể dễ dàng quản lý luồng người dùng và cho phép họ bấm nút "Quay lại" một cách thuận tiện nhất!\s*<\/div>/',
    '<div class="post-text"><?php echo __(\'fb_post_content\'); ?></div>',
    $content
);

file_put_contents($file, $content);
echo "Translated.\n";

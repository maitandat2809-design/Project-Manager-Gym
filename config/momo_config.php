<?php
// ============================================================
// MoMo Payment Gateway Configuration
// 
// SANDBOX SETUP:
// 1. Đăng ký tài khoản tại: https://developers.momo.vn
// 2. Tạo ứng dụng mới trong Dashboard
// 3. Copy partnerCode, accessKey, secretKey vào đây
//
// SANDBOX TEST CREDENTIALS (chính thức từ MoMo):
//   partnerCode : MOMO
//   accessKey   : F8BBA842ECF85
//   secretKey   : K951B6PE1waEflDJdEFiPwAknVohUQ9y
// (Chỉ dùng được với endpoint test-payment.momo.vn)
// ============================================================

// MoMo Credentials
define('MOMO_PARTNER_CODE', 'MOMO');
define('MOMO_ACCESS_KEY',   'F8BBA842ECF85');
define('MOMO_SECRET_KEY',   'K951B6PE1waEflDJdEFiPwAknVohUQ9y');
define('MOMO_ENDPOINT',     'https://test-payment.momo.vn/v2/gateway/api/create');

// ⚠️  QUAN TRỌNG: URL KHÔNG được chứa khoảng trắng hay %20
// Apache Virtual Host nên map /gymmanager → thư mục dự án
// Hoặc dùng ngrok để có URL public cho IPN
// Ví dụ: https://xxxx.ngrok.io/api/momo_ipn.php

// Tự động detect base URL từ server
$_momo_host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

// Đường dẫn tương đối — dùng %2F encoded hoặc urlencode
// KHÔNG dùng '/Gym Manager/' (có khoảng trắng) mà phải dùng alias
// Cách 1: Apache alias /gym → c:/xampp/htdocs/Gym Manager
// Cách 2: Dùng ngrok tunnel
// Cách 3 (dev nhanh): dùng rawurlencode
define('MOMO_BASE_URL',     'http://localhost');

// Redirect URL — nơi user quay về sau khi quét QR
define('MOMO_REDIRECT_URL', 'http://localhost/gymmanager/api/momo_return.php');

// IPN URL — MoMo gọi server-to-server (phải là URL public khi production)
// Dùng ngrok cho môi trường local: ngrok http 80
// Rồi cập nhật: define('MOMO_IPN_URL', 'https://xxxx.ngrok.io/gymmanager/api/momo_ipn.php');
define('MOMO_IPN_URL',      'http://localhost/gymmanager/api/momo_ipn.php');

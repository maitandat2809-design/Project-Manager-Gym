<?php
/**
 * Payment Configuration - Casso + MB Bank
 * 
 * Để lấy API Key:
 * 1. Đăng nhập tại https://my.casso.vn
 * 2. Vào Settings > API
 * 3. Copy API Key và dán vào đây
 * 
 * Để lấy Webhook Secure Token:
 * 1. Vào Settings > Tích hợp > Webhook
 * 2. Thêm Webhook URL: https://yourdomain.com/Gym Manager/api/casso_webhook.php
 * 3. Copy Secure Token và dán vào đây
 */

define('CASSO_API_KEY', 'YOUR_CASSO_API_KEY_HERE');       // API Key từ my.casso.vn
define('CASSO_WEBHOOK_TOKEN', 'YOUR_CASSO_WEBHOOK_SECRET_TOKEN_HERE'); // Secure Token Webhook

// Tài khoản ngân hàng nhận tiền
define('BANK_ACCOUNT_NUMBER', '0364112108');               // Số TK MB Bank
define('BANK_ACCOUNT_NAME', 'GymxFiness');

// Prefix nội dung chuyển khoản để tự động nhận dạng
// Khách sẽ dùng format: MEM<subscription_id> ...
define('PAYMENT_PREFIX', 'MEM');

<?php
$vi_keys = [
    'overview' => 'Tổng quan',
    'product_management' => 'Quản lý sản phẩm',
    'coach_management' => 'Quản lý HLV',
    'booking_management' => 'Quản lý đặt lịch',
    'yearly_report' => 'Báo cáo năm',
    'user_management' => 'Quản lý người dùng',
    'system_overview' => 'Tổng quan hệ thống',
    'total_revenue' => 'Tổng doanh thu',
    'total_bookings' => 'Tổng lịch đặt',
    'total_users' => 'Tổng người dùng',
    'total_coaches' => 'Tổng HLV',
    'products_sold' => 'Sản phẩm đã bán',
    'select_year' => 'Chọn năm:',
    'appointments_in' => 'Lịch đặt năm',
    'products_purchased_in' => 'Sản phẩm mua năm',
    'date_time' => 'Ngày & Giờ',
    'client_name' => 'Tên Khách Hàng',
    'email' => 'Email',
    'phone' => 'Số Điện Thoại',
    'coach' => 'Huấn Luyện Viên',
    'status' => 'Trạng Thái',
    'date' => 'Ngày',
    'order_id' => 'Mã Đơn',
    'total_price' => 'Tổng Tiền',
    'items' => 'Sản Phẩm',
    'no_appointments' => 'Không có cuộc hẹn nào trong năm nay.',
    'no_orders' => 'Không có đơn hàng nào trong năm nay.',
    'client' => 'Khách Hàng',
    'notes' => 'Ghi Chú',
    'actions' => 'Thao Tác',
    'price' => 'Giá',
    'stock' => 'Kho',
    'sales' => 'Đã Bán',
    'product' => 'Sản Phẩm',
    'role' => 'Vai Trò',
    'joined_date' => 'Ngày Tham Gia',
    'confirm' => 'Xác nhận',
    'cancel' => 'Hủy',
    'edit' => 'Sửa',
    'delete' => 'Xóa',
    'reconfirm' => 'Khôi phục',
    'add_product' => 'Thêm Sản Phẩm',
    'update_product' => 'Cập Nhật Sản Phẩm'
];

$en_keys = [
    'overview' => 'Overview',
    'product_management' => 'Product Management',
    'coach_management' => 'Coach Management',
    'booking_management' => 'Booking Management',
    'yearly_report' => 'Yearly Report',
    'user_management' => 'User Management',
    'system_overview' => 'System Overview',
    'total_revenue' => 'Total Revenue',
    'total_bookings' => 'Total Bookings',
    'total_users' => 'Total Users',
    'total_coaches' => 'Total Coaches',
    'products_sold' => 'Products Sold',
    'select_year' => 'Select Year:',
    'appointments_in' => 'Appointments in',
    'products_purchased_in' => 'Products Purchased in',
    'date_time' => 'Date & Time',
    'client_name' => 'Client Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'coach' => 'Coach',
    'status' => 'Status',
    'date' => 'Date',
    'order_id' => 'Order ID',
    'total_price' => 'Total Price',
    'items' => 'Items',
    'no_appointments' => 'No appointments found for this year.',
    'no_orders' => 'No orders found for this year.',
    'client' => 'Client',
    'notes' => 'Notes',
    'actions' => 'Actions',
    'price' => 'Price',
    'stock' => 'Stock',
    'sales' => 'Sales',
    'product' => 'Product',
    'role' => 'Role',
    'joined_date' => 'Joined Date',
    'confirm' => 'Confirm',
    'cancel' => 'Cancel',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'reconfirm' => 'Re-confirm',
    'add_product' => 'Add Product',
    'update_product' => 'Update Product'
];

function appendLang($file, $keys) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Remove closing tag if exists
        $content = preg_replace('/\\?>\s*$/', '', $content);
        
        $new_vars = "";
        foreach ($keys as $k => $v) {
            $new_vars .= "    '$k' => '" . addslashes($v) . "',\n";
        }
        
        // Insert before the last closing bracket of $lang array
        if (preg_match('/(.*)(\];)(.*)/s', $content, $matches)) {
            $content = rtrim($matches[1], "\n,") . ",\n" . $new_vars . $matches[2] . $matches[3];
            file_put_contents($file, $content . "\n?>\n");
            echo "Updated $file\n";
        }
    }
}

appendLang('languages/vi.php', $vi_keys);
appendLang('languages/en.php', $en_keys);

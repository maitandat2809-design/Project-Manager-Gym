<?php
$files = ['manager.php', 'admin.php'];

$replacements = [
    // Sidebar
    '>Overview<' => '><?php echo __("overview"); ?><',
    '>Product Management<' => '><?php echo __("product_management"); ?><',
    '>Coach Management<' => '><?php echo __("coach_management"); ?><',
    '>Booking Management<' => '><?php echo __("booking_management"); ?><',
    '>Yearly Report<' => '><?php echo __("yearly_report"); ?><',
    '>User Management<' => '><?php echo __("user_management"); ?><',

    // Dashboard
    '>System Overview<' => '><?php echo __("system_overview"); ?><',
    '>Total Revenue<' => '><?php echo __("total_revenue"); ?><',
    '>Total Bookings<' => '><?php echo __("total_bookings"); ?><',
    '>Total Users<' => '><?php echo __("total_users"); ?><',
    '>Total Coaches<' => '><?php echo __("total_coaches"); ?><',
    '>Products Sold<' => '><?php echo __("products_sold"); ?><',

    // Yearly Report
    '>Select Year:<' => '><?php echo __("select_year"); ?><',
    '>Appointments in ' => '><?php echo __("appointments_in"); ?> ',
    '>Products Purchased in ' => '><?php echo __("products_purchased_in"); ?> ',
    '>Date & Time<' => '><?php echo __("date_time"); ?><',
    '>Client Name<' => '><?php echo __("client_name"); ?><',
    '>Email<' => '><?php echo __("email"); ?><',
    '>Phone<' => '><?php echo __("phone"); ?><',
    '>Coach<' => '><?php echo __("coach"); ?><',
    '>Status<' => '><?php echo __("status"); ?><',
    '>Date<' => '><?php echo __("date"); ?><',
    '>Order ID<' => '><?php echo __("order_id"); ?><',
    '>Total Price<' => '><?php echo __("total_price"); ?><',
    '>Items<' => '><?php echo __("items"); ?><',
    '>No appointments found for this year.<' => '><?php echo __("no_appointments"); ?><',
    '>No orders found for this year.<' => '><?php echo __("no_orders"); ?><',

    // Tables general
    '>Client<' => '><?php echo __("client"); ?><',
    '>Notes<' => '><?php echo __("notes"); ?><',
    '>Actions<' => '><?php echo __("actions"); ?><',
    '>Price<' => '><?php echo __("price"); ?><',
    '>Stock<' => '><?php echo __("stock"); ?><',
    '>Sales<' => '><?php echo __("sales"); ?><',
    '>Product<' => '><?php echo __("product"); ?><',
    '>Role<' => '><?php echo __("role"); ?><',
    '>Joined Date<' => '><?php echo __("joined_date"); ?><',
    
    // Buttons
    '> Confirm<' => '> <?php echo __("confirm"); ?><',
    '> Cancel<' => '> <?php echo __("cancel"); ?><',
    '> Edit<' => '> <?php echo __("edit"); ?><',
    '> Delete<' => '> <?php echo __("delete"); ?><',
    '> Re-confirm<' => '> <?php echo __("reconfirm"); ?><',
    '>Add Product<' => '><?php echo __("add_product"); ?><',
    '>Update Product<' => '><?php echo __("update_product"); ?><'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        file_put_contents($f, $content);
        echo "Updated $f\n";
    }
}

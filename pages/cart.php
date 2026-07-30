<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle updates
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: /Gym Manager/pages/cart.php");
    exit();
}

include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db.php';

$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $base_ids = array_unique(array_map(function($k) { return explode('_', $k)[0]; }, $ids));
    
    $placeholders = str_repeat('?,', count($base_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_values($base_ids));
    
    $db_products = [];
    foreach ($stmt->fetchAll() as $p) {
        $db_products[$p['id']] = $p;
    }

    foreach ($ids as $cart_key) {
        $parts = explode('_', $cart_key);
        $p_id = $parts[0];
        $weight = isset($parts[1]) ? $parts[1] : '';
        
        if (!isset($db_products[$p_id])) continue;
        $p = $db_products[$p_id];
        
        $price = $p['price'];
        $name_suffix = '';
        if ($weight === '1kg') { $price += 10; $name_suffix = ' (1 kg)'; }
        elseif ($weight === '2kg') { $price += 25; $name_suffix = ' (2 kg)'; }
        elseif ($weight === '5kg') { $price += 60; $name_suffix = ' (5 kg)'; }
        elseif ($weight === '500g') { $name_suffix = ' (500g)'; }

        $qty = $_SESSION['cart'][$cart_key];
        $subtotal = $price * $qty;
        $total += $subtotal;
        
        $p_name = (isset($current_lang) && $current_lang === 'vi' && !empty($p['name_vi'])) ? $p['name_vi'] : $p['name'];
        
        $cart_items[] = [
            'cart_key' => $cart_key,
            'id' => $p['id'],
            'name' => $p_name . $name_suffix,
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $subtotal,
            'image' => $p['image']
        ];
    }
}
?>

<section>
    <div class="section-title">
        <h2><?php echo __('shopping_cart'); ?></h2>
        <div class="underline"></div>
    </div>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-muted); font-size: 1.2rem;"><?php echo __('cart_empty'); ?></p>
            <a href="/Gym Manager/pages/shop.php" class="cta-btn" style="margin-top: 2rem; display: inline-block;"><?php echo __('go_shopping'); ?></a>
        </div>
    <?php else: ?>
        <div class="admin-grid" style="grid-template-columns: 1fr 350px;">
                <div style="background: white; border-radius: 25px; padding: 2rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                    <div class="table-responsive" style="margin-top: 0; border: none; box-shadow: none;">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo __('product'); ?></th>
                                    <th><?php echo __('price'); ?></th>
                                    <th><?php echo __('quantity'); ?></th>
                                    <th><?php echo __('subtotal'); ?></th>
                                    <th><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td style="display: flex; align-items: center; gap: 1.2rem;">
                                        <img src="<?php echo $item['image']; ?>" style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; background: white; padding: 2px; border: 1px solid var(--border);">
                                        <span style="font-weight: 700; color: var(--text-main);"><?php echo $item['name']; ?></span>
                                     </td>
                                    <td style="font-weight: 600;">$<?php echo $item['price']; ?></td>
                                    <td style="font-weight: 600;"><?php echo $item['qty']; ?></td>
                                    <td style="font-weight: 800; color: var(--primary);">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td><a href="/Gym Manager/pages/cart.php?remove=<?php echo $item['cart_key']; ?>" style="color: #ef4444; font-weight: 700; text-decoration: none;"><?php echo __('remove'); ?></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <div class="admin-sidebar" style="background: white; border-radius: 25px; padding: 2.5rem; box-shadow: var(--shadow-premium); border: 1px solid var(--border);">
                <h3 style="margin-bottom: 2rem; color: var(--text-main); font-weight: 800; font-size: 1.5rem;"><?php echo __('cart_totals'); ?></h3>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.2rem; color: var(--text-muted);">
                    <span><?php echo __('subtotal'); ?>:</span>
                    <span style="font-weight: 600; color: var(--text-main);">$<?php echo number_format($total, 2); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 2.5rem; font-weight: 800; color: var(--primary); font-size: 1.4rem; border-top: 2px solid var(--bg-main); padding-top: 1.5rem;">
                    <span><?php echo __('total'); ?>:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <a href="/Gym Manager/pages/checkout.php" class="btn-auth" style="display: block; text-align: center; text-decoration: none;"><?php echo __('proceed_checkout'); ?></a>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

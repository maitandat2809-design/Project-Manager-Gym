<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /Gym Manager/dashboards/admin.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';

// Handle Add to Cart & Buy Now
if (isset($_GET['add_to_cart']) || isset($_GET['buy_now'])) {
    $product_id = isset($_GET['buy_now']) ? $_GET['buy_now'] : $_GET['add_to_cart'];
    $is_buy_now = isset($_GET['buy_now']);
    $weight = isset($_GET['weight']) ? $_GET['weight'] : '';
    $qty = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;
    
    $cart_key = $weight ? $product_id . '_' . $weight : $product_id;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key] += $qty;
    } else {
        $_SESSION['cart'][$cart_key] = $qty;
    }
    
    if ($is_buy_now) {
        header("Location: /Gym Manager/pages/checkout.php");
    } else {
        header("Location: /Gym Manager/pages/shop.php?success=1");
    }
    exit();
}

include_once __DIR__ . '/../includes/header.php';

$search = $_GET['search'] ?? '';
$cat = $_GET['category'] ?? '';
$message = isset($_GET['success']) ? __('cart_success') : "";

// Build search query
$query = "
    SELECT p.*,
    COALESCE(p.name_vi, p.name) as name_vi,
    COALESCE(p.description_vi, p.description) as description_vi,
    COALESCE(AVG(r.rating), 0) as avg_rating, 
    COUNT(r.id) as review_count 
    FROM products p 
    LEFT JOIN product_reviews r ON p.id = r.product_id 
";

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($cat) && in_array($cat, ['supplement', 'accessory'])) {
    $where[] = "p.category = ?";
    $params[] = $cat;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY p.id ";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<section>
    <div class="section-title">
        <h2><?php echo __('shop_title'); ?></h2>
        <div class="underline"></div>
        <p style="margin-top: 10px; color: var(--text-muted);"><?php echo __('shop_subtitle'); ?></p>
    </div>

    <?php if($message): ?>
        <p style="color: #16a34a; text-align: center; margin-bottom: 2rem; background: #f0fdf4; padding: 1.2rem; border-radius: 20px; border: 1px solid #bbf7d0; font-weight: 600;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="search-container">
        <form action="shop.php" method="GET" class="search-form">
            <?php if(!empty($cat)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($cat); ?>">
            <?php endif; ?>
            <div class="search-group">
                <label for="product-search" class="search-label"><?php echo __('search_label'); ?></label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="product-search" name="search" placeholder="<?php echo __('search_products'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
        </form>
    </div>

    <!-- Category Filter Tabs -->
    <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; flex-wrap: wrap;">
        <a href="/Gym Manager/pages/shop.php?search=<?php echo urlencode($search); ?>" class="cta-btn" style="<?php echo empty($cat) ? '' : 'background: white; color: var(--primary); box-shadow: none; border: 2px solid var(--primary);'; ?> margin: 0; padding: 0.8rem 2rem; border-radius: 30px;"><?php echo __('all_products'); ?></a>
        <a href="/Gym Manager/pages/shop.php?category=supplement&search=<?php echo urlencode($search); ?>" class="cta-btn" style="<?php echo $cat == 'supplement' ? '' : 'background: white; color: var(--primary); box-shadow: none; border: 2px solid var(--primary);'; ?> margin: 0; padding: 0.8rem 2rem; border-radius: 30px;"><?php echo __('supplements'); ?></a>
        <a href="/Gym Manager/pages/shop.php?category=accessory&search=<?php echo urlencode($search); ?>" class="cta-btn" style="<?php echo $cat == 'accessory' ? '' : 'background: white; color: var(--primary); box-shadow: none; border: 2px solid var(--primary);'; ?> margin: 0; padding: 0.8rem 2rem; border-radius: 30px;"><?php echo __('accessories'); ?></a>
    </div>

    <?php if(empty($products)): ?>
        <div style="text-align: center; padding: 4rem; grid-column: 1 / -1;">
            <div style="font-size: 4rem; color: var(--border-bright); margin-bottom: 1.5rem;">
                <i class="fas fa-box-open"></i>
            </div>
            <h3 style="color: var(--text-main); font-weight: 700;"><?php echo __('no_products_found'); ?></h3>
            <p style="color: var(--text-muted);"><?php echo __('no_products_desc'); ?> "<?php echo htmlspecialchars($search); ?>".</p>
            <a href="/Gym Manager/pages/shop.php" class="btn-card" style="display: inline-block; margin-top: 1.5rem;"><?php echo __('view_all_products'); ?></a>
        </div>
    <?php endif; ?>

    <div class="grid shop-grid">
        <?php foreach($products as $product): ?>
            <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                <img src="<?php echo get_image_url($product['image']); ?>" class="card-img product-img" alt="<?php echo $product['name']; ?>" style="flex-shrink: 0;">
                <div class="card-content" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 5px;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <div class="card-tag"><?php echo ucfirst($product['category'] ?? 'Supplement'); ?></div>
                            <?php if($product['sales_count'] >= 5): ?>
                                <div class="card-tag" style="background: #ef4444; color: white;"><?php echo __('best_seller'); ?></div>
                            <?php endif; ?>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.8rem;"><?php echo $product['sales_count']; ?> <?php echo __('sold'); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo ($current_lang === 'vi' && !empty($product['name_vi'])) ? $product['name_vi'] : $product['name']; ?></h3>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="color: #f59e0b; display: flex; gap: 2px;">
                                <?php 
                                $rating = round($product['avg_rating']);
                                for($i=1; $i<=5; $i++) echo $i <= $rating ? "★" : "☆";
                                ?>
                            </div>
                            <a href="javascript:void(0);" onclick="openReviewModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" style="color: var(--primary); font-size: 0.8rem; text-decoration: underline; font-weight: 600;">(<?php echo $product['review_count']; ?> reviews)</a>
                        </div>
                        <span style="font-size: 0.8rem; font-weight: 700; color: <?php echo $product['stock'] > 0 ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo $product['stock'] > 0 ? __('stock_label') . $product['stock'] : __('out_of_stock'); ?>
                        </span>
                    </div>

                    <p style="margin-bottom: 1.5rem; flex-grow: 1;"><?php echo ($current_lang === 'vi' && !empty($product['description_vi'])) ? $product['description_vi'] : $product['description']; ?></p>
                    <div class="card-footer" style="margin-top: auto; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="price" style="font-size: 1.5rem;">$<?php echo $product['price']; ?></span>
                        </div>
                        <form id="form-prod-<?php echo $product['id']; ?>" action="shop.php" method="GET" style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="display: flex; flex-direction: column; width: 80px;">
                                    <input type="number" name="qty" value="1" min="1" max="<?php echo $product['stock'] > 0 ? $product['stock'] : 1; ?>" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; outline: none; background: #f8fafc; color: var(--text-main); text-align: center;">
                                </div>
                                <?php if (!empty($product['has_weight_variants']) && $product['has_weight_variants'] == 1): ?>
                                <select name="weight" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; width: 100%; outline: none; background: #f8fafc; color: var(--text-main); flex-grow: 1;">
                                    <option value="500g">500g (Standard)</option>
                                    <option value="1kg">1 kg (+ $10)</option>
                                    <option value="2kg">2 kg (+ $25)</option>
                                    <option value="5kg">5 kg (+ $60)</option>
                                </select>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 0.8rem; height: 45px;">
                                <button type="button" onclick="confirmPurchase(<?php echo $product['stock']; ?>, 'form-prod-<?php echo $product['id']; ?>', 'add_to_cart', <?php echo $product['id']; ?>)" class="btn-card" style="flex: 1; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-main); box-shadow: none; border: 1px solid #e2e8f0; font-size: 0.85rem; padding: 0 0.5rem; cursor: pointer; white-space: nowrap; height: 100%;"><?php echo __('add_to_cart'); ?></button>
                                <button type="button" onclick="confirmPurchase(<?php echo $product['stock']; ?>, 'form-prod-<?php echo $product['id']; ?>', 'buy_now', <?php echo $product['id']; ?>)" class="btn-card" style="flex: 1.2; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; padding: 0 0.5rem; cursor: pointer; border: none; white-space: nowrap; height: 100%;"><?php echo __('buy_now'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Review Modal -->
<div id="reviewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div style="background: white; padding: 2.5rem; border-radius: 25px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 80vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-weight: 800; color: var(--text-main); font-size: 1.3rem;"><?php echo __('product_reviews'); ?></h3>
            <button onclick="closeReviewModal()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        <h4 id="reviewModalTitle" style="color: var(--primary); margin: 0 0 1rem 0; font-size: 1rem;"></h4>
        
        <div id="reviewModalContent" style="overflow-y: auto; flex-grow: 1; padding-right: 10px;">
            <div style="text-align: center; color: #64748b;"><?php echo __('loading_reviews'); ?></div>
        </div>
    </div>
</div>

<script>
function openReviewModal(productId, productName) {
    document.getElementById('reviewModal').style.display = 'flex';
    document.getElementById('reviewModalTitle').innerText = productName;
    document.getElementById('reviewModalContent').innerHTML = '<div style="text-align: center; color: #64748b; padding: 2rem 0;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    
    fetch('get_reviews.php?product_id=' + productId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('reviewModalContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('reviewModalContent').innerHTML = '<p style="color: red; text-align: center;"><?php echo __('error_reviews'); ?></p>';
        });
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

// Custom Confirm logic
let currentFormToSubmit = null;
let currentActionName = '';
let currentProductId = '';

function confirmPurchase(stock, formId, actionName, productId) {
    if (stock <= 0) {
        showCustomModal('<?php echo addslashes(__('out_of_stock_msg')); ?>', '⏳', false);
    } else {
        let currentFormToSubmit = document.getElementById(formId);
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = actionName;
        input.value = productId;
        currentFormToSubmit.appendChild(input);
        currentFormToSubmit.submit();
    }
}

function showCustomModal(message, icon, showYesBtn) {
    document.getElementById('customConfirmModal').style.display = 'flex';
    document.getElementById('confirmMessage').innerText = message;
    document.getElementById('confirmIcon').innerText = icon;
    
    if (showYesBtn) {
        document.getElementById('btnConfirmYes').style.display = 'block';
    } else {
        document.getElementById('btnConfirmYes').style.display = 'none';
    }
}

function closeConfirmModal() {
    document.getElementById('customConfirmModal').style.display = 'none';
    currentFormToSubmit = null;
}

document.getElementById('btnConfirmYes').addEventListener('click', function() {
    if (currentFormToSubmit) {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = currentActionName;
        input.value = currentProductId;
        currentFormToSubmit.appendChild(input);
        currentFormToSubmit.submit();
    }
});
</script>

<!-- Custom Confirm Modal -->
<div id="customConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div style="background: white; padding: 2.5rem; border-radius: 25px; width: 90%; max-width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;">
        <div id="confirmIcon" style="font-size: 3rem; margin-bottom: 1rem;"></div>
        <h3 id="confirmMessage" style="margin: 0 0 1.5rem 0; font-weight: 700; color: var(--text-main); font-size: 1.2rem; line-height: 1.5;"></h3>
        <div style="display: flex; gap: 1rem; justify-content: center;" id="confirmButtons">
            <button id="btnConfirmYes" class="btn-auth" style="margin: 0; padding: 10px 25px; font-size: 1rem; width: auto;"><?php echo __('agree'); ?></button>
            <button onclick="closeConfirmModal()" style="background: #f1f5f9; color: var(--text-muted); border: none; padding: 10px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1rem;"><?php echo __('close'); ?></button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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
    
    // Check product stock in database
    $stmt_chk = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt_chk->execute([$product_id]);
    $prod_chk = $stmt_chk->fetch();
    $max_stock = $prod_chk ? intval($prod_chk['stock']) : 0;
    
    if ($max_stock <= 0) {
        header("Location: /Gym Manager/pages/shop.php");
        exit();
    }
    
    $qty = min($qty, $max_stock);
    
    $cart_key = $weight ? $product_id . '_' . $weight : $product_id;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key] = min($max_stock, $_SESSION['cart'][$cart_key] + $qty);
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

<style>
@keyframes toastSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.92); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

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
                            <div class="card-tag"><?php echo ($current_lang === 'vi') ? ($product['category'] === 'supplement' ? __('supplements') : ($product['category'] === 'accessory' ? __('accessories') : ucfirst($product['category']))) : ucfirst($product['category']); ?></div>
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
                            <a href="javascript:void(0);" onclick="openReviewModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes(($current_lang === 'vi' && !empty($product['name_vi'])) ? $product['name_vi'] : $product['name'])); ?>')" style="color: var(--primary); font-size: 0.8rem; text-decoration: underline; font-weight: 600;">(<?php echo $product['review_count']; ?> <?php echo __('reviews'); ?>)</a>
                        </div>
                        <span style="font-size: 0.8rem; font-weight: 700; color: <?php echo $product['stock'] > 0 ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo $product['stock'] > 0 ? __('stock_label') . $product['stock'] : __('out_of_stock'); ?>
                        </span>
                    </div>

                    <p style="margin-bottom: 1.5rem; flex-grow: 1;"><?php echo ($current_lang === 'vi' && !empty($product['description_vi'])) ? $product['description_vi'] : $product['description']; ?></p>
                    <div class="card-footer" style="margin-top: auto; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="price" style="font-size: 1.5rem;">$<?php echo $product['price']; ?> <span style="font-size: 0.95rem; color: var(--text-muted); font-weight: 600;">(~<?php echo number_format((int)round($product['price'] * 25000), 0, ',', '.'); ?>đ)</span></span>
                        </div>
                        <form id="form-prod-<?php echo $product['id']; ?>" action="shop.php" method="GET" style="display: flex; flex-direction: column; gap: 1rem; width: 100%;" novalidate>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="display: flex; flex-direction: column; width: 80px;">
                                    <input type="number" name="qty" value="1" min="1" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; outline: none; background: #f8fafc; color: var(--text-main); text-align: center;">
                                </div>
                                <?php if (!empty($product['has_weight_variants']) && $product['has_weight_variants'] == 1): ?>
                                <select name="weight" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; width: 100%; outline: none; background: #f8fafc; color: var(--text-main); flex-grow: 1;">
                                    <option value="500g">500g (<?php echo ($current_lang === 'vi') ? 'Tiêu chuẩn' : 'Standard'; ?>)</option>
                                    <option value="1kg">1 kg (+ $10)</option>
                                    <option value="2kg">2 kg (+ $25)</option>
                                    <option value="5kg">5 kg (+ $60)</option>
                                </select>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 0.8rem; height: 45px;">
                                <button type="button" onclick="confirmPurchase(<?php echo $product['stock']; ?>, 'form-prod-<?php echo $product['id']; ?>', 'add_to_cart', <?php echo $product['id']; ?>, this)" class="btn-card" style="flex: 1; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-main); box-shadow: none; border: 1px solid #e2e8f0; font-size: 0.85rem; padding: 0 0.5rem; cursor: pointer; white-space: nowrap; height: 100%; transition: all 0.25s;"><?php echo __('add_to_cart'); ?></button>
                                <button type="button" onclick="confirmPurchase(<?php echo $product['stock']; ?>, 'form-prod-<?php echo $product['id']; ?>', 'buy_now', <?php echo $product['id']; ?>, this)" class="btn-card" style="flex: 1.2; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; padding: 0 0.5rem; cursor: pointer; border: none; white-space: nowrap; height: 100%;"><?php echo __('buy_now'); ?></button>
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
    
    fetch('/Gym Manager/api/get_reviews.php?product_id=' + productId)
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

function updateNavCartBadge(count) {
    const badges = document.querySelectorAll('.nav-cart-btn .nav-badge');
    badges.forEach(b => {
        b.textContent = count;
        b.style.transform = 'scale(1.4)';
        b.style.transition = 'transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        setTimeout(() => {
            b.style.transform = 'scale(1)';
        }, 250);
    });
}

function showCartToast(productName, isEn) {
    let existing = document.getElementById('gymx-cart-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'gymx-cart-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 99999;
        background: #ffffff;
        border-radius: 20px;
        padding: 14px 18px;
        box-shadow: 0 16px 45px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        gap: 14px;
        max-width: 400px;
        font-family: 'Inter', sans-serif;
        animation: toastSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    `;

    const titleText = isEn ? 'Added to Cart!' : 'Đã thêm vào giỏ hàng!';
    const viewCartText = isEn ? 'View Cart' : 'Xem Giỏ Hàng';

    toast.innerHTML = `
        <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
            <i class="fas fa-check"></i>
        </div>
        <div style="flex-grow: 1; min-width: 0;">
            <div style="font-size: 0.88rem; font-weight: 800; color: #0f172a; line-height: 1.2;">${titleText}</div>
            <div style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${productName}</div>
        </div>
        <a href="/Gym Manager/pages/cart.php" style="background: #0f172a; color: white; padding: 8px 14px; border-radius: 10px; font-size: 0.78rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: opacity 0.2s;">
            ${viewCartText} →
        </a>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s, transform 0.4s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(15px)';
        setTimeout(() => toast.remove(), 400);
    }, 3200);
}

function confirmPurchase(stock, formId, actionName, productId, btnElement) {
    let form = document.getElementById(formId);
    if (!form) return;

    let isEn = (typeof GYMX_LANG !== 'undefined' ? GYMX_LANG === 'en' : false);

    if (stock <= 0) {
        let outMsg = isEn ? 'This product is currently out of stock. Please check back soon!' : 'Sản phẩm này hiện đang hết hàng. Vui lòng quay lại sau!';
        let title = isEn ? 'Out of Stock' : 'Hết Hàng';
        if (typeof showGymXAlert === 'function') {
            showGymXAlert(outMsg, title, '⏳');
        } else {
            showCustomModal(outMsg, '⏳', false);
        }
        return;
    }

    let qtyInput = form.querySelector('input[name="qty"]');
    let qty = parseInt(qtyInput ? qtyInput.value : 1);

    if (isNaN(qty) || qty < 1) {
        let minMsg = isEn ? 'Minimum order quantity is 1.' : 'Số lượng sản phẩm đặt mua tối thiểu là 1.';
        let title = isEn ? 'Quantity Warning' : 'Thông Báo Số Lượng';
        if (typeof showGymXAlert === 'function') {
            showGymXAlert(minMsg, title, '⚠️');
        } else {
            showCustomModal(minMsg, '⚠️', false);
        }
        if (qtyInput) qtyInput.value = 1;
        return;
    }

    if (qty > stock) {
        let maxMsg = isEn 
            ? ('Sorry! Requested quantity (' + qty + ') exceeds available stock (' + stock + ' items).')
            : ('Rất tiếc! Số lượng bạn chọn (' + qty + ') vượt quá số lượng hàng còn trong kho (' + stock + ' sản phẩm).');
        let title = isEn ? 'Stock Limit Exceeded' : 'Cảnh Báo Số Lượng Kho';
        if (typeof showGymXAlert === 'function') {
            showGymXAlert(maxMsg, title, '⚠️');
        } else {
            showCustomModal(maxMsg, '⚠️', false);
        }
        if (qtyInput) qtyInput.value = stock;
        return;
    }

    // BUY NOW: standard submit to checkout
    if (actionName === 'buy_now') {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = actionName;
        input.value = productId;
        form.appendChild(input);
        form.submit();
        return;
    }

    // ADD TO CART: AJAX without page reload!
    if (actionName === 'add_to_cart') {
        let weightSelect = form.querySelector('select[name="weight"]');
        let weight = weightSelect ? weightSelect.value : '';

        let originalContent = btnElement ? btnElement.innerHTML : '';
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        fetch('/Gym Manager/api/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                qty: qty,
                weight: weight
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update badge in navbar
                updateNavCartBadge(data.cart_count);

                // Button success animation
                if (btnElement) {
                    btnElement.style.background = '#dcfce7';
                    btnElement.style.color = '#15803d';
                    btnElement.style.borderColor = '#86efac';
                    btnElement.innerHTML = '<i class="fas fa-check"></i> ' + (isEn ? 'Added!' : 'Đã thêm!');
                    setTimeout(() => {
                        btnElement.innerHTML = originalContent;
                        btnElement.style.background = '#f1f5f9';
                        btnElement.style.color = 'var(--text-main)';
                        btnElement.style.borderColor = '#e2e8f0';
                        btnElement.disabled = false;
                    }, 1400);
                }

                // Show toast notification
                showCartToast(data.product_name, isEn);
            } else {
                if (btnElement) {
                    btnElement.innerHTML = originalContent;
                    btnElement.disabled = false;
                }
                alert(data.message || (isEn ? 'Failed to add to cart' : 'Không thể thêm vào giỏ hàng'));
            }
        })
        .catch(err => {
            console.error(err);
            if (btnElement) {
                btnElement.innerHTML = originalContent;
                btnElement.disabled = false;
            }
            // Fallback submit
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = actionName;
            input.value = productId;
            form.appendChild(input);
            form.submit();
        });
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

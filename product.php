<?php
require_once __DIR__ . '/includes/functions.php';

$productId = cleanInput($_GET['id'] ?? '');
$product = getProductById($productId);

if (!$product || empty($product['active'])) {
    http_response_code(404);
    if (is_file(__DIR__ . '/404.php')) {
        require_once __DIR__ . '/404.php';
        exit;
    }
    exit('المنتج غير موجود.');
}

// معالجة الإضافة إلى السلة عند إرسال النموذج
if (isPost() && ($_POST['action'] ?? '') === 'add_to_cart') {
    requireValidCsrf();

    $selectedSize = cleanInput($_POST['size'] ?? '');
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $availableSizes = is_array($product['sizes'] ?? null) ? $product['sizes'] : [];

    if (!empty($availableSizes) && $selectedSize === '') {
        setFlashMessage('error', 'يرجى اختيار المقاس المناسب قبل الإضافة إلى السلة.');
    } elseif ((int) ($product['stock'] ?? 0) <= 0) {
        setFlashMessage('error', 'عذراً، هذا المنتج غير متوفر حالياً في المخزون.');
    } else {
        if (addToCart((string) $product['id'], $selectedSize, $quantity)) {
            setFlashMessage('success', 'تمت إضافة المنتج إلى سلة التسوق بنجاح.');
            redirect('cart.php');
        } else {
            setFlashMessage('error', 'تعذرت إضافة المنتج إلى السلة، يرجى المحاولة مرة أخرى.');
        }
    }
}

$pageTitle = ($product['name'] ?? 'تفاصيل المنتج') . ' | VYRO®';
$pageDescription = $product['description'] ?? 'اكتشف مواصفات ومقاسات ' . ($product['name'] ?? 'المنتج');

$sizes = is_array($product['sizes'] ?? null) && !empty($product['sizes'])
    ? $product['sizes']
    : ['40', '41', '42', '43', '44', '45'];

$stock = (int) ($product['stock'] ?? 0);
$price = getProductPrice($product);
$hasSale = !empty($product['sale_price']) && (float) $product['sale_price'] < (float) ($product['price'] ?? 0);

// منتجات ذات صلة
$relatedProducts = array_values(
    array_filter(
        getProducts(),
        fn($p) => !empty($p['active']) && (string) ($p['id'] ?? '') !== (string) $product['id']
    )
);
$relatedProducts = array_slice($relatedProducts, 0, 4);

require_once __DIR__ . '/includes/header.php';
?>

<section class="product-detail-page section-padding">
    <div class="container">

        <nav class="breadcrumb">
            <a href="index.php">الرئيسية</a>
            <span>/</span>
            <a href="shop.php">المتجر</a>
            <span>/</span>
            <span><?= e($product['name'] ?? '') ?></span>
        </nav>

        <div class="product-detail-grid">
            <!-- صورة المنتج -->
            <div class="product-detail-media">
                <?php if ($hasSale): ?>
                    <span class="product-badge sale-badge">SALE</span>
                <?php elseif (isProductNew($product)): ?>
                    <span class="product-badge">NEW</span>
                <?php endif; ?>

                <img
                    src="<?= e($product['image'] ?? '') ?>"
                    alt="<?= e($product['name'] ?? '') ?>"
                >
            </div>

            <!-- تفاصيل المنتج وخيارات الشراء -->
            <div class="product-detail-info">
                <span class="eyebrow"><?= e(strtoupper($product['category'] ?? 'VYRO COLLECTION')) ?></span>
                <h1><?= e($product['name'] ?? '') ?></h1>

                <div class="product-detail-price">
                    <strong><?= e(formatPrice($price)) ?></strong>
                    <?php if ($hasSale): ?>
                        <del><?= e(formatPrice($product['price'])) ?></del>
                    <?php endif; ?>
                </div>

                <p class="product-detail-desc">
                    <?= e($product['description'] ?? 'حذاء رياضي عصري مصمم ليمنحك أقصى درجات الراحة والثبات في كل خطوة.') ?>
                </p>

                <div class="stock-status <?= $stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?= $stock > 0 ? '● متوفر في المخزون (' . $stock . ' قطعة)' : '● نفدت الكمية حالياً' ?>
                </div>

                <?php if ($stock > 0): ?>
                    <form method="POST" action="product.php?id=<?= e($product['id']) ?>" class="add-to-cart-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_to_cart">

                        <!-- اختيار المقاس -->
                        <div class="size-selector-group">
                            <label>اختر المقاس (EU):</label>
                            <div class="size-options">
                                <?php foreach ($sizes as $index => $size): ?>
                                    <label class="size-pill">
                                        <input
                                            type="radio"
                                            name="size"
                                            value="<?= e((string) $size) ?>"
                                            <?= $index === 0 ? 'checked' : '' ?>
                                            required
                                        >
                                        <span><?= e((string) $size) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- اختيار الكمية وزر الإضافة -->
                        <div class="purchase-actions">
                            <div class="qty-box">
                                <label for="quantity">الكمية:</label>
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?= min($stock, VYRO_MAX_QUANTITY) ?>"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary add-cart-submit">
                                أضف إلى السلة <span>←</span>
                            </button>

                            <button
                                type="button"
                                class="favorite-btn detail-fav-btn <?= isFavorite((string) $product['id']) ? 'is-favorite' : '' ?>"
                                data-favorite="<?= e((string) $product['id']) ?>"
                                aria-label="إضافة للمفضلة"
                                title="المفضلة"
                            >♡</button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="product-perks">
                    <div>✓ شحن سريع لجميع المناطق</div>
                    <div>✓ الدفع نقداً عند الاستلام</div>
                    <div>✓ إمكانية الاستبدال خلال 7 أيام</div>
                </div>
            </div>
        </div>

        <!-- منتجات قد تعجبك -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products-section">
                <div class="section-heading">
                    <div>
                        <span class="section-eyebrow">YOU MAY ALSO LIKE</span>
                        <h2>منتجات <span>مشابهة</span></h2>
                    </div>
                </div>

                <div class="products-grid">
                    <?php foreach ($relatedProducts as $rel): ?>
                        <article class="product-card">
                            <div class="product-image-wrap">
                                <a href="product.php?id=<?= e($rel['id']) ?>">
                                    <img
                                        src="<?= e($rel['image'] ?? '') ?>"
                                        alt="<?= e($rel['name'] ?? '') ?>"
                                        loading="lazy"
                                    >
                                </a>
                                <a href="product.php?id=<?= e($rel['id']) ?>" class="quick-view-btn">
                                    <span>عرض التفاصيل والمقاسات</span>
                                    <span>←</span>
                                </a>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= e(strtoupper($rel['category'] ?? 'VYRO')) ?></div>
                                <h3>
                                    <a href="product.php?id=<?= e($rel['id']) ?>">
                                        <?= e($rel['name'] ?? '') ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <strong><?= e(formatPrice(getProductPrice($rel))) ?></strong>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

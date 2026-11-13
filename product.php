
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$id = cleanInput($_GET['id'] ?? '');
$product = getProductById($id);

if (!$product || empty($product['active'])) {
    http_response_code(404);
    $pageTitle = 'Product Not Found | VYRO';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="empty-state section-padding">
        <div class="container">
            <h1>المنتج غير موجود</h1>
            <p>يبدو أن هذا المنتج لم يعد متاحاً.</p>
            <a href="shop.php" class="btn btn-primary">العودة إلى المتجر</a>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = ($product['name'] ?? 'Product') . ' | VYRO';

$sizes = $product['sizes'] ?? [];
$price = getProductPrice($product);
$oldPrice = (float)($product['price'] ?? 0);
$stock = max(0, (int)($product['stock'] ?? 0));

$relatedProducts = array_filter(
    getProducts(),
    fn($item) =>
        ($item['id'] ?? '') !== $id &&
        !empty($item['active']) &&
        ($item['category'] ?? '') === ($product['category'] ?? '')
);

$relatedProducts = array_slice(array_values($relatedProducts), 0, 4);

require_once __DIR__ . '/includes/header.php';
?>

<section class="product-page section-padding">

    <div class="container">

        <div class="product-layout">

            <div class="product-gallery reveal">

                <div class="product-main-image">

                    <img
                        src="<?= e($product['image'] ?? '') ?>"
                        alt="<?= e($product['name'] ?? 'VYRO Shoe') ?>"
                        id="mainProductImage"
                    >

                    <?php if (!empty($product['sale_price'])): ?>
                        <span class="product-badge">SALE</span>
                    <?php endif; ?>

                </div>

            </div>


            <div class="product-details reveal">

                <span class="eyebrow">
                    <?= e(strtoupper($product['category'] ?? 'VYRO COLLECTION')) ?>
                </span>

                <h1><?= e($product['name'] ?? '') ?></h1>

                <p class="product-description">
                    <?= e($product['description'] ?? 'Designed for movement. Built for everyday performance.') ?>
                </p>


                <div class="product-detail-price">

                    <strong><?= e(formatPrice($price)) ?></strong>

                    <?php if ($price < $oldPrice): ?>
                        <del><?= e(formatPrice($oldPrice)) ?></del>
                        <span class="sale-label">عرض خاص</span>
                    <?php endif; ?>

                </div>


                <div class="product-stock">

                    <?php if ($stock > 0): ?>

                        <span class="stock-dot"></span>
                        متوفر حالياً

                    <?php else: ?>

                        <span class="stock-dot out-of-stock"></span>
                        غير متوفر حالياً

                    <?php endif; ?>

                </div>


                <form action="cart.php" method="POST" class="product-form">

                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">

                    <div class="size-selector">

                        <div class="size-heading">
                            <label>اختر المقاس</label>
                            <span>EU SIZE</span>
                        </div>

                        <div class="size-options">

                            <?php foreach ($sizes as $size): ?>

                                <label class="size-option">

                                    <input
                                        type="radio"
                                        name="size"
                                        value="<?= e((string)$size) ?>"
                                        required
                                    >

                                    <span><?= e((string)$size) ?></span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <div class="quantity-selector">

                        <label for="quantity">الكمية</label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= min(VYRO_MAX_QUANTITY, $stock) ?>"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary add-to-cart-btn"
                        <?= $stock <= 0 ? 'disabled' : '' ?>
                    >
                        <?= $stock > 0 ? 'أضف إلى السلة' : 'غير متوفر' ?>
                    </button>

                </form>


                <div class="product-benefits">

                    <div>
                        <span>↗</span>
                        <p>تصميم يجمع الأداء والأناقة</p>
                    </div>

                    <div>
                        <span>✓</span>
                        <p>جودة مختارة بعناية</p>
                    </div>

                    <div>
                        <span>◇</span>
                        <p>تجربة تسوق سهلة وآمنة</p>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<?php if (!empty($relatedProducts)): ?>

<section class="related-products section-padding">

    <div class="container">

        <div class="section-heading">

            <div>
                <span class="eyebrow">YOU MAY ALSO LIKE</span>
                <h2>منتجات قد تعجبك</h2>
            </div>

            <a href="shop.php" class="text-link">كل المنتجات ←</a>

        </div>


        <div class="products-grid">

            <?php foreach ($relatedProducts as $item): ?>

                <article class="product-card reveal">

                    <div class="product-image">

                        <a href="product.php?id=<?= e($item['id']) ?>">

                            <img
                                src="<?= e($item['image'] ?? '') ?>"
                                alt="<?= e($item['name'] ?? 'VYRO Shoe') ?>"
                                loading="lazy"
                            >

                        </a>

                        <button
                            class="favorite-btn"
                            type="button"
                            data-favorite="<?= e($item['id']) ?>"
                            aria-label="إضافة إلى المفضلة"
                            aria-pressed="false"
                        >♡</button>

                    </div>

                    <div class="product-info">

                        <span class="product-category">
                            <?= e(strtoupper($item['category'] ?? 'VYRO')) ?>
                        </span>

                        <h3>
                            <a href="product.php?id=<?= e($item['id']) ?>">
                                <?= e($item['name'] ?? '') ?>
                            </a>
                        </h3>

                        <div class="product-price">

                            <strong>
                                <?= e(formatPrice(getProductPrice($item))) ?>
                            </strong>

                            <?php if (!empty($item['sale_price'])): ?>
                                <del>
                                    <?= e(formatPrice((float)$item['price'])) ?>
                                </del>
                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<?php endif; ?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

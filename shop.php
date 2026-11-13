<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'المتجر | VYRO®';

$category = cleanInput($_GET['category'] ?? '');
$search = cleanInput($_GET['q'] ?? '');

$products = searchProducts($search);

if ($category !== '') {
    $products = array_values(
        array_filter(
            $products,
            fn($p) => mb_strtolower($p['category'] ?? '') === mb_strtolower($category)
        )
    );
}

$categories = [
    '' => 'الكل',
    'running' => 'أحذية الجري',
    'lifestyle' => 'لايف ستايل',
    'performance' => 'الأداء الرياضي',
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="shop-page section-padding">
    <div class="container">

        <div class="shop-heading">
            <div>
                <span class="eyebrow">VYRO COLLECTION</span>
                <h1>المتجر</h1>
                <p>اكتشف جميع الأحذية المتوفرة واختر ما يناسب أسلوبك.</p>
            </div>

            <div class="shop-count">
                <strong><?= count($products) ?></strong>
                <span>منتج</span>
            </div>
        </div>

        <!-- شريط الفلترة والبحث -->
        <div class="shop-toolbar">
            <div class="shop-categories">
                <?php foreach ($categories as $catKey => $catLabel): ?>
                    <?php
                    $isActive = mb_strtolower($category) === mb_strtolower($catKey);
                    $url = $catKey === '' ? 'shop.php' : 'shop.php?category=' . urlencode($catKey);
                    ?>
                    <a href="<?= e($url) ?>" class="filter-chip <?= $isActive ? 'active' : '' ?>">
                        <?= e($catLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="shop.php" class="shop-search-form">
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($category) ?>">
                <?php endif; ?>
                <input
                    type="search"
                    name="q"
                    value="<?= e($search) ?>"
                    placeholder="ابحث عن حذاء..."
                    aria-label="بحث"
                >
                <button type="submit">بحث</button>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-cart-icon">◇</div>
                <h2>لا توجد منتجات مطابقة</h2>
                <p>جرب البحث بكلمة أخرى أو تصفح جميع الفئات.</p>
                <a href="shop.php" class="btn btn-primary">عرض كل المنتجات</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card reveal">
                        <div class="product-image-wrap">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <span class="product-badge sale-badge">SALE</span>
                            <?php elseif (isProductNew($product)): ?>
                                <span class="product-badge">NEW</span>
                            <?php endif; ?>

                            <button
                                class="favorite-btn <?= isFavorite($product['id']) ? 'is-favorite' : '' ?>"
                                type="button"
                                data-favorite="<?= e($product['id']) ?>"
                                aria-label="المفضلة"
                            >♡</button>

                            <a href="product.php?id=<?= e($product['id']) ?>">
                                <img
                                    src="<?= e($product['image'] ?? '') ?>"
                                    alt="<?= e($product['name'] ?? '') ?>"
                                    loading="lazy"
                                >
                            </a>

                            <a href="product.php?id=<?= e($product['id']) ?>" class="quick-view-btn">
                                <span>عرض التفاصيل والمقاسات</span>
                                <span>←</span>
                            </a>
                        </div>

                        <div class="product-info">
                            <div class="product-category"><?= e(strtoupper($product['category'] ?? 'VYRO')) ?></div>
                            <h3>
                                <a href="product.php?id=<?= e($product['id']) ?>">
                                    <?= e($product['name'] ?? '') ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <strong><?= e(formatPrice(getProductPrice($product))) ?></strong>
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                    <del><?= e(formatPrice($product['price'])) ?></del>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

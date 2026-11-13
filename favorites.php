
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Your Favorites | VYRO';

$favorites = $_SESSION['favorites'] ?? [];

if (!is_array($favorites)) {
    $favorites = [];
}

$favoriteProducts = [];

foreach ($favorites as $favoriteId) {

    $product = getProductById((string)$favoriteId);

    if ($product && !empty($product['active'])) {
        $favoriteProducts[] = $product;
    }

}

require_once __DIR__ . '/includes/header.php';
?>

<section class="favorites-page section-padding">

    <div class="container">

        <div class="shop-heading">

            <div>
                <span class="eyebrow">YOUR PERSONAL COLLECTION</span>
                <h1>منتجاتك المفضلة</h1>
                <p>كل اختياراتك المميزة في مكان واحد.</p>
            </div>

            <div class="shop-count">
                <strong><?= count($favoriteProducts) ?></strong>
                <span>منتج</span>
            </div>

        </div>


        <?php if (empty($favoriteProducts)): ?>

            <div class="empty-state">

                <div class="empty-cart-icon">♡</div>

                <h2>لم تضف أي منتج للمفضلة بعد</h2>

                <p>احفظ المنتجات التي تعجبك لتعود إليها لاحقاً.</p>

                <a href="shop.php" class="btn btn-primary">
                    اكتشف المنتجات
                </a>

            </div>

        <?php else: ?>

            <div class="products-grid">

                <?php foreach ($favoriteProducts as $product): ?>

                    <article class="product-card reveal">

                        <div class="product-image">

                            <a href="product.php?id=<?= e($product['id']) ?>">

                                <img
                                    src="<?= e($product['image'] ?? '') ?>"
                                    alt="<?= e($product['name'] ?? 'VYRO Shoe') ?>"
                                    loading="lazy"
                                >

                            </a>

                            <button
                                class="favorite-btn is-favorite"
                                type="button"
                                data-favorite="<?= e($product['id']) ?>"
                                aria-label="إزالة من المفضلة"
                                aria-pressed="true"
                            >♥</button>

                        </div>


                        <div class="product-info">

                            <span class="product-category">
                                <?= e(strtoupper($product['category'] ?? 'VYRO')) ?>
                            </span>

                            <h3>
                                <a href="product.php?id=<?= e($product['id']) ?>">
                                    <?= e($product['name'] ?? '') ?>
                                </a>
                            </h3>

                            <div class="product-price">

                                <strong>
                                    <?= e(formatPrice(getProductPrice($product))) ?>
                                </strong>

                                <?php if (!empty($product['sale_price'])): ?>

                                    <del>
                                        <?= e(formatPrice((float)$product['price'])) ?>
                                    </del>

                                <?php endif; ?>

                            </div>

                            <a
                                href="product.php?id=<?= e($product['id']) ?>"
                                class="product-link"
                            >
                                عرض المنتج ←
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

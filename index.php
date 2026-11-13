<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'VYRO® | Built To Move';
$featuredProducts = getFeaturedProducts();
$newProducts = getNewProducts();

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-content">
        <span class="hero-eyebrow">
            <span class="eyebrow-line"></span>
            NEW COLLECTION 2026
        </span>

        <h1 class="hero-title">
            صُممت<br>
            <span>للحركة.</span>
        </h1>

        <p class="hero-description">
            أحذية تجمع بين التصميم الجريء، الراحة الفائقة، والأداء اليومي.
            خطوتك القادمة تبدأ من هنا.
        </p>

        <div class="hero-buttons">
            <a href="shop.php" class="btn btn-primary">
                تسوق المجموعة <span>←</span>
            </a>
            <a href="about.php" class="btn btn-outline">
                اكتشف VYRO
            </a>
        </div>

        <div class="hero-stats">
            <div>
                <strong>+50</strong>
                <span>تصميم حصري</span>
            </div>
            <div>
                <strong>100%</strong>
                <span>راحة وأداء</span>
            </div>
            <div>
                <strong>24/7</strong>
                <span>دعم وتوصيل</span>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-floating-label">
            <span>LIMITED EDITION</span>
            <strong>FAST-RB NITRO ELITE 2</strong>
        </div>

        <div class="hero-image-wrap">
            <img
                src="assets/images/hero-shoe.png?v=3"
                alt="PUMA Fast-RB Nitro Elite 2 Black Lime"
                class="hero-image"
            >
        </div>

        <div class="hero-circle">VYRO®</div>
        <span class="hero-vertical-text">BUILT TO MOVE</span>
    </div>

    <div class="hero-scroll">
        <span class="scroll-line"></span>
        SCROLL TO EXPLORE
    </div>
</section>

<!-- BRAND MARQUEE STRIP -->
<div class="brand-strip">
    <div class="brand-strip-track">
        <span>BUILT TO MOVE</span>
        <span>✳</span>
        <span>VYRO® STREETWEAR</span>
        <span>✳</span>
        <span>NEXT-LEVEL COMFORT</span>
        <span>✳</span>
        <span>BOLD DESIGN</span>
        <span>✳</span>
        <span>BUILT TO MOVE</span>
        <span>✳</span>
        <span>VYRO® STREETWEAR</span>
        <span>✳</span>
        <span>NEXT-LEVEL COMFORT</span>
        <span>✳</span>
        <span>BOLD DESIGN</span>
    </div>
</div>

<!-- FEATURED PRODUCTS -->
<section class="section">
    <div class="section-heading reveal">
        <div>
            <span class="section-eyebrow">CURATED SELECTION</span>
            <h2>الأكثر <span>مبيعاً</span></h2>
            <p>اختياراتنا المميزة لإطلالة عصرية وأداء لا يتوقف.</p>
        </div>
        <a href="shop.php" class="text-link">
            عرض جميع المنتجات <span>←</span>
        </a>
    </div>

    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
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
</section>
<!-- EDITORIAL BANNER -->
<section class="editorial-section">
    <div class="editorial-image">
        <img
            src="https://pexels.com"
            alt="VYRO Editorial"
            loading="lazy"
        >
    </div>

    <div class="editorial-content reveal">
        <span class="section-eyebrow">VYRO PHILOSOPHY</span>
        <h2>
            أسلوبك.<br>
            <span>قواعدك.</span>
        </h2>
        <p>
            نصمم أحذية تواكب إيقاع المدينة السريع وتمنحك الثقة في كل خطوة،
            بتفاصيل بسيطة وخامات عالية الجودة.
        </p>
        <a href="about.php" class="btn btn-dark">
            اقرأ قصتنا <span>←</span>
        </a>
    </div>

    <span class="editorial-number">01 / VYRO CULTURE</span>
</section>

<!-- NEW ARRIVALS -->
<?php if (!empty($newProducts)): ?>
<section class="section new-arrivals-section">
    <div class="section-heading reveal">
        <div>
            <span class="section-eyebrow">JUST DROPPED</span>
            <h2>وصل <span>حديثاً</span></h2>
            <p>أحدث الإصدارات التي انضمت إلى عائلة VYRO.</p>
        </div>
        <a href="shop.php" class="text-link">
            تسوق الجديد <span>←</span>
        </a>
    </div>

    <div class="products-grid">
        <?php foreach ($newProducts as $product): ?>
            <article class="product-card reveal">
                <div class="product-image-wrap">
                    <span class="product-badge">NEW</span>

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
</section>
<?php endif; ?>

<!-- VALUES SECTION -->
<section class="values-section">
    <div class="values-heading reveal">
        <span class="section-eyebrow">WHY VYRO®</span>
        <h2>مصممة <span>لأجلك</span></h2>
    </div>

    <div class="values-grid">
        <article class="value-card reveal">
            <span class="value-number">01</span>
            <div class="value-icon">✳</div>
            <h3>خامات مختارة بعناية</h3>
            <p>نستخدم أفضل المواد لضمان المتانة والمرونة في كل حركة تقوم بها طوال اليوم.</p>
        </article>

        <article class="value-card reveal">
            <span class="value-number">02</span>
            <div class="value-icon">↗</div>
            <h3>تصميم عصري جريء</h3>
            <p>خطوط هندسية وتفاصيل فريدة تجعل حضورك لافتاً في كل مكان.</p>
        </article>

        <article class="value-card reveal">
            <span class="value-number">03</span>
            <div class="value-icon">◇</div>
            <h3>توصيل سريع وآمن</h3>
            <p>اطلب بكل سهولة وادفع عند الاستلام مع خدمة توصيل تغطي جميع المناطق.</p>
        </article>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta">
    <div class="cta-content reveal">
        <span class="section-eyebrow">READY TO MOVE?</span>
        <h2>ابدأ <span>حركتك</span> الآن</h2>
        <a href="shop.php" class="btn btn-dark">
            تصفح المتجر <span>←</span>
        </a>
    </div>
    <div class="cta-watermark">VYRO</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

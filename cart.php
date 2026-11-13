<?php
require_once __DIR__ . '/includes/functions.php';

// معالجة تعديل الكميات أو حذف المنتجات من السلة
if (isPost()) {
    requireValidCsrf();
    $action = cleanInput($_POST['action'] ?? '');
    $cartKey = cleanInput($_POST['cart_key'] ?? '');

    if ($action === 'update' && $cartKey !== '') {
        $quantity = (int) ($_POST['quantity'] ?? 1);
        updateCartQuantity($cartKey, $quantity);
        setFlashMessage('success', 'تم تحديث الكمية بنجاح.');
        redirect('cart.php');
    }

    if ($action === 'remove' && $cartKey !== '') {
        removeFromCart($cartKey);
        setFlashMessage('success', 'تم حذف المنتج من السلة.');
        redirect('cart.php');
    }

    if ($action === 'clear') {
        clearCart();
        setFlashMessage('success', 'تم تفريغ سلة التسوق بالكامل.');
        redirect('cart.php');
    }
}

$pageTitle = 'سلة التسوق | VYRO®';
$cartItems = getCartItems();
$cartTotal = getCartTotal();
$cartCount = getCartCount();

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-page section-padding">
    <div class="container">

        <div class="shop-heading">
            <div>
                <span class="eyebrow">YOUR BAG</span>
                <h1>سلة التسوق</h1>
                <p>راجع المنتجات والمقاسات المختارة قبل إتمام طلبك.</p>
            </div>

            <div class="shop-count">
                <strong><?= $cartCount ?></strong>
                <span>قطعة</span>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <div class="empty-cart-icon">◇</div>
                <h2>سلة التسوق فارغة حالياً</h2>
                <p>لم تقم بإضافة أي منتجات إلى سلتك بعد. استكشف أحدث الإصدارات في المتجر.</p>
                <a href="shop.php" class="btn btn-primary">
                    تصفح المتجر <span>←</span>
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- قائمة المنتجات في السلة -->
                <div class="cart-items-list">
                    <?php foreach ($cartItems as $item): ?>
                        <?php $product = $item['product']; ?>
                        <article class="cart-item">
                            <a href="product.php?id=<?= e($product['id']) ?>" class="cart-item-image">
                                <img
                                    src="<?= e($product['image'] ?? '') ?>"
                                    alt="<?= e($product['name'] ?? '') ?>"
                                >
                            </a>

                            <div class="cart-item-info">
                                <span class="product-category"><?= e(strtoupper($product['category'] ?? 'VYRO')) ?></span>
                                <h3>
                                    <a href="product.php?id=<?= e($product['id']) ?>">
                                        <?= e($product['name'] ?? '') ?>
                                    </a>
                                </h3>

                                <?php if (!empty($item['size'])): ?>
                                    <p>المقاس المختار: <strong>EU <?= e($item['size']) ?></strong></p>
                                <?php endif; ?>

                                <p>سعر القطعة: <strong><?= e(formatPrice($item['price'])) ?></strong></p>

                                <div class="cart-item-actions">
                                    <!-- تحديث الكمية -->
                                    <form method="POST" action="cart.php" style="display: flex; gap: 6px;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_key" value="<?= e($item['key']) ?>">
                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?= (int) $item['quantity'] ?>"
                                            min="1"
                                            max="<?= min((int) ($product['stock'] ?? 10), VYRO_MAX_QUANTITY) ?>"
                                            aria-label="الكمية"
                                        >
                                        <button type="submit" class="cart-update-btn">تحديث</button>
                                    </form>

                                    <!-- حذف المنتج -->
                                    <form method="POST" action="cart.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_key" value="<?= e($item['key']) ?>">
                                        <button type="submit" class="cart-remove-btn">حذف</button>
                                    </form>
                                </div>
                            </div>

                            <div class="cart-item-subtotal">
                                <strong><?= e(formatPrice($item['subtotal'])) ?></strong>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <form method="POST" action="cart.php" class="clear-cart-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit">تفريغ السلة بالكامل</button>
                    </form>
                </div>

                <!-- ملخص الطلب -->
                <aside class="cart-summary">
                    <h2>ملخص الطلب</h2>

                    <div class="summary-row">
                        <span>عدد القطع</span>
                        <strong><?= $cartCount ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>المجموع الفرعي</span>
                        <strong><?= e(formatPrice($cartTotal)) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>الشحن والتوصيل</span>
                        <strong>مجاني</strong>
                    </div>

                    <div class="summary-total">
                        <strong>الإجمالي النهائي</strong>
                        <strong><?= e(formatPrice($cartTotal)) ?></strong>
                    </div>

                    <a href="checkout.php" class="btn btn-primary">
                        إتمام الطلب <span>←</span>
                    </a>

                    <a href="shop.php" class="continue-shopping">
                        ← متابعة التسوق
                    </a>
                </aside>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

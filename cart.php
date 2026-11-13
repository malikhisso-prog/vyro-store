
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (isPost()) {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $action = cleanInput($_POST['action'] ?? '');

    if ($action === 'add') {

        $productId = cleanInput($_POST['product_id'] ?? '');
        $size = cleanInput($_POST['size'] ?? '');
        $quantity = filter_var(
            $_POST['quantity'] ?? 1,
            FILTER_VALIDATE_INT
        );

        $product = getProductById($productId);

        if (!$product || empty($product['active'])) {

            setFlashMessage('error', 'المنتج غير متوفر.');

        } elseif (
            !in_array($size, array_map('strval', $product['sizes'] ?? []), true)
        ) {

            setFlashMessage('error', 'المقاس المحدد غير صالح.');

        } elseif ($quantity === false || $quantity < 1) {

            setFlashMessage('error', 'الكمية المحددة غير صالحة.');

        } else {

            $result = addToCart(
                $productId,
                $size,
                min($quantity, VYRO_MAX_QUANTITY)
            );

            if ($result) {
                setFlashMessage('success', 'تمت إضافة المنتج إلى السلة.');
            } else {
                setFlashMessage('error', 'تعذر إضافة المنتج. تحقق من الكمية المتوفرة.');
            }

        }

        redirect('cart.php');

    }


    if ($action === 'update') {

        $cartKey = cleanInput($_POST['cart_key'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? 1,
            FILTER_VALIDATE_INT
        );

        if ($quantity === false || $quantity < 1) {

            setFlashMessage('error', 'الكمية غير صالحة.');

        } else {

            $result = updateCartQuantity(
                $cartKey,
                min($quantity, VYRO_MAX_QUANTITY)
            );

            setFlashMessage(
                $result ? 'success' : 'error',
                $result ? 'تم تحديث السلة.' : 'تعذر تحديث الكمية.'
            );

        }

        redirect('cart.php');

    }


    if ($action === 'remove') {

        $cartKey = cleanInput($_POST['cart_key'] ?? '');

        removeFromCart($cartKey);

        setFlashMessage('success', 'تم حذف المنتج من السلة.');

        redirect('cart.php');

    }


    if ($action === 'clear') {

        clearCart();

        setFlashMessage('success', 'تم إفراغ السلة.');

        redirect('cart.php');

    }

    redirect('cart.php');
}


$cartItems = getCartItems();
$cartTotal = getCartTotal();

require_once __DIR__ . '/includes/header.php';
?>

<section class="cart-page section-padding">

    <div class="container">

        <div class="shop-heading">

            <div>
                <span class="eyebrow">YOUR SELECTION</span>
                <h1>سلة التسوق</h1>
                <p>كل خطوة تبدأ باختيارك.</p>
            </div>

            <div class="shop-count">
                <strong><?= count($cartItems) ?></strong>
                <span>منتج</span>
            </div>

        </div>


        <?php if (empty($cartItems)): ?>

            <div class="empty-state">

                <div class="empty-cart-icon">◇</div>

                <h2>سلتك فارغة حالياً</h2>

                <p>اكتشف مجموعتنا واختر حذاءك القادم.</p>

                <a href="shop.php" class="btn btn-primary">
                    اكتشف المنتجات
                </a>

            </div>

        <?php else: ?>

            <div class="cart-layout">

                <div class="cart-items">

                    <?php foreach ($cartItems as $item): ?>

                        <?php
                        $product = $item['product'] ?? [];
                        $cartKey = $item['key'] ?? '';
                        $quantity = (int)($item['quantity'] ?? 1);
                        $itemPrice = (float)($item['price'] ?? getProductPrice($product));
                        ?>

                        <article class="cart-item">

                            <a
                                href="product.php?id=<?= e($product['id'] ?? '') ?>"
                                class="cart-item-image"
                            >

                                <img
                                    src="<?= e($product['image'] ?? '') ?>"
                                    alt="<?= e($product['name'] ?? 'VYRO Shoe') ?>"
                                >

                            </a>


                            <div class="cart-item-info">

                                <span class="product-category">
                                    <?= e(strtoupper($product['category'] ?? 'VYRO')) ?>
                                </span>

                                <h3>
                                    <?= e($product['name'] ?? 'Product') ?>
                                </h3>

                                <p>المقاس: <?= e((string)($item['size'] ?? '')) ?></p>

                                <strong>
                                    <?= e(formatPrice($itemPrice)) ?>
                                </strong>


                                <div class="cart-item-actions">

                                    <form method="POST" action="cart.php">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update"
                                        >

                                        <input
                                            type="hidden"
                                            name="cart_key"
                                            value="<?= e($cartKey) ?>"
                                        >

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?= $quantity ?>"
                                            min="1"
                                            max="<?= VYRO_MAX_QUANTITY ?>"
                                            aria-label="الكمية"
                                        >

                                        <button type="submit" class="cart-update-btn">
                                            تحديث
                                        </button>

                                    </form>


                                    <form method="POST" action="cart.php">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="remove"
                                        >

                                        <input
                                            type="hidden"
                                            name="cart_key"
                                            value="<?= e($cartKey) ?>"
                                        >

                                        <button type="submit" class="cart-remove-btn">
                                            حذف
                                        </button>

                                    </form>

                                </div>

                            </div>


                            <div class="cart-item-total">

                                <span>الإجمالي</span>

                                <strong>
                                    <?= e(formatPrice($itemPrice * $quantity)) ?>
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>


                    <form method="POST" action="cart.php" class="clear-cart-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="clear"
                        >

                        <button type="submit">
                            إفراغ السلة
                        </button>

                    </form>

                </div>


                <aside class="cart-summary">

                    <span class="eyebrow">ORDER SUMMARY</span>

                    <h2>ملخص الطلب</h2>

                    <div class="summary-row">
                        <span>عدد المنتجات</span>
                        <strong><?= getCartCount() ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>المجموع الفرعي</span>
                        <strong><?= e(formatPrice($cartTotal)) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>الشحن</span>
                        <strong>يحدد لاحقاً</strong>
                    </div>

                    <div class="summary-total">
                        <span>الإجمالي</span>
                        <strong><?= e(formatPrice($cartTotal)) ?></strong>
                    </div>

                    <a href="checkout.php" class="btn btn-primary">
                        متابعة إتمام الطلب
                    </a>

                    <a href="shop.php" class="continue-shopping">
                        متابعة التسوق ←
                    </a>

                </aside>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$cartItems = getCartItems();

if (empty($cartItems)) {
    setFlashMessage('error', 'سلة التسوق فارغة.');
    redirect('shop.php');
}

$errors = [];

$name = '';
$phone = '';
$city = '';
$address = '';
$notes = '';

if (isPost()) {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token.');
    }

    $name = cleanInput($_POST['name'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $city = cleanInput($_POST['city'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'يرجى إدخال الاسم الكامل بشكل صحيح.';
    }

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors[] = 'يرجى إدخال رقم هاتف صحيح.';
    }

    if (mb_strlen($city) < 2 || mb_strlen($city) > 80) {
        $errors[] = 'يرجى إدخال المدينة بشكل صحيح.';
    }

    if (mb_strlen($address) < 5 || mb_strlen($address) > 250) {
        $errors[] = 'يرجى إدخال عنوان تفصيلي.';
    }

    if (mb_strlen($notes) > 500) {
        $errors[] = 'الملاحظات يجب ألا تتجاوز 500 حرف.';
    }

    if (empty($errors)) {

        $latestCartItems = getCartItems();

        if (empty($latestCartItems)) {
            setFlashMessage('error', 'سلة التسوق فارغة.');
            redirect('cart.php');
        }

        $orderItems = [];
        $orderTotal = 0;

        foreach ($latestCartItems as $item) {

            $product = $item['product'] ?? [];
            $productId = $product['id'] ?? '';
            $quantity = (int)($item['quantity'] ?? 0);
            $size = (string)($item['size'] ?? '');

            $currentProduct = getProductById($productId);

            if (
                !$currentProduct ||
                empty($currentProduct['active']) ||
                $quantity < 1 ||
                !in_array($size, array_map('strval', $currentProduct['sizes'] ?? []), true) ||
                (int)($currentProduct['stock'] ?? 0) < $quantity
            ) {
                $errors[] = 'أحد المنتجات لم يعد متوفراً بالكمية أو المقاس المطلوب.';
                break;
            }

            $unitPrice = getProductPrice($currentProduct);

            $orderItems[] = [
                'product_id' => $productId,
                'name' => $currentProduct['name'] ?? '',
                'image' => $currentProduct['image'] ?? '',
                'size' => $size,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity
            ];

            $orderTotal += $unitPrice * $quantity;
        }

        if (empty($errors)) {

            $ordersFile = VYRO_DATA . '/orders.json';

            $orders = readJsonFile($ordersFile, []);

            if (!is_array($orders)) {
                $orders = [];
            }

            $orderNumber = generateOrderNumber();

            $order = [
                'order_number' => $orderNumber,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'customer' => [
                    'name' => $name,
                    'phone' => $phone,
                    'city' => $city,
                    'address' => $address,
                    'notes' => $notes
                ],
                'items' => $orderItems,
                'subtotal' => $orderTotal,
                'shipping' => null,
                'total' => $orderTotal,
                'currency' => VYRO_CURRENCY,
                'payment_method' => 'cash_on_delivery'
            ];

            $orders[] = $order;

            if (writeJsonFile($ordersFile, $orders)) {

                clearCart();

                $_SESSION['last_order_number'] = $orderNumber;

                redirect('order-success.php');

            } else {

                $errors[] = 'تعذر حفظ الطلب حالياً. يرجى المحاولة مرة أخرى.';

            }
        }
    }
}

$cartTotal = getCartTotal();

require_once __DIR__ . '/includes/header.php';
?>

<section class="checkout-page section-padding">

    <div class="container">

        <div class="shop-heading">

            <div>
                <span class="eyebrow">FINAL STEP</span>
                <h1>إتمام الطلب</h1>
                <p>أدخل معلومات التوصيل لإكمال طلبك.</p>
            </div>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="checkout-errors">

                <?php foreach ($errors as $error): ?>

                    <p><?= e($error) ?></p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <div class="checkout-layout">

            <form method="POST" action="checkout.php" class="checkout-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >


                <div class="checkout-section">

                    <span class="eyebrow">01 / CUSTOMER DETAILS</span>

                    <h2>معلومات العميل</h2>

                    <div class="form-group">

                        <label for="name">الاسم الكامل</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($name) ?>"
                            placeholder="أدخل اسمك الكامل"
                            minlength="3"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="phone">رقم الهاتف</label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= e($phone) ?>"
                            placeholder="+963 9XX XXX XXX"
                            minlength="7"
                            maxlength="20"
                            autocomplete="tel"
                            required
                        >

                    </div>

                </div>


                <div class="checkout-section">

                    <span class="eyebrow">02 / DELIVERY DETAILS</span>

                    <h2>عنوان التوصيل</h2>

                    <div class="form-group">

                        <label for="city">المدينة</label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?= e($city) ?>"
                            placeholder="مثال: حلب"
                            minlength="2"
                            maxlength="80"
                            autocomplete="address-level2"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="address">العنوان بالتفصيل</label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            minlength="5"
                            maxlength="250"
                            placeholder="المنطقة، الشارع، أقرب نقطة دلالة..."
                            autocomplete="street-address"
                            required
                        ><?= e($address) ?></textarea>

                    </div>


                    <div class="form-group">

                        <label for="notes">ملاحظات إضافية (اختياري)</label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            maxlength="500"
                            placeholder="أي تفاصيل تساعدنا في توصيل طلبك..."
                        ><?= e($notes) ?></textarea>

                    </div>

                </div>


                <div class="checkout-section payment-section">

                    <span class="eyebrow">03 / PAYMENT</span>

                    <h2>طريقة الدفع</h2>

                    <div class="payment-option">

                        <span class="payment-icon">₵</span>

                        <div>
                            <strong>الدفع عند الاستلام</strong>
                            <p>ادفع نقداً عند وصول طلبك.</p>
                        </div>

                        <span class="payment-check">✓</span>

                    </div>

                </div>


                <button type="submit" class="btn btn-primary checkout-submit">
                    تأكيد الطلب
                </button>

            </form>


            <aside class="checkout-summary">

                <span class="eyebrow">YOUR ORDER</span>

                <h2>ملخص طلبك</h2>


                <?php foreach ($cartItems as $item): ?>

                    <?php
                    $product = $item['product'] ?? [];
                    $quantity = (int)($item['quantity'] ?? 1);
                    $unitPrice = (float)($item['price'] ?? getProductPrice($product));
                    ?>

                    <div class="checkout-product">

                        <img
                            src="<?= e($product['image'] ?? '') ?>"
                            alt="<?= e($product['name'] ?? 'VYRO Shoe') ?>"
                        >

                        <div>

                            <strong><?= e($product['name'] ?? '') ?></strong>

                            <span>
                                المقاس <?= e((string)($item['size'] ?? '')) ?>
                                × <?= $quantity ?>
                            </span>

                        </div>

                        <strong>
                            <?= e(formatPrice($unitPrice * $quantity)) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>


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

                <p class="checkout-note">
                    سيتم تأكيد تفاصيل التوصيل معك قبل إرسال الطلب.
                </p>

            </aside>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

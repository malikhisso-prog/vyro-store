
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$orderNumber = $_SESSION['last_order_number'] ?? '';

if ($orderNumber === '') {
    redirect('index.php');
}

unset($_SESSION['last_order_number']);

$pageTitle = 'Order Confirmed | VYRO';

require_once __DIR__ . '/includes/header.php';
?>

<section class="order-success-page section-padding">

    <div class="container">

        <div class="order-success-card reveal">

            <div class="success-icon">
                <span>✓</span>
            </div>

            <span class="eyebrow">ORDER CONFIRMED</span>

            <h1>طلبك أصبح في الطريق!</h1>

            <p>
                شكراً لاختيارك VYRO.
                تم استلام طلبك بنجاح وسنتواصل معك لتأكيد تفاصيل التوصيل.
            </p>

            <div class="order-number-box">

                <span>رقم طلبك</span>

                <strong><?= e($orderNumber) ?></strong>

            </div>

            <div class="success-info">

                <div>
                    <span>✓</span>
                    <p>تم استلام الطلب</p>
                </div>

                <div>
                    <span>✓</span>
                    <p>الدفع عند الاستلام</p>
                </div>

                <div>
                    <span>↗</span>
                    <p>سيتم التواصل معك قريباً</p>
                </div>

            </div>

            <a href="shop.php" class="btn btn-primary">
                تابع اكتشاف VYRO
            </a>

            <a href="index.php" class="success-home-link">
                العودة إلى الصفحة الرئيسية
            </a>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

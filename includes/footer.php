
<?php
declare(strict_types=1);
?>

</main>

<footer class="site-footer">

    <div class="footer-main">

        <div class="footer-brand">
            <a href="index.php" class="footer-logo">VYRO<span>®</span></a>
            <p>
                أكثر من مجرد حذاء.
                أسلوب حياة، طاقة، وحركة بلا حدود.
            </p>

            <div class="social-links">
                <a href="#" aria-label="Instagram">IG</a>
                <a href="#" aria-label="TikTok">TK</a>
                <a href="#" aria-label="Facebook">FB</a>
            </div>
        </div>

        <div class="footer-column">
            <h3>اكتشف VYRO</h3>
            <a href="shop.php">جميع المنتجات</a>
            <a href="shop.php?category=running">أحذية الجري</a>
            <a href="shop.php?category=lifestyle">لايف ستايل</a>
            <a href="shop.php?category=performance">الأداء الرياضي</a>
        </div>

        <div class="footer-column">
            <h3>معلومات</h3>
            <a href="about.php">عن VYRO</a>
            <a href="contact.php">تواصل معنا</a>
            <a href="shipping.php">الشحن والتوصيل</a>
            <a href="returns.php">سياسة الاستبدال والإرجاع</a>
        </div>

        <div class="footer-column footer-newsletter">
            <h3>ابقَ في الحركة</h3>
            <p>اكتشف أحدث الإصدارات والعروض الحصرية.</p>

            <form class="newsletter-form" action="#" method="post">
                <input
                    type="email"
                    name="email"
                    placeholder="بريدك الإلكتروني"
                    aria-label="البريد الإلكتروني"
                    required
                >
                <button type="submit" aria-label="اشتراك">←</button>
            </form>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© <?= date('Y') ?> VYRO. جميع الحقوق محفوظة.</p>
        <p>BUILT TO MOVE. <span>®</span></p>
    </div>

</footer>

</body>
</html>

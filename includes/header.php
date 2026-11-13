<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? 'VYRO - Built To Move. اكتشف أحدث الأحذية الرياضية والشبابية.';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$cartCount = getCartCount();
$favoritesCount = getFavoritesCount();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?></title>

    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <script src="assets/js/main.js?v=2" defer></script>
</head>
<body>

<div class="announcement-bar">
    <span>BUILT TO MOVE — شحن سريع لجميع الطلبات</span>
    <a href="shop.php">تسوق الآن</a>
</div>

<header class="site-header" id="siteHeader">
    <div class="header-inner">

        <button class="menu-toggle" id="menuToggle" type="button" aria-label="القائمة" aria-expanded="false">
            <span></span>
            <span></span>
        </button>

        <a href="index.php" class="brand-logo" aria-label="VYRO Home">
            VYRO<span>®</span>
        </a>

        <nav class="main-nav" id="mainNav">
            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">الرئيسية</a>
            <a href="shop.php" class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>">المتجر</a>
            <a href="about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">من نحن</a>
            <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">تواصل معنا</a>
        </nav>

        <div class="header-actions">
            <a href="favorites.php" class="header-icon" aria-label="المفضلة" title="المفضلة">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <?php if ($favoritesCount > 0): ?>
                    <span class="cart-count"><?= $favoritesCount ?></span>
                <?php endif; ?>
            </a>

            <a href="cart.php" class="header-icon" aria-label="السلة" title="سلة التسوق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>

    </div>
</header>

<?php if ($flash): ?>
    <div class="flash-message <?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<main class="page-content">

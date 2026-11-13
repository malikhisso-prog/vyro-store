
<?php
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once __DIR__ . '/../auth.php';

requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$pageTitle = $pageTitle ?? 'لوحة التحكم';

$adminNav = [
    'dashboard.php' => 'الرئيسية',
    'products.php' => 'المنتجات',
    'orders.php' => 'الطلبات',
    'messages.php' => 'الرسائل',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> | VYRO® ADMIN</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <script src="../../assets/js/admin.js" defer></script>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-logo" href="dashboard.php">VYRO® ADMIN</a>

        <nav class="admin-nav">
            <?php foreach ($adminNav as $file => $label): ?>
                <a href="<?= e($file) ?>" class="<?= $currentPage === $file ? 'active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>

            <a href="../../index.php" target="_blank" rel="noopener">عرض المتجر</a>
            <a href="logout.php">تسجيل الخروج</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <h1><?= e($pageTitle) ?></h1>
            <span class="admin-muted">VYRO® MANAGEMENT</span>
        </header>

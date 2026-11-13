
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

$products = getProducts();

if (!is_array($products)) {
    $products = [];
}

$search = trim((string)($_GET['search'] ?? ''));

$filteredProducts = array_filter(
    $products,
    static function ($product) use ($search): bool {
        if (!is_array($product)) {
            return false;
        }

        if ($search === '') {
            return true;
        }

        $searchable = implode(' ', [
            (string)($product['id'] ?? ''),
            (string)($product['name'] ?? ''),
            (string)($product['category'] ?? ''),
        ]);

        return function_exists('mb_stripos')
            ? mb_stripos($searchable, $search) !== false
            : stripos($searchable, $search) !== false;
    }
);

$totalProducts = count($products);
$activeProducts = 0;
$lowStockProducts = 0;

foreach ($products as $product) {
    if (!is_array($product)) {
        continue;
    }

    if (!empty($product['active'])) {
        $activeProducts++;
    }

    if ((int)($product['stock'] ?? 0) <= 5) {
        $lowStockProducts++;
    }
}

$notice = '';
$noticeType = 'success';

if (isset($_GET['deleted'])) {
    $notice = 'تم حذف المنتج بنجاح.';
} elseif (isset($_GET['error'])) {
    $noticeType = 'error';

    $notice = match ((string)$_GET['error']) {
        'invalid' => 'بيانات المنتج غير صالحة.',
        'read' => 'تعذر قراءة ملف المنتجات.',
        'notfound' => 'المنتج غير موجود.',
        'save' => 'تعذر حفظ التغييرات.',
        default => 'حدث خطأ غير متوقع.',
    };
}

function adminProductPrice(array $product): float
{
    $price = (float)($product['price'] ?? 0);
    $salePrice = (float)($product['sale_price'] ?? $product['sale'] ?? 0);

    return $salePrice > 0 && $salePrice < $price
        ? $salePrice
        : $price;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Products | VYRO Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --black: #101010;
            --dark: #191919;
            --card: #202020;
            --border: #303030;
            --white: #f7f7f2;
            --gray: #999;
            --lime: #c7f464;
            --red: #ff7777;
            --orange: #ffbf69;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: var(--black);
            color: var(--white);
            font-family: 'Alexandria', sans-serif;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            flex-shrink: 0;
            padding: 30px 20px;
            background: var(--dark);
            border-left: 1px solid var(--border);
        }

        .brand {
            margin-bottom: 45px;
            text-align: center;
            direction: ltr;
        }

        .brand-name {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 4px;
        }

        .brand-name span {
            color: var(--lime);
        }

        .brand-caption {
            margin-top: 4px;
            color: var(--gray);
            font-size: 9px;
            letter-spacing: 3px;
        }

        .sidebar-label {
            margin: 25px 12px 12px;
            color: #777;
            font-size: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            margin-bottom: 7px;
            border-radius: 8px;
            color: var(--gray);
            text-decoration: none;
            font-size: 12px;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .main-content {
            min-width: 0;
            flex: 1;
            padding: 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .topbar h1 {
            font-size: 24px;
        }

        .topbar p {
            margin-top: 7px;
            color: var(--gray);
            font-size: 11px;
        }

        .add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            padding: 0 18px;
            border-radius: 7px;
            background: var(--lime);
            color: var(--black);
            text-decoration: none;
            font-size: 11px;
            font-weight: 800;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            padding: 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 11px;
        }

        .stat-value {
            display: block;
            margin-top: 12px;
            font-size: 27px;
            font-weight: 700;
        }

        .stat-value.lime {
            color: var(--lime);
        }

        .toolbar {
            display: flex;
            gap: 12px;
            padding: 17px;
            margin-bottom: 18px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .search-form {
            display: flex;
            flex: 1;
            gap: 8px;
        }

        .search-form input {
            flex: 1;
            min-width: 0;
            height: 42px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            outline: none;
            background: var(--black);
            color: var(--white);
            font-family: inherit;
            font-size: 11px;
        }

        .search-form input:focus {
            border-color: var(--lime);
        }

        .search-button {
            padding: 0 18px;
            border: none;
            border-radius: 7px;
            background: var(--lime);
            color: var(--black);
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            text-align: right;
            font-size: 11px;
        }

        th {
            color: var(--gray);
            font-weight: 500;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, .02);
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 7px;
            background: #292929;
            object-fit: cover;
        }

        .product-name {
            font-weight: 600;
        }

        .product-id {
            display: block;
            margin-top: 5px;
            color: var(--gray);
            direction: ltr;
            text-align: right;
            font-size: 9px;
        }

        .price {
            color: var(--lime);
            direction: ltr;
            white-space: nowrap;
        }

        .old-price {
            display: block;
            margin-top: 4px;
            color: var(--gray);
            text-decoration: line-through;
            font-size: 9px;
        }

        .stock {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
            font-size: 10px;
        }

        .stock.low {
            background: rgba(255, 191, 105, .1);
            color: var(--orange);
        }

        .stock.empty {
            background: rgba(255, 119, 119, .1);
            color: var(--red);
        }

        .product-status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
            font-size: 10px;
        }

        .product-status.inactive {
            background: rgba(255, 119, 119, .1);
            color: var(--red);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-link,
        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: transparent;
            color: var(--white);
            text-decoration: none;
            font-family: inherit;
            font-size: 10px;
            cursor: pointer;
        }

        .action-link:hover {
            border-color: var(--lime);
            color: var(--lime);
        }

        .action-button:hover {
            border-color: var(--red);
            color: var(--red);
        }

        .empty-state {
            padding: 65px 20px;
            color: var(--gray);
            text-align: center;
            font-size: 12px;
        }

        .alert {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 11px;
        }

        .alert.success {
            border: 1px solid rgba(199, 244, 100, .3);
            background: rgba(199, 244, 100, .08);
            color: var(--lime);
        }

        .alert.error {
            border: 1px solid rgba(255, 119, 119, .3);
            background: rgba(255, 119, 119, .08);
            color: var(--red);
        }

        .admin-footer {
            margin-top: 25px;
            color: #666;
            text-align: center;
            font-size: 10px;
            direction: ltr;
        }

        @media (max-width: 760px) {
            .admin-layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 18px;
                border-left: none;
                border-bottom: 1px solid var(--border);
            }

            .brand {
                margin-bottom: 20px;
            }

            .sidebar-label {
                display: none;
            }

            .sidebar nav {
                display: flex;
                gap: 7px;
                overflow-x: auto;
            }

            .nav-link {
                flex-shrink: 0;
                margin: 0;
                padding: 11px;
            }

            .main-content {
                padding: 22px 15px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .stats-grid {
                gap: 8px;
            }

            .stat-card {
                padding: 13px;
            }

            .stat-value {
                font-size: 22px;
            }

            .toolbar {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <aside class="sidebar">

        <div class="brand">
            <div class="brand-name">VYRO<span>®</span></div>
            <div class="brand-caption">ADMIN PANEL</div>
        </div>

        <div class="sidebar-label">القائمة الرئيسية</div>

        <nav>
            <a href="dashboard.php" class="nav-link">
                <span class="nav-icon">▦</span>
                لوحة التحكم
            </a>

            <a href="products.php" class="nav-link active">
                <span class="nav-icon">◈</span>
                المنتجات
            </a>

            <a href="orders.php" class="nav-link">
                <span class="nav-icon">▤</span>
                الطلبات
            </a>

            <a href="messages.php" class="nav-link">
                <span class="nav-icon">✉</span>
                الرسائل
            </a>
        </nav>

        <div class="sidebar-label">المتجر</div>

        <nav>
            <a href="../index.php" class="nav-link">
                <span class="nav-icon">↗</span>
                عرض المتجر
            </a>
        </nav>

    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>إدارة المنتجات</h1>
                <p>إضافة المنتجات وتعديلها وحذفها ومتابعة المخزون.</p>
            </div>

            <a href="product-form.php" class="add-button">
                + إضافة منتج جديد
            </a>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="alert <?= e($noticeType) ?>" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">

            <div class="stat-card">
                <span class="stat-label">إجمالي المنتجات</span>
                <strong class="stat-value"><?= number_format($totalProducts) ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">المنتجات النشطة</span>
                <strong class="stat-value lime"><?= number_format($activeProducts) ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">مخزون منخفض أو نافد</span>
                <strong class="stat-value"><?= number_format($lowStockProducts) ?></strong>
            </div>

        </section>

        <section class="toolbar">

            <form method="GET" class="search-form">

                <input
                    type="search"
                    name="search"
                    placeholder="ابحث باسم المنتج أو رقمه أو تصنيفه..."
                    value="<?= e($search) ?>"
                >

                <button type="submit" class="search-button">
                    بحث
                </button>

            </form>

        </section>

        <section class="table-container">

            <?php if (empty($filteredProducts)): ?>

                <div class="empty-state">
                    لا توجد منتجات مطابقة لبحثك.
                </div>

            <?php else: ?>

                <table>

                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>التصنيف</th>
                            <th>السعر</th>
                            <th>المخزون</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($filteredProducts as $product): ?>

                            <?php
                            $productId = (string)($product['id'] ?? '');
                            $productName = (string)($product['name'] ?? 'منتج');
                            $image = (string)($product['image'] ?? '');
                            $price = (float)($product['price'] ?? 0);
                            $salePrice = (float)($product['sale_price'] ?? $product['sale'] ?? 0);
                            $stock = (int)($product['stock'] ?? 0);
                            $active = !empty($product['active']);
                            $hasSale = $salePrice > 0 && $salePrice < $price;
                            ?>

                            <tr>

                                <td>
                                    <div class="product-info">

                                        <?php if ($image !== ''): ?>
                                            <img
                                                class="product-image"
                                                src="<?= e($image) ?>"
                                                alt="<?= e($productName) ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <div class="product-image"></div>
                                        <?php endif; ?>

                                        <div>
                                            <span class="product-name">
                                                <?= e($productName) ?>
                                            </span>

                                            <span class="product-id">
                                                <?= e($productId) ?>
                                            </span>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <?= e((string)($product['category'] ?? '—')) ?>
                                </td>

                                <td>
                                    <span class="price">
                                        $<?= number_format($hasSale ? $salePrice : $price, 2) ?>
                                    </span>

                                    <?php if ($hasSale): ?>
                                        <span class="old-price">
                                            $<?= number_format($price, 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="stock <?= $stock <= 0 ? 'empty' : ($stock <= 5 ? 'low' : '') ?>">
                                        <?= number_format($stock) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="product-status <?= $active ? '' : 'inactive' ?>">
                                        <?= $active ? 'نشط' : 'غير نشط' ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            href="product-form.php?id=<?= rawurlencode($productId) ?>"
                                            class="action-link"
                                        >
                                            تعديل
                                        </a>

                                        <form
                                            method="POST"
                                            action="product-delete.php"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج نهائياً؟');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= e($productId) ?>"
                                            >

                                            <button type="submit" class="action-button">
                                                حذف
                                            </button>
                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </section>

        <footer class="admin-footer">
            © <?= date('Y') ?> VYRO® ADMIN PANEL
        </footer>

    </main>

</div>

</body>
</html>

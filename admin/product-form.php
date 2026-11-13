
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

$productsFile = VYRO_DATA . '/products.json';
$products = readJsonFile($productsFile, []);

if (!is_array($products)) {
    $products = [];
}

$error = '';
$isEditing = false;

$product = [
    'id' => '',
    'name' => '',
    'category' => 'running',
    'price' => '',
    'sale_price' => '',
    'stock' => 0,
    'sizes' => [],
    'image' => '',
    'description' => '',
    'featured' => false,
    'new' => false,
    'active' => true,
];

$productId = trim((string)($_GET['id'] ?? ''));

if ($productId !== '') {
    foreach ($products as $existingProduct) {
        if (($existingProduct['id'] ?? '') === $productId) {
            $product = array_merge($product, $existingProduct);
            $isEditing = true;
            break;
        }
    }

    if (!$isEditing) {
        http_response_code(404);
        exit('المنتج غير موجود.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyAdminCsrf();

    $submittedId = trim((string)($_POST['id'] ?? ''));

    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $salePriceInput = trim((string)($_POST['sale_price'] ?? ''));
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
    $image = trim((string)($_POST['image'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    $sizesInput = $_POST['sizes'] ?? [];
    $sizes = is_array($sizesInput)
        ? array_values(array_unique(array_filter(
            array_map('intval', $sizesInput),
            static fn($size) => $size >= 20 && $size <= 60
        )))
        : [];

    $featured = isset($_POST['featured']);
    $isNew = isset($_POST['new']);
    $active = isset($_POST['active']);

    $salePrice = $salePriceInput === ''
        ? 0.0
        : filter_var($salePriceInput, FILTER_VALIDATE_FLOAT);

    if ($submittedId === '') {
        $error = 'رقم المنتج مطلوب.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{2,60}$/', $submittedId)) {
        $error = 'رقم المنتج يجب أن يحتوي على أحرف وأرقام وشرطة فقط.';
    } elseif ($name === '') {
        $error = 'يرجى إدخال اسم المنتج.';
    } elseif (!in_array($category, ['running', 'lifestyle', 'performance'], true)) {
        $error = 'التصنيف المحدد غير صالح.';
    } elseif ($price === false || $price <= 0) {
        $error = 'يرجى إدخال سعر صحيح.';
    } elseif ($salePrice === false || $salePrice < 0) {
        $error = 'سعر التخفيض غير صالح.';
    } elseif ($salePrice > 0 && $salePrice >= $price) {
        $error = 'سعر التخفيض يجب أن يكون أقل من السعر الأصلي.';
    } elseif ($stock === false || $stock < 0) {
        $error = 'كمية المخزون غير صالحة.';
    } elseif (empty($sizes)) {
        $error = 'يرجى اختيار مقاس واحد على الأقل.';
    } elseif ($image === '') {
        $error = 'رابط صورة المنتج مطلوب.';
    } elseif (
        !filter_var($image, FILTER_VALIDATE_URL) ||
        !in_array(strtolower((string)parse_url($image, PHP_URL_SCHEME)), ['http', 'https'], true)
    ) {
        $error = 'يرجى إدخال رابط صورة صحيح يبدأ بـ https:// أو http://.';
    } elseif (strlen($description) > 3000) {
        $error = 'وصف المنتج طويل جداً.';
    }

    if ($error === '') {
        foreach ($products as $existingProduct) {
            if (
                ($existingProduct['id'] ?? '') === $submittedId &&
                (!$isEditing || $submittedId !== $productId)
            ) {
                $error = 'رقم المنتج مستخدم بالفعل.';
                break;
            }
        }
    }

    if ($error === '') {
        $updatedProduct = [
            'id' => $submittedId,
            'name' => $name,
            'category' => $category,
            'price' => (float)$price,
            'sale_price' => (float)$salePrice,
            'stock' => (int)$stock,
            'sizes' => $sizes,
            'image' => $image,
            'description' => $description,
            'featured' => $featured,
            'new' => $isNew,
            'active' => $active,
        ];

        if ($isEditing) {
            foreach ($products as $index => $existingProduct) {
                if (($existingProduct['id'] ?? '') === $productId) {
                    $products[$index] = $updatedProduct;
                    break;
                }
            }
        } else {
            $products[] = $updatedProduct;
        }

        if (writeJsonFile($productsFile, array_values($products))) {
            redirect('products.php');
        }

        $error = 'تعذر حفظ المنتج. تحقق من صلاحيات مجلد البيانات.';
    }

    $product = [
        'id' => $submittedId,
        'name' => $name,
        'category' => $category,
        'price' => $_POST['price'] ?? '',
        'sale_price' => $salePriceInput,
        'stock' => $_POST['stock'] ?? 0,
        'sizes' => $sizes,
        'image' => $image,
        'description' => $description,
        'featured' => $featured,
        'new' => $isNew,
        'active' => $active,
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $isEditing ? 'Edit Product' : 'Add Product' ?> | VYRO Admin</title>

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

        .back-link {
            padding: 11px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--white);
            text-decoration: none;
            font-size: 11px;
        }

        .form-card {
            max-width: 900px;
            padding: 28px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: var(--white);
            font-size: 11px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 47px;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            outline: none;
            background: var(--black);
            color: var(--white);
            font-family: inherit;
            font-size: 11px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--lime);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.9;
        }

        .hint {
            margin-top: 7px;
            color: var(--gray);
            font-size: 9px;
            line-height: 1.8;
        }

        .sizes-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .size-option {
            position: relative;
        }

        .size-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .size-option span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 42px;
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--gray);
            font-size: 11px;
            cursor: pointer;
            direction: ltr;
            transition: .2s;
        }

        .size-option input:checked + span {
            border-color: var(--lime);
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
        }

        .checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--gray);
            font-size: 11px;
            cursor: pointer;
        }

        .checkbox-label input {
            width: 17px;
            min-height: 17px;
            height: 17px;
            accent-color: var(--lime);
        }

        .error-message {
            padding: 13px;
            margin-bottom: 22px;
            border: 1px solid rgba(255, 119, 119, .3);
            border-radius: 8px;
            background: rgba(255, 119, 119, .08);
            color: var(--red);
            font-size: 11px;
            line-height: 1.8;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .submit-button {
            min-width: 160px;
            padding: 14px 22px;
            border: none;
            border-radius: 7px;
            background: var(--lime);
            color: var(--black);
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .cancel-button {
            padding: 14px 22px;
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--white);
            text-decoration: none;
            font-size: 11px;
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

            .form-card {
                padding: 20px 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
                <h1><?= $isEditing ? 'تعديل المنتج' : 'إضافة منتج جديد' ?></h1>
                <p>أدخل بيانات المنتج واحفظ التغييرات.</p>
            </div>

            <a href="products.php" class="back-link">العودة للمنتجات</a>
        </header>

        <section class="form-card">

            <?php if ($error !== ''): ?>
                <div class="error-message" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $isEditing ? 'product-form.php?id=' . rawurlencode($productId) : 'product-form.php' ?>">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <div class="form-grid">

                    <div class="form-group">
                        <label for="id">رقم المنتج</label>
                        <input
                            type="text"
                            id="id"
                            name="id"
                            value="<?= e((string)$product['id']) ?>"
                            placeholder="مثال: vyro-007"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="name">اسم المنتج</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e((string)$product['name']) ?>"
                            placeholder="اسم الحذاء"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category">التصنيف</label>
                        <select id="category" name="category" required>
                            <option value="running" <?= $product['category'] === 'running' ? 'selected' : '' ?>>Running</option>
                            <option value="lifestyle" <?= $product['category'] === 'lifestyle' ? 'selected' : '' ?>>Lifestyle</option>
                            <option value="performance" <?= $product['category'] === 'performance' ? 'selected' : '' ?>>Performance</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock">كمية المخزون</label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?= e((string)$product['stock']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="price">السعر الأصلي بالدولار</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="0.01"
                            step="0.01"
                            value="<?= e((string)$product['price']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="sale_price">سعر التخفيض بالدولار</label>
                        <input
                            type="number"
                            id="sale_price"
                            name="sale_price"
                            min="0"
                            step="0.01"
                            value="<?= e((string)$product['sale_price']) ?>"
                            placeholder="اتركه فارغاً إذا لم يوجد تخفيض"
                        >
                    </div>

                    <div class="form-group full">
                        <label>المقاسات المتوفرة</label>

                        <div class="sizes-grid">
                            <?php foreach (range(38, 46) as $size): ?>
                                <label class="size-option">
                                    <input
                                        type="checkbox"
                                        name="sizes[]"
                                        value="<?= $size ?>"
                                        <?= in_array($size, array_map('intval', (array)$product['sizes']), true) ? 'checked' : '' ?>
                                    >
                                    <span><?= $size ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <p class="hint">يمكن اختيار أكثر من مقاس.</p>
                    </div>

                    <div class="form-group full">
                        <label for="image">رابط صورة المنتج</label>
                        <input
                            type="url"
                            id="image"
                            name="image"
                            value="<?= e((string)$product['image']) ?>"
                            placeholder="https://example.com/shoe.jpg"
                            required
                        >
                        <p class="hint">أدخل رابط صورة مباشر يبدأ بـ https://</p>
                    </div>

                    <div class="form-group full">
                        <label for="description">وصف المنتج</label>
                        <textarea
                            id="description"
                            name="description"
                            maxlength="3000"
                            placeholder="اكتب وصف المنتج..."
                        ><?= e((string)$product['description']) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>إعدادات العرض</label>

                        <div class="checkboxes">

                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="featured"
                                    value="1"
                                    <?= !empty($product['featured']) ? 'checked' : '' ?>
                                >
                                منتج مميز
                            </label>

                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="new"
                                    value="1"
                                    <?= !empty($product['new']) ? 'checked' : '' ?>
                                >
                                منتج جديد
                            </label>

                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="active"
                                    value="1"
                                    <?= !empty($product['active']) ? 'checked' : '' ?>
                                >
                                عرض المنتج في المتجر
                            </label>

                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-button">
                        <?= $isEditing ? 'حفظ التعديلات' : 'إضافة المنتج' ?>
                    </button>

                    <a href="products.php" class="cancel-button">
                        إلغاء
                    </a>
                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>

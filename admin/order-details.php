
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

$ordersFile = VYRO_DATA . '/orders.json';
$orders = readJsonFile($ordersFile, []);

if (!is_array($orders)) {
    $orders = [];
}

$orderNumber = trim((string)($_GET['id'] ?? ''));

$orderIndex = null;
$order = null;

foreach ($orders as $index => $existingOrder) {
    if (
        is_array($existingOrder) &&
        (string)($existingOrder['order_number'] ?? '') === $orderNumber
    ) {
        $orderIndex = $index;
        $order = $existingOrder;
        break;
    }
}

if ($order === null) {
    http_response_code(404);
    exit('الطلب غير موجود.');
}

$error = '';
$success = '';

$allowedStatuses = [
    'pending',
    'processing',
    'shipped',
    'completed',
    'cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyAdminCsrf();

    $newStatus = trim((string)($_POST['status'] ?? ''));

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $error = 'حالة الطلب غير صالحة.';
    } else {
        $orders[$orderIndex]['status'] = $newStatus;

        if (writeJsonFile($ordersFile, array_values($orders))) {
            redirect('order-details.php?id=' . rawurlencode($orderNumber) . '&updated=1');
        }

        $error = 'تعذر حفظ حالة الطلب. تحقق من صلاحيات مجلد البيانات.';
    }
}

if (isset($_GET['updated'])) {
    $success = 'تم تحديث حالة الطلب بنجاح.';
    $order = $orders[$orderIndex];
}

function detailStatusLabel(string $status): string
{
    return match (strtolower($status)) {
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        default => 'غير محدد',
    };
}

function detailDateLabel(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp
        ? date('Y-m-d H:i', $timestamp)
        : '—';
}

$items = $order['items'] ?? [];
if (!is_array($items)) {
    $items = [];
}

$subtotal = (float)($order['subtotal'] ?? $order['total'] ?? 0);
$shipping = (float)($order['shipping'] ?? 0);
$total = (float)($order['total'] ?? ($subtotal + $shipping));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Order Details | VYRO Admin</title>

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
            --blue: #83baff;
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

        .details-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr);
            gap: 20px;
            align-items: start;
        }

        .panel {
            overflow: hidden;
            margin-bottom: 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .panel-heading {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .panel-heading h2 {
            font-size: 14px;
        }

        .panel-body {
            padding: 22px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-size: 11px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--gray);
        }

        .info-value {
            text-align: left;
            overflow-wrap: anywhere;
        }

        .order-number {
            color: var(--lime);
            direction: ltr;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: rgba(255, 191, 105, .1);
            color: var(--orange);
            font-size: 10px;
        }

        .status.processing,
        .status.shipped {
            background: rgba(131, 186, 255, .1);
            color: var(--blue);
        }

        .status.completed {
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
        }

        .status.cancelled {
            background: rgba(255, 119, 119, .1);
            color: var(--red);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
            text-align: right;
            font-size: 10px;
        }

        .items-table th {
            color: var(--gray);
            font-weight: 500;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .totals {
            margin-top: 18px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            color: var(--gray);
            font-size: 11px;
        }

        .total-row.final {
            margin-top: 8px;
            padding-top: 17px;
            border-top: 1px solid var(--border);
            color: var(--white);
            font-size: 15px;
            font-weight: 700;
        }

        .total-row.final strong {
            color: var(--lime);
        }

        .status-form label {
            display: block;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .status-form select {
            width: 100%;
            height: 45px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            outline: none;
            background: var(--black);
            color: var(--white);
            font-family: inherit;
            font-size: 11px;
        }

        .status-form select:focus {
            border-color: var(--lime);
        }

        .submit-button {
            width: 100%;
            height: 45px;
            margin-top: 14px;
            border: none;
            border-radius: 7px;
            background: var(--lime);
            color: var(--black);
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .error-message,
        .success-message {
            padding: 13px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.8;
        }

        .error-message {
            border: 1px solid rgba(255, 119, 119, .3);
            background: rgba(255, 119, 119, .08);
            color: var(--red);
        }

        .success-message {
            border: 1px solid rgba(199, 244, 100, .3);
            background: rgba(199, 244, 100, .08);
            color: var(--lime);
        }

        .notes {
            color: var(--gray);
            font-size: 11px;
            line-height: 2;
            overflow-wrap: anywhere;
        }

        .admin-footer {
            margin-top: 25px;
            color: #666;
            text-align: center;
            font-size: 10px;
            direction: ltr;
        }

        @media (max-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
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

            .topbar h1 {
                font-size: 21px;
            }

            .panel-body {
                padding: 15px;
            }

            .items-table th,
            .items-table td {
                padding: 12px 9px;
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

            <a href="products.php" class="nav-link">
                <span class="nav-icon">◈</span>
                المنتجات
            </a>

            <a href="orders.php" class="nav-link active">
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
                <h1>تفاصيل الطلب</h1>
                <p>مراجعة بيانات العميل والمنتجات وتحديث حالة الطلب.</p>
            </div>

            <a href="orders.php" class="back-link">العودة للطلبات</a>
        </header>

        <?php if ($error !== ''): ?>
            <div class="error-message" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="success-message" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <div class="details-grid">

            <div>

                <section class="panel">

                    <div class="panel-heading">
                        <h2>المنتجات المطلوبة</h2>
                    </div>

                    <div class="panel-body">

                        <?php if (empty($items)): ?>

                            <p class="notes">لا توجد تفاصيل للمنتجات في هذا الطلب.</p>

                        <?php else: ?>

                            <div style="overflow-x:auto;">

                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>المنتج</th>
                                            <th>المقاس</th>
                                            <th>الكمية</th>
                                            <th>السعر</th>
                                            <th>الإجمالي</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($items as $item): ?>

                                            <?php
                                            if (!is_array($item)) {
                                                continue;
                                            }

                                            $quantity = (int)($item['quantity'] ?? 0);
                                            $price = (float)($item['price'] ?? 0);
                                            ?>

                                            <tr>
                                                <td>
                                                    <?= e((string)($item['name'] ?? $item['product_name'] ?? 'منتج')) ?>
                                                </td>

                                                <td>
                                                    <?= e((string)($item['size'] ?? '—')) ?>
                                                </td>

                                                <td>
                                                    <?= number_format($quantity) ?>
                                                </td>

                                                <td>
                                                    $<?= number_format($price, 2) ?>
                                                </td>

                                                <td>
                                                    $<?= number_format($price * $quantity, 2) ?>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>
                                </table>

                            </div>

                        <?php endif; ?>

                        <div class="totals">

                            <div class="total-row">
                                <span>المجموع الفرعي</span>
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </div>

                            <div class="total-row">
                                <span>الشحن</span>
                                <span>$<?= number_format($shipping, 2) ?></span>
                            </div>

                            <div class="total-row final">
                                <span>الإجمالي النهائي</span>
                                <strong>$<?= number_format($total, 2) ?></strong>
                            </div>

                        </div>

                    </div>

                </section>

                <section class="panel">

                    <div class="panel-heading">
                        <h2>ملاحظات العميل</h2>
                    </div>

                    <div class="panel-body">
                        <p class="notes">
                            <?= e((string)($order['notes'] ?? 'لا توجد ملاحظات.')) ?>
                        </p>
                    </div>

                </section>

            </div>

            <div>

                <section class="panel">

                    <div class="panel-heading">
                        <h2>معلومات الطلب</h2>
                    </div>

                    <div class="panel-body">

                        <div class="info-row">
                            <span class="info-label">رقم الطلب</span>
                            <strong class="info-value order-number">
                                <?= e($orderNumber) ?>
                            </strong>
                        </div>

                        <div class="info-row">
                            <span class="info-label">تاريخ الطلب</span>
                            <span class="info-value">
                                <?= e(detailDateLabel((string)($order['created_at'] ?? ''))) ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">حالة الطلب</span>
                            <span class="info-value">
                                <span class="status <?= e(strtolower((string)($order['status'] ?? 'pending'))) ?>">
                                    <?= e(detailStatusLabel((string)($order['status'] ?? 'pending'))) ?>
                                </span>
                            </span>
                        </div>

                    </div>

                </section>

                <section class="panel">

                    <div class="panel-heading">
                        <h2>بيانات العميل</h2>
                    </div>

                    <div class="panel-body">

                        <div class="info-row">
                            <span class="info-label">الاسم</span>
                            <strong class="info-value">
                                <?= e((string)($order['customer_name'] ?? '—')) ?>
                            </strong>
                        </div>

                        <div class="info-row">
                            <span class="info-label">الهاتف</span>
                            <span class="info-value">
                                <?= e((string)($order['phone'] ?? '—')) ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">المدينة</span>
                            <span class="info-value">
                                <?= e((string)($order['city'] ?? '—')) ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">العنوان</span>
                            <span class="info-value">
                                <?= e((string)($order['address'] ?? '—')) ?>
                            </span>
                        </div>

                    </div>

                </section>

                <section class="panel">

                    <div class="panel-heading">
                        <h2>تحديث حالة الطلب</h2>
                    </div>

                    <div class="panel-body">

                        <form method="POST" class="status-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>"
                            >

                            <label for="status">اختر الحالة الجديدة</label>

                            <select id="status" name="status" required>

                                <?php foreach ($allowedStatuses as $status): ?>

                                    <option
                                        value="<?= e($status) ?>"
                                        <?= strtolower((string)($order['status'] ?? 'pending')) === $status ? 'selected' : '' ?>
                                    >
                                        <?= e(detailStatusLabel($status)) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button type="submit" class="submit-button">
                                حفظ حالة الطلب
                            </button>

                        </form>

                    </div>

                </section>

            </div>

        </div>

        <footer class="admin-footer">
            © <?= date('Y') ?> VYRO® ADMIN PANEL
        </footer>

    </main>

</div>

</body>
</html>


<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

$products = getProducts();

$ordersFile = VYRO_DATA . '/orders.json';
$orders = readJsonFile($ordersFile, []);

if (!is_array($orders)) {
    $orders = [];
}

$totalProducts = count(array_filter(
    $products,
    static fn($product) => !empty($product['active'])
));

$totalOrders = count($orders);

$pendingOrders = count(array_filter(
    $orders,
    static fn($order) => strtolower((string)($order['status'] ?? 'pending')) === 'pending'
));

$totalRevenue = 0.0;

foreach ($orders as $order) {
    $status = strtolower((string)($order['status'] ?? 'pending'));

    if (in_array($status, ['cancelled', 'canceled', 'refunded'], true)) {
        continue;
    }

    $totalRevenue += (float)($order['total'] ?? 0);
}

$recentOrders = array_slice(
    array_reverse($orders),
    0,
    8
);

function adminStatusLabel(string $status): string
{
    return match (strtolower($status)) {
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'completed' => 'مكتمل',
        'cancelled', 'canceled' => 'ملغي',
        'refunded' => 'مسترد',
        default => 'غير محدد',
    };
}

function adminFormatDate(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp
        ? date('Y-m-d H:i', $timestamp)
        : '—';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard | VYRO Admin</title>

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
            margin-bottom: 40px;
        }

        .topbar h1 {
            font-size: 25px;
            font-weight: 700;
        }

        .topbar p {
            margin-top: 7px;
            color: var(--gray);
            font-size: 11px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .view-store {
            padding: 11px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--white);
            text-decoration: none;
            font-size: 11px;
            transition: .2s;
        }

        .view-store:hover {
            border-color: var(--lime);
            color: var(--lime);
        }

        .logout-form {
            margin: 0;
        }

        .logout-button {
            padding: 11px 16px;
            border: 1px solid rgba(255, 119, 119, .3);
            border-radius: 8px;
            background: transparent;
            color: var(--red);
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            padding: 24px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 11px;
        }

        .stat-value {
            margin-top: 18px;
            font-size: 29px;
            font-weight: 700;
            direction: ltr;
            text-align: right;
        }

        .stat-value.lime {
            color: var(--lime);
        }

        .section-card {
            overflow: hidden;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 22px;
            border-bottom: 1px solid var(--border);
        }

        .section-heading h2 {
            font-size: 15px;
        }

        .section-heading a {
            color: var(--lime);
            text-decoration: none;
            font-size: 11px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th,
        td {
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
            text-align: right;
            font-size: 11px;
        }

        th {
            color: var(--gray);
            font-weight: 500;
        }

        td {
            color: var(--white);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: rgba(255, 191, 105, .1);
            color: var(--orange);
            font-size: 10px;
        }

        .status.completed {
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
        }

        .status.cancelled,
        .status.refunded {
            background: rgba(255, 119, 119, .1);
            color: var(--red);
        }

        .empty-state {
            padding: 45px 20px;
            color: var(--gray);
            text-align: center;
            font-size: 12px;
        }

        .admin-footer {
            margin-top: 35px;
            color: #666;
            text-align: center;
            font-size: 10px;
            direction: ltr;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
                margin-bottom: 25px;
            }

            .topbar h1 {
                font-size: 21px;
            }

            .stats-grid {
                gap: 10px;
            }

            .stat-card {
                padding: 17px;
            }

            .stat-value {
                font-size: 23px;
            }
        }

        @media (max-width: 380px) {
            .stats-grid {
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
            <a href="dashboard.php" class="nav-link active">
                <span class="nav-icon">▦</span>
                لوحة التحكم
            </a>

            <a href="products.php" class="nav-link">
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
                <h1>لوحة التحكم</h1>
                <p>مرحباً بك في إدارة متجر VYRO.</p>
            </div>

            <div class="topbar-actions">

                <a href="../index.php" class="view-store">
                    عرض المتجر ↗
                </a>

                <form method="POST" action="logout.php" class="logout-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']) ?>"
                    >
                    <button type="submit" class="logout-button">
                        تسجيل الخروج
                    </button>
                </form>

            </div>

        </header>

        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-label">المنتجات النشطة</div>
                <div class="stat-value lime">
                    <?= number_format($totalProducts) ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">إجمالي الطلبات</div>
                <div class="stat-value">
                    <?= number_format($totalOrders) ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">الطلبات قيد الانتظار</div>
                <div class="stat-value">
                    <?= number_format($pendingOrders) ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">إجمالي المبيعات غير الملغاة</div>
                <div class="stat-value lime">
                    $<?= number_format($totalRevenue, 2) ?>
                </div>
            </div>

        </section>

        <section class="section-card">

            <div class="section-heading">
                <h2>أحدث الطلبات</h2>
                <a href="orders.php">عرض جميع الطلبات ↗</a>
            </div>

            <?php if (empty($recentOrders)): ?>

                <div class="empty-state">
                    لا توجد طلبات حتى الآن.
                </div>

            <?php else: ?>

                <div class="table-wrapper">

                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>التاريخ</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($recentOrders as $order): ?>

                                <?php
                                $status = (string)($order['status'] ?? 'pending');
                                $statusClass = strtolower($status);

                                if ($statusClass === 'canceled') {
                                    $statusClass = 'cancelled';
                                }
                                ?>

                                <tr>
                                    <td>
                                        <?= e((string)($order['order_number'] ?? '—')) ?>
                                    </td>

                                    <td>
                                        <?= e((string)($order['customer_name'] ?? '—')) ?>
                                    </td>

                                    <td>
                                        <?= e(adminFormatDate((string)($order['created_at'] ?? ''))) ?>
                                    </td>

                                    <td>
                                        $<?= number_format((float)($order['total'] ?? 0), 2) ?>
                                    </td>

                                    <td>
                                        <span class="status <?= e($statusClass) ?>">
                                            <?= e(adminStatusLabel($status)) ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </section>

        <footer class="admin-footer">
            © <?= date('Y') ?> VYRO® ADMIN PANEL
        </footer>

    </main>

</div>

</body>
</html>

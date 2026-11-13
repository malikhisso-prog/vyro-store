
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

$messagesFile = VYRO_DATA . '/messages.json';
$messages = readJsonFile($messagesFile, []);

if (!is_array($messages)) {
    $messages = [];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyAdminCsrf();

    $action = (string)($_POST['action'] ?? '');
    $messageId = (string)($_POST['message_id'] ?? '');

    if ($action === 'delete') {
        $updatedMessages = [];
        $found = false;

        foreach ($messages as $message) {
            if (
                is_array($message) &&
                (string)($message['id'] ?? '') === $messageId
            ) {
                $found = true;
                continue;
            }

            $updatedMessages[] = $message;
        }

        if (!$found) {
            $error = 'الرسالة غير موجودة.';
        } elseif (writeJsonFile($messagesFile, array_values($updatedMessages))) {
            redirect('messages.php?deleted=1');
        } else {
            $error = 'تعذر حذف الرسالة.';
        }
    } elseif ($action === 'mark_read' || $action === 'mark_unread') {
        $found = false;

        foreach ($messages as &$message) {
            if (
                is_array($message) &&
                (string)($message['id'] ?? '') === $messageId
            ) {
                $message['read'] = ($action === 'mark_read');
                $found = true;
                break;
            }
        }
        unset($message);

        if (!$found) {
            $error = 'الرسالة غير موجودة.';
        } elseif (writeJsonFile($messagesFile, array_values($messages))) {
            redirect('messages.php?updated=1');
        } else {
            $error = 'تعذر تحديث حالة الرسالة.';
        }
    }
}

if (isset($_GET['deleted'])) {
    $success = 'تم حذف الرسالة بنجاح.';
} elseif (isset($_GET['updated'])) {
    $success = 'تم تحديث حالة الرسالة.';
}

$search = trim((string)($_GET['search'] ?? ''));
$filter = trim((string)($_GET['filter'] ?? 'all'));

$filteredMessages = array_filter(
    $messages,
    static function ($message) use ($search, $filter): bool {
        if (!is_array($message)) {
            return false;
        }

        $isRead = !empty($message['read']);

        if ($filter === 'read' && !$isRead) {
            return false;
        }

        if ($filter === 'unread' && $isRead) {
            return false;
        }

        if ($search !== '') {
            $searchable = implode(' ', [
                (string)($message['name'] ?? ''),
                (string)($message['email'] ?? ''),
                (string)($message['subject'] ?? ''),
                (string)($message['message'] ?? ''),
            ]);

            if (function_exists('mb_stripos')) {
                if (mb_stripos($searchable, $search) === false) {
                    return false;
                }
            } elseif (stripos($searchable, $search) === false) {
                return false;
            }
        }

        return true;
    }
);

usort(
    $filteredMessages,
    static function ($a, $b): int {
        return strcmp(
            (string)($b['created_at'] ?? ''),
            (string)($a['created_at'] ?? '')
        );
    }
);

$totalMessages = count($messages);
$unreadMessages = 0;
$readMessages = 0;

foreach ($messages as $message) {
    if (!is_array($message)) {
        continue;
    }

    if (!empty($message['read'])) {
        $readMessages++;
    } else {
        $unreadMessages++;
    }
}

function messageDateLabel(string $date): string
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
    <title>Messages | VYRO Admin</title>

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
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
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
            min-width: 220px;
        }

        .search-form input,
        .filter-select {
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

        .search-form input {
            flex: 1;
            min-width: 0;
        }

        .search-form input:focus,
        .filter-select:focus {
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
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 17px 15px;
            border-bottom: 1px solid var(--border);
            text-align: right;
            font-size: 11px;
        }

        th {
            color: var(--gray);
            font-weight: 500;
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, .02);
        }

        .message-name {
            font-weight: 600;
        }

        .message-email {
            display: block;
            margin-top: 5px;
            color: var(--gray);
            direction: ltr;
            text-align: right;
            font-size: 10px;
        }

        .message-subject {
            display: block;
            max-width: 220px;
            overflow: hidden;
            color: var(--white);
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .message-preview {
            display: block;
            max-width: 220px;
            margin-top: 5px;
            overflow: hidden;
            color: var(--gray);
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 10px;
        }

        .message-status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .06);
            color: var(--gray);
            font-size: 10px;
        }

        .message-status.unread {
            background: rgba(199, 244, 100, .1);
            color: var(--lime);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-button {
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: transparent;
            color: var(--white);
            font-family: inherit;
            font-size: 10px;
            cursor: pointer;
        }

        .action-button:hover {
            border-color: var(--lime);
            color: var(--lime);
        }

        .action-button.delete:hover {
            border-color: var(--red);
            color: var(--red);
        }

        .empty-state {
            padding: 65px 20px;
            color: var(--gray);
            text-align: center;
            font-size: 12px;
        }

        .empty-icon {
            display: block;
            margin-bottom: 15px;
            color: var(--lime);
            font-size: 35px;
        }

        .alert {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.8;
        }

        .alert.error {
            border: 1px solid rgba(255, 119, 119, .3);
            background: rgba(255, 119, 119, .08);
            color: var(--red);
        }

        .alert.success {
            border: 1px solid rgba(199, 244, 100, .3);
            background: rgba(199, 244, 100, .08);
            color: var(--lime);
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
                align-items: stretch;
            }

            .search-form {
                min-width: 100%;
            }

            .filter-select {
                width: 100%;
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

            <a href="orders.php" class="nav-link">
                <span class="nav-icon">▤</span>
                الطلبات
            </a>

            <a href="messages.php" class="nav-link active">
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
                <h1>رسائل العملاء</h1>
                <p>إدارة الرسائل الواردة من نموذج التواصل.</p>
            </div>

            <a href="dashboard.php" class="back-link">لوحة التحكم</a>
        </header>

        <?php if ($error !== ''): ?>
            <div class="alert error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">

            <div class="stat-card">
                <span class="stat-label">إجمالي الرسائل</span>
                <strong class="stat-value"><?= number_format($totalMessages) ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">رسائل غير مقروءة</span>
                <strong class="stat-value lime"><?= number_format($unreadMessages) ?></strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">رسائل مقروءة</span>
                <strong class="stat-value"><?= number_format($readMessages) ?></strong>
            </div>

        </section>

        <section class="toolbar">

            <form method="GET" class="search-form">

                <input
                    type="search"
                    name="search"
                    placeholder="ابحث بالاسم أو البريد أو الموضوع..."
                    value="<?= e($search) ?>"
                >

                <input type="hidden" name="filter" value="<?= e($filter) ?>">

                <button type="submit" class="search-button">
                    بحث
                </button>

            </form>

            <form method="GET">
                <input type="hidden" name="search" value="<?= e($search) ?>">

                <select
                    name="filter"
                    class="filter-select"
                    onchange="this.form.submit()"
                >
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>
                        جميع الرسائل
                    </option>

                    <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>
                        غير المقروءة
                    </option>

                    <option value="read" <?= $filter === 'read' ? 'selected' : '' ?>>
                        المقروءة
                    </option>
                </select>
            </form>

        </section>

        <section class="table-container">

            <?php if (empty($filteredMessages)): ?>

                <div class="empty-state">
                    <span class="empty-icon">✉</span>
                    لا توجد رسائل لعرضها.
                </div>

            <?php else: ?>

                <table>

                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الموضوع والرسالة</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($filteredMessages as $message): ?>

                            <?php
                            $messageId = (string)($message['id'] ?? '');
                            $isRead = !empty($message['read']);
                            ?>

                            <tr>

                                <td>
                                    <span class="message-name">
                                        <?= e((string)($message['name'] ?? 'بدون اسم')) ?>
                                    </span>

                                    <span class="message-email">
                                        <?= e((string)($message['email'] ?? '—')) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="message-subject">
                                        <?= e((string)($message['subject'] ?? 'بدون موضوع')) ?>
                                    </span>

                                    <span class="message-preview">
                                        <?= e((string)($message['message'] ?? '')) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(messageDateLabel((string)($message['created_at'] ?? ''))) ?>
                                </td>

                                <td>
                                    <span class="message-status <?= $isRead ? '' : 'unread' ?>">
                                        <?= $isRead ? 'مقروءة' : 'جديدة' ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="actions">

                                        <form method="POST">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= e($messageId) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="<?= $isRead ? 'mark_unread' : 'mark_read' ?>"
                                            >

                                            <button type="submit" class="action-button">
                                                <?= $isRead ? 'غير مقروءة' : 'تمت القراءة' ?>
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذه الرسالة؟');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= e($messageId) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <button type="submit" class="action-button delete">
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

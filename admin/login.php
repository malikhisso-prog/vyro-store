
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['vyro_admin_authenticated'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

$adminUsername = getenv('VYRO_ADMIN_USERNAME') ?: '';
$adminPasswordHash = getenv('VYRO_ADMIN_PASSWORD_HASH') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        $error = 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مجدداً.';
    } elseif (
        $adminUsername === '' ||
        $adminPasswordHash === ''
    ) {
        $error = 'إعدادات دخول لوحة الإدارة غير مكتملة.';
    } elseif (
        hash_equals($adminUsername, $username) &&
        password_verify($password, $adminPasswordHash)
    ) {
        session_regenerate_id(true);

        $_SESSION['vyro_admin_authenticated'] = true;
        $_SESSION['vyro_admin_login_time'] = time();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirect('dashboard.php');
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | VYRO</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --black: #101010;
            --dark: #191919;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--black);
            color: var(--white);
            font-family: 'Alexandria', sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .brand {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-name {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 5px;
            direction: ltr;
        }

        .brand-name span {
            color: var(--lime);
        }

        .brand-tagline {
            margin-top: 5px;
            color: var(--gray);
            font-size: 11px;
            letter-spacing: 4px;
            direction: ltr;
        }

        .login-card {
            padding: 38px 32px;
            background: var(--dark);
            border: 1px solid #303030;
            border-radius: 18px;
        }

        .login-card h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .login-description {
            color: var(--gray);
            font-size: 12px;
            line-height: 2;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 12px;
            font-weight: 600;
        }

        input {
            width: 100%;
            height: 52px;
            padding: 0 15px;
            background: #101010;
            color: var(--white);
            border: 1px solid #383838;
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s;
        }

        input:focus {
            border-color: var(--lime);
        }

        .login-button {
            width: 100%;
            height: 52px;
            margin-top: 8px;
            border: none;
            border-radius: 8px;
            background: var(--lime);
            color: var(--black);
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
        }

        .login-button:hover {
            opacity: .9;
            transform: translateY(-2px);
        }

        .error-message {
            padding: 13px;
            margin-bottom: 22px;
            border: 1px solid rgba(255, 119, 119, .3);
            border-radius: 8px;
            background: rgba(255, 119, 119, .08);
            color: var(--red);
            font-size: 12px;
            line-height: 1.8;
        }

        .back-link {
            display: block;
            margin-top: 25px;
            color: var(--gray);
            text-align: center;
            text-decoration: none;
            font-size: 12px;
            transition: color .2s;
        }

        .back-link:hover {
            color: var(--lime);
        }

        .copyright {
            margin-top: 28px;
            color: #666;
            text-align: center;
            font-size: 10px;
            direction: ltr;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 22px;
            }

            .brand-name {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>

    <main class="login-container">

        <div class="brand">
            <div class="brand-name">VYRO<span>®</span></div>
            <div class="brand-tagline">BUILT TO MOVE.</div>
        </div>

        <section class="login-card">

            <h1>تسجيل دخول الإدارة</h1>

            <p class="login-description">
                أدخل بيانات الدخول للوصول إلى لوحة تحكم المتجر.
            </p>

            <?php if ($error !== ''): ?>
                <div class="error-message" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= e($username) ?>"
                        placeholder="اسم المستخدم"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="كلمة المرور"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">
                    تسجيل الدخول
                </button>

            </form>

        </section>

        <a href="../index.php" class="back-link">
            العودة إلى المتجر
        </a>

        <div class="copyright">
            © <?= date('Y') ?> VYRO®. ALL RIGHTS RESERVED.
        </div>

    </main>

</body>
</html>

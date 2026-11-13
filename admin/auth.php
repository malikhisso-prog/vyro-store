
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

/**
 * التحقق من تسجيل دخول مدير المتجر.
 */
function requireAdmin(): void
{
    if (empty($_SESSION['vyro_admin_authenticated'])) {
        redirect('login.php');
    }

    $loginTime = (int)($_SESSION['vyro_admin_login_time'] ?? 0);

    // انتهاء الجلسة الإدارية بعد 8 ساعات.
    if (
        $loginTime <= 0 ||
        (time() - $loginTime) > 28800
    ) {
        unset(
            $_SESSION['vyro_admin_authenticated'],
            $_SESSION['vyro_admin_login_time']
        );

        session_regenerate_id(true);

        redirect('login.php');
    }
}

/**
 * التحقق من صلاحية طلب POST داخل لوحة الإدارة.
 */
function verifyAdminCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');

    if (
        $submittedToken === '' ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);
        exit('طلب غير صالح. يرجى تحديث الصفحة والمحاولة مجدداً.');
    }
}

/**
 * تسجيل خروج المدير.
 */
function adminLogout(): void
{
    unset(
        $_SESSION['vyro_admin_authenticated'],
        $_SESSION['vyro_admin_login_time']
    );

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    session_regenerate_id(true);
}


<?php
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$pageTitle = 'Page Not Found | VYRO®';

require_once __DIR__ . '/includes/header.php';
?>

<section style="min-height:65vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:60px 20px;">
    <div>
        <p style="font-size:14px;letter-spacing:5px;color:#a3e635;font-weight:700;">VYRO®</p>

        <h1 style="font-size:clamp(90px,18vw,180px);line-height:1;margin:20px 0;font-weight:900;">
            404
        </h1>

        <h2 style="font-size:26px;margin-bottom:15px;">
            PAGE NOT FOUND
        </h2>

        <p style="font-size:15px;opacity:.7;margin-bottom:30px;">
            الصفحة التي تبحث عنها غير موجودة أو تم نقلها.
        </p>

        <a href="index.php" style="display:inline-block;background:#a3e635;color:#111;padding:15px 35px;text-decoration:none;font-weight:800;border-radius:4px;">
            BACK TO HOME
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

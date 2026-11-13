
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verifyAdminCsrf();

$productId = trim((string)($_POST['product_id'] ?? ''));

if ($productId === '') {
    redirect('products.php?error=invalid');
}

$productsFile = VYRO_DATA . '/products.json';
$products = readJsonFile($productsFile, []);

if (!is_array($products)) {
    redirect('products.php?error=read');
}

$updatedProducts = [];
$found = false;

foreach ($products as $product) {
    if (
        is_array($product) &&
        (string)($product['id'] ?? '') === $productId
    ) {
        $found = true;
        continue;
    }

    $updatedProducts[] = $product;
}

if (!$found) {
    redirect('products.php?error=notfound');
}

if (!writeJsonFile($productsFile, array_values($updatedProducts))) {
    redirect('products.php?error=save');
}

redirect('products.php?deleted=1');

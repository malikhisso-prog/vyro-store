<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

/*
|--------------------------------------------------------------------------
| Security & Output
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function cleanInput(mixed $value): string
{
    return trim(strip_tags((string) $value));
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');

    return is_string($token)
        && $token !== ''
        && hash_equals(csrfToken(), $token);
}

function verifyCsrf(): bool
{
    return verifyCsrfToken();
}

function requireValidCsrf(): void
{
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page.');
    }
}

/*
|--------------------------------------------------------------------------
| JSON Storage
|--------------------------------------------------------------------------
*/

function readJsonFile(string $filename, array $default = []): array
{
    $path = VYRO_DATA . '/' . basename($filename);

    if (!is_file($path) || !is_readable($path)) {
        return $default;
    }

    $content = file_get_contents($path);

    if ($content === false || trim($content) === '') {
        return $default;
    }

    $data = json_decode($content, true);

    return is_array($data) ? $data : $default;
}

function writeJsonFile(string $filename, array $data): bool
{
    $path = VYRO_DATA . '/' . basename($filename);

    if (!is_dir(VYRO_DATA) || !is_writable(VYRO_DATA)) {
        return false;
    }

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return false;
    }

    $temporaryFile = $path . '.tmp';

    if (file_put_contents($temporaryFile, $json, LOCK_EX) === false) {
        return false;
    }

    if (!rename($temporaryFile, $path)) {
        @unlink($temporaryFile);
        return false;
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| Product Helpers
|--------------------------------------------------------------------------
*/

function getProducts(): array
{
    return readJsonFile('products.json', []);
}

function getProductById(string $id): ?array
{
    foreach (getProducts() as $product) {
        if ((string) ($product['id'] ?? '') === $id) {
            return $product;
        }
    }

    return null;
}

function isProductNew(array $product): bool
{
    return !empty($product['new']) || !empty($product['is_new']);
}

function getFeaturedProducts(): array
{
    return array_values(
        array_filter(
            getProducts(),
            fn($product) => !empty($product['featured']) && !empty($product['active'])
        )
    );
}

function getNewProducts(): array
{
    return array_values(
        array_filter(
            getProducts(),
            fn($product) => isProductNew($product) && !empty($product['active'])
        )
    );
}

function searchProducts(string $keyword): array
{
    $keyword = mb_strtolower(trim($keyword), 'UTF-8');

    $products = array_values(
        array_filter(getProducts(), fn($product) => !empty($product['active']))
    );

    if ($keyword === '') {
        return $products;
    }

    return array_values(
        array_filter(
            $products,
            function ($product) use ($keyword): bool {
                $searchable = mb_strtolower(
                    implode(' ', [
                        $product['name'] ?? '',
                        $product['brand'] ?? '',
                        $product['category'] ?? '',
                        $product['description'] ?? ''
                    ]),
                    'UTF-8'
                );

                return mb_strpos($searchable, $keyword, 0, 'UTF-8') !== false;
            }
        )
    );
}

/*
|--------------------------------------------------------------------------
| Currency
|--------------------------------------------------------------------------
*/

function formatPrice(float|int|string $price): string
{
    return number_format((float) $price, 2) . ' ' . VYRO_CURRENCY_SYMBOL;
}

function getProductPrice(array $product): float
{
    $salePrice = (float) ($product['sale_price'] ?? $product['sale'] ?? 0);
    $regularPrice = (float) ($product['price'] ?? 0);

    if ($salePrice > 0 && $salePrice < $regularPrice) {
        return $salePrice;
    }

    return $regularPrice;
}
/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function setFlash(string $type, string $message): void
{
    setFlashMessage($type, $message);
}

function getFlashMessage(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function getFlash(): ?array
{
    return getFlashMessage();
}

/*
|--------------------------------------------------------------------------
| Shopping Cart
|--------------------------------------------------------------------------
*/

function getCart(): array
{
    return is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
}

function addToCart(string $productId, string $size = '', int $quantity = 1): bool
{
    $product = getProductById($productId);

    if (!$product || empty($product['active'])) {
        return false;
    }

    $availableStock = (int) ($product['stock'] ?? 0);
    if ($availableStock <= 0) {
        return false;
    }

    $cartKey = $productId . ($size !== '' ? '__' . $size : '');
    $currentQuantity = (int) ($_SESSION['cart'][$cartKey]['quantity'] ?? 0);

    $newQuantity = min(
        $currentQuantity + max(1, $quantity),
        VYRO_MAX_QUANTITY,
        $availableStock
    );

    if ($newQuantity <= 0) {
        return false;
    }

    $_SESSION['cart'][$cartKey] = [
        'product_id' => $productId,
        'size' => $size,
        'quantity' => $newQuantity
    ];

    return true;
}

function updateCartQuantity(string $cartKey, int $quantity): bool
{
    if (!isset($_SESSION['cart'][$cartKey])) {
        return false;
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$cartKey]);
        return true;
    }

    $productId = $_SESSION['cart'][$cartKey]['product_id'] ?? '';
    $product = getProductById($productId);

    if (!$product || empty($product['active'])) {
        unset($_SESSION['cart'][$cartKey]);
        return false;
    }

    $stock = (int) ($product['stock'] ?? 0);
    if ($stock <= 0) {
        unset($_SESSION['cart'][$cartKey]);
        return false;
    }

    $_SESSION['cart'][$cartKey]['quantity'] = min(
        $quantity,
        VYRO_MAX_QUANTITY,
        $stock
    );

    return true;
}

function removeFromCart(string $cartKey): void
{
    unset($_SESSION['cart'][$cartKey]);
}

function clearCart(): void
{
    $_SESSION['cart'] = [];
}

function getCartCount(): int
{
    $count = 0;
    foreach (getCart() as $entry) {
        $count += is_array($entry) ? (int) ($entry['quantity'] ?? 0) : (int) $entry;
    }
    return $count;
}

function getCartItems(): array
{
    $items = [];

    foreach (getCart() as $cartKey => $entry) {
        $productId = is_array($entry) ? ($entry['product_id'] ?? '') : (string) $cartKey;
        $size = is_array($entry) ? ($entry['size'] ?? '') : '';
        $rawQty = is_array($entry) ? ($entry['quantity'] ?? 0) : $entry;

        $product = getProductById((string) $productId);

        if (!$product || empty($product['active'])) {
            continue;
        }

        $quantity = min(
            (int) $rawQty,
            (int) ($product['stock'] ?? 0),
            VYRO_MAX_QUANTITY
        );

        if ($quantity <= 0) {
            continue;
        }

        $price = getProductPrice($product);

        $items[] = [
            'key' => (string) $cartKey,
            'product' => $product,
            'size' => $size,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity
        ];
    }

    return $items;
}

function getCartTotal(): float
{
    $total = 0.0;

    foreach (getCartItems() as $item) {
        $total += $item['subtotal'];
    }

    return $total;
}

/*
|--------------------------------------------------------------------------
| Order Helpers
|--------------------------------------------------------------------------
*/

function generateOrderNumber(): string
{
    return VYRO_ORDER_PREFIX . '-' .
        date('ymd') . '-' .
        strtoupper(bin2hex(random_bytes(3)));
}

/*
|--------------------------------------------------------------------------
| Favorites
|--------------------------------------------------------------------------
*/

function getFavorites(): array
{
    return is_array($_SESSION['favorites'] ?? null) ? $_SESSION['favorites'] : [];
}

function getFavoritesCount(): int
{
    return count(getFavorites());
}

function toggleFavorite(string $productId): bool
{
    if (!getProductById($productId)) {
        return false;
    }

    $favorites = getFavorites();

    if (in_array($productId, $favorites, true)) {
        $_SESSION['favorites'] = array_values(array_diff($favorites, [$productId]));
        return false;
    }

    $favorites[] = $productId;
    $_SESSION['favorites'] = $favorites;

    return true;
}

function isFavorite(string $productId): bool
{
    return in_array($productId, getFavorites(), true);
}

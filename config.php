<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| VYRO STORE - Configuration
|--------------------------------------------------------------------------
*/

define('SITE_NAME', 'VYRO');
define('SITE_URL', 'http://localhost/vyro-store');

// إعدادات المتجر والعملة
define('VYRO_CURRENCY', 'USD');
define('VYRO_CURRENCY_SYMBOL', '$');
define('VYRO_MAX_QUANTITY', 10);
define('VYRO_ORDER_PREFIX', 'VYRO');

// المسارات
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('VYRO_DATA', DATA_PATH);

define('PRODUCTS_FILE', DATA_PATH . '/products.json');
define('ORDERS_FILE', DATA_PATH . '/orders.json');
define('MESSAGES_FILE', DATA_PATH . '/messages.json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Damascus');

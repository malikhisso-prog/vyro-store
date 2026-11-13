
# VYRO® — BUILT TO MOVE.

A modern Arabic RTL sneaker and streetwear e-commerce store.

## Features

- Responsive design for desktop, tablet, and mobile.
- Arabic RTL interface.
- Product catalog and product details.
- Product search, filtering, and sorting.
- Shopping cart.
- Checkout and order management.
- Customer contact form.
- Admin dashboard.
- Product management.
- Order management.
- Customer messages management.
- JSON-based data storage.
- CSRF protection and secure admin authentication.

## Requirements

- PHP 8.0 or newer.
- Apache web server.
- Apache mod_rewrite enabled.
- PHP JSON extension.
- PHP mbstring extension.

## Installation

1. Upload the project folder to your PHP hosting.
2. Make sure the `data` directory is writable by PHP.
3. Open `config.php` and configure the site settings if needed.
4. Configure the admin environment variables on your server.

## Admin Configuration

Set these environment variables on your hosting server:

- `VYRO_ADMIN_USERNAME`
- `VYRO_ADMIN_PASSWORD_HASH`

Generate a password hash using PHP:

```php
<?php
echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);
```

Copy the generated hash into `VYRO_ADMIN_PASSWORD_HASH`.

Never store your admin password directly in the source code.

## Admin Panel

Open:

`/admin/login.php`

Log in using the username and password configured on your server.

## Project Structure

```text
vyro-store/
│
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── data/
├── includes/
│
├── .htaccess
├── .gitignore
├── config.php
├── index.php
├── shop.php
├── product.php
├── cart.php
├── checkout.php
├── order-success.php
├── favorites.php
├── about.php
├── contact.php
├── shipping.php
├── returns.php
└── README.md
```

## Data Storage

The store uses JSON files to store:

- Products
- Orders
- Customer messages

Keep customer and order data private. Do not commit private customer information to a public repository.

## Technologies

- HTML5
- CSS3
- JavaScript
- PHP
- JSON

## Brand

**VYRO®**

BUILT TO MOVE.

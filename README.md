<div align="center">
    <img width="250" src="resources/images/xshop-logo.svg" alt="Zhonella logo">
</div>

# Zhonella

> [!NOTE]
> Zhonella is a private, Persian-first e-commerce platform for gold shops, built on Laravel 13, Bootstrap 5, and Vue 3.

## Features

- **Gold shop focused**: products with karat/proportion-aware pricing via `ProductPriceCalculator`
- **Full storefront**: home, category/product listing, product detail, cart, and checkout flow
- **Admin panel**: products, categories, props, quantities/stock, discounts, invoices, orders with an order board
- **Payments**: Zibal gateway integration (`app/Payment/Zibal.php`) plus payment receipts and customer credit
- **Customers & addresses**: customer accounts, addresses, states/cities, delivery management
- **Content**: posts, groups, menus, galleries, clips, comments, questions, rates, tickets
- **Multi-language ready**: `XLang` model with translatable models (Spatie packages)
- **Media & tags**: Spatie Media Library, Tags, and Translatable integration
- **API**: REST API for products, categories, posts, tags, states, and visitors
- **Admin tooling**: admin logs, shop visit tracking, dashboard stats, help catalog

## Stack

- PHP 8.3+ / Laravel 13
- Bootstrap 5 (native, RTL-aware) + RemixIcon
- Vue 3 + Vuex + Vite
- MySQL / MariaDB / SQLite
- Spatie: Media Library, Permission, Tags, Translatable
- laravel-mpdf (PDF invoices), php-qrcode, Zibal payment gateway

## Installation (development)

> [!IMPORTANT]
> Create a new database, copy `.env.example` to `.env`, and update your DB/app settings, then run:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

npm install
npm run dev

php artisan serv
```

> [!TIP]
> Default seeded logins: `developer@example.com` / `admin@example.com`, password: `password`

## Production build

```bash
npm run build
php artisan optimize
composer install --optimize-autoloader --no-dev
```

## Cron

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Custom artisan commands

```bash
php artisan make:xcontroller Model   # semi-automatic CRUD controller with logging
php artisan make:part PartName segmentName   # storefront theme part (blade/scss/js)
php artisan client   # compile client assets (scss/js/css)
```

## Structure

```
app/
├── Http/Controllers/Admin   # admin panel controllers
├── Http/Controllers/Api     # REST API
├── Models/                  # Eloquent models (Product, Invoice, Customer, ...)
├── Payment/                 # payment gateways (Zibal)
└── Services/                # pricing, cart quote, delivery, dashboard stats

resources/views/
├── admin/                   # admin panel (Bootstrap 5)
└── client/ website/         # storefront theme
```

<p align="center">
    Developed With Love ! ❤️
</p>

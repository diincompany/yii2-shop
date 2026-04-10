# yii2-shop

Reusable Shop module for Yii2 applications.

## Package

- **Name:** `diincompany/yii2-shop`
- **Namespace:** `diincompany\shop`
- **Module class:** `diincompany\shop\Module`
- **GitHub:** https://github.com/diincompany/yii2-shop
- **Requires:** PHP >=7.4, yiisoft/yii2 ~2.0.45

## Install

```bash
composer require diincompany/yii2-shop
```

The repository is public. No extra `repositories` entry is needed unless you want to pin to a specific branch during development:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/diincompany/yii2-shop.git"
    }
  ],
  "require": {
    "diincompany/yii2-shop": "dev-develop"
  }
}
```

## Register Module

In `config/web.php`:

```php
'modules' => [
    'shop' => [
        'class' => diincompany\shop\Module::class,

        // Optional: override the Yii component ID that provides ShopApiClientInterface
        // Default: 'diinapi'
        // 'apiClientComponent' => 'diinapi',

        // Optional: override layout used by shop controllers
        // 'layout' => '@app/views/layouts/page',

        // Optional: path to breadcrumb partial rendered by shop views
        // 'breadcrumbsView' => '@app/views/layouts/includes/breadcrumbs',

        // Optional: Yii component ID that implements ShopLoggerInterface
        // Falls back to 'logtail' component (wrapped), then NullShopLogger
        // 'loggerComponent' => 'shopLogger',

        // Optional: Yii component ID that implements ShopSessionContextInterface
        // Falls back to DefaultShopSessionContext (Yii session-based)
        // 'sessionContextComponent' => 'shopSessionContext',
    ],
],
```

## Register Routes

In `config/web.php`, merge shop routes into `urlManager`:

```php
$shopRoutes = '@vendor/diincompany/yii2-shop/routes.php';
if (is_file(Yii::getAlias($shopRoutes))) {
    $config['components']['urlManager']['rules'] = array_merge(
        $config['components']['urlManager']['rules'] ?? [],
        require Yii::getAlias($shopRoutes)
    );
}
```

Available routes include: `shop`, `shop/cart`, `shop/checkout`, `shop/confirmation/<hash>`, `shop/category/<slug>`, `shop/products/<slug>`, cart AJAX endpoints, and more. See [`routes.php`](routes.php) for the full list.

Important: cart AJAX endpoints are exposed under the `/shop/*` prefix (for example, `/shop/cart/add-item`).
Do not post to `/cart/*` or `/streetid-store/cart/*`.

## Host Integration Contracts

The module defines three contracts that the host application must satisfy:

### `ShopApiClientInterface` _(required)_

Resolved via the `apiClientComponent` config key (default: `'diinapi'`). Must be a Yii application component implementing `diincompany\shop\contracts\ShopApiClientInterface`.

If the configured component does not implement the contract, the module throws `yii\base\InvalidConfigException` with a descriptive message.

Key methods include: `getProducts()`, `getProduct()`, `getCategories()`, `postOrder()`, `getOrderByHash()`, `calculateShippingQuote()`, `getAccessToken()`, etc.

### `ShopLoggerInterface` _(optional)_

Resolved via `loggerComponent`. Falls back automatically to:
1. `logtail` app component, wrapped in `YiiComponentShopLogger`
2. `NullShopLogger` (silent no-op)

If `loggerComponent` is explicitly configured and does not implement the contract, the module throws `yii\base\InvalidConfigException`.

Interface: `info()`, `warning()`, `error()`, `critical()`, `debug()`

### `ShopSessionContextInterface` _(optional)_

Resolved via `sessionContextComponent`. Falls back to `DefaultShopSessionContext`, which uses Yii's built-in session.

If `sessionContextComponent` is explicitly configured and does not implement the contract, the module throws `yii\base\InvalidConfigException`.

Interface: `getAnonymousSessionId(bool $regenerate = false): string`

## Alias

The module registers the `@diinshop` alias pointing to its root directory on `init()`. Use it to reference module assets or views from host code:

```php
Yii::getAlias('@diinshop/views/...');
```

## Widgets

Available widgets under the `diincompany\shop\widgets` namespace:

| Widget | Class | Description |
|---|---|---|
| Cart sidebar | `cart\CartSidebar` | Slide-in cart panel with item list and totals |
| Add to cart | `AddToCartButton` | Button to add a product to cart |
| Product card | `product\ProductCard` | Product grid/list card |
| Item card | `item\ItemCard` | Compact order line item display |
| Category card | `CategoryCard` | Category thumbnail card |
| Search form | `search\SearchForm` | Product search input |
| Cart icon | `cart\CartIcon` | Header cart icon with item count badge |
| WhatsApp redirect | `WhatsAppRedirect` | Button to redirect to WhatsApp checkout |
| Turnstile | `TurnstileWidget` | Cloudflare Turnstile CAPTCHA widget |
| SEO meta | `SeoMeta` | Meta tags helper |

## i18n

The module self-registers the `shop*` translation category on `init()` and in each widget's `init()`. Source language is `en-US`; translation files live in `messages/`.

Usage in views:

```php
Yii::t('shop', 'Your Cart')
Yii::t('shop', 'Checkout')
```

No manual registration is needed in the host app.

## Monorepo Sync and Release

The package source lives in `module_yii2/shop` inside `streetid-store`.
Changes are published to `diincompany/yii2-shop` using a sync script. All commands run from the **host project root** (`streetid-store/`):

**Dry run (preview what would be synced):**

```bash
make shop-package-sync-dry
```

**Sync to the `develop` branch of the package repo:**

```bash
make shop-package-sync
```

**Publish a release tag to the `main` branch of the package repo:**

```bash
make shop-package-tag TAG=v1.0.0
```

**Refresh the host `composer.lock` after pulling new commits from the package:**

```bash
make shop-package-update-lock
```

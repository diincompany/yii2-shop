# yii2-shop

Reusable Shop module for Yii2 applications.

## Package

- Name: `diincompany/yii2-shop`
- Namespace: `DiinCompany\\Yii2Shop`
- Module class: `DiinCompany\\Yii2Shop\\Module`

## Install

```bash
composer require diincompany/yii2-shop
```

For private repository usage:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:diincompany/yii2-shop.git"
    }
  ]
}
```

## Register Module

```php
'modules' => [
    'shop' => [
        'class' => DiinCompany\Yii2Shop\Module::class,
        // Optional host overrides
        // 'layout' => '@app/views/layouts/page',
        // 'breadcrumbsView' => '@app/views/layouts/includes/breadcrumbs',
    ],
],
```

## Register Routes

```php
$shopRoutes = '@vendor/diincompany/yii2-shop/routes.php';
if (is_file(Yii::getAlias($shopRoutes))) {
    $config['components']['urlManager']['rules'] = array_merge(
        $config['components']['urlManager']['rules'] ?? [],
        require Yii::getAlias($shopRoutes)
    );
}
```

## Host Integration Requirements

Host app should provide compatible implementations for module contracts:

- `DiinCompany\\Yii2Shop\\contracts\\ShopApiClientInterface`
- `DiinCompany\\Yii2Shop\\contracts\\ShopLoggerInterface`
- `DiinCompany\\Yii2Shop\\contracts\\ShopSessionContextInterface`

## i18n

This module uses translation category `shop` and registers `shop*` internally.
Use:

```php
Yii::t('shop', 'Your Cart')
```

## Monorepo Sync and Release

The package source lives in `module_yii2/shop` inside this repository.
To publish changes to `diincompany/yii2-shop`, use the sync script based on `git subtree split`.

Dry run:

```bash
make shop-package-sync-dry
```

Synchronize branch:

```bash
make shop-package-sync
```

Publish a release tag (to package repo):

```bash
make shop-package-tag TAG=v1.0.0
```

After changing host `composer.json` constraints, refresh lockfile:

```bash
make shop-package-update-lock
```

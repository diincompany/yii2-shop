<?php
use diincompany\shop\assets\ProductAsset;
use diincompany\shop\widgets\SeoMeta;
use yii\helpers\Html;
use yii\helpers\Url;

ProductAsset::register($this);

$this->title = $product['name'];
$moduleId = trim((string) (Yii::$app->controller->module->id ?? ''), '/');
if ($moduleId === '' || $moduleId === Yii::$app->id) {
    $moduleId = 'shop';
}
$moduleRoute = '/' . $moduleId;

$this->params['breadcrumbs'][] = ['label' => Yii::t('shop','Tienda'), 'url' => [$moduleRoute]];
if($product['category'] ?? null) {
    $this->params['breadcrumbs'][] = ['label' => $product['category'] ? $product['category']['name'] : null, 'url' => $product['category'] ? [$moduleRoute . '/category/' . $product['category']['slug']] : null];
}
$this->params['breadcrumbs'][] = $this->title;

$productDescriptionRaw = (string)($product['description'] ?? '');
$shortDescription = trim((string) ($product['short_description'] ?? ''));
$hasDescriptionContent = trim(strip_tags($productDescriptionRaw)) !== '';
$productDescription = $hasDescriptionContent
    ? $productDescriptionRaw
    : ($shortDescription !== '' ? '<p>' . Html::encode($shortDescription) . '</p>' : '');

$formatPrice = static function ($price): array {
    $parts = explode('.', number_format((float) $price, 2, '.', ''));
    return [$parts[0], $parts[1]];
};

$stockKeys = [
    'stock',
    'available_stock',
    'inventory_quantity',
    'stock_quantity',
    'quantity_available',
    'available_quantity',
    'on_hand',
];

$resolveStockData = static function (array $source) use ($stockKeys): array {
    foreach ($stockKeys as $key) {
        if (!array_key_exists($key, $source)) {
            continue;
        }

        $value = $source[$key];
        if ($value === null || $value === '') {
            continue;
        }

        return [
            'value' => (int) $value,
            'explicit' => true,
        ];
    }

    return [
        'value' => 0,
        'explicit' => false,
    ];
};

$basePriceAmount = (float) ($product['price'] ?? 0);
$baseSalePriceAmount = (float) ($product['sale_price'] ?? 0);
$baseStockData = $resolveStockData($product);
$baseStock = $baseStockData['value'];
$backorderAvailable = (bool) ($product['backorder_available'] ?? false);
$backorderMessage = trim((string) ($product['backorder_message'] ?? ''));

$variantsRaw = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$variants = array_values(array_filter($variantsRaw, static function ($variant) {
    return (int) ($variant['active'] ?? 1) === 1;
}));

$hasVariants = (int) ($product['has_variants'] ?? ($product['product_has_variants'] ?? (!empty($variants) ? 1 : 0)));
$variantInventoryMode = (int) ($product['variant_inventory_mode'] ?? ($product['product_variant_inventory_mode'] ?? 0));

$extractVariantStock = static function (array $variant, int $fallbackStock) use ($resolveStockData): array {
    $stockData = $resolveStockData($variant);

    if ($stockData['explicit']) {
        return $stockData;
    }

    return [
        'value' => $fallbackStock,
        'explicit' => false,
    ];
};

$hasVariantStockValues = false;
foreach ($variants as $variantItem) {
    if ($extractVariantStock($variantItem, $baseStock)['explicit']) {
        $hasVariantStockValues = true;
        break;
    }
}

$shouldEnforceVariantStock = $hasVariants === 1 && (
    $variantInventoryMode === 2 ||
    ($variantInventoryMode === 0 && $hasVariantStockValues)
);

$variantSelectorLabel = Yii::t('shop', 'Variant');
$variantOptionNames = [];
$variantOptionGroupsMap = [];

foreach ($variants as $variant) {
    $optionValues = is_array($variant['option_values'] ?? null) ? $variant['option_values'] : [];

    foreach ($optionValues as $optionValue) {
        $optionName = trim((string) ($optionValue['option_name'] ?? ''));
        $optionValueText = trim((string) ($optionValue['value'] ?? ''));

        if ($optionName === '') {
            continue;
        }

        $normalizedOptionName = mb_strtolower($optionName, 'UTF-8');

        if (!array_key_exists($normalizedOptionName, $variantOptionNames)) {
            $variantOptionNames[$normalizedOptionName] = $optionName;
        }

        if (!array_key_exists($normalizedOptionName, $variantOptionGroupsMap)) {
            $variantOptionGroupsMap[$normalizedOptionName] = [
                'key' => $normalizedOptionName,
                'label' => $optionName,
                'values' => [],
            ];
        }

        if ($optionValueText === '') {
            continue;
        }

        $normalizedOptionValue = mb_strtolower(preg_replace('/\s+/', ' ', $optionValueText) ?? '', 'UTF-8');

        if (!array_key_exists($normalizedOptionValue, $variantOptionGroupsMap[$normalizedOptionName]['values'])) {
            $variantOptionGroupsMap[$normalizedOptionName]['values'][$normalizedOptionValue] = [
                'value' => $normalizedOptionValue,
                'label' => $optionValueText,
            ];
        }
    }
}

$variantOptionGroups = [];
foreach ($variantOptionGroupsMap as $group) {
    $group['values'] = array_values($group['values']);
    $variantOptionGroups[] = $group;
}

if (count($variantOptionNames) === 1) {
    $variantSelectorLabel = (string) reset($variantOptionNames);
}

$normalizeVariantText = static function (string $value): string {
    $normalized = mb_strtolower(trim($value), 'UTF-8');

    return preg_replace('/\s+/', ' ', $normalized) ?? '';
};

$buildVariantLabel = static function (array $variant) use ($normalizeVariantText): string {
    $name = trim((string) ($variant['name'] ?? ''));
    $optionValues = is_array($variant['option_values'] ?? null) ? $variant['option_values'] : [];
    $optionSummary = [];
    $optionValueTexts = [];

    foreach ($optionValues as $value) {
        $optionName = trim((string) ($value['option_name'] ?? ''));
        $optionValue = trim((string) ($value['value'] ?? ''));

        if ($optionValue !== '') {
            $optionValueTexts[] = $optionValue;
        }

        if ($optionName !== '' && $optionValue !== '') {
            $optionSummary[] = $optionName . ': ' . $optionValue;
        } elseif ($optionValue !== '') {
            $optionSummary[] = $optionValue;
        }
    }

    $summaryText = implode(' / ', $optionSummary);

    if ($name !== '') {
        $normalizedName = $normalizeVariantText($name);

        if (count($optionValueTexts) === 1 && $normalizeVariantText($optionValueTexts[0]) === $normalizedName) {
            return $name;
        }

        if ($summaryText !== '') {
            return $name . ' - ' . $summaryText;
        }

        return $name;
    }

    return $summaryText !== '' ? $summaryText : Yii::t('shop', 'Variant');
};

$defaultVariant = null;
foreach ($variants as $variant) {
    if ((int) ($variant['is_default'] ?? 0) === 1) {
        $defaultVariant = $variant;
        break;
    }
}

if ($defaultVariant === null && !empty($variants)) {
    $defaultVariant = $variants[0];
}

$defaultVariantStockData = $defaultVariant !== null
    ? $extractVariantStock($defaultVariant, $baseStock)
    : ['value' => $baseStock, 'explicit' => false];

if ($defaultVariant !== null && $shouldEnforceVariantStock && $defaultVariantStockData['explicit'] && $defaultVariantStockData['value'] <= 0) {
    foreach ($variants as $variantOption) {
        $variantOptionStockData = $extractVariantStock($variantOption, $baseStock);

        if (!$variantOptionStockData['explicit'] || $variantOptionStockData['value'] > 0) {
            $defaultVariant = $variantOption;
            $defaultVariantStockData = $variantOptionStockData;
            break;
        }
    }
}

$selectedPriceAmount = $basePriceAmount;
$selectedSalePriceAmount = $baseSalePriceAmount;
$selectedStock = $baseStock;
$selectedVariantTracksStock = false;

if ($defaultVariant !== null) {
    if (($defaultVariant['price'] ?? null) !== null && $defaultVariant['price'] !== '') {
        $selectedPriceAmount = (float) $defaultVariant['price'];
    }

    if (($defaultVariant['sale_price'] ?? null) !== null && $defaultVariant['sale_price'] !== '') {
        $selectedSalePriceAmount = (float) $defaultVariant['sale_price'];
    }

    $selectedStock = $defaultVariantStockData['value'];
    $selectedVariantTracksStock = $shouldEnforceVariantStock && $defaultVariantStockData['explicit'];
}

$selectedPrice = $formatPrice($selectedPriceAmount);
$selectedSalePrice = $selectedSalePriceAmount > 0 ? $formatPrice($selectedSalePriceAmount) : null;
$defaultVariantOptions = [];

if ($defaultVariant !== null) {
    $defaultOptionValues = is_array($defaultVariant['option_values'] ?? null) ? $defaultVariant['option_values'] : [];

    foreach ($defaultOptionValues as $defaultOptionValue) {
        $optionName = trim((string) ($defaultOptionValue['option_name'] ?? ''));
        $optionValue = trim((string) ($defaultOptionValue['value'] ?? ''));

        if ($optionName === '' || $optionValue === '') {
            continue;
        }

        $defaultVariantOptions[mb_strtolower($optionName, 'UTF-8')] = mb_strtolower(
            preg_replace('/\s+/', ' ', $optionValue) ?? '',
            'UTF-8'
        );
    }
}

if (!empty($variants)) {
    $isProductAvailable = $selectedVariantTracksStock ? ($selectedStock > 0 || $backorderAvailable) : true;
} else {
    $isProductAvailable = $selectedStock > 0 || $backorderAvailable;
}

$seoMainImage = (string) (
    $product['product_images'][0]['url'] ??
    $product['productImages'][0]['url'] ??
    $product['main_image'] ??
    ''
);

$seoDescription = $shortDescription !== ''
    ? $shortDescription
    : strip_tags($productDescription);

echo SeoMeta::widget([
    'type'        => SeoMeta::TYPE_PRODUCT,
    'title'       => $product['name'],
    'description' => $seoDescription,
    'image'       => $seoMainImage,
    'url'         => Url::to([$moduleRoute . '/' . $product['slug']], true),
    'product'     => $product,
    'price'       => $selectedPriceAmount,
    'salePrice'   => $selectedSalePriceAmount > 0 ? $selectedSalePriceAmount : null,
    'inStock'     => $isProductAvailable,
    'breadcrumbs' => $this->params['breadcrumbs'] ?? [],
]);

$mainImage = null;
$thumbnails = [];

// Handle new API structure with product_images array
$productImages = $product['product_images'] ?? $product['productImages'] ?? [];

foreach($productImages as $img) {
    if(isset($img['is_main']) && $img['is_main']) {
        $mainImage = $img;
    } else {
        $thumbnails[] = $img;
    }
}

if(!$mainImage && !empty($productImages)) {
    $mainImage = $productImages[0];
}

$imageUrl = ($mainImage['url'] ?? $product['main_image'] ?? 'https://placehold.net/product-600x800.png');

$variantsForJs = [];
$variantStocksById = [];
$variantTracksStockById = [];
foreach ($variants as $variant) {
    $variantId = trim((string) ($variant['id'] ?? ''));
    if ($variantId === '') {
        continue;
    }

    $variantStockData = $extractVariantStock($variant, $baseStock);
    $variantStock = $variantStockData['value'];
    $variantTracksStock = $shouldEnforceVariantStock && $variantStockData['explicit'];
    $isSelectable = !$variantTracksStock || $variantStock > 0 || $backorderAvailable;
    $variantOptionsMap = [];
    $variantOptionValues = is_array($variant['option_values'] ?? null) ? $variant['option_values'] : [];

    foreach ($variantOptionValues as $optionValue) {
        $optionName = trim((string) ($optionValue['option_name'] ?? ''));
        $optionValueText = trim((string) ($optionValue['value'] ?? ''));

        if ($optionName === '' || $optionValueText === '') {
            continue;
        }

        $variantOptionsMap[mb_strtolower($optionName, 'UTF-8')] = mb_strtolower(
            preg_replace('/\s+/', ' ', $optionValueText) ?? '',
            'UTF-8'
        );
    }

    $variantStocksById[$variantId] = $variantStock;
    $variantTracksStockById[$variantId] = $variantTracksStock;

    $variantsForJs[] = [
        'id' => $variantId,
        'label' => $buildVariantLabel($variant),
        'options' => $variantOptionsMap,
        'price' => ($variant['price'] ?? null) !== null && $variant['price'] !== '' ? (float) $variant['price'] : $basePriceAmount,
        'sale_price' => ($variant['sale_price'] ?? null) !== null && $variant['sale_price'] !== '' ? (float) $variant['sale_price'] : 0,
        'stock' => $variantStock,
        'track_stock' => $variantTracksStock ? 1 : 0,
        'is_selectable' => $isSelectable ? 1 : 0,
        'backorder_available' => $backorderAvailable ? 1 : 0,
    ];
}

$gaTrackerClass = 'diincompany\\yii2googleanalytics\\EcommerceTracker';
$gaItemPayload = [];

if (class_exists($gaTrackerClass)) {
    $gaItemPayload = $gaTrackerClass::buildItem(
        (string) ($product['sku'] ?? $product['id'] ?? ''),
        (string) ($product['name'] ?? ''),
        (float) ($selectedSalePriceAmount > 0 ? $selectedSalePriceAmount : $selectedPriceAmount),
        1,
        (string) ($product['category']['name'] ?? ''),
        (string) (($product['brand']['name'] ?? $product['brand_name'] ?? 'StreetID')),
        is_array($defaultVariant) ? $buildVariantLabel($defaultVariant) : ''
    );

    $viewItemJs = $gaTrackerClass::viewItemJs(
        $gaItemPayload,
        (float) ($selectedSalePriceAmount > 0 ? $selectedSalePriceAmount : $selectedPriceAmount),
        'HNL'
    );

    $this->registerJs(
        'if (typeof window.gtag === "function") { ' . $viewItemJs . ' }',
        \yii\web\View::POS_END,
        'shop-ga4-view-item-' . (string) ($product['id'] ?? uniqid('product-', false))
    );
}
?>
<div class="shop-module shop-product-page">

<?= $this->render('includes/_product_content', [
    'product' => $product,
    'productImages' => $productImages,
    'mainImage' => $mainImage,
    'thumbnails' => $thumbnails,
    'imageUrl' => $imageUrl,
    'productDescription' => $productDescription,
    'selectedSalePrice' => $selectedSalePrice,
    'selectedPrice' => $selectedPrice,
    'isProductAvailable' => $isProductAvailable,
    'variants' => $variants,
    'variantSelectorLabel' => $variantSelectorLabel,
    'variantOptionGroups' => $variantOptionGroups,
    'variantStocksById' => $variantStocksById,
    'variantTracksStockById' => $variantTracksStockById,
    'shouldEnforceVariantStock' => $shouldEnforceVariantStock,
    'defaultVariant' => $defaultVariant,
    'defaultVariantOptions' => $defaultVariantOptions,
    'buildVariantLabel' => $buildVariantLabel,
    'selectedStock' => $selectedStock,
    'selectedVariantTracksStock' => $selectedVariantTracksStock,
    'backorderAvailable' => $backorderAvailable,
    'backorderMessage' => $backorderMessage,
    'gaItemPayload' => $gaItemPayload,
]) ?>

<?= $this->render('includes/_related_products', [
    'relatedProducts' => $product['related_products'] ?? [],
]) ?>

<?= $this->render('includes/_variant_selector_script', [
    'variantsForJs' => $variantsForJs,
    'defaultVariantOptions' => $defaultVariantOptions,
    'product' => $product,
]) ?>
</div>

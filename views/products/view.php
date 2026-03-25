<?php
use DiinCompany\Yii2Shop\widgets\SeoMeta;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $product['name'];
$moduleRoute = '/' . trim((string) (Yii::$app->controller->module->id ?? 'shop'), '/');

$this->params['breadcrumbs'][] = ['label' => Yii::t('shop','Tienda'), 'url' => [$moduleRoute]];
$this->params['breadcrumbs'][] = ['label' => $product['category'] ? $product['category']['name'] : null, 'url' => $product['category'] ? [$moduleRoute . '/category/' . $product['category']['slug']] : null];
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

$basePriceAmount = (float) ($product['price'] ?? 0);
$baseSalePriceAmount = (float) ($product['sale_price'] ?? 0);
$baseStock = (int) ($product['stock'] ?? 0);

$variantsRaw = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$variants = array_values(array_filter($variantsRaw, static function ($variant) {
    return (int) ($variant['active'] ?? 1) === 1;
}));

$hasVariants = (int) ($product['has_variants'] ?? ($product['product_has_variants'] ?? (!empty($variants) ? 1 : 0)));
$variantInventoryMode = (int) ($product['variant_inventory_mode'] ?? ($product['product_variant_inventory_mode'] ?? 0));

$extractVariantStock = static function (array $variant, int $fallbackStock): int {
    if (($variant['stock'] ?? null) !== null && $variant['stock'] !== '') {
        return (int) $variant['stock'];
    }

    return $fallbackStock;
};

$hasVariantStockValues = false;
foreach ($variants as $variantItem) {
    if (($variantItem['stock'] ?? null) !== null && $variantItem['stock'] !== '') {
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

foreach ($variants as $variant) {
    $optionValues = is_array($variant['option_values'] ?? null) ? $variant['option_values'] : [];

    foreach ($optionValues as $optionValue) {
        $optionName = trim((string) ($optionValue['option_name'] ?? ''));

        if ($optionName === '') {
            continue;
        }

        $normalizedOptionName = mb_strtolower($optionName, 'UTF-8');

        if (!array_key_exists($normalizedOptionName, $variantOptionNames)) {
            $variantOptionNames[$normalizedOptionName] = $optionName;
        }
    }
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

if ($defaultVariant !== null && $shouldEnforceVariantStock && $extractVariantStock($defaultVariant, $baseStock) <= 0) {
    foreach ($variants as $variantOption) {
        if ($extractVariantStock($variantOption, $baseStock) > 0) {
            $defaultVariant = $variantOption;
            break;
        }
    }
}

$selectedPriceAmount = $basePriceAmount;
$selectedSalePriceAmount = $baseSalePriceAmount;
$selectedStock = $baseStock;

if ($defaultVariant !== null) {
    if (($defaultVariant['price'] ?? null) !== null && $defaultVariant['price'] !== '') {
        $selectedPriceAmount = (float) $defaultVariant['price'];
    }

    if (($defaultVariant['sale_price'] ?? null) !== null && $defaultVariant['sale_price'] !== '') {
        $selectedSalePriceAmount = (float) $defaultVariant['sale_price'];
    }

    if (($defaultVariant['stock'] ?? null) !== null && $defaultVariant['stock'] !== '') {
        $selectedStock = (int) $defaultVariant['stock'];
    }
}

$selectedPrice = $formatPrice($selectedPriceAmount);
$selectedSalePrice = $selectedSalePriceAmount > 0 ? $formatPrice($selectedSalePriceAmount) : null;

if (!empty($variants)) {
    $isProductAvailable = $shouldEnforceVariantStock ? $selectedStock > 0 : true;
} else {
    $isProductAvailable = $selectedStock > 0;
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

$imageUrl = ($mainImage['url'] ?? $product['main_image'] ?? 'https://ik.imagekit.io/ready/diin/img/site/placeholder.png');

$variantsForJs = [];
$variantStocksById = [];
foreach ($variants as $variant) {
    $variantId = trim((string) ($variant['id'] ?? ''));
    if ($variantId === '') {
        continue;
    }

    $variantStock = $extractVariantStock($variant, $baseStock);
    $isSelectable = !$shouldEnforceVariantStock || $variantStock > 0;

    $variantStocksById[$variantId] = $variantStock;

    $variantsForJs[] = [
        'id' => $variantId,
        'label' => $buildVariantLabel($variant),
        'price' => ($variant['price'] ?? null) !== null && $variant['price'] !== '' ? (float) $variant['price'] : $basePriceAmount,
        'sale_price' => ($variant['sale_price'] ?? null) !== null && $variant['sale_price'] !== '' ? (float) $variant['sale_price'] : 0,
        'stock' => $variantStock,
        'track_stock' => $shouldEnforceVariantStock ? 1 : 0,
        'is_selectable' => $isSelectable ? 1 : 0,
    ];
}
?>

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
    'variantStocksById' => $variantStocksById,
    'shouldEnforceVariantStock' => $shouldEnforceVariantStock,
    'defaultVariant' => $defaultVariant,
    'buildVariantLabel' => $buildVariantLabel,
    'selectedStock' => $selectedStock,
]) ?>

<?= $this->render('includes/_product_tabs', [
    'productDescription' => $productDescription,
]) ?>

<?= $this->render('includes/_related_products', [
    'relatedProducts' => $product['related_products'] ?? [],
]) ?>

<?= $this->render('includes/_variant_selector_script', [
    'variantsForJs' => $variantsForJs,
]) ?>

<?php
/**
 * @var yii\web\View $this
 * @var array $product
 * @var array $productImages
 * @var array|null $mainImage
 * @var array $thumbnails
 * @var string $imageUrl
 * @var string $productDescription
 * @var array|null $selectedSalePrice
 * @var array $selectedPrice
 * @var bool $isProductAvailable
 * @var array $variants
 * @var string $variantSelectorLabel
 * @var array $variantOptionGroups
 * @var array $variantStocksById
 * @var bool $shouldEnforceVariantStock
 * @var array|null $defaultVariant
 * @var array $defaultVariantOptions
 * @var callable $buildVariantLabel
 * @var int $selectedStock
 * @var bool $backorderAvailable
 * @var string $backorderMessage
 */
?>

<section class="pt-5 pb-6 pb-md-10">
    <div class="container px-4 px-lg-5 my-5">
        <div class="row gx-4 gx-lg-5 align-items-center">
            <?= $this->render('_product_gallery', [
                'product' => $product,
                'productImages' => $productImages,
                'mainImage' => $mainImage,
                'thumbnails' => $thumbnails,
                'imageUrl' => $imageUrl,
            ]) ?>

            <?= $this->render('_product_info', [
                'product' => $product,
                'productDescription' => $productDescription,
                'selectedSalePrice' => $selectedSalePrice,
                'selectedPrice' => $selectedPrice,
                'isProductAvailable' => $isProductAvailable,
                'variants' => $variants,
                'variantSelectorLabel' => $variantSelectorLabel,
                'variantOptionGroups' => $variantOptionGroups,
                'variantStocksById' => $variantStocksById,
                'shouldEnforceVariantStock' => $shouldEnforceVariantStock,
                'defaultVariant' => $defaultVariant,
                'defaultVariantOptions' => $defaultVariantOptions,
                'buildVariantLabel' => $buildVariantLabel,
                'selectedStock' => $selectedStock,
                'backorderAvailable' => $backorderAvailable,
                'backorderMessage' => $backorderMessage,
            ]) ?>
        </div>
    </div>
</section>

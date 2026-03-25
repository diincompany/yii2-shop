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
 * @var array $variantStocksById
 * @var bool $shouldEnforceVariantStock
 * @var array|null $defaultVariant
 * @var callable $buildVariantLabel
 * @var int $selectedStock
 */
?>

<section class="pt-5 pb-6 pb-md-10">
    <div class="container">
        <div class="row">
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
                'variantStocksById' => $variantStocksById,
                'shouldEnforceVariantStock' => $shouldEnforceVariantStock,
                'defaultVariant' => $defaultVariant,
                'buildVariantLabel' => $buildVariantLabel,
                'selectedStock' => $selectedStock,
            ]) ?>
        </div>
    </div>
</section>

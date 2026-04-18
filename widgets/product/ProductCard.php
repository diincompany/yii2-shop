<?php

namespace diincompany\shop\widgets\product;

use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\Widget;

class ProductCard extends Widget
{
    public const VARIANT_DEFAULT = 'default';
    public const VARIANT_MINIMAL = 'minimal';
    public const VARIANT_SMALL = 'small';

    public $product = [];
    public $variant = self::VARIANT_DEFAULT;
    public $fallbackImage = 'https://placehold.net/product.png';
    public $detailsLabel;

    private array $normalizedProduct = [];

    public function init(): void
    {
        parent::init();
        ShopModule::registerTranslations();

        $this->product = is_array($this->product) ? $this->product : [];

        if (!in_array($this->variant, [self::VARIANT_DEFAULT, self::VARIANT_MINIMAL, self::VARIANT_SMALL], true)) {
            $this->variant = self::VARIANT_DEFAULT;
        }

        $this->detailsLabel = $this->detailsLabel ?: Yii::t('shop', 'Details');
        $this->normalizedProduct = $this->normalizeProduct($this->product);
    }

    public function run(): string
    {
        if ($this->normalizedProduct['slug'] === '') {
            return '';
        }

        return $this->render($this->resolveView(), [
            'product' => $this->normalizedProduct,
            'detailsLabel' => $this->detailsLabel,
        ]);
    }

    private function resolveView(): string
    {
        if ($this->variant === self::VARIANT_SMALL) {
            return 'card-small';
        }

        if ($this->variant === self::VARIANT_MINIMAL) {
            return 'card-minimal';
        }

        return 'card-default';
    }

    private function normalizeProduct(array $product): array
    {
        $productId = (int)($product['id'] ?? 0);
        $slug = (string)($product['slug'] ?? '');
        $name = (string)($product['name'] ?? '');
        $shortDescription = (string)($product['short_description'] ?? '');
        $mainImage = (string)($product['main_image'] ?? $this->fallbackImage);
        $price = (float)($product['price'] ?? 0);
        $salePrice = (float)($product['sale_price'] ?? 0);
        $stock = (int)($product['stock'] ?? 0);
        $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
        $hasVariantsFlag = (int)($product['has_variants'] ?? ($product['product_has_variants'] ?? 0)) === 1;
        $hasVariants = $hasVariantsFlag || !empty($variants);
        $hasDiscount = $salePrice > 0 && $salePrice < $price;
        $currentPrice = $hasDiscount ? $salePrice : $price;
        $backorderAvailable = (bool)($product['backorder_available'] ?? false);
        $backorderMessage = trim((string)($product['backorder_message'] ?? ''));
        $discountPercent = $hasDiscount && $price > 0
            ? (int)round((($price - $salePrice) / $price) * 100)
            : 0;

        // Short description must have no more than 50 characters
        if (mb_strlen($shortDescription) > 50) {
            $shortDescription = mb_substr($shortDescription, 0, 47) . '...';
        }

        return [
            'id' => $productId,
            'slug' => $slug,
            'name' => $name,
            'short_description' => $shortDescription,
            'image_default' => $this->withTransform($mainImage, 'w-400,h-400'),
            'image_small' => $this->withTransform($mainImage, 'w-300,h-300'),
            'price' => $price,
            'sale_price' => $salePrice,
            'current_price' => $currentPrice,
            'has_sale_price' => $salePrice > 0,
            'has_discount' => $hasDiscount,
            'original_price' => $hasDiscount ? $price : null,
            'discount_percent' => $discountPercent,
            'has_variants' => $hasVariants,
            'stock' => $stock,
            'backorder_available' => $backorderAvailable,
            'backorder_message' => $backorderMessage,
            'is_new' => !empty($product['is_new']),
            'rating' => (int)($product['rating'] ?? 0),
        ];
    }

    private function withTransform(string $imageUrl, string $transform): string
    {
        $baseUrl = $imageUrl !== '' ? $imageUrl : $this->fallbackImage;
        $separator = strpos($baseUrl, '?') === false ? '?' : '&';

        return $baseUrl . $separator . 'tr=' . $transform;
    }
}

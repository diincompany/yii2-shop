<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var array $product
 * @var string $detailsLabel
 */
?>

<div class="product-card-4 rounded overflow-hidden">
    <div class="product-card-image">
        <a href="<?= Url::to(['/shop/products/view', 'slug' => $product['slug']]) ?>">
            <img src="<?= Html::encode($product['image_small']) ?>"
                 alt="<?= Html::encode($product['name']) ?>"
                 title="<?= Html::encode($product['name']) ?>"
                 class="img-fluid"
                 loading="lazy">
        </a>
    </div>

    <div class="product-card-info">
        <h6 class="product-title">
            <a href="<?= Url::to(['/shop/products/view', 'slug' => $product['slug']]) ?>" tabindex="0">
                <?= Html::encode($product['name']) ?>
            </a>
        </h6>

        <div class="product-price">
            <?php if ($product['has_sale_price']): ?>
                <span class="text-primary">L. <?= number_format($product['sale_price'], 2) ?></span>
                <del class="fs-sm text-muted">L. <?= number_format($product['price'], 2) ?></del>
            <?php else: ?>
                <span class="text-primary">L. <?= number_format($product['price'], 2) ?></span>
            <?php endif; ?>
        </div>

        <div class="produc-card-cart">
            <a class="link-effect" href="<?= Url::to(['/shop/products/view', 'slug' => $product['slug']]) ?>">
                <?= Html::encode($detailsLabel) ?>
            </a>
        </div>
    </div>
</div>

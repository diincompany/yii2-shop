<?php

use DiinCompany\Yii2Shop\widgets\AddToCartButton;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var array $product
 * @var string $detailsLabel
 */

$moduleRoute = '/' . trim((string) (Yii::$app->controller->module->id ?? 'shop'), '/');
?>

<div class="product-card-8">
    <div class="product-card-image">
        <?php if ($product['has_discount']): ?>
            <div class="badge-ribbon">
                <span class="badge bg-danger"><?= $product['discount_percent'] ?>% OFF</span>
            </div>
        <?php endif; ?>

        <?php if ($product['is_new']): ?>
            <div class="badge-ribbon badge-ribbon-right">
                <span class="badge bg-success"><?= Yii::t('shop', 'New') ?></span>
            </div>
        <?php endif; ?>

        <div class="product-media">
        <a href="<?= Url::to([$moduleRoute . '/products/view', 'slug' => $product['slug']]) ?>">
            <img src="<?= Html::encode($product['image_default']) ?>"
                 alt="<?= Html::encode($product['name']) ?>"
                 title="<?= Html::encode($product['name']) ?>"
                 class="img-fluid"
                 loading="lazy">
        </a>
        </div>
    </div>

    <div class="product-card-info">
        <?php if ($product['rating'] > 0): ?>
            <div class="rating-star text">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $product['rating'] ? '-fill active' : '' ?>"></i>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <h6 class="product-title">
            <a href="<?= Url::to([$moduleRoute . '/products/view', 'slug' => $product['slug']]) ?>">
                <?= Html::encode($product['name']) ?>
            </a>
        </h6>

        <div class="product-price">
            <span class="text-primary">
                <?= Yii::$app->formatter->asCurrency($product['current_price']) ?>
            </span>
            <?php if ($product['has_discount']): ?>
                <del class="fs-sm text-muted">
                    <?= Yii::$app->formatter->asCurrency($product['original_price']) ?>
                </del>
            <?php endif; ?>
        </div>

        <?php if ($product['id'] > 0): ?>
            <div class="product-cart-btn">
                <?php if ($product['has_variants']): ?>
                    <a class="btn btn-mode me-3" href="<?= Url::to([$moduleRoute . '/products/view', 'slug' => $product['slug']]) ?>">
                        <?= Html::encode(Yii::t('shop', 'See more')) ?>
                    </a>
                <?php else: ?>
                    <?= AddToCartButton::widget([
                        'productId' => $product['id'],
                        'quantity' => 1,
                    ]) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var array $product
 * @var string $detailsLabel
 */

$moduleId = trim((string) (Yii::$app->controller->module->id ?? ''), '/');
if ($moduleId === '' || $moduleId === Yii::$app->id) {
    $moduleId = 'shop';
}
$moduleRoute = '/' . $moduleId;
?>

<div class="product-card-minimal h-100">
    <a
        class="card lift h-100"
        href="<?= Url::to([$moduleRoute . '/products/view', 'slug' => $product['slug']]) ?>"
        data-ga-select-item="1"
        data-ga-product-id="<?= (int) ($product['id'] ?? 0) ?>"
    >
        <div class="card-flag card-flag-dark card-flag-top-right card-flag-lg">
            <?= Yii::$app->formatter->asCurrency($product['current_price']) ?>
            <?php if ($product['has_discount']): ?>
                <del class="small text-muted ms-2">
                    <?= Yii::$app->formatter->asCurrency($product['original_price']) ?>
                </del>
            <?php endif; ?>
        </div>
        <img class="card-img-top" src="<?= Html::encode($product['image_small']) ?>"
                 alt="<?= Html::encode($product['name']) ?>"
                 title="<?= Html::encode($product['name']) ?>"
                 loading="lazy">
        <div class="card-body p-3">
            <div class="card-title small mb-0"><?= Html::encode($product['name']) ?></div>
            <div class="text-xs text-gray-500"><?= Html::encode($product['short_description'] ?? '') ?></div>
        </div>
    </a>
</div>

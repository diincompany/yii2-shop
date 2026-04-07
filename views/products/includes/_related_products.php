<?php

use diincompany\shop\widgets\product\ProductCard;

/**
 * @var array $relatedProducts
 */

$relatedProducts = is_array($relatedProducts ?? null) ? $relatedProducts : [];

if (empty($relatedProducts)) {
    return;
}

?>

<section class="pb-6 py-md-6 pb-lg-10 pt-lg-5 bg-white">
    <div class="container">
        <h4 class="mb-5 text-center text-uppercase fw-light"><?= Yii::t('shop', 'productos_relacionados') ?></h4>
        <div class="row g-4">
            <?php foreach (array_slice($relatedProducts, 0, 6) as $relatedProduct): ?>
                <?php
                $slug = (string) ($relatedProduct['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                ?>
                <div class="col-md-6 col-lg-2">
                    <?= ProductCard::widget([
                        'product' => $relatedProduct,
                        'variant' => Yii::$app->params['shop']['products']['cardVariant'] ?? ProductCard::VARIANT_DEFAULT,
                    ]) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
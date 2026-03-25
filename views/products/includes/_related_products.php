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

<section class="pb-6 py-md-6 pb-lg-10 pt-lg-5">
    <div class="container">
        <h4 class="mb-5"><?= Yii::t('shop', 'productos_relacionados') ?></h4>
        <div class="row g-4">
            <?php foreach (array_slice($relatedProducts, 0, 5) as $relatedProduct): ?>
                <?php
                $slug = (string) ($relatedProduct['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                ?>
                <div class="col-md-6 col-lg-4 col-xl-2-4">
                    <?= ProductCard::widget([
                        'product' => $relatedProduct,
                        'variant' => ProductCard::VARIANT_SMALL,
                    ]) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
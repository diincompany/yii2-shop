<?php

use yii\helpers\Html;

/**
 * @var array $product
 * @var array $productImages
 * @var array|null $mainImage
 * @var array $thumbnails
 * @var string $imageUrl
 */
?>

<div class="col-lg-6">
    <div class="product-gallery-image">
        <div class="swiper swiper_main_gallery">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="pd-gallery-slide">
                        <a class="gallery-link" href="<?= $imageUrl ?>?tr=w-1200,h-1200">
                            <?= Html::img($imageUrl . '?tr=w-600,h-800', ['class' => 'img-fluid rounded', 'alt' => $product['name']]) ?>
                        </a>
                    </div>
                </div>
                <?php foreach ($thumbnails as $img): ?>
                    <div class="swiper-slide">
                        <div class="pd-gallery-slide">
                            <a class="gallery-link" href="<?= $img['url'] ?>?tr=w-1200,h-1200">
                                <?= Html::img($img['url'] . '?tr=w-600,h-800', ['class' => 'img-fluid rounded', 'alt' => $product['name']]) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (count($productImages) > 1): ?>
            <div class="swiper swiper_thumb_gallery product-thumb">
                <div class="swiper-wrapper">
                    <?php if ($mainImage): ?>
                        <div class="swiper-slide">
                            <div class="pd-gallery-slide-thumb">
                                <?= Html::img($mainImage['url'] . '?tr=w-150,h-150', ['class' => 'img-fluid', 'alt' => $product['name']]) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($thumbnails as $img): ?>
                        <div class="swiper-slide">
                            <div class="pd-gallery-slide-thumb">
                                <?= Html::img($img['url'] . '?tr=w-150,h-150', ['class' => 'img-fluid', 'alt' => $product['name']]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-arrow-style-03 swiper-next swiper-next-pd-details_thumb"><i class="bi bi-chevron-right"></i></div>
                <div class="swiper-arrow-style-03 swiper-prev swiper-prev-pd-details_thumb"><i class="bi bi-chevron-left"></i></div>
            </div>
        <?php endif; ?>
    </div>
</div>

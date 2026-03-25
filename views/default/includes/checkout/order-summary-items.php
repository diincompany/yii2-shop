<?php
/**
 * Checkout order summary items.
 *
 * @var array $items
 * @var string $moduleRoute
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<ul class="list-unstyled m-0 p-0" id="checkout-items">
    <?php foreach ($items as $item): ?>
        <?php
            $variantLabel = trim((string) ($item['variant_name'] ?? ($item['product_variant_name'] ?? '')));
            if ($variantLabel === '' && !empty($item['variant']) && is_array($item['variant'])) {
                $variantLabel = trim((string) ($item['variant']['name'] ?? ''));
            }

            if ($variantLabel === '' && !empty($item['variant_snapshot']) && is_array($item['variant_snapshot'])) {
                $variantLabel = trim((string) ($item['variant_snapshot']['name'] ?? ''));
            }

            if ($variantLabel === '' && !empty($item['option_values']) && is_array($item['option_values'])) {
                $parts = [];
                foreach ($item['option_values'] as $optionValue) {
                    if (!is_array($optionValue)) {
                        continue;
                    }

                    $optionName = trim((string) ($optionValue['option_name'] ?? ''));
                    $value = trim((string) ($optionValue['value'] ?? ''));

                    if ($optionName !== '' && $value !== '') {
                        $parts[] = $optionName . ': ' . $value;
                    } elseif ($value !== '') {
                        $parts[] = $value;
                    }
                }

                $variantLabel = implode(' / ', $parts);
            }

            if (!empty($item['variant_snapshot']) && is_array($item['variant_snapshot']) && !empty($item['variant_snapshot']['option_values']) && is_array($item['variant_snapshot']['option_values'])) {
                $parts = [];
                foreach ($item['variant_snapshot']['option_values'] as $optionValue) {
                    if (!is_array($optionValue)) {
                        continue;
                    }

                    $optionName = trim((string) ($optionValue['option_name'] ?? ''));
                    $value = trim((string) ($optionValue['value'] ?? ''));

                    if ($optionName !== '' && $value !== '') {
                        $parts[] = $optionName . ': ' . $value;
                    } elseif ($value !== '') {
                        $parts[] = $value;
                    }
                }

                $optionsLabel = implode(' / ', $parts);
                if ($optionsLabel !== '') {
                    $variantLabel = $variantLabel !== ''
                        ? $variantLabel . ' - ' . $optionsLabel
                        : $optionsLabel;
                }
            }

            $variantSnapshot = is_array($item['variant_snapshot'] ?? null) ? $item['variant_snapshot'] : [];
            $displayPrice = (float) ($item['price_amount'] ?? 0);
            $variantPrice = (float) ($variantSnapshot['price'] ?? ($item['variant_price'] ?? 0));
            $variantSalePrice = (float) ($variantSnapshot['sale_price'] ?? ($item['variant_sale_price'] ?? 0));
            $itemSalePrice = (float) ($item['sale_price'] ?? 0);

            if ($variantSalePrice > 0) {
                $displayPrice = $variantSalePrice;
            } elseif ($variantPrice > 0) {
                $displayPrice = $variantPrice;
            } elseif ($itemSalePrice > 0) {
                $displayPrice = $itemSalePrice;
            }
        ?>
        <li class="pb-3 mb-3 border-bottom">
            <div class="row align-items-center">
                <div class="col-3 col-md-2 col-lg-3">
                    <a href="<?= Url::to([$moduleRoute . '/products/view', 'id' => $item['product_id']]) ?>">
                        <img class="img-fluid border rounded"
                             src="<?= ($item['main_image'] ?? 'https://ik.imagekit.io/ready/diin/img/site/placeholder.png') . '?tr=w-300,h-300' ?>"
                             alt="<?= Html::encode($item['product_name'] ?? 'Product') ?>">
                    </a>
                </div>
                <div class="col-9 col-md-10 col-lg-9">
                    <p class="mb-1">
                        <a class="text-mode fw-500" href="<?= Url::to([$moduleRoute . '/products/view', 'id' => $item['product_id']]) ?>">
                            <?= Html::encode($item['product_name'] ?? Yii::t('shop', 'product_name')) ?>
                        </a>
                        <?php if ($variantLabel !== ''): ?>
                            <span class="m-0 text-muted small w-100 d-block"><?= Yii::t('shop', 'Variant') ?>: <?= Html::encode($variantLabel) ?></span>
                        <?php endif; ?>
                        <span class="m-0 text-muted w-100 d-block">
                            L<?= Yii::$app->formatter->asDecimal($displayPrice, 2) ?>
                        </span>
                    </p>
                    <div class="d-flex align-items-center">
                        <span class="small me-2">
                            <?= Yii::t('shop', 'quantity') ?>: <?= (int) $item['quantity'] ?>
                        </span>
                        <a class="small link-danger ms-auto remove-item"
                           href="#!"
                           data-item-id="<?= $item['id'] ?>">
                            <i class="bi bi-x"></i> <?= Yii::t('shop', 'remove') ?>
                        </a>
                    </div>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
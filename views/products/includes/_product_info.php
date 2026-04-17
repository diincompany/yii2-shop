<?php

use diincompany\shop\widgets\AddToCartButton;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var array $product
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
 * @var array $gaItemPayload
 */

$moduleId = trim((string) (Yii::$app->controller->module->id ?? ''), '/');
if ($moduleId === '' || $moduleId === Yii::$app->id) {
    $moduleId = 'shop';
}
$moduleRoute = '/' . $moduleId;
?>

<div class="col-lg-6 ps-lg-5">
    <div class="product-detail pt-4 pt-lg-0">
        <?php if (isset($product['brand']['name'])): ?>
            <div class="products-brand pb-2">
                <span><?= Html::encode($product['brand']['name']) ?></span>
            </div>
        <?php endif; ?>

        <div class="products-title mb-2">
            <h1 class="display-5 fw-bolder"><?= Html::encode($product['name']) ?></h1>
        </div>

        <?php if ($isProductAvailable): ?>
            <div id="product-stock-badge" class="badge bg-success mb-3"><?= Yii::t('shop', 'Disponible') ?></div>
        <?php else: ?>
            <div id="product-stock-badge" class="badge bg-danger mb-3"><?= Yii::t('shop', 'Sin Existencia') ?></div>
        <?php endif; ?>

        <div class="product-description">
            <?= $productDescription ?>
        </div>

        <div id="product-price-display" class="product-price fs-3 fw-500 mb-2 mt-4">
            <?php if ($selectedSalePrice): ?>
                <del class="text-muted fs-6">L<?= $selectedPrice[0] ?>.<small><?= $selectedPrice[1] ?></small></del>
                <span class="text-primary">L<?= $selectedSalePrice[0] ?>.<small><?= $selectedSalePrice[1] ?></small></span>
            <?php else: ?>
                <span class="text-primary">L<?= $selectedPrice[0] ?>.<small><?= $selectedPrice[1] ?></small></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($variants)): ?>
            <div class="pt-2">
                <?php if (!empty($variantOptionGroups)): ?>
                    <?php foreach ($variantOptionGroups as $group): ?>
                        <div class="product-option-group mb-3" data-option-key="<?= Html::encode($group['key']) ?>">
                            <label class="form-label fw-500 mb-2"><?= Html::encode($group['label']) ?></label>
                            <div class="d-flex flex-wrap gap-2" role="group" aria-label="<?= Html::encode($group['label']) ?>">
                                <?php foreach ($group['values'] as $value): ?>
                                    <?php
                                    $isSelected = ($defaultVariantOptions[$group['key']] ?? null) === ($value['value'] ?? null);
                                    $buttonClass = 'btn btn-outline-secondary product-option-button' . ($isSelected ? ' active' : '');
                                    ?>
                                    <button
                                        type="button"
                                        class="<?= Html::encode($buttonClass) ?>"
                                        data-option-key="<?= Html::encode($group['key']) ?>"
                                        data-option-value="<?= Html::encode($value['value']) ?>"
                                        aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                                    >
                                        <?= Html::encode($value['label']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <label class="form-label fw-500 mb-2"><?= Html::encode($variantSelectorLabel) ?></label>
                    <div class="d-flex flex-wrap gap-2 mb-3" role="radiogroup" aria-label="<?= Html::encode($variantSelectorLabel) ?>">
                        <?php foreach ($variants as $variant): ?>
                            <?php
                            $variantId = trim((string) ($variant['id'] ?? ''));
                            $defaultVariantId = is_array($defaultVariant) ? trim((string) ($defaultVariant['id'] ?? '')) : '';
                            $isSelected = $defaultVariant !== null && $defaultVariantId !== '' && $defaultVariantId === $variantId;
                            $variantLabel = $buildVariantLabel($variant);
                            if ($variantId === '') {
                                continue;
                            }
                            $variantStock = (int) ($variantStocksById[$variantId] ?? 0);
                            $isVariantInStock = $variantStock > 0;
                            $disableVariant = $shouldEnforceVariantStock && !$isVariantInStock;
                            $variantButtonClass = 'btn btn-outline-secondary' . ($disableVariant ? ' disabled' : '');
                            $variantInputId = 'product-variant-visible-' . (int) ($product['id'] ?? 0) . '-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $variantId);
                            ?>
                            <input
                                type="radio"
                                class="btn-check product-variant-radio"
                                name="product-variant-visible-<?= (int) ($product['id'] ?? 0) ?>"
                                id="<?= Html::encode($variantInputId) ?>"
                                value="<?= Html::encode($variantId) ?>"
                                autocomplete="off"
                                <?= $isSelected ? 'checked' : '' ?>
                                <?= $disableVariant ? 'disabled' : '' ?>
                            >
                            <label
                                class="<?= Html::encode($variantButtonClass) ?>"
                                for="<?= Html::encode($variantInputId) ?>"
                                <?= $disableVariant ? 'aria-disabled="true"' : '' ?>
                                <?= $disableVariant ? 'title="' . Html::encode(Yii::t('shop', 'Sin Existencia')) . '"' : '' ?>
                            >
                                <?= Html::encode($variantLabel) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($variantOptionGroups)): ?>
                    <div class="d-none" role="radiogroup" aria-label="<?= Html::encode($variantSelectorLabel) ?>">
                        <?php foreach ($variants as $variant): ?>
                            <?php
                            $variantId = trim((string) ($variant['id'] ?? ''));
                            $defaultVariantId = is_array($defaultVariant) ? trim((string) ($defaultVariant['id'] ?? '')) : '';
                            $isSelected = $defaultVariant !== null && $defaultVariantId !== '' && $defaultVariantId === $variantId;
                            $variantLabel = $buildVariantLabel($variant);
                            if ($variantId === '') {
                                continue;
                            }
                            $variantStock = (int) ($variantStocksById[$variantId] ?? 0);
                            $isVariantInStock = $variantStock > 0;
                            $disableVariant = $shouldEnforceVariantStock && !$isVariantInStock;
                            $variantInputId = 'product-variant-' . (int) ($product['id'] ?? 0) . '-' . preg_replace('/[^a-zA-Z0-9\-_]/', '-', $variantId);
                            ?>
                            <input
                                type="radio"
                                class="product-variant-radio"
                                name="product-variant-<?= (int) ($product['id'] ?? 0) ?>"
                                id="<?= Html::encode($variantInputId) ?>"
                                value="<?= Html::encode($variantId) ?>"
                                autocomplete="off"
                                <?= $isSelected ? 'checked' : '' ?>
                                <?= $disableVariant ? 'disabled' : '' ?>
                            >
                            <label for="<?= Html::encode($variantInputId) ?>"><?= Html::encode($variantLabel) ?></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="product-detail-actions d-flex flex-wrap pt-3">
            <div class="cart-qty me-3 mb-3">
                <div class="dec qty-btn qty_btn">-</div>
                <input class="cart-qty-input form-control" type="text" name="qtybutton" value="1" data-max-stock="<?= max(1, $selectedStock) ?>" <?= $isProductAvailable ? '' : 'disabled' ?>>
                <div class="inc qty-btn qty_btn">+</div>
            </div>
            <div class="cart-button mb-3 d-flex">
                <?= AddToCartButton::widget([
                    'productId' => $product['id'],
                    'buttonClass' => 'btn btn-primary me-3 add-to-cart-btn',
                    'gaItemData' => $gaItemPayload ?? [],
                    'gaCurrency' => 'HNL',
                ]) ?>
            </div>
        </div>

        <?php if (!$isProductAvailable): ?>
            <?php
            $this->registerJs("document.addEventListener('DOMContentLoaded', function () { var btn = document.querySelector('.add-to-cart-btn'); if (btn) { btn.setAttribute('disabled', 'disabled'); } });");
            ?>
        <?php endif; ?>

        <div class="pt-3 border-top mt-3 small">
            <p class="theme-link mb-2">
                <label class="m-0 text-mode"><?= Yii::t('shop', 'Código') ?>:</label>
                <?= Html::encode($product['code']) ?>
            </p>
            <?php if (!empty($product['category'])): ?>
                <p class="theme-link mb-2">
                    <label class="m-0 text-mode"><?= Yii::t('shop', 'Categoría') ?>:</label>
                    <?= Html::a($product['category']['name'], [$moduleRoute . '/category/' . $product['category']['slug']]) ?>
                </p>
            <?php endif; ?>
            <p class="theme-link m-0">
                <label class="m-0 text-mode"><?= Yii::t('shop', 'Compartir') ?>:</label>
                <a class="icon icon-sm icon-secondary me-2" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(Url::to([$moduleRoute . '/' . $product['slug']], true)) ?>" target="_blank">
                    <i class="bi bi-facebook fs-5"></i>
                </a>
                <a class="icon icon-sm icon-secondary me-2" href="https://twitter.com/intent/tweet?url=<?= urlencode(Url::to([$moduleRoute . '/' . $product['slug']], true)) ?>&text=<?= urlencode($product['name']) ?>" target="_blank">
                    <i class="bi bi-twitter fs-5"></i>
                </a>
                <a class="icon icon-sm icon-secondary me-2" href="https://wa.me/?text=<?= urlencode($product['name'] . ' ' . Url::to([$moduleRoute . '/' . $product['slug']], true)) ?>" target="_blank">
                    <i class="bi bi-whatsapp fs-5"></i>
                </a>
            </p>
        </div>
    </div>
</div>

<?php
use yii\helpers\Html;

$cartUrl = Yii::$app->urlManager->createUrl(["/{$moduleId}/cart"]);
?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="modalMiniCart" aria-labelledby="modalMiniCartLabel">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title" id="modalMiniCartLabel">
            <?= Yii::t('shop', 'Your Cart') ?> (<?= $itemCount ?>)
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php if (empty($cartItems['items'])): ?>
            <div class="text-center py-5">
                <i class="fi-shopping-cart fs-1 text-muted"></i>
                <p class="text-muted mt-3"><?= Yii::t('shop', 'Your cart is empty') ?></p>
            </div>
        <?php else: ?>
            <ul class="list-unstyled m-0 p-0">
                <?php foreach ($cartItems['items'] as $item): ?>
                    <?php
                        // Normalize possible JSON-string payloads from API.
                        $toArray = static function ($value): array {
                            if (is_array($value)) {
                                return $value;
                            }

                            if (is_string($value) && $value !== '') {
                                $decoded = json_decode($value, true);
                                if (is_array($decoded)) {
                                    return $decoded;
                                }
                            }

                            return [];
                        };

                        $toPositiveNumber = static function (...$values): float {
                            foreach ($values as $value) {
                                if ($value === null || $value === '') {
                                    continue;
                                }

                                if (is_string($value)) {
                                    $value = str_replace([',', ' '], ['', ''], $value);
                                }

                                if (is_numeric($value)) {
                                    $number = (float) $value;
                                    if ($number > 0) {
                                        return $number;
                                    }
                                }
                            }

                            return 0.0;
                        };

                        $optionValuesToText = static function ($optionValues) use ($toArray): string {
                            $list = $toArray($optionValues);
                            if ($list === []) {
                                return '';
                            }

                            $parts = [];
                            foreach ($list as $optionValue) {
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

                            return implode(' / ', $parts);
                        };

                        $variant = $toArray($item['variant'] ?? null);
                        $variantSnapshot = $toArray($item['variant_snapshot'] ?? ($item['product_variant_snapshot'] ?? null));

                        $variantLabel = trim((string) ($item['variant_name'] ?? ($item['product_variant_name'] ?? '')));
                        if ($variantLabel === '' && $variant !== []) {
                            $variantLabel = trim((string) ($variant['name'] ?? ''));
                        }

                        if ($variantLabel === '' && $variantSnapshot !== []) {
                            $variantLabel = trim((string) ($variantSnapshot['name'] ?? ''));
                        }

                        if ($variantLabel === '') {
                            $variantLabel = trim((string) ($item['variant_sku'] ?? ($variantSnapshot['sku'] ?? '')));
                        }

                        if ($variantLabel === '') {
                            $variantLabel = $optionValuesToText($item['option_values'] ?? []);
                        }

                        $snapshotOptionsLabel = $optionValuesToText($variantSnapshot['option_values'] ?? []);
                        if ($snapshotOptionsLabel !== '') {
                            $variantLabel = $variantLabel !== ''
                                ? $variantLabel . ' - ' . $snapshotOptionsLabel
                                : $snapshotOptionsLabel;
                        }

                        $quantity = max(1, (int) ($item['quantity'] ?? 1));
                        $lineTotal = (float) ($item['total_amount'] ?? 0);
                        $unitPriceFromLineTotal = $lineTotal > 0 ? $lineTotal / $quantity : 0;
                        $variantId = (int) ($item['product_variant_id'] ?? ($item['variant_id'] ?? ($variantSnapshot['variant_id'] ?? 0)));
                        $hasVariants = (int) ($item['has_variants'] ?? ($item['product_has_variants'] ?? 0));
                        $variantPricingMode = (int) ($item['variant_pricing_mode'] ?? ($item['product_variant_pricing_mode'] ?? 0));
                        $variantMetadataMissing = $hasVariants <= 0 && $variantPricingMode <= 0;
                        $useVariantPricing = $variantId > 0 && (
                            ($hasVariants === 1 && $variantPricingMode === 2) ||
                            $variantMetadataMissing
                        );

                        if ($useVariantPricing) {
                            $displayPrice = $toPositiveNumber(
                                $variantSnapshot['sale_price'] ?? null,
                                $variantSnapshot['sale_price_amount'] ?? null,
                                $item['variant_sale_price'] ?? null,
                                $item['variant_sale_price_amount'] ?? null,
                                $item['product_variant_sale_price'] ?? null,
                                $item['product_variant_sale_price_amount'] ?? null,
                                $variantSnapshot['price'] ?? null,
                                $variantSnapshot['price_amount'] ?? null,
                                $variantSnapshot['variant_price'] ?? null,
                                $item['variant_price'] ?? null,
                                $item['variant_price_amount'] ?? null,
                                $item['product_variant_price'] ?? null,
                                $item['product_variant_price_amount'] ?? null,
                                $unitPriceFromLineTotal
                            );
                        } else {
                            $displayPrice = 0.0;
                        }

                        if ($displayPrice <= 0) {
                            $displayPrice = $toPositiveNumber(
                                $item['sale_price'] ?? null,
                                $item['sale_price_amount'] ?? null,
                                $item['price_amount'] ?? null,
                                $item['price'] ?? null,
                                $unitPriceFromLineTotal
                            );
                        }
                    ?>
                    <li class="py-2">
                        <div class="row align-items-center">
                            <div class="col-3">
                                <a href="<?= Yii::$app->urlManager->createUrl(["/{$moduleId}/products/view", 'id' => $item['product_id']]) ?>">
                                    <img src="<?= $item['main_image'] ?>" 
                                         class="img-fluid" 
                                         alt="<?= Html::encode($item['product_name'] ?? '') ?>">
                                </a>
                            </div>
                            <div class="col-9">
                                <!-- Title -->
                                <p class="mb-2">
                                    <a class="text-mode fw-500" href="<?= Yii::$app->urlManager->createUrl(["/{$moduleId}/products/view", 'id' => $item['product_id']]) ?>"><?= Html::encode($item['product_name'] ?? '') ?></a>
                                    <?php if ($variantLabel !== ''): ?>
                                        <span class="m-0 text-muted small w-100 d-block"><?= Yii::t('shop', 'Variant') ?>: <?= Html::encode($variantLabel) ?></span>
                                    <?php endif; ?>
                                    <span class="m-0 text-muted w-100 d-block"><?= Html::encode($currencySymbol) ?> <?= number_format($displayPrice, 2) ?></span>
                                </p>
                                <!--Footer -->
                                <div class="d-flex align-items-center">
                                    <!-- Select -->
                                    <select class="form-select form-select-sm w-auto cart-quantity" data-item-id="<?= $item['id'] ?>">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $item['quantity'] ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <!-- Remove -->
                                    <a class="small text-mode ms-auto cart-remove" href="#!" data-item-id="<?= $item['id'] ?>">
                                        <i class="bi bi-x"></i> <?= Yii::t('shop', 'Remove') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="offcanvas-footer border-top p-3"<?= empty($cartItems['items']) ? ' style="display:none;"' : '' ?>>
        <?php if (!empty($cartItems['items'])): ?>
            <div class="row g-0 py-2">
                <div class="col-8">
                    <span class="text-mode"><?= Yii::t('shop', 'Subtotal') ?></span>
                </div>
                <div class="col-4 text-end">
                    <span class="ml-auto"><?= Html::encode($currencySymbol) ?> <?= number_format($cartItems['subtotal_amount'], 2) ?></span>
                </div>
            </div>
            <div class="row g-0 py-2">
                <div class="col-8">
                    <span class="text-mode"><?= Yii::t('shop', 'Taxes') ?>:</span>
                </div>
                <div class="col-4 text-end">
                    <span class="ml-auto"><?= Html::encode($currencySymbol) ?> <?= number_format($cartItems['tax_amount'], 2) ?></span>
                </div>
            </div>
            <div class="row g-0 pt-2 mt-2 border-top fw-bold text-mode">
                <div class="col-8">
                    <span class="text-mode"><?= Yii::t('shop', 'Total') ?></span>
                </div>
                <div class="col-4 text-end">
                    <span class="ml-auto"><?= Html::encode($currencySymbol) ?> <?= number_format($cartItems['total_amount'], 2) ?></span>
                </div>
            </div>
            <div class="pt-4">
                <?= Html::a(Yii::t('shop', 'View Cart'), $cartUrl, ['class' => 'btn btn-block btn-mode w-100']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
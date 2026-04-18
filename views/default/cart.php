<?php
/**
 * Shopping Cart page
 * @var yii\web\View $this
 * @var array $items
 * @var float $subtotal
 * @var float $taxes
 * @var float $shippingAmount
 * @var float $discountAmount
 * @var float $grandTotal
 * @var string $couponCode
 */

use diincompany\shop\assets\ShopAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = Yii::t('shop', 'shopping_cart');
$this->params['breadcrumbs'][] = $this->title;
ShopAsset::register($this);

$module = Yii::$app->controller->module;
$breadcrumbsView = is_object($module) && method_exists($module, 'getBreadcrumbsView')
    ? $module->getBreadcrumbsView()
    : null;
$moduleRoute = '/' . trim((string) ($module->id ?? 'shop'), '/');

$gaTrackerClass = 'diincompany\\yii2googleanalytics\\EcommerceTracker';
$gaItems = [];

if (class_exists($gaTrackerClass) && !empty($items)) {
    foreach ($items as $item) {
        $gaItems[] = $gaTrackerClass::buildItem(
            (string) ($item['sku'] ?? $item['product_id'] ?? ''),
            (string) ($item['product_name'] ?? ''),
            (float) ($item['price_amount'] ?? $item['price'] ?? 0),
            max(1, (int) ($item['quantity'] ?? 1)),
            (string) ($item['category_name'] ?? ''),
            'StreetID',
            (string) ($item['variant_name'] ?? $item['product_variant_name'] ?? '')
        );
    }

    if (!empty($gaItems)) {
        $this->registerJs(
            'if (typeof window.gtag === "function") { ' . $gaTrackerClass::viewCartJs($gaItems, (float) $grandTotal, 'HNL') . ' }',
            \yii\web\View::POS_END,
            'shop-ga4-view-cart'
        );
    }
}
?>

<div class="shop-module shop-cart-page">
<!-- Cart Content -->
<div class="py-6">
    <div class="container">
        <?php if (empty($items)): ?>
            <!-- Empty Cart -->
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center py-5">
                    <i class="bi bi-cart-x" style="font-size: 5rem; color: #ccc;"></i>
                    <h3 class="mt-4"><?= Yii::t('shop', 'Your cart is empty') ?></h3>
                    <p class="text-muted mb-4"><?= Yii::t('shop', 'empty_cart_message') ?></p>
                    <?= Html::a(Yii::t('shop', 'Continue Shopping'), [$moduleRoute . '/products/index'], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Table -->
            <div class="table-content table-responsive cart-table-content">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr class="text-uppercase">
                            <th class="text-mode text-center fw-500 text-nowrap"><?= Yii::t('shop', 'image') ?></th>
                            <th class="text-mode text-center fw-500 text-nowrap"><?= Yii::t('shop', 'product_name') ?></th>
                            <th class="text-mode text-center fw-500 text-nowrap"><?= Yii::t('shop', 'unit_price') ?></th>
                            <th class="text-mode text-center fw-500 text-nowrap"><?= Yii::t('shop', 'quantity') ?></th>
                            <th class="text-mode text-center fw-500 text-nowrap"><?= Yii::t('shop', 'subtotal') ?></th>
                            <th class="text-mode fw-500 text-end text-nowrap"><?= Yii::t('shop', 'action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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

                                    $value = trim((string) ($optionValue['value'] ?? ''));
                                    if ($value !== '') {
                                        $parts[] = $value;
                                    }
                                }

                                return implode(' / ', $parts);
                            };
                        ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                                $variant = $toArray($item['variant'] ?? null);
                                $variantSnapshot = $toArray($item['variant_snapshot'] ?? ($item['product_variant_snapshot'] ?? null));
                                $isBackorder = (bool) ($item['is_backorder'] ?? false);
                                $backorderEstimatedDate = trim((string) ($item['backorder_estimated_date'] ?? ''));

                                $variantId = (int) ($item['product_variant_id'] ?? ($item['variant_id'] ?? ($variantSnapshot['variant_id'] ?? 0)));
                                $hasVariants = (int) ($item['has_variants'] ?? ($item['product_has_variants'] ?? 0));
                                $variantPricingMode = (int) ($item['variant_pricing_mode'] ?? ($item['product_variant_pricing_mode'] ?? 0));
                                $variantInventoryMode = (int) ($item['variant_inventory_mode'] ?? ($item['product_variant_inventory_mode'] ?? 0));
                                $variantMetadataMissing = $hasVariants <= 0 && $variantPricingMode <= 0;
                                $useVariantPricing = $variantId > 0 && (
                                    ($hasVariants === 1 && $variantPricingMode === 2) ||
                                    $variantMetadataMissing
                                );

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

                                if ($useVariantPricing) {
                                    $displayPrice = $toPositiveNumber(
                                        $item['variant_sale_price'] ?? null,
                                        $item['variant_sale_price_amount'] ?? null,
                                        $variantSnapshot['sale_price'] ?? null,
                                        $variantSnapshot['sale_price_amount'] ?? null,
                                        $item['variant_price'] ?? null,
                                        $item['variant_price_amount'] ?? null,
                                        $variantSnapshot['price'] ?? null,
                                        $variantSnapshot['price_amount'] ?? null,
                                        $unitPriceFromLineTotal
                                    );
                                } else {
                                    $displayPrice = $toPositiveNumber(
                                        $item['sale_price'] ?? null,
                                        $item['sale_price_amount'] ?? null,
                                        $item['price_amount'] ?? null,
                                        $item['price'] ?? null,
                                        $unitPriceFromLineTotal
                                    );
                                }

                                if ($displayPrice <= 0) {
                                    $displayPrice = $toPositiveNumber($unitPriceFromLineTotal);
                                }
                            ?>
                            <tr
                                data-item-id="<?= $item['id'] ?>"
                                data-has-variants="<?= $hasVariants ?>"
                                data-variant-pricing-mode="<?= $variantPricingMode ?>"
                                data-variant-inventory-mode="<?= $variantInventoryMode ?>"
                                data-ga-item='<?= Html::encode(Json::htmlEncode([
                                    'item_id' => (string) ($item['sku'] ?? $item['product_id'] ?? ''),
                                    'item_name' => (string) ($item['product_name'] ?? ''),
                                    'price' => (float) $displayPrice,
                                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                                    'item_variant' => (string) $variantLabel,
                                ])) ?>'
                            >
                                <td class="text-center product-thumbnail">
                                    <a class="text-reset" href="<?= Url::to([$moduleRoute . '/products/view', 'id' => $item['product_id']]) ?>">
                                        <img src="<?= $item['main_image'] . '?tr=w-300,h-300' ?>" 
                                             class="img-fluid" 
                                             width="100" 
                                             alt="<?= Html::encode($item['product_name'] ?? '') ?>">
                                    </a>
                                </td>
                                <td class="text-center product-name">
                                    <a class="text-reset" href="<?= Url::to([$moduleRoute . '/products/view', 'id' => $item['product_id']]) ?>">
                                        <?= Html::encode($item['product_name'] ?? Yii::t('shop', 'product_name')) ?>
                                    </a>
                                    <?php if ($isBackorder): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning text-dark"><?= Yii::t('shop', 'Backorder') ?></span>
                                        </div>
                                        <?php if ($backorderEstimatedDate !== ''): ?>
                                            <div class="small text-muted product-backorder-note">
                                                <?= Yii::t('shop', 'Available on {date}', [
                                                    'date' => Yii::$app->formatter->asDate($backorderEstimatedDate, 'long'),
                                                ]) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($variantLabel !== ''): ?>
                                        <div class="small text-muted product-variant-label"><?= Yii::t('shop', 'Variant') ?>: <?= Html::encode($variantLabel) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center product-price-cart">
                                    <span class="amount">L<?= Yii::$app->formatter->asDecimal($displayPrice, 2) ?></span>
                                </td>
                                <td class="text-center product-quantity">
                                    <div class="cart-qty d-inline-flex">
                                        <div class="dec qty-btn update-quantity" data-action="decrease">-</div>
                                        <input class="cart-qty-input form-control" 
                                               type="number"
                                               name="qtybutton" 
                                               value="<?= (int)$item['quantity'] ?>"
                                               data-item-id="<?= $item['id'] ?>"
                                               min="1"
                                               step="1"
                                               inputmode="numeric">
                                        <div class="inc qty-btn update-quantity" data-action="increase">+</div>
                                    </div>
                                </td>
                                <td class="text-center product-subtotal">
                                    L<?= Yii::$app->formatter->asDecimal($item['total_amount'] ?? 0, 2) ?>
                                </td>
                                <td class="product-remove text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-danger text-nowrap px-3 remove-item" 
                                            data-item-id="<?= $item['id'] ?>">
                                        <i class="bi bi-x lh-1"></i> 
                                        <span class="d-none d-md-inline-block"><?= Yii::t('shop', 'remove') ?></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- End Cart Table -->
            
            <!-- Cart Actions -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <?= Html::a(
                        '<i class="ci-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">' . Yii::t('shop', 'Continue Shopping') . '</span><span class="d-inline d-sm-none">' . Yii::t('shop', 'back') . '</span>',
                        [$moduleRoute],
                        ['class' => 'btn btn-outline-mode']
                    ) ?>
                </div>
                <div>
                    <button type="button" class="btn btn-primary d-none" id="update-cart-btn">
                        <?= Yii::t('shop', 'update_cart') ?>
                    </button>
                </div>
            </div>

            <!-- Cart Summary and Coupon -->
            <div class="row flex-row-reverse pt-4">
                <!-- Order Total -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent py-3">
                            <h6 class="m-0 mb-1"><?= Yii::t('shop', 'order_total') ?></h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="me-2 text-body"><?= Yii::t('shop', 'subtotal') ?></h6>
                                    <span class="text-end" id="cart-subtotal">L<?= Yii::$app->formatter->asDecimal($subtotal, 2) ?></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center mb-2" id="cart-taxes-row"<?= $taxes > 0 ? '' : ' style="display:none;"' ?>>
                                    <h6 class="me-2 text-body"><?= Yii::t('shop', 'taxes') ?></h6>
                                    <span class="text-end" id="cart-taxes">L<?= Yii::$app->formatter->asDecimal($taxes, 2) ?></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center mb-2" id="cart-shipping-row"<?= $shippingAmount > 0 ? '' : ' style="display:none;"' ?>>
                                    <h6 class="me-2 text-body"><?= Yii::t('shop', 'shipping') ?></h6>
                                    <span class="text-end" id="cart-shipping">L<?= Yii::$app->formatter->asDecimal($shippingAmount, 2) ?></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="me-2 text-body" id="cart-discount-label">
                                        <?= Yii::t('shop', 'Discount') ?><?= !empty($couponCode) ? ' (' . Html::encode($couponCode) . ')' : '' ?>
                                    </h6>
                                    <span class="text-end" id="cart-discount"><?= $discountAmount > 0 ? '-L' : 'L' ?><?= Yii::$app->formatter->asDecimal(abs($discountAmount), 2) ?></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                                    <h6 class="me-2"><?= Yii::t('shop', 'grand_total') ?></h6>
                                    <span class="text-end text-mode fw-bold" id="cart-grand-total">L<?= Yii::$app->formatter->asDecimal($grandTotal, 2) ?></span>
                                </li>
                            </ul>
                            <div class="d-grid gap-2 mx-auto">
                                <?= Html::a(
                                    Yii::t('shop', 'Go to Checkout'),
                                    [$moduleRoute . '/default/checkout'],
                                    ['class' => 'btn btn-primary']
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coupon Code -->
                <div class="col-md-6 col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent py-3">
                            <h6 class="m-0"><?= Yii::t('shop', 'use_coupon_code') ?></h6>
                        </div>
                        <div class="card-body">
                            <form id="coupon-form">
                                <div class="form-group mb-3">
                                    <label class="form-label"><?= Yii::t('shop', 'have_coupon_code') ?></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="coupon-code" 
                                           name="coupon_code" 
                                           value="<?= Html::encode($couponCode ?? '') ?>"
                                           placeholder="<?= Yii::t('shop', 'enter_coupon_code') ?>">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?= Yii::t('shop', 'apply') ?>
                                    </button>
                                    <button type="button" class="btn btn-outline-mode" id="remove-coupon-btn">
                                        <?= Yii::t('shop', 'remove_coupon') ?>
                                    </button>
                                </div>
                            </form>
                            <div id="coupon-message" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
<!-- End Cart Content -->

<?php
// Register custom JS for cart functionality
$updateItemsUrl = Url::to([$moduleRoute . '/cart/update-items']);
$removeItemUrl = Url::to([$moduleRoute . '/cart/remove-item']);
$applyCouponUrl = Url::to([$moduleRoute . '/cart/apply-coupon']);
$cartSummaryUrl = Url::to([$moduleRoute . '/cart/summary']);
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;
$confirmRemoveItemJson = json_encode(Yii::t('shop', 'confirm_remove_item'));
$pleaseEnterCouponCodeJson = json_encode(Yii::t('shop', 'please_enter_coupon_code'));
$errorUpdatingQuantityJson = json_encode(Yii::t('shop', 'error_updating_quantity'));
$errorRemovingItemJson = json_encode(Yii::t('shop', 'error_removing_item'));
$errorApplyingCouponJson = json_encode(Yii::t('shop', 'error_applying_coupon'));
$couponAppliedJson = json_encode(Yii::t('shop', 'coupon_applied'));
$couponRemovedJson = json_encode(Yii::t('shop', 'coupon_removed'));
$discountTextJson = json_encode(Yii::t('shop', 'Discount'));
$variantTextJson = json_encode(Yii::t('shop', 'Variant'));

$this->registerJs(<<<JS
$(document).ready(function() {
    var isUpdatingCart = false;

    function formatPrice(value) {
        var amount = parseFloat(value || 0);
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function toInt(value, fallback) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? (typeof fallback === 'number' ? fallback : 0) : parsed;
    }

    function firstPositiveNumber() {
        for (var i = 0; i < arguments.length; i++) {
            var value = arguments[i];

            if (value === null || typeof value === 'undefined' || value === '') {
                continue;
            }

            if (typeof value === 'string') {
                value = value.replace(/[\s,]/g, '');
            }

            var number = Number(value);
            if (!isNaN(number) && isFinite(number) && number > 0) {
                return number;
            }
        }

        return 0;
    }

    function asObject(value) {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            return value;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                var parsed = JSON.parse(value);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (_error) {
                return {};
            }
        }

        return {};
    }

    function asArray(value) {
        if (Array.isArray(value)) {
            return value;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                var parsed = JSON.parse(value);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (_error) {
                return [];
            }
        }

        return [];
    }

    function optionValuesToText(optionValues) {
        var values = asArray(optionValues);
        if (!values.length) {
            return '';
        }

        var parts = [];

        for (var i = 0; i < values.length; i++) {
            var optionValue = values[i];

            if (!optionValue || typeof optionValue !== 'object') {
                continue;
            }

            var value = (optionValue.value || '').toString().trim();
            if (value) {
                parts.push(value);
            }
        }

        return parts.join(' / ');
    }

    function resolveVariantLabel(apiItem) {
        if (!apiItem || typeof apiItem !== 'object') {
            return '';
        }

        var variantSnapshot = asObject(apiItem.variant_snapshot || apiItem.product_variant_snapshot || null);
        var variant = asObject(apiItem.variant || null);

        var variantLabel = (apiItem.variant_name || apiItem.product_variant_name || '').toString().trim();

        if (!variantLabel) {
            variantLabel = (variant.name || '').toString().trim();
        }

        if (!variantLabel) {
            variantLabel = (variantSnapshot.name || '').toString().trim();
        }

        if (!variantLabel) {
            variantLabel = (apiItem.variant_sku || variantSnapshot.sku || '').toString().trim();
        }

        if (!variantLabel) {
            variantLabel = optionValuesToText(apiItem.option_values);
        }

        var snapshotOptions = optionValuesToText(variantSnapshot.option_values);
        if (snapshotOptions) {
            return variantLabel ? (variantLabel + ' - ' + snapshotOptions) : snapshotOptions;
        }

        return variantLabel;
    }

    function shouldUseVariantPricing(apiItem) {
        if (!apiItem || typeof apiItem !== 'object') {
            return false;
        }

        var variantSnapshot = asObject(apiItem.variant_snapshot || apiItem.product_variant_snapshot || null);
        var variantId = toInt(apiItem.product_variant_id || apiItem.variant_id || variantSnapshot.variant_id || 0, 0);
        var hasVariants = toInt(apiItem.has_variants || apiItem.product_has_variants || 0, 0);
        var variantPricingMode = toInt(apiItem.variant_pricing_mode || apiItem.product_variant_pricing_mode || 0, 0);
        var variantMetadataMissing = hasVariants <= 0 && variantPricingMode <= 0;

        return variantId > 0 && ((hasVariants === 1 && variantPricingMode === 2) || variantMetadataMissing);
    }

    function resolveUnitPrice(apiItem) {
        if (!apiItem || typeof apiItem !== 'object') {
            return 0;
        }

        var variantSnapshot = asObject(apiItem.variant_snapshot || apiItem.product_variant_snapshot || null);
        var quantity = Math.max(toInt(apiItem.quantity, 1), 1);
        var lineTotal = Number(apiItem.total_amount || 0);
        var unitFromLineTotal = isFinite(lineTotal) && lineTotal > 0 ? (lineTotal / quantity) : 0;

        if (shouldUseVariantPricing(apiItem)) {
            return firstPositiveNumber(
                apiItem.variant_sale_price,
                apiItem.variant_sale_price_amount,
                variantSnapshot.sale_price,
                variantSnapshot.sale_price_amount,
                apiItem.variant_price,
                apiItem.variant_price_amount,
                variantSnapshot.price,
                variantSnapshot.price_amount,
                unitFromLineTotal
            );
        }

        return firstPositiveNumber(
            apiItem.sale_price,
            apiItem.sale_price_amount,
            apiItem.price_amount,
            apiItem.price,
            unitFromLineTotal
        );
    }

    function updateCartSummary(cartData) {
        if (!cartData) {
            return;
        }

        var normalized = cartData.order || cartData;
        var taxes = parseFloat(normalized.tax_amount || 0);
        var shipping = parseFloat(normalized.shipping_amount || ((normalized.shipping && normalized.shipping.shipping_cost) || 0) || 0);
        var discount = parseFloat(normalized.discount_amount || 0);
        var couponCode = (normalized.coupon_code || (normalized.coupon && normalized.coupon.code) || '').toString().trim();
        var discountLabel = couponCode ? $discountTextJson + ' (' + couponCode + ')' : $discountTextJson;

        $('#cart-subtotal').text('L' + formatPrice(normalized.subtotal_amount || 0));
        $('#cart-taxes').text('L' + formatPrice(taxes));
        $('#cart-taxes-row').toggle(taxes > 0);
        $('#cart-shipping').text('L' + formatPrice(shipping));
        $('#cart-shipping-row').toggle(shipping > 0);
        $('#cart-discount-label').text(discountLabel);
        $('#cart-discount').text((discount > 0 ? '-L' : 'L') + formatPrice(Math.abs(discount)));
        $('#cart-grand-total').text('L' + formatPrice(normalized.total_amount || 0));
    }

    function isSuccessfulResponse(response) {
        if (!response || typeof response !== 'object') {
            return false;
        }

        if (response.success === true) {
            return true;
        }

        var status = (response.status || '').toString().toLowerCase();
        var statusCode = parseInt(response.status_code, 10);

        return status === 'success' || statusCode === 200;
    }

    function refreshCartSummary() {
        return $.ajax({
            url: '$cartSummaryUrl',
            type: 'GET',
            dataType: 'json'
        }).done(function(response) {
            if (response && response.success && response.data) {
                updateCartSummary(response.data);
                if (typeof jQuery !== 'undefined') {
                    jQuery(document).trigger('cartDataUpdated', [response.data]);
                }
            }
        });
    }

    function updateRowFromApi(row, apiItem) {
        if (!row.length || !apiItem) {
            return;
        }

        row.attr('data-has-variants', toInt(apiItem.has_variants || apiItem.product_has_variants || 0, 0));
        row.attr('data-variant-pricing-mode', toInt(apiItem.variant_pricing_mode || apiItem.product_variant_pricing_mode || 0, 0));
        row.attr('data-variant-inventory-mode', toInt(apiItem.variant_inventory_mode || apiItem.product_variant_inventory_mode || 0, 0));

        if (typeof apiItem.quantity !== 'undefined') {
            row.find('.cart-qty-input').val(parseInt(apiItem.quantity, 10));
        }

        if (typeof apiItem.total_amount !== 'undefined') {
            row.find('.product-subtotal').text('L' + formatPrice(apiItem.total_amount));
        }

        var unitPrice = resolveUnitPrice(apiItem);
        if (unitPrice > 0) {
            row.find('.product-price-cart .amount').text('L' + formatPrice(unitPrice));
        }

        var variantLabel = resolveVariantLabel(apiItem);
        var productNameCell = row.find('.product-name');
        var variantElement = productNameCell.find('.product-variant-label');

        if (variantLabel) {
            if (!variantElement.length) {
                variantElement = $('<div class="small text-muted product-variant-label"></div>');
                productNameCell.append(variantElement);
            }

            variantElement.text($variantTextJson + ': ' + variantLabel);
        } else {
            variantElement.remove();
        }
    }

    function normalizeQuantity(value) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) || parsed < 1 ? 1 : parsed;
    }

    function rowHasPendingChanges(row) {
        var input = row.find('.cart-qty-input');
        var currentQty = normalizeQuantity(input.val());
        var originalQty = normalizeQuantity(input.data('original-qty'));

        return currentQty !== originalQty;
    }

    function refreshUpdateCartButtonState() {
        var hasChanges = false;

        $('tr[data-item-id]').each(function() {
            var row = $(this);

            if (rowHasPendingChanges(row)) {
                hasChanges = true;
                return false;
            }

            return true;
        });

        if (hasChanges) {
            $('#update-cart-btn').removeClass('d-none').prop('disabled', isUpdatingCart);
            return;
        }

        $('#update-cart-btn').addClass('d-none').prop('disabled', false);
    }

    function syncRowPendingState(row) {
        var input = row.find('.cart-qty-input');
        var normalizedQty = normalizeQuantity(input.val());

        input.val(normalizedQty);

        if (rowHasPendingChanges(row)) {
            row.addClass('cart-row-dirty');
        } else {
            row.removeClass('cart-row-dirty');
        }

        refreshUpdateCartButtonState();
    }

    function collectPendingChanges() {
        var updates = [];

        $('tr[data-item-id]').each(function() {
            var row = $(this);
            var input = row.find('.cart-qty-input');

            if (!rowHasPendingChanges(row)) {
                return;
            }

            updates.push({
                item_id: parseInt(row.data('item-id'), 10),
                quantity: normalizeQuantity(input.val())
            });
        });

        return updates;
    }

    function applyCartItemsFromApi(cartData) {
        var normalized = cartData && cartData.order ? cartData.order : cartData;
        var items = normalized && Array.isArray(normalized.items) ? normalized.items : [];

        for (var index = 0; index < items.length; index++) {
            var apiItem = items[index];
            var row = $('tr[data-item-id="' + apiItem.id + '"]');

            if (!row.length) {
                continue;
            }

            updateRowFromApi(row, apiItem);
            row.find('.cart-qty-input').data('original-qty', normalizeQuantity(apiItem.quantity));
            row.removeClass('cart-row-dirty');
        }

        refreshUpdateCartButtonState();
    }

    $('.cart-qty-input').each(function() {
        var input = $(this);
        var quantity = normalizeQuantity(input.val());

        input.val(quantity);
        input.data('original-qty', quantity);
    });

    refreshUpdateCartButtonState();

    // Update quantity
    $('.update-quantity').on('click', function() {
        var btn = $(this);
        var action = btn.data('action');
        var row = btn.closest('tr');
        var input = row.find('.cart-qty-input');
        var currentQty = normalizeQuantity(input.val());
        var newQty = action === 'increase' ? currentQty + 1 : Math.max(1, currentQty - 1);

        input.val(newQty);
        syncRowPendingState(row);
    });

    $('.cart-qty-input').on('change blur', function() {
        var input = $(this);
        var row = input.closest('tr');

        syncRowPendingState(row);
    });
    
    // Remove item
    $('.remove-item').on('click', function() {
        var btn = $(this);
        var itemId = btn.data('item-id');
        var row = btn.closest('tr');

        if (typeof window.gtag === 'function') {
            var rawGaItem = (row.attr('data-ga-item') || '').toString().trim();

            if (rawGaItem !== '') {
                try {
                    var gaItem = JSON.parse(rawGaItem);
                    var quantityInput = row.find('.cart-qty-input').first();
                    var quantity = normalizeQuantity(quantityInput.val());
                    var unitPrice = parseFloat(gaItem.price || 0);

                    gaItem.quantity = quantity;

                    window.gtag('event', 'remove_from_cart', {
                        currency: 'HNL',
                        value: (isFinite(unitPrice) ? unitPrice : 0) * quantity,
                        items: [gaItem]
                    });
                } catch (_error) {
                    // Ignore malformed GA payload and continue with remove flow.
                }
            }
        }
        
        if (confirm($confirmRemoveItemJson)) {
            removeItem(itemId);
        }
    });
    
    // Update cart button
    $('#update-cart-btn').on('click', function() {
        if (isUpdatingCart) {
            return;
        }

        var updates = collectPendingChanges();

        if (!updates.length) {
            refreshUpdateCartButtonState();
            return;
        }

        isUpdatingCart = true;
        refreshUpdateCartButtonState();

        $.ajax({
            url: '$updateItemsUrl',
            type: 'POST',
            data: {
                items: updates,
                '$csrfParam': '$csrfToken'
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    var cartData = response.data || null;

                    applyCartItemsFromApi(cartData);
                    updateCartSummary(cartData);

                    if (typeof jQuery !== 'undefined') {
                        jQuery(document).trigger('cartDataUpdated', [cartData]);
                    }
                } else {
                    alert((response && response.message) ? response.message : $errorUpdatingQuantityJson);
                }
            },
            error: function() {
                alert($errorUpdatingQuantityJson);
            },
            complete: function() {
                isUpdatingCart = false;
                refreshUpdateCartButtonState();
            }
        });
    });
    
    // Apply coupon
    $('#coupon-form').on('submit', function(e) {
        e.preventDefault();
        var couponCode = ($('#coupon-code').val() || '').trim();
        
        if (!couponCode) {
            showMessage($pleaseEnterCouponCodeJson, 'warning');
            return;
        }
        
        applyCoupon(couponCode);
    });

    $('#remove-coupon-btn').on('click', function() {
        applyCoupon('');
    });
    
    // Functions
    function removeItem(itemId) {
        $.ajax({
            url: '$removeItemUrl',
            type: 'POST',
            data: {
                item_id: itemId,
                '$csrfParam': '$csrfToken'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert($errorRemovingItemJson);
            }
        });
    }
    
    function applyCoupon(code) {
        $.ajax({
            url: '$applyCouponUrl',
            type: 'POST',
            data: {
                coupon_code: code,
                '$csrfParam': '$csrfToken'
            },
            dataType: 'json',
            success: function(response) {
                if (isSuccessfulResponse(response)) {
                    var cartData = response.data || null;
                    var responseMessage = (response && response.message ? response.message : '').toString().trim();
                    var successMessage = (!responseMessage || responseMessage === 'Order Found')
                        ? (code ? $couponAppliedJson : $couponRemovedJson)
                        : responseMessage;
                    var normalized = cartData ? (cartData.order || cartData) : null;

                    if (code === '') {
                        $('#coupon-code').val('');
                    }

                    if (normalized && typeof normalized.total_amount !== 'undefined') {
                        updateCartSummary(normalized);

                        if (typeof jQuery !== 'undefined') {
                            jQuery(document).trigger('cartDataUpdated', [normalized]);
                        }
                    } else {
                        refreshCartSummary();
                    }

                    showMessage(successMessage, 'success');
                } else {
                    var errorMessage = (response && response.message ? response.message : '') || $errorApplyingCouponJson;
                    showMessage(errorMessage, 'danger');
                }
            },
            error: function() {
                showMessage($errorApplyingCouponJson, 'danger');
            }
        });
    }
    
    function showMessage(message, type) {
        var html = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                   message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                   '</div>';
        $('#coupon-message').html(html);
    }
});
JS
);
?>

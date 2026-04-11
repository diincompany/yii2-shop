<?php
/**
 * Shipping Address Section
 * @var array $order
 */

use yii\helpers\Html;

$shipping = is_array($order['shipping'] ?? null) ? $order['shipping'] : [];
$selectedOption = is_array($shipping['selected_option'] ?? null) ? $shipping['selected_option'] : [];
$currentLanguage = strtolower((string) (Yii::$app->language ?? ''));
$isSpanishLanguage = strpos($currentLanguage, 'es') === 0;
$deliveryTypeLabel = trim((string) (
    ($isSpanishLanguage ? ($shipping['courier_delivery_type_label_es'] ?? null) : ($shipping['courier_delivery_type_label_en'] ?? null))
    ?? ($isSpanishLanguage ? ($selectedOption['delivery_type_label_es'] ?? null) : ($selectedOption['delivery_type_label_en'] ?? null))
    ?? $shipping['courier_delivery_type']
    ?? $selectedOption['delivery_type']
    ?? ''
));
$providerName = trim((string) ($shipping['provider_name'] ?? $shipping['provider_code'] ?? ''));
$courierName = trim((string) ($shipping['courier_name'] ?? $selectedOption['courier_name'] ?? $selectedOption['name'] ?? ''));
$warehouseName = trim((string) ($shipping['warehouse_name'] ?? ($shipping['warehouse']['name'] ?? '')));
$warehouseAddress = trim((string) ($shipping['warehouse']['address_1'] ?? ''));
$isPickupShipping = strpos(strtolower($deliveryTypeLabel), 'pickup') !== false || strpos(strtolower($deliveryTypeLabel), 'recoger') !== false;
$shippingCost = isset($shipping['shipping_cost']) ? (float) $shipping['shipping_cost'] : null;
$hasSelectedShipping = $providerName !== ''
    || $courierName !== ''
    || $deliveryTypeLabel !== ''
    || $warehouseName !== ''
    || $shippingCost !== null;
?>

<?php if (!empty($order['shipping_address'])): ?>
    <!-- Shipping Address -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 text-uppercase fw-bold"><?= Yii::t('shop', 'Shipping Address') ?></h6>
        </div>
        <div class="card-body">
            <?php 
                $shippingAddr = $order['shipping_address'];
                $location = $shippingAddr['location'] ?? [];
                $fullName = trim((string) ($shippingAddr['full_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = trim((string) ($shippingAddr['first_name'] ?? '') . ' ' . (string) ($shippingAddr['last_name'] ?? ''));
                }

                $cityName = $location['city']['name'] ?? ($shippingAddr['city_name'] ?? null);
                $stateName = $location['state']['name'] ?? ($shippingAddr['state_name'] ?? null);
                $countryName = $location['country']['name'] ?? ($shippingAddr['country_name'] ?? null);
                $locationStr = array_filter([$cityName, $stateName, $countryName]);
            ?>
            
            <!-- Full Name -->
            <?php if ($fullName !== ''): ?>
                <p class="mb-2">
                    <strong><?= Html::encode($fullName) ?></strong>
                </p>
            <?php endif; ?>

            <!-- Contact -->
            <?php if (!empty($shippingAddr['email'])): ?>
                <p class="text-muted small mb-1">
                    <i class="bi bi-envelope me-2"></i><?= Html::encode($shippingAddr['email']) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($shippingAddr['phone_number'])): ?>
                <p class="text-muted small mb-2">
                    <i class="bi bi-telephone me-2"></i><?= Html::encode($shippingAddr['phone_number']) ?>
                </p>
            <?php endif; ?>
            
            <!-- Street Address -->
            <?php if (!empty($shippingAddr['address_1']) || !empty($shippingAddr['address_2'])): ?>
                <p class="mb-1 small text-dark">
                    <?= Html::encode($shippingAddr['address_1'] ?? '') ?>
                    <?php if (!empty($shippingAddr['address_2'])): ?>
                        <br>
                        <?= Html::encode($shippingAddr['address_2']) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($shippingAddr['reference'])): ?>
                <p class="mb-1 text-muted small">
                    <strong><?= Yii::t('shop', 'Reference') ?>:</strong> <?= Html::encode($shippingAddr['reference']) ?>
                </p>
            <?php endif; ?>
            
            <!-- City, State, Country -->
            <?php if (!empty($locationStr)): ?>
                <p class="mb-2 small text-dark">
                    <?= Html::encode(implode(', ', $locationStr)) ?>
                </p>
            <?php endif; ?>
            
            <!-- Postal Code -->
            <?php if (!empty($shippingAddr['zipcode'])): ?>
                <p class="mb-1 text-muted small">
                    <strong><?= Yii::t('shop', 'Postal Code') ?>:</strong> <?= Html::encode($shippingAddr['zipcode']) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($shippingAddr['label'])): ?>
                <p class="mb-0 text-muted small">
                    <strong><?= Yii::t('shop', 'Address Label') ?>:</strong> <?= Html::encode($shippingAddr['label']) ?>
                </p>
            <?php endif; ?>

            <?php if ($hasSelectedShipping): ?>
                <hr>
                <h6 class="mb-3 text-uppercase fw-bold small"><?= Yii::t('shop', 'Selected Shipping') ?></h6>

                <?php if ($providerName !== ''): ?>
                    <p class="mb-1 text-muted small">
                        <strong><?= Yii::t('shop', 'Provider') ?>:</strong> <?= Html::encode($providerName) ?>
                    </p>
                <?php endif; ?>

                <?php if ($courierName !== ''): ?>
                    <p class="mb-1 text-muted small">
                        <strong><?= Yii::t('shop', 'Courier') ?>:</strong> <?= Html::encode($courierName) ?>
                    </p>
                <?php endif; ?>

                <?php if ($deliveryTypeLabel !== ''): ?>
                    <p class="mb-1 text-muted small">
                        <strong><?= Yii::t('shop', 'Delivery Type') ?>:</strong> <?= Html::encode($deliveryTypeLabel) ?>
                    </p>
                <?php endif; ?>

                <?php if ($warehouseName !== ''): ?>
                    <p class="mb-1 text-muted small">
                        <strong><?= Yii::t('shop', 'Warehouse') ?>:</strong> <?= Html::encode($warehouseName) ?>
                    </p>
                <?php endif; ?>

                <?php if ($isPickupShipping && $warehouseAddress !== ''): ?>
                    <p class="mb-1 text-muted small">
                        <strong><?= Yii::t('shop', 'Pickup Address') ?>:</strong> <?= Html::encode($warehouseAddress) ?>
                    </p>
                <?php endif; ?>

                <?php if ($shippingCost !== null): ?>
                    <p class="mb-0 text-muted small">
                        <strong><?= Yii::t('shop', 'shipping') ?>:</strong> L <?= Html::encode(number_format($shippingCost, 2)) ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

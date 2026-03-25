<?php
/**
 * Shipping Address Section
 * @var array $order
 */

use yii\helpers\Html;
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
        </div>
    </div>
<?php endif; ?>

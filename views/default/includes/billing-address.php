<?php
/**
 * Billing Address Section
 * @var array $order
 */

use yii\helpers\Html;
?>

<?php if (!empty($order['billing_address'])): ?>
    <!-- Billing Address -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 text-uppercase fw-bold"><?= Yii::t('shop', 'Billing Address') ?></h6>
        </div>
        <div class="card-body">
            <?php 
                $billingAddr = $order['billing_address'];
                $location = $billingAddr['location'] ?? [];
                $fullName = trim((string) ($billingAddr['full_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = trim((string) ($billingAddr['first_name'] ?? '') . ' ' . (string) ($billingAddr['last_name'] ?? ''));
                }

                $cityName = $location['city']['name'] ?? ($billingAddr['city_name'] ?? null);
                $stateName = $location['state']['name'] ?? ($billingAddr['state_name'] ?? null);
                $countryName = $location['country']['name'] ?? ($billingAddr['country_name'] ?? null);
                $locationStr = array_filter([$cityName, $stateName, $countryName]);
            ?>
            
            <!-- Full Name -->
            <?php if ($fullName !== ''): ?>
                <p class="mb-2">
                    <strong><?= Html::encode($fullName) ?></strong>
                </p>
            <?php endif; ?>

            <!-- Contact -->
            <?php if (!empty($billingAddr['email'])): ?>
                <p class="text-muted small mb-1">
                    <i class="bi bi-envelope me-2"></i><?= Html::encode($billingAddr['email']) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($billingAddr['phone_number'])): ?>
                <p class="text-muted small mb-2">
                    <i class="bi bi-telephone me-2"></i><?= Html::encode($billingAddr['phone_number']) ?>
                </p>
            <?php endif; ?>
            
            <!-- Street Address -->
            <?php if (!empty($billingAddr['address_1'])): ?>
                <p class="mb-1 small text-dark">
                    <?= Html::encode($billingAddr['address_1']) ?>
                    <?php if (!empty($billingAddr['address_2'])): ?>
                        <br>
                        <?= Html::encode($billingAddr['address_2']) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($billingAddr['reference'])): ?>
                <p class="mb-1 text-muted small">
                    <strong><?= Yii::t('shop', 'Reference') ?>:</strong> <?= Html::encode($billingAddr['reference']) ?>
                </p>
            <?php endif; ?>
            
            <!-- City, State, Country -->
            <?php if (!empty($locationStr)): ?>
                <p class="mb-2 small text-dark">
                    <?= Html::encode(implode(', ', $locationStr)) ?>
                </p>
            <?php endif; ?>
            
            <!-- Postal Code -->
            <?php if (!empty($billingAddr['zipcode'])): ?>
                <p class="mb-1 text-muted small">
                    <strong><?= Yii::t('shop', 'Postal Code') ?>:</strong> <?= Html::encode($billingAddr['zipcode']) ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($billingAddr['label'])): ?>
                <p class="mb-0 text-muted small">
                    <strong><?= Yii::t('shop', 'Address Label') ?>:</strong> <?= Html::encode($billingAddr['label']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

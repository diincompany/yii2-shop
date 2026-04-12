<?php
/**
 * Checkout form card.
 *
 * @var array $countries
 * @var array $shippingAddress
 * @var array $billingAddress
 * @var string $orderNotes
 * @var string $shippingServiceLevel
 * @var float $shipping
 * @var string $moduleRoute
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="card">
    <div class="card-body">
        <h5 class="border-bottom mb-4 pb-3"><?= Yii::t('shop', 'shipping_address') ?></h5>
        <form id="checkout-form" action="<?= Url::to([$moduleRoute . '/default/process-checkout']) ?>" method="post">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <?= Html::hiddenInput('service_level', $shippingServiceLevel ?? '') ?>
            <?= Html::hiddenInput('provider_code', '') ?>
            <?= Html::hiddenInput('selected_option', '') ?>
            <?= Html::hiddenInput('shipping_cost', $shipping ?? 0) ?>
            <?= Html::hiddenInput('customer_shipping_latitude', '') ?>
            <?= Html::hiddenInput('customer_shipping_longitude', '') ?>
            <?= Html::hiddenInput('shipping_latitude', '') ?>
            <?= Html::hiddenInput('shipping_longitude', '') ?>

            <?= $this->render('../../_forms/shipping-address.php', [
                'countries' => $countries,
                'address' => $shippingAddress ?? [],
                'orderNotes' => $orderNotes ?? '',
                'moduleRoute' => $moduleRoute,
            ]) ?>

            <?= $this->render('../../_forms/billing-address-toggle.php') ?>

            <?= $this->render('../../_forms/billing-address.php', [
                'countries' => $countries,
                'address' => $billingAddress ?? [],
                'moduleRoute' => $moduleRoute,
            ]) ?>

            <div class="mt-4" id="shipping-geolocation-section">
                <h6 class="border-bottom mb-3 pb-2"><?= Yii::t('shop', 'Ubicación de entrega') ?></h6>
                <p class="small text-muted mb-2"><?= Yii::t('shop', 'Selecciona el punto exacto en el mapa para cotizar con el warehouse más cercano.') ?></p>
                <div id="shipping-location-map" style="height: 280px; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;"></div>
                <p id="shipping-location-coords" class="small text-muted mt-2 mb-0"></p>
            </div>

            <div id="shipping-options-section" class="d-none mt-4">
                <h6 class="border-bottom mb-3 pb-2"><?= Yii::t('shop', 'shipping_options') ?></h6>
                
                <!-- Loader -->
                <div id="shipping-options-loader" class="d-none text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden"><?= Yii::t('shop', 'loading') ?></span>
                    </div>
                    <span class="text-muted small"><?= Yii::t('shop', 'Loading Shipping Options...') ?></span>
                </div>
                
                <!-- Container for options -->
                <div id="shipping-options-container">
                    <!-- Options will be rendered here -->
                </div>
            </div>

            <div class="pt-4">
                <button type="button" class="btn btn-primary w-100" id="place-order-btn">
                    <?= Yii::t('shop', 'place_order') ?>
                </button>
                <p class="m-0 pt-3 small text-muted">
                    <?= Yii::t('shop', 'checkout_terms_message', [
                        'terms_link' => Html::a(Yii::t('shop', 'terms_conditions'), ['/site/terms'], ['target' => '_blank']),
                        'privacy_link' => Html::a(Yii::t('shop', 'privacy_policy'), ['/site/privacy'], ['target' => '_blank']),
                    ]) ?>
                </p>
            </div>
        </form>
    </div>
</div>

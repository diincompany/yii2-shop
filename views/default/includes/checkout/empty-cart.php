<?php
/**
 * Empty cart state for checkout.
 *
 * @var string $moduleRoute
 */

use yii\helpers\Html;
?>
<div class="py-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <div class="card">
                    <div class="card-body py-6">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: var(--bs-info);"></i>
                        <h4 class="mt-4 mb-3"><?= Yii::t('shop', 'Your cart is empty') ?></h4>
                        <p class="text-muted mb-4"><?= Yii::t('shop', 'empty_cart_checkout_message') ?></p>
                        <?= Html::a(Yii::t('shop', 'Continue Shopping'), [$moduleRoute . '/products/index'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
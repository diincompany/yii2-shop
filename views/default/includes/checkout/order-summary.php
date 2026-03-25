<?php
/**
 * Checkout order summary sidebar.
 *
 * @var array $items
 * @var float $subtotal
 * @var float $taxes
 * @var float $discountAmount
 * @var float $shipping
 * @var float $grandTotal
 * @var string $couponCode
 * @var string $moduleRoute
 */
?>
<div class="card">
    <div class="card-body">
        <h5 class="border-bottom mb-4 pb-3"><?= Yii::t('shop', 'order_summary') ?></h5>

        <?= $this->render('order-summary-items.php', [
            'items' => $items,
            'moduleRoute' => $moduleRoute,
        ]) ?>

        <ul class="list-unstyled m-0">
            <li class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="me-2 text-body"><?= Yii::t('shop', 'subtotal') ?></h6>
                <span class="text-end" id="checkout-subtotal">L<?= Yii::$app->formatter->asDecimal($subtotal, 2) ?></span>
            </li>
            <li class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="me-2 text-body"><?= Yii::t('shop', 'taxes') ?></h6>
                <span class="text-end" id="checkout-taxes">L<?= Yii::$app->formatter->asDecimal($taxes, 2) ?></span>
            </li>
            <li class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="me-2 text-body" id="checkout-discount-label">
                    <?= Yii::t('shop', 'Discount') ?><?= !empty($couponCode) ? ' (' . yii\helpers\Html::encode($couponCode) . ')' : '' ?>
                </h6>
                <span class="text-end" id="checkout-discount"><?= $discountAmount > 0 ? '-L' : 'L' ?><?= Yii::$app->formatter->asDecimal(abs($discountAmount), 2) ?></span>
            </li>
            <li class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="me-2 text-body"><?= Yii::t('shop', 'shipping') ?></h6>
                <span class="text-end" id="checkout-shipping">L<?= Yii::$app->formatter->asDecimal($shipping, 2) ?></span>
            </li>
            <li class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                <h6 class="me-2"><?= Yii::t('shop', 'grand_total') ?></h6>
                <span class="text-end text-mode fw-bold" id="checkout-grand-total">L<?= Yii::$app->formatter->asDecimal($grandTotal, 2) ?></span>
            </li>
        </ul>
    </div>
</div>
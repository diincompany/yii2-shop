<?php
/**
 * Order Totals Section
 * @var array $order
 */

use yii\helpers\Html;
?>

<?php
$discountAmount = (float)($order['discount_amount'] ?? 0);
$shippingAmount = (float)($order['shipping_amount'] ?? ($order['shipping']['shipping_cost'] ?? 0));
$couponCode = trim((string)($order['coupon_code'] ?? ($order['coupon']['code'] ?? '')));
$discountLabel = Yii::t('shop', 'Discount') . ($couponCode !== '' ? ' (' . Html::encode($couponCode) . ')' : '');
?>

<!-- Order Totals -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <p class="text-muted mb-2"><?= Yii::t('shop', 'subtotal') ?></p>
                <p class="text-muted mb-2"><?= Yii::t('shop', 'taxes') ?></p>
                <?php if ($discountAmount > 0): ?>
                    <p class="text-muted mb-2"><?= $discountLabel ?></p>
                <?php endif; ?>
                <p class="text-muted mb-3"><?= Yii::t('shop', 'shipping') ?></p>
                <hr class="my-2">
                <h6 class="text-uppercase fw-bold"><?= Yii::t('shop', 'grand_total') ?></h6>
            </div>
            <div class="col-6 text-end">
                <p class="mb-2">L <?= number_format((float)($order['subtotal_amount'] ?? 0), 2) ?></p>
                <p class="mb-2">L <?= number_format((float)($order['tax_amount'] ?? 0), 2) ?></p>
                <?php if ($discountAmount > 0): ?>
                    <p class="mb-2">-L <?= number_format($discountAmount, 2) ?></p>
                <?php endif; ?>
                <p class="mb-3">L <?= number_format($shippingAmount, 2) ?></p>
                <hr class="my-2">
                <h6 class="text-success fw-bold">L <?= number_format((float)($order['total_amount'] ?? 0), 2) ?></h6>
            </div>
        </div>
    </div>
</div>

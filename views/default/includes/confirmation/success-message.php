<?php
/**
 * Success/pending/cancelled header section for confirmation page.
 * @var bool $isPendingPayment
 * @var bool $isCancelledOrder
 */
?>

<div class="py-6 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi <?= $isCancelledOrder ? 'bi-x-circle text-danger' : ($isPendingPayment ? 'bi-hourglass-split text-warning' : 'bi-check-circle text-success') ?>" style="font-size: 3.5rem;"></i>
            </div>
            <h1 class="mb-2">
                <?= $isCancelledOrder
                    ? Yii::t('shop', 'Order cancelled')
                    : ($isPendingPayment ? Yii::t('shop', 'Order received - pending payment') : Yii::t('shop', 'Thank you for your order!')) ?>
            </h1>
            <p class="text-muted fs-5">
                <?= $isCancelledOrder
                    ? Yii::t('shop', 'This order has been cancelled.')
                    : ($isPendingPayment ? Yii::t('shop', 'Your order is pending payment confirmation.') : Yii::t('shop', 'Your order has been received and is being processed.')) ?>
            </p>
        </div>
    </div>
</div>

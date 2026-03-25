<?php
/**
 * Empty order state for confirmation page.
 */

use yii\helpers\Html;
?>

<div class="py-6">
    <div class="container">
        <div class="text-center">
            <div class="mb-4">
                <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
            </div>
            <h2 class="mb-3"><?= Yii::t('shop', 'No order information available') ?></h2>
            <p class="text-muted mb-4"><?= Yii::t('shop', 'Unable to retrieve your order details.') ?></p>
            <?= Html::a(
                '<i class="bi bi-house me-2"></i>' . Yii::t('shop', 'Back to Home'),
                ['/'],
                ['class' => 'btn btn-primary']
            ) ?>
        </div>
    </div>
</div>

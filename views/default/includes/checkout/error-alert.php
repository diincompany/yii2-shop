<?php
/**
 * Checkout error state.
 *
 * @var string $error
 * @var string $moduleRoute
 */

use yii\helpers\Html;
?>
<div class="py-6">
    <div class="container">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <strong><?= Yii::t('shop', 'error') ?></strong>: <?= Html::encode($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="text-center">
            <?= Html::a(Yii::t('shop', 'back_to_cart'), [$moduleRoute . '/cart'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>
</div>
<?php
/**
 * Action buttons section for confirmation page.
 * @var array $order
 * @var bool $canCancelOrder
 */

use yii\helpers\Html;

$moduleId = trim((string) (Yii::$app->controller->module->id ?? ''), '/');
if ($moduleId === '' || $moduleId === Yii::$app->id) {
    $moduleId = 'shop';
}
$moduleRoute = '/' . $moduleId;
$canCancelOrder = isset($canCancelOrder) ? (bool) $canCancelOrder : false;
$orderStatus = strtolower(trim((string) ($order['status'] ?? '')));
$cancelBlockedMessage = Yii::t('shop', 'You can only cancel an order before it has been dispatched.');
$cancelDisabledMessage = in_array($orderStatus, ['cancelled', 'canceled'], true)
    ? Yii::t('shop', 'This order cannot be cancelled.')
    : $cancelBlockedMessage;
?>

<div class="row mt-5">
    <div class="col-12">
        <?php if (Yii::$app->session->hasFlash('shopCancelSuccess')): ?>
            <div class="alert alert-success" role="alert">
                <?= Html::encode(Yii::$app->session->getFlash('shopCancelSuccess')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('shopCancelError')): ?>
            <div class="alert alert-warning" role="alert">
                <?= Html::encode(Yii::$app->session->getFlash('shopCancelError')) ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <?php if (!empty($order['hash'])): ?>
                <?= Html::a(
                    '<i class="bi bi-file-earmark-pdf me-2"></i>' . Yii::t('shop', 'Download PDF'),
                    [$moduleRoute . '/default/confirmation-pdf', 'hash' => $order['hash']],
                    [
                        'class' => 'btn btn-outline-danger px-4',
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                    ]
                ) ?>
            <?php endif; ?>
            <?php if (!empty($order['hash']) && $canCancelOrder): ?>
                <?= Html::button(
                    '<i class="bi bi-x-circle me-2"></i>' . Yii::t('shop', 'Cancel Order'),
                    [
                        'class' => 'btn btn-outline-danger px-4',
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#cancelOrderModal',
                    ]
                ) ?>
            <?php elseif (!empty($order['hash'])): ?>
                <?= Html::button(
                    '<i class="bi bi-x-circle me-2"></i>' . Yii::t('shop', 'Cancel Order'),
                    [
                        'class' => 'btn btn-outline-danger px-4',
                        'disabled' => true,
                        'title' => $cancelDisabledMessage,
                    ]
                ) ?>
            <?php endif; ?>
            <?= Html::a(
                '<i class="bi bi-arrow-left me-2"></i>' . Yii::t('shop', 'Back to Home'),
                ['/'],
                ['class' => 'btn btn-outline-secondary px-4']
            ) ?>
            <?= Html::a(
                '<i class="bi bi-shop me-2"></i>' . Yii::t('shop', 'Continue Shopping'),
                [$moduleRoute . '/products/index'],
                ['class' => 'btn btn-primary px-4']
            ) ?>
        </div>

        <?php if (!empty($order['hash']) && $canCancelOrder): ?>
            <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <?= Html::beginForm([$moduleRoute . '/default/cancel-order', 'hash' => $order['hash']], 'post') ?>
                            <div class="modal-header">
                                <h5 class="modal-title" id="cancelOrderModalLabel"><?= Html::encode(Yii::t('shop', 'Cancel Order')) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Html::encode(Yii::t('shop', 'Close')) ?>"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3"><?= Html::encode(Yii::t('shop', 'Please tell us why you are cancelling this order.')) ?></p>
                                <label for="cancellation-reason" class="form-label"><?= Html::encode(Yii::t('shop', 'Cancellation Reason')) ?></label>
                                <?= Html::textarea('cancellation_reason', '', [
                                    'id' => 'cancellation-reason',
                                    'class' => 'form-control',
                                    'rows' => 4,
                                    'required' => true,
                                    'maxlength' => 1000,
                                    'placeholder' => Yii::t('shop', 'Write the reason for cancellation here'),
                                ]) ?>
                            </div>
                            <div class="modal-footer">
                                <?= Html::button(Yii::t('shop', 'Keep Order'), [
                                    'type' => 'button',
                                    'class' => 'btn btn-outline-secondary',
                                    'data-bs-dismiss' => 'modal',
                                ]) ?>
                                <?= Html::submitButton(Yii::t('shop', 'Confirm Cancellation'), [
                                    'class' => 'btn btn-danger',
                                ]) ?>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

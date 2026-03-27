<?php
/**
 * Action buttons section for confirmation page.
 * @var array $order
 */

use yii\helpers\Html;

$moduleId = trim((string) (Yii::$app->controller->module->id ?? ''), '/');
if ($moduleId === '' || $moduleId === Yii::$app->id) {
    $moduleId = 'shop';
}
$moduleRoute = '/' . $moduleId;
?>

<div class="row mt-5">
    <div class="col-12">
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
    </div>
</div>

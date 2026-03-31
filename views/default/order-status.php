<?php
/**
 * Order status lookup page.
 * @var yii\web\View $this
 * @var array|null $order
 * @var string|null $lookupError
 * @var bool $lookupSubmitted
 * @var string $orderNumber
 * @var string $email
 */

use yii\helpers\Html;

$this->title = Yii::t('shop', 'Order status search');
?>

<div class="py-6 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="mb-2"><?= Html::encode(Yii::t('shop', 'Order status')) ?></h1>
            <p class="text-muted mb-0"><?= Html::encode(Yii::t('shop', 'Review the latest details of your purchase.')) ?></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <?= Html::beginForm(['/shop/order-status'], 'post', ['class' => 'row g-3']) ?>
                            <div class="col-md-6">
                                <label class="form-label"><?= Html::encode(Yii::t('shop', 'Order Number')) ?></label>
                                <?= Html::input('text', 'order_number', $orderNumber, [
                                    'class' => 'form-control',
                                    'placeholder' => Yii::t('shop', 'Please enter your order number.'),
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= Html::encode(Yii::t('shop', 'email_address')) ?></label>
                                <?= Html::input('email', 'email', $email, [
                                    'class' => 'form-control',
                                    'placeholder' => Yii::t('shop', 'Please enter the email used for your order.'),
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end">
                                <?= Html::submitButton(Yii::t('shop', 'Find your order'), ['class' => 'btn btn-primary px-4']) ?>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                </div>

                <?php if ($lookupSubmitted && $lookupError !== null): ?>
                    <div class="alert alert-warning" role="alert">
                        <?= Html::encode($lookupError) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($order)): ?>
    <div class="py-6">
        <div class="container">
            <div class="mb-3 d-flex justify-content-end">
                <?= Html::a(
                    Yii::t('shop', 'Lookup another order'),
                    ['/shop/order-status'],
                    ['class' => 'btn btn-outline-secondary btn-sm']
                ) ?>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <?= $this->render('includes/order-header', ['order' => $order]) ?>
                    <?= $this->render('includes/order-items', ['order' => $order]) ?>
                    <?= $this->render('includes/order-totals', ['order' => $order]) ?>
                </div>
                <div class="col-lg-4">
                    <?= $this->render('includes/customer-info', ['order' => $order]) ?>
                    <?= $this->render('includes/shipping-address', ['order' => $order]) ?>
                    <?= $this->render('includes/billing-address', ['order' => $order]) ?>
                    <?= $this->render('includes/payment-info', [
                        'order' => $order,
                        'showTransferDetailsInSidebar' => true,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

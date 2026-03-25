<?php
/**
 * Payment Information Section
 * @var array $order
 * @var bool $showTransferDetailsInSidebar
 */

use yii\helpers\Html;
?>

<?php
$payments = (!empty($order['payments']) && is_array($order['payments'])) ? $order['payments'] : [];
$payment = !empty($payments) ? reset($payments) : [];
$showTransferDetailsInSidebar = isset($showTransferDetailsInSidebar)
    ? (bool) $showTransferDetailsInSidebar
    : true;
$orderStatus = strtolower(trim((string) ($order['status'] ?? '')));

$rawResponse = $payment['raw_response'] ?? [];
if (is_string($rawResponse)) {
    $decodedRawResponse = json_decode($rawResponse, true);
    $rawResponse = is_array($decodedRawResponse) ? $decodedRawResponse : [];
}
if (!is_array($rawResponse)) {
    $rawResponse = [];
}

$paymentStatusRaw = strtolower(trim((string) ($payment['status'] ?? '')));
$isPaid = in_array($paymentStatusRaw, ['1', 'paid', 'success'], true)
    || !empty($payment['paid_at'])
    || in_array($orderStatus, ['paid', 'completed'], true);

$isPending = !$isPaid && (
    $paymentStatusRaw === '0'
    || $paymentStatusRaw === 'pending'
    || $orderStatus === 'pending'
);

$statusText = $isPaid
    ? Yii::t('shop', 'Paid')
    : ($isPending ? Yii::t('shop', 'Pending Payment') : Yii::t('shop', 'Payment Not Completed'));
$statusClass = $isPaid ? 'bg-success' : 'bg-warning text-dark';
$statusIcon = $isPaid ? 'bi-check-circle' : 'bi-hourglass-split';

$gatewayCode = strtolower(trim((string) ($payment['gateway_code'] ?? $rawResponse['gateway'] ?? '')));
$paymentMethodIcon = 'bi-credit-card';
$paymentMethodText = Yii::t('shop', 'Credit Card');

if ($gatewayCode !== '') {
    if (strpos($gatewayCode, 'bank') !== false || strpos($gatewayCode, 'transfer') !== false) {
        $paymentMethodIcon = 'bi-bank';
        $paymentMethodText = Yii::t('shop', 'Bank Transfer');
    } elseif (strpos($gatewayCode, 'paypal') !== false) {
        $paymentMethodIcon = 'bi-paypal';
        $paymentMethodText = 'PayPal';
    } elseif (strpos($gatewayCode, 'card') !== false || $gatewayCode === 'stripe') {
        $paymentMethodIcon = 'bi-credit-card';
        $paymentMethodText = Yii::t('shop', 'Credit Card');
    }
}

$paymentAmount = isset($payment['amount']) ? (float) $payment['amount'] : 0.0;
$transactionId = trim((string) ($payment['code'] ?? $payment['gateway_reference'] ?? $payment['reference'] ?? ''));
$instructions = is_array($rawResponse['instructions'] ?? null) ? $rawResponse['instructions'] : [];
$instructionSteps = is_array($instructions['steps'] ?? null) ? $instructions['steps'] : [];
$support = is_array($instructions['support'] ?? null) ? $instructions['support'] : [];
$bankAccounts = is_array($rawResponse['bank_accounts'] ?? null) ? $rawResponse['bank_accounts'] : [];
$paymentLink = trim((string) ($order['payment_link'] ?? ''));
?>

<!-- Payment Information -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 text-uppercase fw-bold"><?= Yii::t('shop', 'Payment Information') ?></h6>
    </div>
    <div class="card-body">
        <div class="mb-3 pb-3 border-bottom">
            <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Payment Status') ?></small>
            <span class="badge <?= $statusClass ?>">
                <i class="bi <?= $statusIcon ?> me-1"></i><?= Html::encode($statusText) ?>
            </span>
        </div>

        <div class="mb-3 pb-3 border-bottom">
            <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Payment Method') ?></small>
            <i class="bi <?= Html::encode($paymentMethodIcon) ?> me-2"></i><?= Html::encode($paymentMethodText) ?>
        </div>

        <?php if ($paymentAmount > 0): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Amount') ?></small>
                <strong><?= Html::encode('L ' . number_format($paymentAmount, 2)) ?></strong>
            </div>
        <?php endif; ?>

        <?php if (!empty($payment['auth_code'])): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Authorization Code') ?></small>
                <strong><?= Html::encode($payment['auth_code']) ?></strong>
            </div>
        <?php endif; ?>

        <?php if (!empty($payment['card_last_four'])): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Card Number') ?></small>
                <strong><?= Html::encode($payment['card'] ?? 'VISA') ?></strong>
                <span class="text-muted">•••• •••• •••• <?= Html::encode($payment['card_last_four']) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($payment['paid_at'])): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Paid At') ?></small>
                <span><?= Yii::$app->formatter->asDatetime($payment['paid_at']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($showTransferDetailsInSidebar && !empty($instructions)): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Payment Instructions') ?></small>

                <?php if (!empty($instructions['title'])): ?>
                    <p class="small mb-1"><strong><?= Html::encode($instructions['title']) ?></strong></p>
                <?php endif; ?>

                <?php if (!empty($instructions['description'])): ?>
                    <p class="small mb-2 text-muted"><?= Html::encode($instructions['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($instructionSteps)): ?>
                    <ol class="small mb-2 ps-3">
                        <?php foreach ($instructionSteps as $step): ?>
                            <li><?= Html::encode((string) $step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (!empty($support['email']) || !empty($support['phone'])): ?>
                    <p class="small mb-0 text-muted">
                        <strong><?= Yii::t('shop', 'Support') ?>:</strong>
                        <?php if (!empty($support['email'])): ?>
                            <?= Html::encode($support['email']) ?>
                        <?php endif; ?>
                        <?php if (!empty($support['email']) && !empty($support['phone'])): ?>
                            |
                        <?php endif; ?>
                        <?php if (!empty($support['phone'])): ?>
                            <?= Html::encode($support['phone']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($showTransferDetailsInSidebar && !empty($bankAccounts)): ?>
            <div class="mb-3">
                <small class="text-muted text-uppercase d-block mb-2"><?= Yii::t('shop', 'Bank Accounts') ?></small>
                <?php foreach ($bankAccounts as $account): ?>
                    <div class="small mb-2">
                        <strong><?= Html::encode($account['bank_name'] ?? Yii::t('shop', 'Bank Transfer')) ?></strong><br>
                        <?= Html::encode($account['holder_name'] ?? '') ?><br>
                        <?= Html::encode($account['account_number'] ?? '') ?>
                        <?php if (!empty($account['account_type_label'])): ?>
                            (<?= Html::encode($account['account_type_label']) ?>)
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

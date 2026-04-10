<?php
/**
 * Next Steps Section
 * @var array|null $order
 * @var bool $showBankTransferDetails
 */

use yii\helpers\Html;

$showBankTransferDetails = isset($showBankTransferDetails) ? (bool) $showBankTransferDetails : false;
$payments = (!empty($order['payments']) && is_array($order['payments'])) ? $order['payments'] : [];
$payment = !empty($payments) ? reset($payments) : [];
$rawResponse = $payment['raw_response'] ?? [];

if (is_string($rawResponse)) {
    $decodedRawResponse = json_decode($rawResponse, true);
    $rawResponse = is_array($decodedRawResponse) ? $decodedRawResponse : [];
}

if (!is_array($rawResponse)) {
    $rawResponse = [];
}

$orderMetadata = is_array($order['metadata'] ?? null) ? $order['metadata'] : [];
$orderPaymentMetadata = is_array($orderMetadata['payment'] ?? null) ? $orderMetadata['payment'] : [];
$gatewayCode = strtolower(trim((string) ($payment['gateway_code'] ?? $rawResponse['gateway'] ?? $orderPaymentMetadata['gateway_code'] ?? $orderPaymentMetadata['gateway'] ?? '')));
$isCashOnDelivery = in_array($gatewayCode, ['cash_on_delivery', 'cashondelivery'], true);
$instructions = is_array($rawResponse['instructions'] ?? null) ? $rawResponse['instructions'] : [];
$instructionSteps = is_array($instructions['steps'] ?? null) ? $instructions['steps'] : [];
$support = is_array($instructions['support'] ?? null) ? $instructions['support'] : [];
$bankAccounts = is_array($rawResponse['bank_accounts'] ?? null) ? $rawResponse['bank_accounts'] : [];
$showInstructionBlock = $showBankTransferDetails || $isCashOnDelivery || !empty($instructions);
$showDefaultCodSteps = $isCashOnDelivery && empty($instructions) && empty($bankAccounts);
?>

<!-- Next Steps -->
<div class="card border-0 shadow-sm bg-light">
    <div class="card-body">
        <h6 class="text-uppercase fw-bold mb-3"><?= Yii::t('shop', 'Next Steps') ?></h6>
        <?php if ($showInstructionBlock): ?>
            <div class="mb-3 pb-3 border-bottom">
                <small class="text-muted d-block">
                    <?= $isCashOnDelivery
                        ? Yii::t('shop', 'Your payment will be collected upon delivery.')
                        : Yii::t('shop', 'Your payment is pending confirmation.') ?>
                </small>
            </div>

            <?php if (!empty($instructions)): ?>
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

            <?php if (!empty($bankAccounts)): ?>
                <div class="mb-0">
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

            <?php if ($showDefaultCodSteps): ?>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-cash-coin text-success me-2"></i>
                        <small><?= Yii::t('shop', 'Pay with cash on delivery when your order arrives') ?></small>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <small><?= Yii::t('shop', 'We may contact you to confirm delivery details before dispatch') ?></small>
                    </li>
                    <li>
                        <i class="bi bi-truck me-2"></i>
                        <small><?= Yii::t('shop', 'Have the exact amount ready if possible to speed up delivery') ?></small>
                    </li>
                </ul>
            <?php elseif (empty($instructions) && empty($bankAccounts)): ?>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <small><?= Yii::t('shop', 'You will receive a confirmation email shortly') ?></small>
                    </li>
                    <li>
                        <i class="bi bi-hourglass-split text-warning me-2"></i>
                        <small><?= Yii::t('shop', 'Your payment is pending confirmation.') ?></small>
                    </li>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <ul class="list-unstyled">
                <li class="mb-2">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <small><?= Yii::t('shop', 'You will receive a confirmation email shortly') ?></small>
                </li>
                <li class="mb-2">
                    <i class="bi bi-package me-2"></i>
                    <small><?= Yii::t('shop', 'We are preparing your order for shipment') ?></small>
                </li>
                <li>
                    <i class="bi bi-truck me-2"></i>
                    <small><?= Yii::t('shop', 'You will be notified when your order ships') ?></small>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>

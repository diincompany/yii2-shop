<?php
/**
 * Order Confirmation Page
 * @var yii\web\View $this
 * @var array|null $order
 */

$this->title = Yii::t('shop', 'Order Confirmation');
?>

<?php if (empty($order)): ?>
    <?= $this->render('includes/confirmation/no-order') ?>
<?php else: ?>
    <?php
        $orderStatus = strtolower(trim((string) ($order['status'] ?? '')));
        $isPendingPayment = $orderStatus === 'pending';

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

        $gatewayCode = strtolower(trim((string) ($payment['gateway_code'] ?? $rawResponse['gateway'] ?? '')));
        $hasBankAccounts = is_array($rawResponse['bank_accounts'] ?? null) && !empty($rawResponse['bank_accounts']);
        $isBankTransfer = (
            $gatewayCode !== ''
            && (strpos($gatewayCode, 'bank') !== false || strpos($gatewayCode, 'transfer') !== false)
        ) || $hasBankAccounts;
    ?>
    <?= $this->render('includes/confirmation/success-message', [
        'isPendingPayment' => $isPendingPayment,
    ]) ?>

    <!-- Order Details Section -->
    <div class="py-6">
        <div class="container">
            <div class="row">
                <!-- Main Column -->
                <div class="col-lg-8 mb-4">
                    <!-- Order Header -->
                    <?= $this->render('includes/order-header', ['order' => $order]) ?>

                    <!-- Order Items -->
                    <?= $this->render('includes/order-items', ['order' => $order]) ?>

                    <!-- Order Totals -->
                    <?= $this->render('includes/order-totals', ['order' => $order]) ?>

                    <!-- Next Steps -->
                    <?= $this->render('includes/next-steps', [
                        'order' => $order,
                        'showBankTransferDetails' => $isBankTransfer,
                    ]) ?>
                </div>

                <!-- Sidebar Column -->
                <div class="col-lg-4">
                    <!-- Customer Information -->
                    <?= $this->render('includes/customer-info', ['order' => $order]) ?>

                    <!-- Shipping Address -->
                    <?= $this->render('includes/shipping-address', ['order' => $order]) ?>

                    <!-- Billing Address (if different from shipping) -->
                    <?= $this->render('includes/billing-address', ['order' => $order]) ?>

                    <!-- Payment Information -->
                    <?= $this->render('includes/payment-info', [
                        'order' => $order,
                        'showTransferDetailsInSidebar' => !$isBankTransfer,
                    ]) ?>
                </div>
            </div>

            <?= $this->render('includes/confirmation/action-buttons', ['order' => $order]) ?>
        </div>
    </div>
<?php endif; ?>
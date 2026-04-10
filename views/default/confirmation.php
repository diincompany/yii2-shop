<?php
/**
 * Order Confirmation Page
 * @var yii\web\View $this
 * @var array|null $order
 */

use yii\helpers\Json;

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

        $orderMetadata = is_array($order['metadata'] ?? null) ? $order['metadata'] : [];
        $orderPaymentMetadata = is_array($orderMetadata['payment'] ?? null) ? $order['metadata']['payment'] : [];
        $gatewayCode = strtolower(trim((string) ($payment['gateway_code'] ?? $rawResponse['gateway'] ?? $orderPaymentMetadata['gateway_code'] ?? $orderPaymentMetadata['gateway'] ?? '')));
        $hasBankAccounts = is_array($rawResponse['bank_accounts'] ?? null) && !empty($rawResponse['bank_accounts']);
        $isBankTransfer = (
            $gatewayCode !== ''
            && (strpos($gatewayCode, 'bank') !== false || strpos($gatewayCode, 'transfer') !== false)
        ) || $hasBankAccounts;

        $gaTrackerClass = 'diincompany\\yii2googleanalytics\\EcommerceTracker';
        if (class_exists($gaTrackerClass)) {
            $gaOrderItems = [];
            foreach ((array) ($order['items'] ?? []) as $item) {
                $gaOrderItems[] = $gaTrackerClass::buildItem(
                    (string) ($item['sku'] ?? $item['product_id'] ?? ''),
                    (string) ($item['product_name'] ?? ''),
                    (float) ($item['price_amount'] ?? $item['price'] ?? 0),
                    max(1, (int) ($item['quantity'] ?? 1)),
                    (string) ($item['category_name'] ?? ''),
                    'StreetID',
                    (string) ($item['variant_name'] ?? $item['product_variant_name'] ?? '')
                );
            }

            $transactionId = (string) ($order['hash'] ?? $order['id'] ?? '');
            $paymentType = $gatewayCode !== '' ? $gatewayCode : 'checkout';

            if ($transactionId !== '' && !empty($gaOrderItems)) {
                $addPaymentInfoJs = $gaTrackerClass::addPaymentInfoJs(
                    $gaOrderItems,
                    (float) ($order['total_amount'] ?? 0),
                    $paymentType,
                    (string) ($order['coupon_code'] ?? ''),
                    'HNL'
                );

                $purchaseJs = $gaTrackerClass::purchaseJs(
                    $transactionId,
                    $gaOrderItems,
                    (float) ($order['total_amount'] ?? 0),
                    (float) ($order['tax_amount'] ?? 0),
                    (float) ($order['shipping_amount'] ?? 0),
                    'HNL',
                    (string) ($order['coupon_code'] ?? ''),
                    'StreetID Online'
                );

                $purchaseGuardKeyJson = Json::htmlEncode('ga4_purchase_' . $transactionId);

                $this->registerJs(
                    <<<JS
if (typeof window.gtag === 'function') {
    var purchaseGuardKey = $purchaseGuardKeyJson;
    if (!window.sessionStorage || !window.sessionStorage.getItem(purchaseGuardKey)) {
        $addPaymentInfoJs
        $purchaseJs
        if (window.sessionStorage) {
            window.sessionStorage.setItem(purchaseGuardKey, '1');
        }
    }
}
JS,
                    \yii\web\View::POS_END,
                    'shop-ga4-purchase-' . $transactionId
                );
            }
        }
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

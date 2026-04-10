<?php
/**
 * Order confirmation PDF template.
 *
 * @var yii\web\View $this
 * @var array $order
 */

use yii\helpers\Html;

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

$customer = is_array($order['customer'] ?? null) ? $order['customer'] : [];
$shippingAddress = is_array($order['shipping_address'] ?? null) ? $order['shipping_address'] : [];
$billingAddress = is_array($order['billing_address'] ?? null) ? $order['billing_address'] : [];
$merchant = is_array($order['merchant'] ?? null) ? $order['merchant'] : [];
$store = is_array($order['store'] ?? null) ? $order['store'] : [];
$orderMetadata = is_array($order['metadata'] ?? null) ? $order['metadata'] : [];
$metadataMerchant = is_array($orderMetadata['merchant'] ?? null) ? $orderMetadata['merchant'] : [];
$metadataStore = is_array($orderMetadata['store'] ?? null) ? $orderMetadata['store'] : [];
$instructions = is_array($rawResponse['instructions'] ?? null) ? $rawResponse['instructions'] : [];
$instructionSteps = is_array($instructions['steps'] ?? null) ? $instructions['steps'] : [];
$bankAccounts = is_array($rawResponse['bank_accounts'] ?? null) ? $rawResponse['bank_accounts'] : [];
$support = is_array($instructions['support'] ?? null) ? $instructions['support'] : [];

$defaultCompany = is_array(Yii::$app->params['company'] ?? null) ? Yii::$app->params['company'] : [];
$defaultSupport = is_array(Yii::$app->params['support'] ?? null) ? Yii::$app->params['support'] : [];

$firstNonEmpty = static function (...$values): string {
    foreach ($values as $value) {
        $text = trim((string) ($value ?? ''));
        if ($text !== '') {
            return $text;
        }
    }

    return '';
};

$customerName = $firstNonEmpty(
    $customer['name'] ?? null,
    $customer['full_name'] ?? null,
    trim((string) (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))),
    $shippingAddress['full_name'] ?? null,
    trim((string) (($shippingAddress['first_name'] ?? '') . ' ' . ($shippingAddress['last_name'] ?? ''))),
    $payment['payer_name'] ?? null
);

$customerEmail = $firstNonEmpty(
    $customer['email'] ?? null,
    $shippingAddress['email'] ?? null,
    $billingAddress['email'] ?? null,
    $payment['payer_email'] ?? null
);

$customerPhone = $firstNonEmpty(
    $customer['phone_number'] ?? null,
    $shippingAddress['phone_number'] ?? null,
    $billingAddress['phone_number'] ?? null,
    $payment['payer_phone'] ?? null
);

$paymentStatusRaw = strtolower(trim((string) ($payment['status'] ?? '')));
$orderStatus = strtolower(trim((string) ($order['status'] ?? '')));
$isPaid = in_array($paymentStatusRaw, ['1', 'paid', 'success'], true)
    || !empty($payment['paid_at'])
    || in_array($orderStatus, ['paid', 'completed'], true);

$statusMap = [
    'paid' => Yii::t('shop', 'Paid'),
    'pending' => Yii::t('shop', 'Pending'),
    'processing' => Yii::t('shop', 'Processing'),
    'shipped' => Yii::t('shop', 'Shipped'),
    'delivered' => Yii::t('shop', 'Delivered'),
];

$formatAddress = static function (array $address) use ($firstNonEmpty): string {
    $fullName = $firstNonEmpty(
        $address['full_name'] ?? null,
        trim((string) (($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? '')))
    );
    $line1 = trim((string) ($address['address_1'] ?? ''));
    $line2 = trim((string) ($address['address_2'] ?? ''));
    $city = trim((string) ($address['location']['city']['name'] ?? ''));
    $state = trim((string) ($address['location']['state']['name'] ?? ''));
    $country = trim((string) ($address['location']['country']['name'] ?? ''));
    $zipCode = trim((string) ($address['zipcode'] ?? ''));

    $parts = array_filter([$fullName, $line1, $line2, trim(implode(', ', array_filter([$city, $state, $country]))), $zipCode]);

    return implode("\n", $parts);
};

$shippingAddressText = $formatAddress($shippingAddress);
$billingAddressText = $formatAddress($billingAddress);

$orderMetadata = is_array($order['metadata'] ?? null) ? $order['metadata'] : [];
$orderPaymentMetadata = is_array($orderMetadata['payment'] ?? null) ? $orderMetadata['payment'] : [];
$gatewayCode = strtolower(trim((string) ($payment['gateway_code'] ?? $rawResponse['gateway'] ?? $orderPaymentMetadata['gateway_code'] ?? $orderPaymentMetadata['gateway'] ?? '')));
$paymentMethodText = Yii::t('shop', 'Credit Card');
if ($gatewayCode !== '') {
    if (in_array($gatewayCode, ['cash_on_delivery', 'cashondelivery'], true)) {
        $paymentMethodText = Yii::t('shop', 'Cash On Delivery');
    } elseif (strpos($gatewayCode, 'bank') !== false || strpos($gatewayCode, 'transfer') !== false) {
        $paymentMethodText = Yii::t('shop', 'Bank Transfer');
    } elseif (strpos($gatewayCode, 'paypal') !== false) {
        $paymentMethodText = 'PayPal';
    }
}

$discountAmount = (float) ($order['discount_amount'] ?? 0);
$shippingAmount = (float) ($order['shipping_amount'] ?? ($order['shipping']['shipping_cost'] ?? 0));
$shippingSummary = is_array($order['shipping'] ?? null) ? $order['shipping'] : [];
$selectedShippingOption = is_array($shippingSummary['selected_option'] ?? null) ? $shippingSummary['selected_option'] : [];
$shippingProvider = $firstNonEmpty($shippingSummary['provider_name'] ?? null, $shippingSummary['provider_code'] ?? null);
$shippingCourier = $firstNonEmpty($shippingSummary['courier_name'] ?? null, $selectedShippingOption['courier_name'] ?? null, $selectedShippingOption['name'] ?? null);
$currentLanguage = strtolower((string) (Yii::$app->language ?? ''));
$isSpanishLanguage = strpos($currentLanguage, 'es') === 0;
$shippingDeliveryType = $firstNonEmpty(
    $isSpanishLanguage ? ($shippingSummary['courier_delivery_type_label_es'] ?? null) : ($shippingSummary['courier_delivery_type_label_en'] ?? null),
    $isSpanishLanguage ? ($selectedShippingOption['delivery_type_label_es'] ?? null) : ($selectedShippingOption['delivery_type_label_en'] ?? null),
    $shippingSummary['courier_delivery_type'] ?? null,
    $selectedShippingOption['delivery_type'] ?? null
);
$shippingWarehouse = $firstNonEmpty($shippingSummary['warehouse_name'] ?? null, $shippingSummary['warehouse']['name'] ?? null);

$merchantLogoUrl = $firstNonEmpty(
    $merchant['logo_url'] ?? null,
    $merchant['logo'] ?? null,
    $merchant['image'] ?? null,
    $merchant['image_url'] ?? null,
    $merchant['brand_logo_url'] ?? null,
    $store['logo_url'] ?? null,
    $store['logo'] ?? null,
    $store['image'] ?? null,
    $metadataMerchant['logo_url'] ?? null,
    $metadataMerchant['logo'] ?? null,
    $metadataStore['logo_url'] ?? null,
    $metadataStore['logo'] ?? null,
    $orderMetadata['merchant_logo_url'] ?? null,
    $orderMetadata['logo_url'] ?? null,
    'https://ik.imagekit.io/ready/streetid/img/logo-streetid.svg'
);

$storeName = $firstNonEmpty(
    $merchant['name'] ?? null,
    $merchant['trade_name'] ?? null,
    $merchant['business_name'] ?? null,
    $merchant['legal_name'] ?? null,
    $store['name'] ?? null,
    $store['trade_name'] ?? null,
    $store['business_name'] ?? null,
    $metadataMerchant['name'] ?? null,
    $metadataMerchant['trade_name'] ?? null,
    $metadataMerchant['business_name'] ?? null,
    $metadataStore['name'] ?? null,
    $orderMetadata['merchant_name'] ?? null,
    $defaultCompany['legalName'] ?? null,
    Yii::$app->name
);

$storeEmail = $firstNonEmpty(
    $merchant['email'] ?? null,
    $merchant['support_email'] ?? null,
    $store['email'] ?? null,
    $store['support_email'] ?? null,
    $metadataMerchant['email'] ?? null,
    $metadataStore['email'] ?? null,
    $support['email'] ?? null,
    $defaultCompany['email'] ?? null,
    $defaultSupport['email'] ?? null,
    Yii::$app->params['supportEmail'] ?? null
);

$storePhone = $firstNonEmpty(
    $merchant['phone'] ?? null,
    $merchant['phone_number'] ?? null,
    $merchant['support_phone'] ?? null,
    $store['phone'] ?? null,
    $store['phone_number'] ?? null,
    $metadataMerchant['phone'] ?? null,
    $metadataMerchant['phone_number'] ?? null,
    $metadataStore['phone'] ?? null,
    $metadataStore['phone_number'] ?? null,
    $support['phone'] ?? null,
    $defaultCompany['phone'] ?? null,
    $defaultSupport['phone'] ?? null
);

$storeTaxId = $firstNonEmpty(
    $merchant['tax_id'] ?? null,
    $merchant['taxId'] ?? null,
    $merchant['rtn'] ?? null,
    $store['tax_id'] ?? null,
    $store['taxId'] ?? null,
    $metadataMerchant['tax_id'] ?? null,
    $metadataMerchant['taxId'] ?? null,
    $metadataMerchant['rtn'] ?? null,
    $metadataStore['tax_id'] ?? null,
    $metadataStore['taxId'] ?? null,
    $order['tax_id'] ?? null,
    $defaultCompany['taxId'] ?? null
);

$storeAddress = $firstNonEmpty(
    $merchant['address'] ?? null,
    $merchant['address_1'] ?? null,
    $store['address'] ?? null,
    $store['address_1'] ?? null,
    $metadataMerchant['address'] ?? null,
    $metadataStore['address'] ?? null,
    trim(implode(', ', array_filter([
        (string) ($defaultCompany['address'] ?? ''),
        (string) ($defaultCompany['city'] ?? ''),
        (string) ($defaultCompany['country'] ?? ''),
    ])))
);
?>

<style>
    body {
        font-family: sans-serif;
        color: #1f2937;
        font-size: 12px;
    }
    .header {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 14px;
        padding-bottom: 10px;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
    }
    .header-left {
        width: 62%;
        vertical-align: top;
    }
    .header-right {
        width: 38%;
        vertical-align: top;
        text-align: right;
        font-size: 11px;
        color: #374151;
    }
    .merchant-logo {
        max-height: 46px;
        max-width: 240px;
        margin-bottom: 6px;
    }
    .store-name {
        font-size: 15px;
        font-weight: bold;
        color: #111827;
        margin-bottom: 4px;
    }
    .store-line {
        margin-bottom: 2px;
    }
    .title {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
    }
    .subtitle {
        margin-top: 4px;
        color: #6b7280;
    }
    .section {
        margin-bottom: 14px;
    }
    .section-title {
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 6px;
        text-transform: uppercase;
        color: #111827;
    }
    .meta-table,
    .items-table,
    .totals-table,
    .accounts-table {
        width: 100%;
        border-collapse: collapse;
    }
    .meta-table td {
        padding: 4px 0;
        vertical-align: top;
    }
    .items-table th,
    .items-table td,
    .accounts-table th,
    .accounts-table td {
        border: 1px solid #e5e7eb;
        padding: 6px;
    }
    .items-table th,
    .accounts-table th {
        background: #f9fafb;
        text-align: left;
    }
    .text-right {
        text-align: right;
    }
    .mono {
        white-space: pre-line;
    }
</style>

<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <?php if ($merchantLogoUrl !== ''): ?>
                    <img class="merchant-logo" src="<?= Html::encode($merchantLogoUrl) ?>" alt="<?= Html::encode($storeName) ?>">
                <?php endif; ?>

                <h1 class="title"><?= Html::encode(Yii::t('shop', 'Order Confirmation')) ?></h1>
                <div class="subtitle">
                    <?= Html::encode(Yii::t('shop', 'Order Number')) ?>: <?= Html::encode((string) ($order['order_number'] ?? $order['id'] ?? Yii::t('shop', 'Not available'))) ?> |
                    <?= Html::encode(Yii::t('shop', 'Order Date')) ?>: <?= Html::encode(Yii::$app->formatter->asDate($order['order_date'] ?? $order['created_at'] ?? time(), 'medium')) ?>
                </div>
            </td>
            <td class="header-right">
                <div class="store-name"><?= Html::encode($storeName) ?></div>
                <?php if ($storeAddress !== ''): ?>
                    <div class="store-line"><?= Html::encode($storeAddress) ?></div>
                <?php endif; ?>
                <?php if ($storeEmail !== ''): ?>
                    <div class="store-line"><?= Html::encode($storeEmail) ?></div>
                <?php endif; ?>
                <?php if ($storePhone !== ''): ?>
                    <div class="store-line"><?= Html::encode($storePhone) ?></div>
                <?php endif; ?>
                <?php if ($storeTaxId !== ''): ?>
                    <div class="store-line"><?= Html::encode(Yii::t('shop', 'Tax ID')) ?>: <?= Html::encode($storeTaxId) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Status')) ?></div>
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <?= Html::encode($statusMap[$orderStatus] ?? ucfirst($orderStatus !== '' ? $orderStatus : Yii::t('shop', 'Unknown'))) ?>
            </td>
            <td class="text-right">
                <strong><?= Html::encode(Yii::t('shop', 'Order Total')) ?>: L <?= Html::encode(number_format((float) ($order['total_amount'] ?? 0), 2)) ?></strong>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Customer Information')) ?></div>
    <table class="meta-table">
        <tr>
            <td style="width: 33%;"><strong><?= Html::encode(Yii::t('shop', 'first_name')) ?> / <?= Html::encode(Yii::t('shop', 'last_name')) ?>:</strong><br><?= Html::encode($customerName !== '' ? $customerName : Yii::t('shop', 'Not available')) ?></td>
            <td style="width: 33%;"><strong><?= Html::encode(Yii::t('shop', 'email_address')) ?>:</strong><br><?= Html::encode($customerEmail !== '' ? $customerEmail : Yii::t('shop', 'Not available')) ?></td>
            <td style="width: 34%;"><strong><?= Html::encode(Yii::t('shop', 'phone_number')) ?>:</strong><br><?= Html::encode($customerPhone !== '' ? $customerPhone : Yii::t('shop', 'Not available')) ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Shipping Address')) ?></div>
    <div class="mono"><?= Html::encode($shippingAddressText !== '' ? $shippingAddressText : Yii::t('shop', 'Not available')) ?></div>
</div>

<?php if (!empty($shippingSummary)): ?>
    <div class="section">
        <div class="section-title"><?= Html::encode(Yii::t('shop', 'Selected Shipping')) ?></div>
        <table class="meta-table">
            <tr>
                <td style="width: 25%;"><strong><?= Html::encode(Yii::t('shop', 'Provider')) ?>:</strong><br><?= Html::encode($shippingProvider !== '' ? $shippingProvider : Yii::t('shop', 'Not available')) ?></td>
                <td style="width: 25%;"><strong><?= Html::encode(Yii::t('shop', 'Courier')) ?>:</strong><br><?= Html::encode($shippingCourier !== '' ? $shippingCourier : Yii::t('shop', 'Not available')) ?></td>
                <td style="width: 25%;"><strong><?= Html::encode(Yii::t('shop', 'Delivery Type')) ?>:</strong><br><?= Html::encode($shippingDeliveryType !== '' ? $shippingDeliveryType : Yii::t('shop', 'Not available')) ?></td>
                <td style="width: 25%;"><strong><?= Html::encode(Yii::t('shop', 'Warehouse')) ?>:</strong><br><?= Html::encode($shippingWarehouse !== '' ? $shippingWarehouse : Yii::t('shop', 'Not available')) ?></td>
            </tr>
        </table>
    </div>
<?php endif; ?>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Billing Address')) ?></div>
    <div class="mono"><?= Html::encode($billingAddressText !== '' ? $billingAddressText : Yii::t('shop', 'Not available')) ?></div>
</div>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Payment Information')) ?></div>
    <table class="meta-table">
        <tr>
            <td style="width: 33%;"><strong><?= Html::encode(Yii::t('shop', 'Payment Method')) ?>:</strong><br><?= Html::encode($paymentMethodText) ?></td>
            <td style="width: 33%;"><strong><?= Html::encode(Yii::t('shop', 'Payment Status')) ?>:</strong><br><?= Html::encode($isPaid ? Yii::t('shop', 'Paid') : Yii::t('shop', 'Pending Payment')) ?></td>
            <td style="width: 34%;"><strong><?= Html::encode(Yii::t('shop', 'Transaction ID')) ?>:</strong><br><?= Html::encode((string) ($payment['code'] ?? $payment['gateway_reference'] ?? $payment['reference'] ?? Yii::t('shop', 'Not available'))) ?></td>
        </tr>
    </table>
</div>

<?php if (!empty($instructionSteps)): ?>
    <div class="section">
        <div class="section-title"><?= Html::encode(Yii::t('shop', 'Payment Instructions')) ?></div>
        <?php if (!empty($instructions['title'])): ?>
            <div><strong><?= Html::encode((string) $instructions['title']) ?></strong></div>
        <?php endif; ?>
        <?php if (!empty($instructions['description'])): ?>
            <div><?= Html::encode((string) $instructions['description']) ?></div>
        <?php endif; ?>
        <ol>
            <?php foreach ($instructionSteps as $step): ?>
                <li><?= Html::encode((string) $step) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
<?php endif; ?>

<?php if (!empty($bankAccounts)): ?>
    <div class="section">
        <div class="section-title"><?= Html::encode(Yii::t('shop', 'Bank Accounts')) ?></div>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th><?= Html::encode(Yii::t('shop', 'Bank')) ?></th>
                    <th><?= Html::encode(Yii::t('shop', 'Account Holder')) ?></th>
                    <th><?= Html::encode(Yii::t('shop', 'Account Number')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bankAccounts as $account): ?>
                    <tr>
                        <td><?= Html::encode((string) ($account['bank_name'] ?? Yii::t('shop', 'Not available'))) ?></td>
                        <td><?= Html::encode((string) ($account['holder_name'] ?? Yii::t('shop', 'Not available'))) ?></td>
                        <td><?= Html::encode((string) ($account['account_number'] ?? Yii::t('shop', 'Not available'))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'Order Items')) ?></div>
    <table class="items-table">
        <thead>
            <tr>
                <th><?= Html::encode(Yii::t('shop', 'product_name')) ?></th>
                <th><?= Html::encode(Yii::t('shop', 'quantity')) ?></th>
                <th class="text-right"><?= Html::encode(Yii::t('shop', 'unit_price')) ?></th>
                <th class="text-right"><?= Html::encode(Yii::t('shop', 'Total')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($order['items']) && is_array($order['items'])): ?>
                <?php foreach ($order['items'] as $item): ?>
                    <?php
                        $variantSnapshot = is_array($item['variant_snapshot'] ?? null) ? $item['variant_snapshot'] : [];
                        $optionValues = is_array($variantSnapshot['option_values'] ?? null) ? $variantSnapshot['option_values'] : [];
                        $isVariant = !empty($item['product_variant_id']) || !empty($variantSnapshot['variant_id']);
                    ?>
                    <tr>
                        <td>
                            <?= Html::encode((string) ($item['product_name'] ?? '')) ?>
                            <?php if ($isVariant): ?>
                                <?php if (!empty($optionValues)): ?>
                                    <?php foreach ($optionValues as $option): ?>
                                        <?php
                                            $optionName = trim((string) ($option['option_name'] ?? ''));
                                            $optionValue = trim((string) ($option['value'] ?? ''));
                                            $optionLabel = '';

                                            if ($optionName !== '' && $optionValue !== '') {
                                                $optionLabel = $optionName . ': ' . $optionValue;
                                            } elseif ($optionName !== '') {
                                                $optionLabel = $optionName;
                                            } elseif ($optionValue !== '') {
                                                $optionLabel = $optionValue;
                                            }
                                        ?>
                                        <?php if ($optionLabel !== ''): ?>
                                            <br>
                                            <small><?= Html::encode($optionLabel) ?></small>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php $variantName = trim((string) ($variantSnapshot['name'] ?? $item['variant_sku'] ?? '')); ?>
                                    <?php if ($variantName !== ''): ?>
                                        <br>
                                        <small><?= Html::encode(Yii::t('shop', 'Variant') . ': ' . $variantName) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <br>
                                <small><?= Html::encode(Yii::t('shop', 'Simple product')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= Html::encode((string) ((int) ($item['quantity'] ?? 0))) ?></td>
                        <td class="text-right">L <?= Html::encode(number_format((float) ((isset($item['sale_price']) && (float) $item['sale_price'] > 0) ? $item['sale_price'] : ($item['price_amount'] ?? 0)), 2)) ?></td>
                        <td class="text-right">L <?= Html::encode(number_format((float) ($item['total_amount'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><?= Html::encode(Yii::t('shop', 'No products in order')) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title"><?= Html::encode(Yii::t('shop', 'order_summary')) ?></div>
    <table class="totals-table">
        <tr>
            <td><?= Html::encode(Yii::t('shop', 'subtotal')) ?></td>
            <td class="text-right">L <?= Html::encode(number_format((float) ($order['subtotal_amount'] ?? 0), 2)) ?></td>
        </tr>
        <tr>
            <td><?= Html::encode(Yii::t('shop', 'taxes')) ?></td>
            <td class="text-right">L <?= Html::encode(number_format((float) ($order['tax_amount'] ?? 0), 2)) ?></td>
        </tr>
        <?php if ($discountAmount > 0): ?>
            <tr>
                <td><?= Html::encode(Yii::t('shop', 'Discount')) ?></td>
                <td class="text-right">-L <?= Html::encode(number_format($discountAmount, 2)) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td><?= Html::encode(Yii::t('shop', 'shipping')) ?></td>
            <td class="text-right">L <?= Html::encode(number_format($shippingAmount, 2)) ?></td>
        </tr>
        <tr>
            <td><strong><?= Html::encode(Yii::t('shop', 'grand_total')) ?></strong></td>
            <td class="text-right"><strong>L <?= Html::encode(number_format((float) ($order['total_amount'] ?? 0), 2)) ?></strong></td>
        </tr>
    </table>
</div>

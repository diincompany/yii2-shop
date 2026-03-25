<?php
/**
 * Customer Information Section
 * @var array $order
 */

use yii\helpers\Html;
?>

<?php
$customer = is_array($order['customer'] ?? null) ? $order['customer'] : [];
$shippingAddress = is_array($order['shipping_address'] ?? null) ? $order['shipping_address'] : [];
$billingAddress = is_array($order['billing_address'] ?? null) ? $order['billing_address'] : [];
$payments = is_array($order['payments'] ?? null) ? $order['payments'] : [];
$payment = !empty($payments) ? reset($payments) : [];

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
    $billingAddress['full_name'] ?? null,
    trim((string) (($billingAddress['first_name'] ?? '') . ' ' . ($billingAddress['last_name'] ?? ''))),
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

$hasCustomerInfo = $customerName !== '' || $customerEmail !== '' || $customerPhone !== '';
?>

<?php if ($hasCustomerInfo): ?>
    <!-- Customer Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 text-uppercase fw-bold"><?= Yii::t('shop', 'Customer Information') ?></h6>
        </div>
        <div class="card-body">
            <p class="mb-1">
                <strong><?= Html::encode($customerName !== '' ? $customerName : 'N/A') ?></strong>
            </p>
            <p class="text-muted small mb-1">
                <i class="bi bi-envelope me-2"></i><?= Html::encode($customerEmail !== '' ? $customerEmail : 'N/A') ?>
            </p>
            <p class="text-muted small">
                <i class="bi bi-telephone me-2"></i><?= Html::encode($customerPhone !== '' ? $customerPhone : 'N/A') ?>
            </p>
        </div>
    </div>
<?php endif; ?>

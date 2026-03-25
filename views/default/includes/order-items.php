<?php
/**
 * Order Items Section
 * @var array $order
 */

use yii\helpers\Html;
?>

<!-- Order Items -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 text-uppercase fw-bold"><?= Yii::t('shop', 'Order Items') ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= Yii::t('shop', 'product_name') ?></th>
                        <th class="text-center"><?= Yii::t('shop', 'quantity') ?></th>
                        <th class="text-end"><?= Yii::t('shop', 'unit_price') ?></th>
                        <th class="text-end"><?= Yii::t('shop', 'Total') ?></th>
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
                                    <span class="fw-500"><?= Html::encode($item['product_name'] ?? 'Product') ?></span>

                                    <?php if ($isVariant): ?>
                                        <?php if (!empty($optionValues)): ?>
                                            <?php foreach ($optionValues as $option): ?>
                                                <?php
                                                    $optionName = trim((string) ($option['option_name'] ?? ''));
                                                    $optionValue = trim((string) ($option['value'] ?? ''));
                                                ?>
                                                <?php if ($optionName !== '' || $optionValue !== ''): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= Html::encode($optionName . ($optionValue !== '' ? ': ' . $optionValue : '')) ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php $variantName = trim((string) ($variantSnapshot['name'] ?? $item['variant_sku'] ?? '')); ?>
                                            <?php if ($variantName !== ''): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= Html::encode(Yii::t('shop', 'Variant') . ': ' . $variantName) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <br>
                                        <small class="text-muted"><?= Yii::t('shop', 'Simple product') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= (int)($item['quantity'] ?? 0) ?></td>
                                    <td class="text-end">L <?= number_format((float)((!empty($item['sale_price']) && (float)$item['sale_price'] > 0) ? $item['sale_price'] : $item['price_amount'] ?? 0), 2) ?></td>
                                <td class="text-end fw-bold">L <?= number_format((float)($item['total_amount'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <?= Yii::t('shop', 'No products in order') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

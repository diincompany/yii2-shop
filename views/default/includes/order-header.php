<?php
/**
 * Order Header Section
 * @var array $order
 */

use yii\helpers\Html;
?>

<!-- Order Header -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small mb-2"><?= Yii::t('shop', 'Order Number') ?></h6>
                <h5 class="mb-3"><?= Html::encode($order['order_number'] ?? $order['id'] ?? 'N/A') ?></h5>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted text-uppercase small mb-2"><?= Yii::t('shop', 'Order Date') ?></h6>
                <h5 class="mb-3">
                    <?php 
                        $date = $order['order_date'] ?? $order['created_at'] ?? time();
                        echo Yii::$app->formatter->asDate($date, 'medium');
                    ?>
                </h5>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small mb-2"><?= Yii::t('shop', 'Status') ?></h6>
                <?php $orderStatus = strtolower(trim((string) ($order['status'] ?? ''))); ?>
                <span class="badge <?= $orderStatus === 'paid' ? 'bg-success' : (in_array($orderStatus, ['cancelled', 'canceled'], true) ? 'bg-danger' : 'bg-warning') ?>">
                    <?php 
                        $statusMap = [
                            'paid' => 'Pagado',
                            'pending' => Yii::t('shop', 'Pending'),
                            'cancelled' => Yii::t('shop', 'Cancelled'),
                            'canceled' => Yii::t('shop', 'Cancelled'),
                            'processing' => Yii::t('shop', 'Processing'),
                            'ready_to_pickup' => Yii::t('shop', 'Ready to Pickup'),
                            'shipped' => Yii::t('shop', 'Shipped'),
                            'delivered' => Yii::t('shop', 'Delivered'),
                        ];
                        echo Html::encode($statusMap[$orderStatus] ?? ucfirst($order['status'] ?? 'Unknown'));
                    ?>
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted text-uppercase small mb-2"><?= Yii::t('shop', 'Order Total') ?></h6>
                <h5 class="text-success">
                    <?= Html::encode('L ' . number_format($order['total_amount'] ?? 0, 2)) ?>
                </h5>
            </div>
        </div>
    </div>
</div>

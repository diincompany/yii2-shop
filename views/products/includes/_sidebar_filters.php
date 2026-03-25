<?php
use yii\helpers\Html;

/* @var $filters array */

$currentMinPrice = Yii::$app->request->get('min_price');
$currentMaxPrice = Yii::$app->request->get('max_price');
?>

<!-- Price Filter -->
<div class="shop-sidebar-block">
    <div class="shop-sidebar-title">
        <a class="h5" data-bs-toggle="collapse" href="#shop_price" role="button" aria-expanded="true" aria-controls="shop_price">
            <?= Yii::t('shop', 'Price') ?> <i class="bi bi-chevron-up"></i>
        </a>
    </div>
    <div class="shop-sidebar-list collapse show" id="shop_price">
        <ul>
            <?php foreach ($filters['price_ranges'] as $index => $range): ?>
                <?php
                    $isCurrentMin = $currentMinPrice !== null && $currentMinPrice !== ''
                        && is_numeric($currentMinPrice)
                        && (float)$currentMinPrice === (float)$range['min'];
                    $hasRangeMax = array_key_exists('max', $range) && $range['max'] !== null && $range['max'] !== '';
                    $isCurrentMax = $hasRangeMax
                        ? ($currentMaxPrice !== null && $currentMaxPrice !== '' && is_numeric($currentMaxPrice) && (float)$currentMaxPrice === (float)$range['max'])
                        : ($currentMaxPrice === null || $currentMaxPrice === '');
                    $isChecked = $isCurrentMin && $isCurrentMax;
                ?>
                <li class="custom-checkbox">
                    <input class="custom-control-input" id="price<?= $index ?>" type="checkbox" 
                           data-min="<?= $range['min'] ?>" 
                           data-max="<?= $range['max'] ?? '' ?>"
                           <?= $isChecked ? 'checked' : '' ?>
                           onchange="applyPriceFilter(this)">
                    <label class="custom-control-label" for="price<?= $index ?>">
                        <?= $range['label'] ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="d-flex align-items-center pt-3">
            <input type="number" id="min_price" class="form-control form-control-sm" placeholder="$10.00" min="0" value="<?= Html::encode((string)$currentMinPrice) ?>">
            <div class="text-gray-350 mx-2">‒</div>
            <input type="number" id="max_price" class="form-control form-control-sm" placeholder="$350.00" value="<?= Html::encode((string)$currentMaxPrice) ?>">
        </div>
        <button class="btn btn-primary btn-sm mt-2 w-100" onclick="applyCustomPriceFilter()">
            <?= Yii::t('shop', 'Apply') ?>
        </button>
    </div>
</div>

<!-- Brands Filter (if available) -->
<?php if (!empty($filters['brands'])): ?>
<div class="shop-sidebar-block">
    <div class="shop-sidebar-title">
        <a class="h5" data-bs-toggle="collapse" href="#shop_brand" role="button" aria-expanded="true" aria-controls="shop_brand">
            <?= Yii::t('shop', 'Brands') ?> <i class="bi bi-chevron-up"></i>
        </a>
    </div>
    <div class="shop-sidebar-list collapse show" id="shop_brand">
        <ul>
            <?php foreach ($filters['brands'] as $index => $brand): ?>
                <li class="custom-checkbox">
                    <input class="custom-control-input" id="brand<?= $index ?>" type="checkbox" 
                           value="<?= Html::encode($brand) ?>"
                           onchange="applyFilter('brand', this.value, this.checked)">
                    <label class="custom-control-label" for="brand<?= $index ?>">
                        <?= Html::encode($brand) ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

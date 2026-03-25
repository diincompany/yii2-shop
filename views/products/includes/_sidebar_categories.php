<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $category array|null */
/* @var $categories array */

$moduleRoute = '/' . trim((string) (Yii::$app->controller->module->id ?? 'shop'), '/');
?>

<!-- Categories Filter -->
<?php if (!empty($categories)): ?>
<div class="shop-sidebar-block">
    <div class="shop-sidebar-title">
        <a class="h5" data-bs-toggle="collapse" href="#shop_categories" role="button" aria-expanded="true" aria-controls="shop_categories">
            <?= Yii::t('shop', 'Categories') ?> <i class="bi bi-chevron-up"></i>
        </a>
    </div>
    <div class="shop-category-list collapse show" id="shop_categories">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= Url::to([$moduleRoute]) ?>" class="nav-link <?= !$category ? 'active' : '' ?>">
                    <?= Yii::t('shop', 'All Products') ?>
                </a>
            </li>
            <?php $catIndex = 0; ?>
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat['active'] == 1): ?>
                    <li class="nav-item">
                                <a href="<?= Yii::$app->urlManager->createUrl([$moduleRoute . '/category', 'slug' => $cat['slug'] ?? $cat['id']]) ?>" 
                           class="nav-link <?= $category && (string)$category['id'] === (string)$cat['id'] ? 'active' : '' ?>">
                            <?= Html::encode($cat['name']) ?>
                            <?php if (!empty($cat['products_count'])): ?>
                                <span class="text-muted">(<?= $cat['products_count'] ?>)</span>
                            <?php endif; ?>
                        </a>
                        <?php 
                            $subcategories = $cat['subcategories'] ?? [];
                            if (!empty($subcategories)): 
                        ?>
                        <a data-bs-toggle="collapse" href="#shop_cat_<?= $catIndex ?>" role="button" aria-expanded="false" aria-controls="shop_cat_<?= $catIndex ?>" class="s-icon"></a>
                        <div class="collapse" id="shop_cat_<?= $catIndex ?>">
                            <ul class="nav nav-pills flex-column nav-hierarchy">
                                <?php foreach ($subcategories as $subcat): ?>
                                    <li class="nav-item">
                                        <a href="<?= Yii::$app->urlManager->createUrl([$moduleRoute . '/category', 'slug' => $subcat['slug'] ?? $subcat['id']]) ?>" class="nav-link">
                                            <?= Html::encode($subcat['name']) ?>
                                            <?php if (!empty($subcat['products_count'])): ?>
                                                <span class="text-muted">(<?= $subcat['products_count'] ?>)</span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </li>
                    <?php $catIndex++; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="nav-item">
                <a href="<?= Yii::$app->urlManager->createUrl([$moduleRoute . '/products/uncategorized']) ?>" class="nav-link">
                    <?= Yii::t('shop', 'Uncategorized') ?>
                </a>
            </li>
        </ul>
    </div>
</div>
<?php endif; ?>

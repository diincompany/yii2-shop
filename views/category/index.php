<?php
use DiinCompany\Yii2Shop\widgets\SeoMeta;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $categories array */

$moduleRoute = '/' . trim((string) (Yii::$app->controller->module->id ?? 'shop'), '/');

$this->title = Yii::t('shop', 'Shop by Category') . ' - ' . Yii::$app->name;
$this->params['breadcrumbs'][] = Yii::t('shop', 'Shop');

echo SeoMeta::widget([
    'type'        => SeoMeta::TYPE_LISTING,
    'title'       => Yii::t('shop', 'Shop by Category'),
    'description' => Yii::t('shop', 'Browse our full collection organized by category.'),
    'breadcrumbs' => $this->params['breadcrumbs'] ?? [],
]);
?>

<!-- Page Title -->
<section class="section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="h2 mb-1"><?= Yii::t('shop', 'Shop by Category') ?></h1>
                <p class="text-muted"><?= Yii::t('shop', 'Browse our collection by category') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Grid -->
<section class="section">
    <div class="container">
        <div class="row g-4">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                        <h5><?= Yii::t('shop', 'No categories available') ?></h5>
                        <p><?= Yii::t('shop', 'Check back later for new categories.') ?></p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <?php if ($category['active'] == 1): ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm hover-shadow-lg transition">
                                <?php if (!empty($category['image'])): ?>
                                    <img src="<?= Html::encode($category['image']) ?>" class="card-img-top" alt="<?= Html::encode($category['name']) ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-tag text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?= Yii::$app->urlManager->createUrl([$moduleRoute . '/category', 'slug' => $category['slug']]) ?>" class="text-reset text-decoration-none">
                                            <?= Html::encode($category['name']) ?>
                                        </a>
                                    </h5>
                                    
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="card-text text-muted small">
                                            <?= Html::encode(mb_substr($category['description'], 0, 100)) ?>
                                            <?= mb_strlen($category['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($category['products_count'])): ?>
                                        <p class="text-muted small mb-0">
                                            <i class="bi bi-box"></i> <?= Yii::t('shop', '{n} products', ['n' => $category['products_count']]) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-transparent border-0 pt-0">
                                    <a href="<?= Yii::$app->urlManager->createUrl([$moduleRoute . '/category', 'slug' => $category['slug']]) ?>" class="btn btn-outline-primary btn-sm w-100">
                                        <?= Yii::t('shop', 'Browse {category}', ['category' => $category['name']]) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.hover-shadow-lg {
    transition: all 0.3s ease;
}
.hover-shadow-lg:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}
</style>
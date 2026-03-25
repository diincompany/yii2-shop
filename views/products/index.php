<?php
use diincompany\shop\widgets\product\ProductCard;
use diincompany\shop\widgets\SeoMeta;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $category array|null */
/* @var $categories array */
/* @var $products array */
/* @var $meta array */
/* @var $filters array */
/* @var $currentSort string */

$pageTitle = $category ? $category['name'] : Yii::t('shop', 'All Products');
$moduleRoute = '/' . trim((string) (Yii::$app->controller->module->id ?? 'shop'), '/');

$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => Yii::t('shop', 'Shop'), 'url' => [$moduleRoute]];

if ($category) {
    $this->params['breadcrumbs'][] = $category['name'];
}

echo SeoMeta::widget([
    'type'        => SeoMeta::TYPE_CATEGORY,
    'title'       => $pageTitle,
    'description' => $category['description'] ?? '',
    'image'       => $category['image'] ?? '',
    'category'    => $category,
    'products'    => $products ?? [],
    'breadcrumbs' => $this->params['breadcrumbs'] ?? [],
]);

$sortOptions = [
    'featured' => Yii::t('shop', 'Featured'),
    'best_selling' => Yii::t('shop', 'Best selling'),
    'price_asc' => Yii::t('shop', 'Price, low to high'),
    'price_desc' => Yii::t('shop', 'Price, high to low'),
    'date_desc' => Yii::t('shop', 'Date, new to old'),
    'date_asc' => Yii::t('shop', 'Date, old to new'),
];

$currentSort = isset($sortOptions[$currentSort]) ? $currentSort : 'featured';
?>

<!-- Shop Content -->
<section class="section">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-4 col-xl-3 pe-xl-5 offcanvas-lg offcanvas-start px-0 px-lg-3" tabindex="-1" id="shop_filter" aria-labelledby="shop_filterLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="shop_filterLabel"><?= Yii::t('shop', 'Filters') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#shop_filter" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body flex-column">
                    <?= $this->render('includes/_sidebar_categories', [
                        'category' => $category,
                        'categories' => $categories,
                    ]) ?>
                    
                    <?= $this->render('includes/_sidebar_filters', [
                        'filters' => $filters,
                    ]) ?>
                    
                    <!-- Clear Filters -->
                    <div class="mt-3">
                        <a href="<?= $category ? Yii::$app->urlManager->createUrl([$moduleRoute . '/category', 'slug' => $category['slug']]) : Url::to([$moduleRoute]) ?>" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle"></i> <?= Yii::t('shop', 'Clear Filters') ?>
                        </a>
                    </div>
                </div>
            </div>
            <!-- End Sidebar -->
            
            <!-- Product Grid -->
            <div class="col-lg-8 col-xl-9">
                <div class="shop-top-bar d-flex pb-3 mb-4 border-bottom">
                    <div class="layout-change">
                        <!-- Mobile Toggle -->
                        <button class="btn btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#shop_filter" aria-controls="shop_filter">
                            <i class="fs-4 lh-1 bi bi-justify-left"></i>
                        </button>
                        <!-- Results Count -->
                        <span class="ms-3 text-muted">
                            <?= Yii::t('shop', 'Showing {count} products', ['count' => count($products)]) ?>
                            <?php if (isset($meta['total'])): ?>
                                <?= Yii::t('shop', 'of {total}', ['total' => $meta['total']]) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="shortby-dropdown ms-auto">
                        <div class="dropdown">
                            <a class="btn btn-none btn-sm border dropdown-toggle text-mode" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= Yii::t('shop', 'Sort by') ?>: <?= $sortOptions[$currentSort] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                <?php foreach ($sortOptions as $sortKey => $sortLabel): ?>
                                    <li>
                                        <a
                                            class="dropdown-item <?= $currentSort === $sortKey ? 'active' : '' ?>"
                                            href="#"
                                            onclick="applySorting('<?= $sortKey ?>'); return false;"
                                        >
                                            <?= $sortLabel ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3" id="products-grid">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center py-5">
                                <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                                <h5><?= Yii::t('shop', 'No products found') ?></h5>
                                <p><?= Yii::t('shop', 'Try adjusting your filters or check back later.') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-12 col-sm-6 col-xl-4">
                                <?= ProductCard::widget([
                                    'product' => $product,
                                    'variant' => ProductCard::VARIANT_DEFAULT,
                                ]) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if (isset($meta['last_page']) && $meta['last_page'] > 1): ?>
                <nav class="mt-5" aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($meta['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Url::current(['page' => $meta['current_page'] - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $meta['last_page']; $i++): ?>
                            <li class="page-item <?= $i == $meta['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= Url::current(['page' => $i]) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($meta['current_page'] < $meta['last_page']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Url::current(['page' => $meta['current_page'] + 1]) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$this->registerJs(<<<JS
    window.applyPriceFilter = function (checkbox) {
        const url = new URL(window.location.href);
        document.querySelectorAll('input[id^="price"]').forEach((item) => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        if (checkbox.checked) {
            url.searchParams.set('min_price', checkbox.dataset.min);
            if (checkbox.dataset.max) {
                url.searchParams.set('max_price', checkbox.dataset.max);
            } else {
                url.searchParams.delete('max_price');
            }
        } else {
            url.searchParams.delete('min_price');
            url.searchParams.delete('max_price');
        }
        window.location.href = url.toString();
    };
    
    window.applyCustomPriceFilter = function () {
        const minPrice = document.getElementById('min_price').value;
        const maxPrice = document.getElementById('max_price').value;
        const url = new URL(window.location.href);
        
        if (minPrice !== '') {
            url.searchParams.set('min_price', minPrice);
        } else {
            url.searchParams.delete('min_price');
        }

        if (maxPrice !== '') {
            url.searchParams.set('max_price', maxPrice);
        } else {
            url.searchParams.delete('max_price');
        }
        
        window.location.href = url.toString();
    };
    
    window.applyFilter = function (type, value, checked) {
        const url = new URL(window.location.href);
        if (checked) {
            url.searchParams.set(type, value);
        } else {
            url.searchParams.delete(type);
        }
        window.location.href = url.toString();
    };
    
    window.applySorting = function (sort) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sort);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    };
JS
);
?>

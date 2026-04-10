<?php

namespace diincompany\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use diincompany\diinapi\contracts\ShopApiClientInterface;
use diincompany\shop\Module as ShopModule;

/**
 * Products controller for the shop module
 */
class ProductsController extends Controller
{
    private function shopModule(): ShopModule
    {
        $module = $this->module;

        if (!$module instanceof ShopModule) {
            throw new \yii\base\InvalidConfigException('ProductsController must run inside DiinCompany\\Yii2Shop\\Module.');
        }

        return $module;
    }

    private function apiClient(): ShopApiClientInterface
    {
        return $this->shopModule()->getApiClient();
    }

    /**
     * Lists all products with optional filters
     * @return string
     */
    public function actionIndex()
    {
        $api = $this->apiClient();
        
        // Build filters array
        $filters = [
            'active' => 1,
            // TODO: Descomentar cuando la API maneje estos parámetros correctamente
            // 'per_page' => Yii::$app->request->get('per_page', 12),
            // 'page' => Yii::$app->request->get('page', 1),
        ];

        $sort = $this->getRequestedSort();
        if ($sort !== null) {
            $filters['sort'] = $sort;
        }
        
        // Category filter
        $categoryId = Yii::$app->request->get('cat');
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        // Price filters
        $minPrice = Yii::$app->request->get('min_price');
        $maxPrice = Yii::$app->request->get('max_price');
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $filters['min_price'] = (float)$minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $filters['max_price'] = (float)$maxPrice;
        }
        
        // Brand filter
        $brand = Yii::$app->request->get('brand');
        if ($brand) {
            $filters['brand'] = $brand;
        }
        
        // Color filter
        $color = Yii::$app->request->get('color');
        if ($color) {
            $filters['color'] = $color;
        }
        
        // Size filter
        $size = Yii::$app->request->get('size');
        if ($size) {
            $filters['size'] = $size;
        }
        
        $keyword = Yii::$app->request->get('keyword');
        if ($keyword) {
            $filters['keyword'] = $keyword;
        }

        $onSale = $this->getRequestedOnSale();
        if ($onSale !== null) {
            $filters['on_sale'] = $onSale;
        }

        $featured = $this->getRequestedFeatured();
        if ($featured !== null) {
            $filters['featured'] = $featured;
        }

        // Get products
        $response = $api->getProducts($filters);
        
        // Get all categories for sidebar
        $categoriesResponse = $api->getCategories();
        $categories = $categoriesResponse['data'] ?? [];
        
        // Get current category if filtered
        $category = null;
        if ($categoryId) {
            // Find category in the categories array instead of making another API call
            foreach ($categories as $cat) {
                if ((string)$cat['id'] === (string)$categoryId) {
                    $category = $cat;
                    break;
                }
            }
        }
        
        return $this->render('index', [
            'products' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? [],
            'category' => $category,
            'categories' => $categories,
            'filters' => $this->getAvailableFilters(),
            'keyword' => $keyword,
            'currentSort' => $sort ?? 'featured',
        ]);
    }

    /**
     * View products by category slug
     * @param string $slug
     * @return string
     */
    public function actionCategory(string $slug)
    {
        $api = $this->apiClient();

        // Get all categories for sidebar and to find the current category
        $categoriesResponse = $api->getCategories();
        $categories = $categoriesResponse['data'] ?? [];
        
        // Find category by slug (simple search)
        $category = null;
        foreach ($categories as $cat) {
            if ($cat['slug'] === $slug) {
                $category = $cat;
                break;
            }
            // Also check subcategories
            $subcategories = $cat['subcategories'] ?? [];
            foreach ($subcategories as $subcat) {
                if ($subcat['slug'] === $slug) {
                    $category = $subcat;
                    break 2;
                }
            }
        }
        
        // Build filters with category slug
        $baseFilters = [
            'active' => 1,
            'category_slug' => $slug,
        ];

        $sort = $this->getRequestedSort();
        if ($sort !== null) {
            $baseFilters['sort'] = $sort;
        }
        
        // Price filters
        $minPrice = Yii::$app->request->get('min_price');
        $maxPrice = Yii::$app->request->get('max_price');
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $baseFilters['min_price'] = (float)$minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $baseFilters['max_price'] = (float)$maxPrice;
        }
        
        // Brand filter
        $brand = Yii::$app->request->get('brand');
        if ($brand) {
            $baseFilters['brand'] = $brand;
        }
        
        // Color filter
        $color = Yii::$app->request->get('color');
        if ($color) {
            $baseFilters['color'] = $color;
        }
        
        // Size filter
        $size = Yii::$app->request->get('size');
        if ($size) {
            $baseFilters['size'] = $size;
        }

        $onSale = $this->getRequestedOnSale();
        if ($onSale !== null) {
            $baseFilters['on_sale'] = $onSale;
        }

        $featured = $this->getRequestedFeatured();
        if ($featured !== null) {
            $baseFilters['featured'] = $featured;
        }
        
        // Get products using category_slug parameter
        $response = $api->getProducts($baseFilters);
        
        return $this->render('index', [
            'products' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? [],
            'category' => $category,
            'categories' => $categories,
            'filters' => $this->getAvailableFilters(),
            'currentSort' => $sort ?? 'featured',
        ]);
    }

    /**
     * View uncategorized products
     * @return string
     */
    public function actionUncategorized()
    {
        $api = $this->apiClient();
        
        // Build filters array for products without category
        $filters = [
            'active' => 1,
            'uncategorized' => 1,
        ];

        $sort = $this->getRequestedSort();
        if ($sort !== null) {
            $filters['sort'] = $sort;
        }
        
        // Price filters
        $minPrice = Yii::$app->request->get('min_price');
        $maxPrice = Yii::$app->request->get('max_price');
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $filters['min_price'] = (float)$minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $filters['max_price'] = (float)$maxPrice;
        }
        
        // Brand filter
        $brand = Yii::$app->request->get('brand');
        if ($brand) {
            $filters['brand'] = $brand;
        }
        
        // Color filter
        $color = Yii::$app->request->get('color');
        if ($color) {
            $filters['color'] = $color;
        }
        
        // Size filter
        $size = Yii::$app->request->get('size');
        if ($size) {
            $filters['size'] = $size;
        }

        $onSale = $this->getRequestedOnSale();
        if ($onSale !== null) {
            $filters['on_sale'] = $onSale;
        }

        $featured = $this->getRequestedFeatured();
        if ($featured !== null) {
            $filters['featured'] = $featured;
        }
        
        // Get products
        $response = $api->getProducts($filters);
        
        // Get all categories for sidebar
        $categoriesResponse = $api->getCategories();
        $categories = $categoriesResponse['data'] ?? [];
        
        return $this->render('index', [
            'products' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? [],
            'category' => null,
            'categories' => $categories,
            'filters' => $this->getAvailableFilters(),
            'currentSort' => $sort ?? 'featured',
        ]);
    }

    /**
     * View single product
     * @param string $slug
     * @return string
     */
    public function actionView(string $slug)
    {
        $api = $this->apiClient();

        $response = $api->getProduct(['slug' => $slug]);

        if (empty($response['data'])) {
            throw new NotFoundHttpException(Yii::t('shop', 'The requested product does not exist.'));
        }

        // Disable the head section for this view
        $this->view->params['hideHead'] = true;

        return $this->render('view', [
            'product' => $response['data'],
        ]);
    }
    
    /**
     * Get available filters for products
     * @return array
     */
    private function getAvailableFilters()
    {
        // This would ideally come from the API
        // For now, return basic structure
        return [
            'brands' => [],
            'colors' => [],
            'sizes' => [],
            'price_ranges' => [
                ['min' => 0, 'max' => 50, 'label' => '$0 - $50'],
                ['min' => 50, 'max' => 100, 'label' => '$50 - $100'],
                ['min' => 100, 'max' => 200, 'label' => '$100 - $200'],
                ['min' => 200, 'max' => null, 'label' => '$200+'],
            ],
        ];
    }

    /**
     * Return validated sort key from query params.
     */
    private function getRequestedSort(): ?string
    {
        $sort = Yii::$app->request->get('sort');

        if ($sort === null || $sort === '') {
            return null;
        }

        $sortRaw = strtolower(trim((string)$sort));
        $sortKey = preg_replace('/[^a-z0-9]+/', '_', $sortRaw);
        $sortKey = trim((string)$sortKey, '_');

        $sortAliases = [
            'featured' => 'featured',
            'best_selling' => 'best_selling',
            'best_seller' => 'best_selling',
            'best_sellers' => 'best_selling',
            'price_asc' => 'price_asc',
            'price_desc' => 'price_desc',
            'date_desc' => 'date_desc',
            'newest' => 'date_desc',
            'date_asc' => 'date_asc',
            'oldest' => 'date_asc',
        ];

        if (isset($sortAliases[$sortRaw])) {
            return $sortAliases[$sortRaw];
        }

        if (isset($sortAliases[$sortKey])) {
            return $sortAliases[$sortKey];
        }

        return null;
    }

    /**
     * Return validated on_sale filter from query params.
     */
    private function getRequestedOnSale(): ?int
    {
        $onSale = Yii::$app->request->get('on_sale');

        if ($onSale === null || $onSale === '') {
            return null;
        }

        if (is_bool($onSale)) {
            return $onSale ? 1 : 0;
        }

        $onSaleRaw = strtolower(trim((string)$onSale));

        if (in_array($onSaleRaw, ['1', 'true', 'yes'], true)) {
            return 1;
        }

        if (in_array($onSaleRaw, ['0', 'false', 'no'], true)) {
            return 0;
        }

        return null;
    }

    /**
     * Return validated featured filter from query params.
     */
    private function getRequestedFeatured(): ?int
    {
        $featured = Yii::$app->request->get('featured');

        if ($featured === null || $featured === '') {
            return null;
        }

        if (is_bool($featured)) {
            return $featured ? 1 : 0;
        }

        $featuredRaw = strtolower(trim((string)$featured));

        if (in_array($featuredRaw, ['1', 'true', 'yes'], true)) {
            return 1;
        }

        if (in_array($featuredRaw, ['0', 'false', 'no'], true)) {
            return 0;
        }

        return null;
    }
}

<?php

namespace diincompany\shop\events;

use diincompany\shop\Module;
use diincompany\diinapi\contracts\ShopApiClientInterface;
use Yii;
use yii\base\Event;

class BeforeRequestHandler
{
    public static function handle(Event $event): void
    {
        if (!(Yii::$app instanceof \yii\web\Application)) {
            return;
        }

        if (!Yii::$app->has('request') || !Yii::$app->has('response')) {
            return;
        }

        $request = Yii::$app->request;
        $response = Yii::$app->response;
        $view = Yii::$app->has('view') ? Yii::$app->view : null;

        // Cache bypass via query params is restricted to dev and authenticated admins.
        $canBypassCache = defined('YII_ENV_DEV') && YII_ENV_DEV;
        if (
            !$canBypassCache
            && Yii::$app->has('user')
            && !Yii::$app->user->isGuest
            && Yii::$app->has('authManager')
            && Yii::$app->user->can('admin')
        ) {
            $canBypassCache = true;
        }

        $forceRefreshAll = false;
        $forceMerchantRefresh = false;
        $forceCategoriesRefresh = false;

        if ($canBypassCache) {
            $refreshCacheParam = strtolower((string) $request->get('refreshCache', ''));
            $forceRefreshAll = in_array($refreshCacheParam, ['1', 'true', 'yes'], true);

            $refreshMerchantParam = strtolower((string) $request->get('refreshMerchant', ''));
            $forceMerchantRefresh = $forceRefreshAll || in_array($refreshMerchantParam, ['1', 'true', 'yes'], true);
            $refreshCategoriesParam = strtolower((string) $request->get('refreshCategories', ''));
            $forceCategoriesRefresh = $forceRefreshAll || in_array($refreshCategoriesParam, ['1', 'true', 'yes'], true);
        }

        $maintenanceRaw = getenv('MAINTENANCE_MODE');
        $maintenanceMode = in_array(strtolower((string) $maintenanceRaw), ['1', 'true', 'yes'], true);
        $currentRoute = trim((string) $request->pathInfo, '/');

        if ($maintenanceMode) {
            if ($currentRoute !== 'maintenance' && $currentRoute !== 'site/maintenance') {
                $response->redirect(['site/maintenance'])->send();
                Yii::$app->end();
            }
        } else {
            // If not maintenance mode, but currently on maintenance page, redirect to home
            if ($currentRoute === 'maintenance' || $currentRoute === 'site/maintenance') {
                $response->redirect(['/'])->send();
                Yii::$app->end();
            }
        }

        $apiClient = self::resolveApiClient();
        if (!$apiClient instanceof ShopApiClientInterface) {
            Yii::warning('Unable to preload shop context: API client component is missing or invalid.', __METHOD__);
            return;
        }

        try {
            $merchantResponse = $apiClient->getMerchant($forceMerchantRefresh);
            $merchantData = (isset($merchantResponse['data']) && is_array($merchantResponse['data']))
                ? $merchantResponse['data']
                : [];

            Yii::$app->params['merchant'] = $merchantData;
            Yii::$app->params['merchantResponse'] = $merchantResponse;
            if ($view !== null) {
                $view->params['merchant'] = $merchantData;
            }
        } catch (\Throwable $e) {
            Yii::warning('Unable to preload merchant context: ' . $e->getMessage(), __METHOD__);
            Yii::$app->params['merchant'] = Yii::$app->params['merchant'] ?? [];
            Yii::$app->params['merchantResponse'] = Yii::$app->params['merchantResponse'] ?? ['data' => []];
            if ($view !== null) {
                $view->params['merchant'] = Yii::$app->params['merchant'];
            }
        }

        try {
            $categoriesResponse = $apiClient->getCategories(null, $forceCategoriesRefresh);
            $categoriesData = (isset($categoriesResponse['data']) && is_array($categoriesResponse['data']))
                ? $categoriesResponse['data']
                : [];

            Yii::$app->params['categories'] = $categoriesData;
            Yii::$app->params['categoriesResponse'] = $categoriesResponse;
            if ($view !== null) {
                $view->params['categories'] = $categoriesData;
            }
        } catch (\Throwable $e) {
            Yii::warning('Unable to preload categories context: ' . $e->getMessage(), __METHOD__);
            Yii::$app->params['categories'] = Yii::$app->params['categories'] ?? [];
            Yii::$app->params['categoriesResponse'] = Yii::$app->params['categoriesResponse'] ?? ['data' => []];
            if ($view !== null) {
                $view->params['categories'] = Yii::$app->params['categories'];
            }
        }
    }

    private static function resolveApiClient(): ?ShopApiClientInterface
    {
        /** @var mixed $shopModule */
        $shopModule = Yii::$app->getModule('shop', false);

        if ($shopModule instanceof Module) {
            try {
                return $shopModule->getApiClient();
            } catch (\Throwable $e) {
                Yii::warning('Unable to resolve module API client: ' . $e->getMessage(), __METHOD__);
            }
        }

        $fallback = Yii::$app->get('diinapi', false);
        return $fallback instanceof ShopApiClientInterface ? $fallback : null;
    }
}

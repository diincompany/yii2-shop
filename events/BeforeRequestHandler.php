<?php

namespace diincompany\shop\events;

use Yii;
use yii\base\Event;

class BeforeRequestHandler
{
    public static function handle(Event $event): void
    {
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
            $refreshCacheParam = strtolower((string) Yii::$app->request->get('refreshCache', ''));
            $forceRefreshAll = in_array($refreshCacheParam, ['1', 'true', 'yes'], true);

            $refreshMerchantParam = strtolower((string) Yii::$app->request->get('refreshMerchant', ''));
            $forceMerchantRefresh = $forceRefreshAll || in_array($refreshMerchantParam, ['1', 'true', 'yes'], true);
            $refreshCategoriesParam = strtolower((string) Yii::$app->request->get('refreshCategories', ''));
            $forceCategoriesRefresh = $forceRefreshAll || in_array($refreshCategoriesParam, ['1', 'true', 'yes'], true);
        }

        $maintenanceMode = $_ENV['MAINTENANCE_MODE'] ?? 'false';
        if ($maintenanceMode === 'true' || $maintenanceMode === '1' || $maintenanceMode === true) {
            $currentRoute = Yii::$app->request->pathInfo;
            if ($currentRoute !== 'maintenance' && $currentRoute !== 'site/maintenance') {
                Yii::$app->response->redirect(['site/maintenance'])->send();
                Yii::$app->end();
            }
        } else {
            // If not maintenance mode, but currently on maintenance page, redirect to home
            $currentRoute = Yii::$app->request->pathInfo;
            if ($currentRoute === 'maintenance' || $currentRoute === 'site/maintenance') {
                Yii::$app->response->redirect(['/'])->send();
                Yii::$app->end();
            }
        }

        try {
            $merchantResponse = Yii::$app->diinapi->getMerchant($forceMerchantRefresh);
            $merchantData = (isset($merchantResponse['data']) && is_array($merchantResponse['data']))
                ? $merchantResponse['data']
                : [];

            Yii::$app->params['merchant'] = $merchantData;
            Yii::$app->params['merchantResponse'] = $merchantResponse;
            Yii::$app->view->params['merchant'] = $merchantData;
        } catch (\Throwable $e) {
            Yii::warning('Unable to preload merchant context: ' . $e->getMessage(), __METHOD__);
            Yii::$app->params['merchant'] = Yii::$app->params['merchant'] ?? [];
            Yii::$app->params['merchantResponse'] = Yii::$app->params['merchantResponse'] ?? ['data' => []];
            Yii::$app->view->params['merchant'] = Yii::$app->params['merchant'];
        }

        try {
            $categoriesResponse = Yii::$app->diinapi->getCategories(null, $forceCategoriesRefresh);
            $categoriesData = (isset($categoriesResponse['data']) && is_array($categoriesResponse['data']))
                ? $categoriesResponse['data']
                : [];

            Yii::$app->params['categories'] = $categoriesData;
            Yii::$app->params['categoriesResponse'] = $categoriesResponse;
            Yii::$app->view->params['categories'] = $categoriesData;
        } catch (\Throwable $e) {
            Yii::warning('Unable to preload categories context: ' . $e->getMessage(), __METHOD__);
            Yii::$app->params['categories'] = Yii::$app->params['categories'] ?? [];
            Yii::$app->params['categoriesResponse'] = Yii::$app->params['categoriesResponse'] ?? ['data' => []];
            Yii::$app->view->params['categories'] = Yii::$app->params['categories'];
        }
    }
}

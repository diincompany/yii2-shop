<?php
namespace diincompany\shop\widgets\cart;

use diincompany\shop\assets\CartAsset;
use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\Widget;
use yii\helpers\Url;

class CartSidebar extends Widget
{
    public $params;

    public function init() {
        parent::init();
        ShopModule::registerTranslations();

        $this->params = $this->params ?: [];
    }

    public function run() {

        $view = $this->getView();
        CartAsset::register($view);

        $moduleId = $this->resolveModuleId();
        $moduleRoute = '/' . $moduleId;
        $currencySymbol = 'L';

        $view->registerJsVar('shopCartConfig', [
            'cartSummaryUrl' => Url::to([$moduleRoute . '/cart/summary']),
            'updateQuantityUrl' => Url::to([$moduleRoute . '/cart/update-quantity']),
            'removeItemUrl' => Url::to([$moduleRoute . '/cart/remove-item']),
            'checkoutUrl' => Url::to([$moduleRoute . '/checkout']),
            'cartUrl' => Url::to([$moduleRoute . '/cart']),
            'productViewUrlTemplate' => Url::to([$moduleRoute . '/products/view', 'id' => '__PRODUCT_ID__']),
            'currencySymbol' => $currencySymbol,
        ]);

        $view->registerJsVar('shopCartI18n', [
            'Your Cart' => Yii::t('shop', 'Your Cart'),
            'items in cart' => Yii::t('shop', 'items in cart'),
            'Your cart is empty' => Yii::t('shop', 'Your cart is empty'),
            'Variant' => Yii::t('shop', 'Variant'),
            'Remove' => Yii::t('shop', 'Remove'),
            'Subtotal' => Yii::t('shop', 'Subtotal'),
            'shipping' => Yii::t('shop', 'shipping'),
            'Taxes' => Yii::t('shop', 'Taxes'),
            'Total' => Yii::t('shop', 'Total'),
            'View Cart' => Yii::t('shop', 'View Cart'),
            'Quantity updated' => Yii::t('shop', 'Quantity updated'),
            'Error updating quantity' => Yii::t('shop', 'Error updating quantity'),
            'Confirm remove cart item' => Yii::t('shop', 'Confirm remove cart item'),
            'Product removed from cart' => Yii::t('shop', 'Product removed from cart'),
            'Error removing product' => Yii::t('shop', 'Error removing product'),
        ]);

        $itemCount = (int) ($this->params['itemCount'] ?? 0);
        $cartItems = $this->params['cartItems'] ?? [];

        return $this->render('cart-sidebar', [
            'moduleId' => $moduleId,
            'currencySymbol' => $currencySymbol,
            'itemCount' => $itemCount,
            'cartItems' => $cartItems,
        ]);
    }

    private function resolveModuleId(): string
    {
        $instance = ShopModule::getInstance();
        if ($instance !== null) {
            return $instance->id;
        }

        foreach (Yii::$app->getModules(false) as $id => $moduleDefinition) {
            if (is_array($moduleDefinition) && isset($moduleDefinition['class']) && is_a($moduleDefinition['class'], ShopModule::class, true)) {
                return (string)$id;
            }

            if ($moduleDefinition instanceof ShopModule) {
                return (string)$id;
            }
        }

        return 'shop';
    }
}
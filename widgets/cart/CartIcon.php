<?php
namespace diincompany\shop\widgets\cart;

use diincompany\shop\Module as ShopModule;
use yii\base\Widget;

class CartIcon extends Widget
{
    public $params;

    public function init() {
        parent::init();

        $this->params = $this->params ?: [];
    }

    public function run() {
        ShopModule::registerTranslations();

        $itemCount = (int) ($this->params['itemCount'] ?? 0);

        return $this->render('cart-icon', [
            'itemCount' => $itemCount,
        ]);
    }
}
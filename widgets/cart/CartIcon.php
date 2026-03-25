<?php
namespace DiinCompany\Yii2Shop\widgets\cart;

use DiinCompany\Yii2Shop\Module as ShopModule;
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
<?php

namespace diincompany\shop\widgets;

use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class OrderStatusLookup extends Widget
{
    public function init()
    {
        parent::init();
        ShopModule::registerTranslations();
    }

    public function run()
    {
        $orderStatusUrl = Url::to(['/shop/order-status']);

        $form = Html::beginForm($orderStatusUrl, 'post', [
            'class' => 'px-3 py-2',
            'style' => 'min-width: 320px;',
        ]);

        $form .= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken());

        $form .= Html::tag('div', Yii::t('shop', 'Use the order number and email associated with your purchase.'), [
            'class' => 'small text-muted mb-2',
        ]);

        $form .= Html::input('text', 'order_number', '', [
            'class' => 'form-control form-control-sm mb-2',
            'placeholder' => Yii::t('shop', 'Order number'),
            'required' => true,
        ]);

        $form .= Html::input('email', 'email', '', [
            'class' => 'form-control form-control-sm mb-2',
            'placeholder' => Yii::t('shop', 'email_address'),
            'required' => true,
        ]);

        $form .= Html::submitButton(Yii::t('shop', 'Find your order'), [
            'class' => 'btn btn-primary btn-sm w-100',
        ]);

        $form .= Html::endForm();

        return Html::tag('li',
            Html::a(Yii::t('shop', 'Track your order'), '#', ['class' => 'nav-link'])
            . Html::tag('label', '', ['class' => 'px-dropdown-toggle mob-menu'])
            . Html::tag('ul', Html::tag('li', $form), ['class' => 'dropdown-menu left shadow-lg']),
            ['class' => 'dropdown nav-item']
        );
    }
}

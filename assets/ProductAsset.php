<?php

namespace diincompany\shop\assets;

use yii\web\AssetBundle;

class ProductAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../web';

    public $publishOptions = [
        'forceCopy' => YII_ENV_DEV,
    ];

    public $css = [
        'vendor/swiper/swiper-bundle.min.css',
        'vendor/magnific/magnific-popup.css',
    ];

    public $js = [
        'vendor/swiper/swiper-bundle.min.js',
        'vendor/magnific/jquery.magnific-popup.min.js',
        'js/product-item.js',
    ];

    public $depends = [
        'diincompany\shop\assets\ShopAsset',
        'yii\web\JqueryAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}

<?php

namespace diincompany\shop\assets;

use yii\web\AssetBundle;

class ShopAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../web';

    public $publishOptions = [
        'forceCopy' => YII_ENV_DEV,
    ];

    public $css = [
        'css/shop.css',
    ];

    public $js = [
        'https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.4/bootbox.min.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}

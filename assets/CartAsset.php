<?php

namespace diincompany\shop\assets;

use yii\web\AssetBundle;

class CartAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../web';

    // Avoid stale published assets while iterating in development.
    public $publishOptions = [
        'forceCopy' => YII_ENV_DEV,
    ];

    public $js = [
        'js/cart.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}

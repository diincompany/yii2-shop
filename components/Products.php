<?php
namespace diincompany\shop\components;

use Yii;

class Products
{
    public static function getFeaturedProducts()
    {
        $api = Yii::$app->diinapi;

        $data = $api->getProducts(['featured' => 1]);

        return $data;
    }
}
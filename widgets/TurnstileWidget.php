<?php

namespace diincompany\shop\widgets;

use Yii;
use yii\base\Widget;

class TurnstileWidget extends Widget
{
    public array $options = [];

    public function run(): string
    {
        if (Yii::$app->turnstile->disable) {
            return '';
        }

        return Yii::$app->turnstile->getCaptcha($this->options);
    }
}

<?php

namespace DiinCompany\Yii2Shop\services;

use DiinCompany\Yii2Shop\components\SessionId;
use DiinCompany\Yii2Shop\contracts\ShopSessionContextInterface;

class DefaultShopSessionContext implements ShopSessionContextInterface
{
    public function getAnonymousSessionId(bool $regenerate = false): string
    {
        return (string) SessionId::getAnonymousSessionId($regenerate);
    }
}

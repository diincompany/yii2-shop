<?php

namespace diincompany\shop\services;

use diincompany\shop\components\SessionId;
use diincompany\shop\contracts\ShopSessionContextInterface;

class DefaultShopSessionContext implements ShopSessionContextInterface
{
    public function getAnonymousSessionId(bool $regenerate = false): string
    {
        return (string) SessionId::getAnonymousSessionId($regenerate);
    }
}

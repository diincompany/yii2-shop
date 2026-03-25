<?php

namespace DiinCompany\Yii2Shop\contracts;

interface ShopSessionContextInterface
{
    public function getAnonymousSessionId(bool $regenerate = false): string;
}

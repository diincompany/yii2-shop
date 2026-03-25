<?php

namespace diincompany\shop\contracts;

interface ShopSessionContextInterface
{
    public function getAnonymousSessionId(bool $regenerate = false): string;
}

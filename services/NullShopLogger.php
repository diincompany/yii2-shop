<?php

namespace DiinCompany\Yii2Shop\services;

use DiinCompany\Yii2Shop\contracts\ShopLoggerInterface;

class NullShopLogger implements ShopLoggerInterface
{
    public function info(string $message, array $context = []): void
    {
    }

    public function warning(string $message, array $context = []): void
    {
    }

    public function error(string $message, array $context = []): void
    {
    }

    public function critical(string $message, array $context = []): void
    {
    }

    public function debug(string $message, array $context = []): void
    {
    }
}

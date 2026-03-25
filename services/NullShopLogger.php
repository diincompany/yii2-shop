<?php

namespace diincompany\shop\services;

use diincompany\shop\contracts\ShopLoggerInterface;

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

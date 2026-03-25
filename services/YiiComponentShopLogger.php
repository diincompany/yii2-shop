<?php

namespace DiinCompany\Yii2Shop\services;

use DiinCompany\Yii2Shop\contracts\ShopLoggerInterface;

class YiiComponentShopLogger implements ShopLoggerInterface
{
    private object $logger;

    public function __construct(object $logger)
    {
        $this->logger = $logger;
    }

    public function info(string $message, array $context = []): void
    {
        $this->forward('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->forward('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->forward('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->forward('critical', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->forward('debug', $message, $context);
    }

    private function forward(string $level, string $message, array $context): void
    {
        if (method_exists($this->logger, $level)) {
            $this->logger->{$level}($message, $context);
            return;
        }

        // Best-effort fallback to prevent logging calls from breaking checkout/cart flows.
        if (method_exists($this->logger, 'error')) {
            $this->logger->error($message, array_merge(['level' => $level], $context));
        }
    }
}

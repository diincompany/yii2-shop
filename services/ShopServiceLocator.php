<?php

namespace diincompany\shop\services;

use diincompany\diinapi\contracts\ShopApiClientInterface;
use diincompany\shop\contracts\ShopLoggerInterface;
use diincompany\shop\contracts\ShopSessionContextInterface;
use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\InvalidConfigException;

class ShopServiceLocator
{
    public static function getApiClient(): ShopApiClientInterface
    {
        $module = self::resolveModule();
        if ($module !== null) {
            return $module->getApiClient();
        }

        $component = Yii::$app->get('diinapi', false);
        if ($component instanceof ShopApiClientInterface) {
            return $component;
        }

        $type = is_object($component) ? get_class($component) : gettype($component);
        throw new InvalidConfigException("Shop API client must implement ShopApiClientInterface, {$type} given.");
    }

    public static function getLogger(): ShopLoggerInterface
    {
        $module = self::resolveModule();
        if ($module !== null) {
            return $module->getLogger();
        }

        $component = Yii::$app->get('logtail', false);
        if (is_object($component)) {
            return new YiiComponentShopLogger($component);
        }

        return new NullShopLogger();
    }

    public static function getSessionContext(): ShopSessionContextInterface
    {
        $module = self::resolveModule();
        if ($module !== null) {
            return $module->getSessionContext();
        }

        return new DefaultShopSessionContext();
    }

    private static function resolveModule(): ?ShopModule
    {
        $instance = ShopModule::getInstance();
        if ($instance instanceof ShopModule) {
            return $instance;
        }

        foreach (Yii::$app->getModules(false) as $id => $definition) {
            if ($definition instanceof ShopModule) {
                return $definition;
            }

            if (is_array($definition) && isset($definition['class']) && is_a($definition['class'], ShopModule::class, true)) {
                $resolved = Yii::$app->getModule((string) $id, false);
                if ($resolved instanceof ShopModule) {
                    return $resolved;
                }
            }
        }

        return null;
    }
}

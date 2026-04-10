<?php

namespace diincompany\shop;

use Yii;
use yii\base\Application;
use yii\base\Event;
use yii\base\InvalidConfigException;
use diincompany\shop\contracts\ShopApiClientInterface;
use diincompany\shop\contracts\ShopLoggerInterface;
use diincompany\shop\contracts\ShopSessionContextInterface;
use diincompany\shop\events\BeforeRequestHandler;
use diincompany\shop\services\DefaultShopSessionContext;
use diincompany\shop\services\NullShopLogger;
use diincompany\shop\services\YiiComponentShopLogger;

/**
 * store module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'diincompany\shop\controllers';

    /**
     * Default layout used by shop controllers.
     *
     * Host projects can override this in module configuration.
     *
     * @var string
     */
    public $layout = null;

    /**
     * Optional breadcrumbs partial path rendered by shop views.
     *
     * Set to null to disable breadcrumbs rendering from module views.
     *
     * @var string|null
     */
    public $breadcrumbsView = null;

    /**
     * The app component ID that implements ShopApiClientInterface.
     * Override in module config if your project uses a different component name.
     *
     * @var string
     */
    public $apiClientComponent = 'diinapi';

    /**
     * Optional app component ID that implements ShopLoggerInterface.
     * If null, falls back to 'logtail' component (wrapped) or NullShopLogger.
     *
     * @var string|null
     */
    public $loggerComponent = null;

    /**
     * Optional app component ID that implements ShopSessionContextInterface.
     * If null, DefaultShopSessionContext is used (Yii session-based).
     *
     * @var string|null
     */
    public $sessionContextComponent = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        Yii::setAlias('@diinshop', __DIR__);

        if (Yii::$app instanceof \yii\web\Application) {
            Event::on(
                Application::class,
                Application::EVENT_BEFORE_REQUEST,
                [BeforeRequestHandler::class, 'handle']
            );
        }

        self::registerTranslations();
    }

    public static function registerTranslations(): void
    {
        if (!isset(Yii::$app->i18n->translations['shop*'])) {
            Yii::$app->i18n->translations['shop*'] = [
                'class' => 'yii\\i18n\\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US',
                'fileMap' => [
                    'shop' => 'shop.php',
                ],
            ];
        }
    }

    public function getBreadcrumbsView(): ?string
    {
        return $this->breadcrumbsView;
    }

    public function getApiClient(): ShopApiClientInterface
    {
        $component = Yii::$app->get($this->apiClientComponent);
        if (!$component instanceof ShopApiClientInterface) {
            throw new InvalidConfigException(
                "The '" . $this->apiClientComponent . "' component does not implement ShopApiClientInterface. "
                . "Please configure a valid component in module config 'apiClientComponent'."
            );
        }
        return $component;
    }

    public function getLogger(): ShopLoggerInterface
    {
        if ($this->loggerComponent !== null) {
            $component = Yii::$app->get($this->loggerComponent);
            if (!$component instanceof ShopLoggerInterface) {
                throw new InvalidConfigException(
                    "The '" . $this->loggerComponent . "' component does not implement ShopLoggerInterface. "
                    . "Please configure a valid component in module config 'loggerComponent'."
                );
            }
            return $component;
        }

        $component = Yii::$app->get('logtail', false);
        if (is_object($component)) {
            return new YiiComponentShopLogger($component);
        }

        return new NullShopLogger();
    }

    public function getSessionContext(): ShopSessionContextInterface
    {
        if ($this->sessionContextComponent !== null) {
            $component = Yii::$app->get($this->sessionContextComponent);
            if (!$component instanceof ShopSessionContextInterface) {
                throw new InvalidConfigException(
                    "The '" . $this->sessionContextComponent . "' component does not implement ShopSessionContextInterface. "
                    . "Please configure a valid component in module config 'sessionContextComponent'."
                );
            }
            return $component;
        }

        return new DefaultShopSessionContext();
    }
}

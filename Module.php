<?php

namespace DiinCompany\Yii2Shop;

use Yii;

/**
 * store module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'DiinCompany\Yii2Shop\controllers';

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
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        Yii::setAlias('@yii2shop', __DIR__);

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
}

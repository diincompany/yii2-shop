<?php
namespace diincompany\shop\widgets;

use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class PoweredBy extends Widget
{
    public $label = 'Powered by';
    public $url = 'https://diincompany.com';
    public $logoUrl = 'https://ik.imagekit.io/ready/diin/img/site/logo-diin.svg';
    public $logoAlt = 'Diin Company';
    public $wrapperOptions = ['class' => 'w-100 py-3 small d-flex align-items-center justify-content-center'];
    public $textOptions = ['class' => 'text-muted'];
    public $linkOptions = ['target' => '_blank', 'rel' => 'noopener'];
    public $imageOptions = ['style' => 'height:16px;'];

    public function init()
    {
        parent::init();
        ShopModule::registerTranslations();

        if (!isset($this->imageOptions['alt'])) {
            $this->imageOptions['alt'] = $this->logoAlt;
        }
    }

    public function run()
    {
        $label = Html::encode(Yii::t('shop', $this->label));
        $logo = Html::img($this->logoUrl, $this->imageOptions);
        $link = Html::a($logo, $this->url, $this->linkOptions);

        return Html::tag(
            'div',
            Html::tag('div', $label . ' ' . $link, $this->textOptions),
            $this->wrapperOptions
        );
    }
}
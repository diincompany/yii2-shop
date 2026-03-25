<?php
namespace app\modules\store\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class CategoryCard extends Widget
{
    public $img;
    public $label;
    public $link;

    public function init() {
        parent::init();

        $this->img = $this->img ?? 'https://ik.imagekit.io/ready/diin/img/site/placeholder.png';
    }

    public function run() {
        $img = Html::img($this->img, [
            'class' => 'img-fluid',
            'style' => 'max-width: 75px;',
        ]);
        $url = Url::to($this->link);

        $content = <<<HTML
            <a href="{$url}" class="text-decoration-none">
                <div class="border rounded p-3 py-5">
                    <div class="text-center mb-2">
                        {$img}
                    </div>
                    <div class="text-center">{$this->label}</div>
                </div>
            </a>
        HTML;

        return $content;
    }
}
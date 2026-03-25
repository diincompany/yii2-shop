<?php
namespace app\modules\store\widgets\item;

use app\modules\store\widgets\QuoteButton;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class ItemCard extends Widget
{
    public $product_id;
    public $img;
    public $title;
    public $code;
    public $out_of_stock;
    public $enabled_stock;
    public $label;
    public $link;
    public $on_sale;

    public function init() {
        parent::init();

        $this->label = $this->label ?: Yii::t('shop','Cotizar');
        $this->img = !empty($this->img) ? $this->img.'?tr=h-300,w-300' : 'https://ik.imagekit.io/ready/diin/img/site/placeholder.png';
    }

    public function run() {
        $onSale = null;
        $outOfStock = null;

        $img = Html::img($this->img, [
            'class' => 'card-img-top',
            'alt' => $this->title,
        ]);
        
        $url = Url::to($this->link);
        
        $button = QuoteButton::widget([
            'params' => [
                'pid' => $this->product_id,
            ],
        ]);

        if($this->enabled_stock) {
            $outOfStock =  $this->out_of_stock ? Html::tag('div',Yii::t('shop','Sin Existencia'), [
                'class' => 'text-bg-warning py-1 px-2 rounded-end-4 position-absolute shadow-sm text-uppercase',
                'style' => 'top: 20px; left: -8px; font-size: 0.6rem;',
            ]) : '';
        }

        if(empty($outOfStock)) {
            $onSale = $this->on_sale ? Html::tag('div',Yii::t('shop','Oferta'), [
                'class' => 'text-bg-red py-1 px-2 rounded-end-4 position-absolute shadow-sm text-uppercase',
                'style' => 'top: 20px; left: -8px; font-size: 0.6rem;',
            ]) : '';
        }

        $code = $this->code ? Html::tag('div', $this->code, [
            'class' => 'text-gray-400 small',
        ]) : '';

        $content = <<<HTML
            <div class="card shadow-none h-100">
                {$onSale}
                {$outOfStock}
                <a href="{$url}" class="text-decoration-none">{$img}</a>
                <div class="card-body p-2">
                    <div class="small fw-bold">{$this->title}</div>
                    {$code}
                </div>
                <div class="card-footer p-2">
                    <div class="d-grid">
                        {$button}
                    </div>
                </div>
            </div>
        HTML;

        return $content;
    }
}
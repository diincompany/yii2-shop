<?php
namespace diincompany\shop\widgets\search;

use Yii;
use yii\base\Widget;

class SearchForm extends Widget
{
    public string $placeholder = 'What are you looking for?';
    public string $inputName = 'keyword';
    public string $formMethod = 'get';
    public ?string $formAction = null;
    public string $keyword = '';

    public function run(): string
    {
        return $this->render('search-form', [
            'placeholder' => Yii::t('shop', $this->placeholder),
            'inputName' => $this->inputName,
            'formMethod' => $this->formMethod,
            'formAction' => $this->formAction,
            'keyword' => $this->keyword,
        ]);
    }
}

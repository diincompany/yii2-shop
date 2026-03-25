<?php
namespace DiinCompany\Yii2Shop\widgets\search;

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
            'placeholder' => $this->placeholder,
            'inputName' => $this->inputName,
            'formMethod' => $this->formMethod,
            'formAction' => $this->formAction,
            'keyword' => $this->keyword,
        ]);
    }
}

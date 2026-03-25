<?php
namespace app\modules\store\widgets;

use Yii;
use yii\base\Widget;
use yii\bootstrap5\Modal;
use yii\helpers\Html;

class ModalForm extends Widget
{
  public $id;
  public $title;
  public $icon;
  public $size;
  public $clientOptions;

  const SIZE_LARGE = 'lg';
  const SIZE_SMALL = 'sm';
  const SIZE_DEFAULT = '';

	public function init()
	{
      parent::init();
      $this->id = ($this->id==null?'form-modal-'.rand():$this->id);
      $this->title = ($this->title==null?'Modal Title':$this->title);
      $this->size = ($this->size==null?'':$this->size);
      $this->icon = ($this->icon==null?'fas fa-file-alt':$this->icon);
      $this->clientOptions = ($this->clientOptions==null?[ 'backdrop' => 'static', 'keyboard' => false, ]:$this->clientOptions);
	}

	public function run()
	{
    $view = Yii::$app->view;
	
    $view->on($view::EVENT_END_BODY, function() {
      Modal::begin([
        'id' => $this->id,
        'title' => $this->title,
        'size'   => $this->size,
        'options' => [
          'tabindex' => false,
        ],
        'clientOptions' => $this->clientOptions,
      ]);
      
      echo Html::tag('div', '', ['id' => 'modal-content']);
      
      Modal::end();
    });

    $view->registerJs(<<<JS
      $('.open-modal').click(function () {
        let modalTitle = $(this).data('modal-title');
        let modalIcon = $(this).data('modal-icon');
        let modalSize = $(this).data('modal-size');
        let modalBackdrop = $(this).data('modal-backdrop');
        let modalVCentered = $(this).data('modal-vertical-centered');
        let modalClass = $(this).data('modal-class');

        console.log('model triggered');

        if(modalIcon==undefined)
          modalIcon = 'far fa-circle';

        $.get($(this).attr('href'), function(data) {
          $('#{$this->id}').modal('show').find('#modal-content').html(data);
        });

        $('#{$this->id}').on('show.bs.modal', function() {
          $('#{$this->id}').addClass(modalClass);

          if(modalTitle!==undefined)
            $('#{$this->id}').find('.modal-header h5').html('<i class=\"'+modalIcon+' mr-2\"></i> '+modalTitle);

          if(modalSize!==undefined)
            $('#{$this->id}').find('.modal-dialog').addClass('modal-'+modalSize);
          
          if(modalVCentered!==undefined)
            $('#{$this->id}').find('.modal-dialog').addClass('modal-dialog-centered');
        })
        return false;
      });
    JS);
  }
}
<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$form = ActiveForm::begin();
?>
    <?=$form->field($model,'name')->textInput([
        'placeholder' => 'Nombre'
    ])->label(false)?>
    <?=$form->field($model,'email')->textInput([
        'placeholder' => 'Correo electronico'
    ])->label(false)?>
    <?=$form->field($model,'phone')->textInput([
        'placeholder' => 'Telefono'
    ])->label(false)?>
    <?=$form->field($model,'message')->textarea([
        'placeholder' => 'Detallanos un poco más de lo que necesitas...',
        'rows' => 3,
    ])->label(false)?>

    <?php if(!Yii::$app->turnstile->disable) : ?>
        <div class="mb-3">
            <?=Yii::$app->turnstile->getCaptcha()?>
        </div>
    <?php endif; ?>

    <div class="d-grid gap-2">
        <?=Html::submitButton('Enviar', [
            'class' => 'btn btn-primary w-100'
        ])?>
        <?=Html::button('Cancelar', [
            'class' => 'btn btn-light w-100',
            'data' => [
                'bs-dismiss' => 'modal',
            ]
        ])?>
    </div>

    <?=Html::activeHiddenInput($model,'items')?>
<?php ActiveForm::end();?>
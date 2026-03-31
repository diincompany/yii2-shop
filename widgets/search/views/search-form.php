<?php

use yii\helpers\Html;

/** @var string $placeholder */
/** @var string $inputName */
/** @var string $formMethod */
/** @var string|null $formAction */
?>
<form method="<?= Html::encode($formMethod) ?>" action="<?= Html::encode($formAction ?? '') ?>">
    <input
        class="form-control"
        type="text"
        name="<?= Html::encode($inputName) ?>"
        placeholder="<?= Html::encode($placeholder) ?>"
        value="<?= Html::encode($keyword) ?>"
    >
    <button type="button" class="btn shadow-none">
        <i class="fi-search"></i>
    </button>
</form>

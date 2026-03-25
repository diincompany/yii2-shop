<?php
/**
 * Billing Address Toggle
 * @var yii\web\View $this
 */
?>

<div class="row mt-3">
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="use_billing_address" name="use_billing_address">
            <label class="form-check-label" for="use_billing_address">
                <?= Yii::t('shop', 'use_different_billing_address') ?>
            </label>
        </div>
    </div>
</div>

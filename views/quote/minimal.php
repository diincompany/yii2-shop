<div id="quote" class="row">
    <?php if(!empty($data)) : ?>
        <div class="col-12">
            <?=$this->render('_product', [
                'data' => $data,
            ])?>
        </div>
    <?php endif ?>
    <div class="col-12">
        <?=$this->render('_form', [
            'data' => $data,
            'model' => $model,
        ])?>
    </div>
</div>
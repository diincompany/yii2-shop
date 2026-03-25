<?php
use yii\bootstrap5\Html;
?>
<div class="card shadow-none mb-3">
    <div class="row g-0">
        <div class="col-md-4">
            <?php if(empty($data['productImages'])) : ?>
                <?=Html::img('https://ik.imagekit.io/ready/diin/img/site/placeholder.png', [
                    'class' => 'img-fluid rounded-start',
                ])?>
            <?php endif ?>
            <?php foreach($data['productImages'] as $img) : ?>
                <?php if($img['is_main']) : ?>
                    <?=Html::img($img['url'].'?tr=w-300,h-300', [
                        'class' => 'img-fluid rounded-start',
                    ])?>
                    <?php break ?>
                <?php endif ?>
            <?php endforeach ?>
        </div>
        <div class="col-md-8">
            <div class="card-body">
                <h5 class="card-title"><?=$data['name']?></h5>
                <p class="card-text text-truncate"><?=$data['short_description']?></p>
                <p class="m-0 small text-muted"><?=$data['code']?> | <?=$data['category']['name']?></p>
            </div>
        </div>
    </div>
</div>
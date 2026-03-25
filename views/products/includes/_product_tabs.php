<?php
/**
 * @var string $productDescription
 */
?>

<section class="pb-6 py-md-6 pb-lg-10 pt-lg-5">
    <div class="container">
        <div class="product-tabs">
            <ul class="nav product-nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="#" class="nav-link active" id="pd_description_tab" data-bs-toggle="tab" data-bs-target="#pd_description" role="tab" aria-controls="pd_description" aria-selected="true"><?= Yii::t('shop', 'Descripción') ?></a>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="pd_description" role="tabpanel" aria-labelledby="pd_description_tab">
                    <div class="row">
                        <div class="col-lg-12">
                            <h5><?= Yii::t('shop', 'Detalles del Producto') ?></h5>
                            <?= $productDescription ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

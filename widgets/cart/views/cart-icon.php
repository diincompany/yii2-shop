<a class="nav-link position-relative" data-bs-toggle="offcanvas" href="#modalMiniCart" role="button" aria-controls="modalMiniCart">
    <i class="fi-shopping-cart"></i>
    <?php if ($itemCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $itemCount ?>
            <span class="visually-hidden"><?= Yii::t('shop', 'items in cart') ?></span>
        </span>
    <?php endif; ?>
</a>
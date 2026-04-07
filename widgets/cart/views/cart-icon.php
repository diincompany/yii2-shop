<a class="nav-link position-relative" data-bs-toggle="offcanvas" href="#modalMiniCart" role="button" aria-controls="modalMiniCart">
    <span class="cart-icon-wrapper position-relative d-inline-block">
        <i class="bi bi-cart fs-3"></i>
            <span class="badge rounded-pill bg-danger cart-badge">
                <?= $itemCount ?>
                <span class="visually-hidden"><?= Yii::t('shop', 'items in cart') ?></span>
            </span>
    </span>
</a>
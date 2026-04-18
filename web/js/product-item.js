(function ($) {
    'use strict';

    function initProductGallery(scope) {
        if (typeof Swiper === 'undefined') {
            return;
        }

        var thumbGalleryEl = scope.querySelector('.swiper_thumb_gallery');
        var thumbGallery = null;

        if (thumbGalleryEl) {
            thumbGallery = new Swiper(thumbGalleryEl, {
                spaceBetween: 10,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true,
                navigation: {
                    nextEl: scope.querySelector('.swiper-next-pd-details_thumb'),
                    prevEl: scope.querySelector('.swiper-prev-pd-details_thumb')
                }
            });
        }

        var mainGalleryEl = scope.querySelector('.swiper_main_gallery');
        if (!mainGalleryEl) {
            return;
        }

        var config = {
            spaceBetween: 10
        };

        if (thumbGallery) {
            config.thumbs = {
                swiper: thumbGallery
            };
        }

        new Swiper(mainGalleryEl, config);
    }

    function initQuantityButtons(scope) {
        function normalizeQuantityInput($input) {
            var currentVal = parseInt($input.val(), 10);
            var rawMaxStock = $input.attr('data-max-stock');
            var maxStock = rawMaxStock === undefined || rawMaxStock === null || rawMaxStock === ''
                ? null
                : parseInt(rawMaxStock, 10);

            if (!Number.isFinite(currentVal) || currentVal < 1) {
                currentVal = 1;
            }

            if (Number.isFinite(maxStock) && maxStock > 0 && currentVal > maxStock) {
                currentVal = maxStock;
            }

            $input.val(currentVal);

            return {
                currentVal: currentVal,
                maxStock: Number.isFinite(maxStock) && maxStock > 0 ? maxStock : null
            };
        }

        $(scope).on('click.shopProductQty', '.qty_btn', function (event) {
            event.preventDefault();

            var $input = $(this).siblings('.cart-qty-input');
            var quantityState = normalizeQuantityInput($input);
            var currentVal = quantityState.currentVal;
            var maxStock = quantityState.maxStock;

            if ($(this).hasClass('inc') && (maxStock === null || currentVal < maxStock)) {
                $input.val(currentVal + 1).trigger('change');
                return;
            }

            if ($(this).hasClass('dec') && currentVal > 1) {
                $input.val(currentVal - 1).trigger('change');
            }
        });

        $(scope).on('change.shopProductQty blur.shopProductQty', '.cart-qty-input', function () {
            normalizeQuantityInput($(this));
        });
    }

    function initGalleryLightbox(scope) {
        if (typeof $.fn.magnificPopup === 'undefined') {
            return;
        }

        $(scope).find('.gallery-link').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            },
            image: {
                titleSrc: 'title'
            }
        });
    }

    $(function () {
        var scopes = document.querySelectorAll('.shop-module.shop-product-page');

        scopes.forEach(function (scope) {
            initProductGallery(scope);
            initQuantityButtons(scope);
            initGalleryLightbox(scope);
        });
    });
})(jQuery);

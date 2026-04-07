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
        $(scope).on('click.shopProductQty', '.qty_btn', function (event) {
            event.preventDefault();

            var $input = $(this).siblings('.cart-qty-input');
            var currentVal = parseInt($input.val(), 10) || 1;
            var maxStock = parseInt($input.data('max-stock'), 10) || 999;

            if ($(this).hasClass('inc') && currentVal < maxStock) {
                $input.val(currentVal + 1).trigger('change');
                return;
            }

            if ($(this).hasClass('dec') && currentVal > 1) {
                $input.val(currentVal - 1).trigger('change');
            }
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

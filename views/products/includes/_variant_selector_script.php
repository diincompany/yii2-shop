<?php
/**
 * @var yii\web\View $this
 * @var array $variantsForJs
 */

if (empty($variantsForJs)) {
    return;
}

$variantsJson = json_encode($variantsForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$availableTextJson = json_encode(Yii::t('shop', 'Disponible'));
$outOfStockTextJson = json_encode(Yii::t('shop', 'Sin Existencia'));

$this->registerJs(<<<JS
(function () {
    var variants = $variantsJson || [];
    var variantRadios = document.querySelectorAll('.product-variant-radio');
    var stockBadge = document.getElementById('product-stock-badge');
    var priceDisplay = document.getElementById('product-price-display');
    var qtyInput = document.querySelector('.product-detail-actions .cart-qty-input');
    var addButton = document.querySelector('.product-detail-actions .add-to-cart-btn');
    var availableText = $availableTextJson;
    var outOfStockText = $outOfStockTextJson;

    if (!variantRadios.length || !variants.length || !stockBadge || !priceDisplay) {
        return;
    }

    function findVariantById(variantId) {
        var selectedVariantId = String(variantId || '').trim();

        return variants.find(function (item) {
            return String(item.id || '').trim() === selectedVariantId;
        }) || null;
    }

    function getSelectedVariantId() {
        var checkedRadio = document.querySelector('.product-variant-radio:checked');

        return checkedRadio ? checkedRadio.value : null;
    }

    function splitPrice(value) {
        var amount = Number(value || 0);
        if (Number.isNaN(amount)) {
            amount = 0;
        }

        var parts = amount.toFixed(2).split('.');
        return { whole: parts[0], decimal: parts[1] };
    }

    function renderPrice(variant) {
        var basePrice = Number(variant.price || 0);
        var salePrice = Number(variant.sale_price || 0);
        var base = splitPrice(basePrice);

        if (salePrice > 0) {
            var sale = splitPrice(salePrice);
            priceDisplay.innerHTML = '<del class="text-muted fs-6">L' + base.whole + '.<small>' + base.decimal + '</small></del>' +
                '<span class="text-primary">L' + sale.whole + '.<small>' + sale.decimal + '</small></span>';
            return;
        }

        priceDisplay.innerHTML = '<span class="text-primary">L' + base.whole + '.<small>' + base.decimal + '</small></span>';
    }

    function applyVariant(variantId) {
        var variant = findVariantById(variantId);

        if (!variant) {
            return;
        }

        renderPrice(variant);

        var trackStock = Number(variant.track_stock || 0) === 1;
        var selectable = Number(variant.is_selectable || 0) === 1;
        var stock = Number(variant.stock || 0);
        var hasStock = !trackStock || (selectable && stock > 0);

        stockBadge.textContent = hasStock ? availableText : outOfStockText;
        stockBadge.classList.remove('bg-success', 'bg-danger');
        stockBadge.classList.add(hasStock ? 'bg-success' : 'bg-danger');

        if (qtyInput) {
            qtyInput.setAttribute('data-max-stock', String(Math.max(stock, 1)));
            qtyInput.disabled = !hasStock;

            if (!hasStock) {
                qtyInput.value = '1';
            } else {
                var current = Number(qtyInput.value || 1);
                if (current < 1) {
                    qtyInput.value = '1';
                } else if (current > stock) {
                    qtyInput.value = String(stock);
                }
            }
        }

        if (addButton) {
            addButton.disabled = !hasStock;
        }
    }

    variantRadios.forEach(function (radio) {
        var variant = findVariantById(radio.value);
        if (!variant) {
            return;
        }

        var trackStock = Number(variant.track_stock || 0) === 1;
        var selectable = Number(variant.is_selectable || 0) === 1;
        if (trackStock && !selectable) {
            radio.disabled = true;

            var label = document.querySelector('label[for="' + radio.id + '"]');
            if (label) {
                label.classList.add('disabled');
                label.setAttribute('aria-disabled', 'true');
                label.setAttribute('title', outOfStockText);
            }

            if (radio.checked) {
                radio.checked = false;
            }
        }
    });

    if (!document.querySelector('.product-variant-radio:checked')) {
        var firstEnabledRadio = Array.prototype.find.call(variantRadios, function (radio) {
            return !radio.disabled;
        });

        if (firstEnabledRadio) {
            firstEnabledRadio.checked = true;
        }
    }

    var initialVariantId = getSelectedVariantId();
    if (initialVariantId !== null) {
        applyVariant(initialVariantId);
    }

    variantRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (this.checked) {
                applyVariant(this.value);
            }
        });
    });
})();
JS);

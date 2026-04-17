<?php
/**
 * @var yii\web\View $this
 * @var array $variantsForJs
 * @var array $defaultVariantOptions
 */

if (empty($variantsForJs)) {
    return;
}

$variantsJson = json_encode($variantsForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$defaultVariantOptionsJson = json_encode($defaultVariantOptions ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$availableTextJson = json_encode(Yii::t('shop', 'Disponible'));
$outOfStockTextJson = json_encode(Yii::t('shop', 'Sin Existencia'));

$this->registerJs(<<<JS
(function () {
    var variants = $variantsJson || [];
    var defaultVariantOptions = $defaultVariantOptionsJson || {};
    var variantRadios = document.querySelectorAll('.product-variant-radio');
    var optionButtons = document.querySelectorAll('.product-option-button');
    var optionGroups = document.querySelectorAll('.product-option-group');
    var stockBadge = document.getElementById('product-stock-badge');
    var priceDisplay = document.getElementById('product-price-display');
    var qtyInput = document.querySelector('.product-detail-actions .cart-qty-input');
    var addButton = document.querySelector('.product-detail-actions .add-to-cart-btn');
    var availableText = $availableTextJson;
    var outOfStockText = $outOfStockTextJson;
    var selectedOptions = {};

    if (!variantRadios.length || !variants.length || !stockBadge || !priceDisplay) {
        return;
    }

    function findVariantById(variantId) {
        var selectedVariantId = String(variantId || '').trim();

        return variants.find(function (item) {
            return String(item.id || '').trim() === selectedVariantId;
        }) || null;
    }

    function getOptionGroupKeys() {
        return Array.prototype.map.call(optionGroups, function (group) {
            return String(group.getAttribute('data-option-key') || '').trim();
        }).filter(Boolean);
    }

    function getSelectableVariants() {
        return variants.filter(function (variant) {
            return Number(variant.is_selectable || 0) === 1;
        });
    }

    function variantMatchesSelection(variant, selection) {
        var variantOptions = variant && variant.options && typeof variant.options === 'object' ? variant.options : {};

        return Object.keys(selection).every(function (key) {
            return !selection[key] || variantOptions[key] === selection[key];
        });
    }

    function findMatchingVariant(selection) {
        var requiredKeys = getOptionGroupKeys();

        if (!requiredKeys.length) {
            return findVariantById(getSelectedVariantId());
        }

        var hasCompleteSelection = requiredKeys.every(function (key) {
            return Boolean(selection[key]);
        });

        if (!hasCompleteSelection) {
            return null;
        }

        return getSelectableVariants().find(function (variant) {
            return variantMatchesSelection(variant, selection);
        }) || null;
    }

    function hasSelectableVariant(selection) {
        return getSelectableVariants().some(function (variant) {
            return variantMatchesSelection(variant, selection);
        });
    }

    function getSelectionWithoutKey(selection, excludedKey) {
        var nextSelection = {};

        Object.keys(selection).forEach(function (key) {
            if (key !== excludedKey && selection[key]) {
                nextSelection[key] = selection[key];
            }
        });

        return nextSelection;
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

    function syncRadioSelection(variantId) {
        variantRadios.forEach(function (radio) {
            radio.checked = String(radio.value || '').trim() === String(variantId || '').trim();
        });
    }

    function markButtonSelection() {
        optionButtons.forEach(function (button) {
            var optionKey = String(button.getAttribute('data-option-key') || '').trim();
            var optionValue = String(button.getAttribute('data-option-value') || '').trim();
            var isSelected = selectedOptions[optionKey] === optionValue;

            button.classList.toggle('active', isSelected);
            button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    }

    function refreshOptionAvailability() {
        optionGroups.forEach(function (group) {
            var groupKey = String(group.getAttribute('data-option-key') || '').trim();
            var buttons = group.querySelectorAll('.product-option-button');

            buttons.forEach(function (button) {
                var optionValue = String(button.getAttribute('data-option-value') || '').trim();
                var probeSelection = getSelectionWithoutKey(selectedOptions, groupKey);
                probeSelection[groupKey] = optionValue;
                var isAvailable = hasSelectableVariant(probeSelection);

                button.disabled = !isAvailable;
                button.classList.toggle('disabled', !isAvailable);
                button.setAttribute('aria-disabled', !isAvailable ? 'true' : 'false');
                button.setAttribute('title', !isAvailable ? outOfStockText : '');
            });
        });
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

    function applySelectionState(lastChangedKey) {
        if (optionButtons.length) {
            refreshOptionAvailability();
            markButtonSelection();
        }

        var matchedVariant = optionButtons.length
            ? findMatchingVariant(selectedOptions)
            : findVariantById(getSelectedVariantId());

        if (matchedVariant) {
            syncRadioSelection(matchedVariant.id);
            applyVariant(matchedVariant.id);
            return;
        }

        syncRadioSelection('');

        if (addButton) {
            addButton.disabled = true;
        }

        if (qtyInput) {
            qtyInput.disabled = true;
            qtyInput.value = '1';
            qtyInput.setAttribute('data-max-stock', '1');
        }

        stockBadge.textContent = outOfStockText;
        stockBadge.classList.remove('bg-success');
        stockBadge.classList.add('bg-danger');
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

    if (optionButtons.length) {
        Object.keys(defaultVariantOptions).forEach(function (key) {
            if (defaultVariantOptions[key]) {
                selectedOptions[key] = defaultVariantOptions[key];
            }
        });

        if (!Object.keys(selectedOptions).length) {
            var firstSelectableVariant = getSelectableVariants()[0] || null;
            if (firstSelectableVariant && firstSelectableVariant.options) {
                selectedOptions = Object.assign({}, firstSelectableVariant.options);
            }
        }

        optionButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.disabled) {
                    return;
                }

                var optionKey = String(button.getAttribute('data-option-key') || '').trim();
                var optionValue = String(button.getAttribute('data-option-value') || '').trim();

                if (!optionKey || !optionValue) {
                    return;
                }

                selectedOptions[optionKey] = optionValue;
                applySelectionState(optionKey);
            });
        });

        applySelectionState('');
        return;
    }

    var initialVariantId = getSelectedVariantId();
    if (initialVariantId === null) {
        var firstEnabledRadio = Array.prototype.find.call(variantRadios, function (radio) {
            return !radio.disabled;
        });

        if (firstEnabledRadio) {
            firstEnabledRadio.checked = true;
            initialVariantId = firstEnabledRadio.value;
        }
    }

    if (initialVariantId !== null) {
        applySelectionState('');
    }

    variantRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (this.checked) {
                applySelectionState('');
            }
        });
    });
})();
JS);

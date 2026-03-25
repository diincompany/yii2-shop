<?php
/**
 * Checkout page
 * @var yii\web\View $this
 * @var array $items
 * @var float $subtotal
 * @var float $taxes
 * @var float $discountAmount
 * @var float $shipping
 * @var float $grandTotal
 * @var string $couponCode
 * @var array $persistedShipping
 * @var string $shippingServiceLevel
 * @var array $countries
 * @var array $shippingAddress
 * @var array $billingAddress
 * @var string $orderNotes
 * @var string|null $error
 */

use yii\helpers\Url;

$this->title = Yii::t('shop', 'checkout');
$module = Yii::$app->controller->module;
$breadcrumbsView = is_object($module) && method_exists($module, 'getBreadcrumbsView')
    ? $module->getBreadcrumbsView()
    : null;
$moduleRoute = '/' . trim((string) ($module->id ?? 'shop'), '/');

$this->params['breadcrumbs'][] = ['label' => Yii::t('shop', 'Cart'), 'url' => [$moduleRoute . '/cart']];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if (!empty($error)): ?>
    <?= $this->render('includes/checkout/error-alert.php', [
        'error' => $error,
        'moduleRoute' => $moduleRoute,
    ]) ?>
<?php endif; ?>

<?php if (empty($error)): ?>
    <?php if (!empty($items)): ?>
        <div class="py-6">
            <div class="container">
                <div class="row flex-row-reverse">
                    <div class="col-lg-5 ps-lg-5">
                        <?= $this->render('includes/checkout/order-summary.php', [
                            'items' => $items,
                            'subtotal' => $subtotal,
                            'taxes' => $taxes,
                            'discountAmount' => $discountAmount,
                            'shipping' => $shipping,
                            'grandTotal' => $grandTotal,
                            'couponCode' => $couponCode,
                            'moduleRoute' => $moduleRoute,
                        ]) ?>
                    </div>

                    <div class="col-lg-7">
                        <?= $this->render('includes/checkout/form.php', [
                            'countries' => $countries,
                            'shippingAddress' => $shippingAddress ?? [],
                            'billingAddress' => $billingAddress ?? [],
                            'orderNotes' => $orderNotes ?? '',
                            'shippingServiceLevel' => $shippingServiceLevel ?? '',
                            'shipping' => $shipping ?? 0,
                            'moduleRoute' => $moduleRoute,
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?= $this->render('includes/checkout/empty-cart.php', [
            'moduleRoute' => $moduleRoute,
        ]) ?>
    <?php endif; ?>
<?php endif; ?>

<?php if (empty($error) && !empty($items)): ?>
<?php
// Register custom JS for checkout functionality
$processCheckoutUrl = Url::to([$moduleRoute . '/default/process-checkout']);
$removeItemUrl = Url::to([$moduleRoute . '/cart/remove-item']);
$getShippingOptionsUrl = Url::to([$moduleRoute . '/get-shipping-options']);
$calculateShippingUrl = Url::to([$moduleRoute . '/calculate-shipping']);
$processCheckoutUrlJson = json_encode($processCheckoutUrl);
$removeItemUrlJson = json_encode($removeItemUrl);
$getShippingOptionsUrlJson = json_encode($getShippingOptionsUrl);
$calculateShippingUrlJson = json_encode($calculateShippingUrl);
$errorProcessingCheckoutJson = json_encode(Yii::t('shop', 'error_processing_checkout'));
$confirmRemoveItemJson = json_encode(Yii::t('shop', 'confirm_remove_item'));
$daysTextJson = json_encode(Yii::t('shop', 'days'));
$discountTextJson = json_encode(Yii::t('shop', 'Discount'));
$noShippingOptionsFallbackTextJson = json_encode(Yii::t('shop', 'shipping_options_unavailable_fallback'));
$initialServiceLevelJson = json_encode((string) ($shippingServiceLevel ?? ''));
$initialShippingOptionJson = json_encode((array) ($persistedShipping ?? []));

$this->registerJs(<<<JS
    jQuery(function($) {
        var processCheckoutUrl = $processCheckoutUrlJson;
        var removeItemUrl = $removeItemUrlJson;
        var getShippingOptionsUrl = $getShippingOptionsUrlJson;
        var calculateShippingUrl = $calculateShippingUrlJson;
        var csrfParam = $('meta[name="csrf-param"]').attr('content');
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var daysText = $daysTextJson;
        var discountText = $discountTextJson;
        var noShippingOptionsFallbackText = $noShippingOptionsFallbackTextJson;
        var initialServiceLevel = ($initialServiceLevelJson || '').toString().trim();
        var initialShippingOption = $initialShippingOptionJson || {};

        function updateCheckoutTotals(order) {
            if (!order) {
                return;
            }

            var normalized = order.order || order;
            var discount = parseFloat(normalized.discount_amount || 0);
            var couponCode = (normalized.coupon_code || (normalized.coupon && normalized.coupon.code) || '').toString().trim();
            var discountLabel = couponCode ? discountText + ' (' + couponCode + ')' : discountText;

            $('#checkout-subtotal').text('L' + parseFloat(normalized.subtotal_amount || 0).toFixed(2));
            $('#checkout-taxes').text('L' + parseFloat(normalized.tax_amount || 0).toFixed(2));
            $('#checkout-discount-label').text(discountLabel);
            $('#checkout-discount').text((discount > 0 ? '-L' : 'L') + Math.abs(discount).toFixed(2));
            $('#checkout-shipping').text('L' + parseFloat(normalized.shipping_amount || 0).toFixed(2));
            $('#checkout-grand-total').text('L' + parseFloat(normalized.total_amount || 0).toFixed(2));
        }

        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('cartDataUpdated', function(e, cartData) {
                updateCheckoutTotals(cartData || null);
            });
        }

        function parseCurrencyAmount(rawValue) {
            var normalized = (rawValue || '').toString().replace(/[^0-9.-]/g, '');
            var parsed = parseFloat(normalized);

            return Number.isFinite(parsed) ? parsed : 0;
        }

        function updateGrandTotalFromSummary() {
            var subtotal = parseCurrencyAmount($('#checkout-subtotal').text());
            var taxes = parseCurrencyAmount($('#checkout-taxes').text());
            var shipping = parseCurrencyAmount($('#checkout-shipping').text());
            var discount = parseCurrencyAmount($('#checkout-discount').text());
            var total = subtotal + taxes + shipping + discount;

            $('#checkout-grand-total').text('L' + total.toFixed(2));
        }

        function applyNoShippingFallback(messageText) {
            var fallbackMessage = (messageText || noShippingOptionsFallbackText || '').toString();

            $('#shipping-options-loader').addClass('d-none');
            $('#shipping-options-section').removeClass('d-none');
            $('#shipping-options-container').html('<div class="alert alert-warning mb-0">' + fallbackMessage + '</div>');
            $('#checkout-shipping').text('L0.00');
            $('[name="shipping_cost"]').val('0');
            $('[name="service_level"]').val('');
            $('#place-order-btn').prop('disabled', false);

            updateGrandTotalFromSummary();
        }

        function syncShippingToBilling() {
            // Always keep billing in sync with shipping when not using different address
            var checked = $('#use_billing_address').is(':checked');
            
            if (!checked) {
                // Sync billing with shipping
                $('[name="billing_first_name"]').val($('[name="first_name"]').val());
                $('[name="billing_last_name"]').val($('[name="last_name"]').val());
                $('[name="billing_email"]').val($('[name="email"]').val());
                $('[name="billing_phone"]').val($('[name="phone"]').val());
                $('[name="billing_street"]').val($('[name="address_1"]').val());
                $('[name="billing_zip"]').val($('[name="zip"]').val());
                $('[name="billing_country_id"]').val($('[name="country_id"]').val());
                $('[name="billing_state_id"]').val($('[name="state_id"]').val());
                $('[name="billing_city_id"]').val($('[name="city_id"]').val());
            }
        }

        function toggleBillingAddressFields() {
            var checked = $('#use_billing_address').is(':checked');
            var container = $('#billing-address-fields');
            var requiredFields = [
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_phone',
                'billing_street',
                'billing_city_id',
                'billing_state_id',
                'billing_country_id'
            ];

            if (checked) {
                // Show fields when user wants different address
                container.removeClass('d-none');
                requiredFields.forEach(function(fieldName) {
                    $('[name="' + fieldName + '"]').prop('required', true);
                });
            } else {
                // Hide fields and sync with shipping
                container.addClass('d-none');
                syncShippingToBilling();
                requiredFields.forEach(function(fieldName) {
                    $('[name="' + fieldName + '"]').prop('required', false);
                });
            }
        }

        function calculateShipping() {
            var countryId = $('[name="country_id"]').val();
            var stateId = $('[name="state_id"]').val();
            var cityId = $('[name="city_id"]').val();

            console.log('calculateShipping called with address:', {countryId, stateId, cityId});

            // Don't calculate if incomplete address
            if (!countryId || !stateId || !cityId) {
                console.log('Address incomplete, skipping shipping options');
                return;
            }

            // First, get shipping options
            getShippingOptions(countryId, stateId, cityId);
        }

        function getShippingOptions(countryId, stateId, cityId) {
            var payload = {
                country_id: countryId,
                state_id: stateId,
                city_id: cityId
            };

            if (csrfParam && csrfToken) {
                payload[csrfParam] = csrfToken;
            }

            console.log('getShippingOptions called with:', payload);

            // Show loader and disable submit button
            $('#shipping-options-section').removeClass('d-none');
            $('#shipping-options-loader').removeClass('d-none');
            $('#shipping-options-container').empty();
            $('#place-order-btn').prop('disabled', true);

            var ajaxSettings = {
                url: getShippingOptionsUrl,
                type: 'POST',
                data: payload,
                dataType: 'json',
            };

            $.ajax(ajaxSettings)
                .done(function(response) {
                    console.log('get-shipping-options response:', response);
                    var data = (response && response.data) ? response.data : {};
                    var options = [];
                    var noShippingAvailable = Boolean(data.no_shipping_available);

                    console.log('Raw options data:', data);

                    if (Array.isArray(data.options)) {
                        options = data.options;
                    } else if (data.shipping && !Array.isArray(data.shipping) && data.shipping.service_level) {
                        options = [data.shipping];
                    } else if (data.shipping && Array.isArray(data.shipping.options)) {
                        options = data.shipping.options;
                    } else if (Array.isArray(data)) {
                        options = data;
                    }

                    if (response.success) {
                        if (options.length > 0) {
                            displayShippingOptions(options, data.currency);
                        } else if (!noShippingAvailable && initialShippingOption && initialShippingOption.service_level) {
                            displayShippingOptions([initialShippingOption], data.currency || 'HNL');
                        } else {
                            applyNoShippingFallback(response.message);
                        }
                    } else {
                        $('#shipping-options-container').html('<div class="alert alert-danger mb-0">' + (response.message || 'No se pudieron obtener las opciones de envío') + '</div>');
                        $('#shipping-options-section').removeClass('d-none');
                        $('#place-order-btn').prop('disabled', false);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error getting shipping options:', {status, error, response: xhr.responseText});
                    $('#shipping-options-container').html('<div class="alert alert-danger mb-0">No se pudieron obtener las opciones de envío</div>');
                    $('#shipping-options-section').removeClass('d-none');
                    $('#place-order-btn').prop('disabled', false);
                });
        }

        function displayShippingOptions(options, currency) {
            console.log('displayShippingOptions called with', options.length, 'options and currency:', currency);
            console.log('Full options data:', JSON.stringify(options, null, 2));
            
            var container = $('#shipping-options-container');
            container.empty();

            // Hide loader and enable submit button
            $('#shipping-options-loader').addClass('d-none');
            $('#place-order-btn').prop('disabled', false);

            if (!options || options.length === 0) {
                $('#shipping-options-section').addClass('d-none');
                return;
            }

            var html = '<div class="btn-group-vertical w-100" role="group">';
            var selectedIndex = 0;

            if (initialServiceLevel) {
                var matchedIndex = options.findIndex(function(option) {
                    return ((option.service_level || '').toString().trim() === initialServiceLevel);
                });

                if (matchedIndex >= 0) {
                    selectedIndex = matchedIndex;
                }
            }

            options.forEach(function(option, index) {
                console.log('Processing option ' + index + ':', {
                    service_level: option.service_level,
                    shipping_cost: option.shipping_cost,
                    free_shipping: option.free_shipping
                });
                
                var isSelected = index === selectedIndex ? ' checked' : '';
                var daysDisplay = '';
                if (option.estimated_days_min && option.estimated_days_max) {
                    daysDisplay = ' (' + option.estimated_days_min + '-' + option.estimated_days_max + ' ' + daysText + ')';
                }
                
                var costText = option.free_shipping ? 'Gratis' : 'L' + parseFloat(option.shipping_cost).toFixed(2);
                
                html += '<input type="radio" class="btn-check shipping-option" name="shipping_option" value="' + option.service_level + '" id="shipping_' + index + '"' + isSelected + ' data-service-level="' + option.service_level + '" data-shipping-cost="' + option.shipping_cost + '">';
                html += '<label class="btn btn-outline-primary w-100 text-start" for="shipping_' + index + '">';
                html += '  ' + option.service_level.charAt(0).toUpperCase() + option.service_level.slice(1) + ' ' + daysDisplay + ' - ' + costText;
                html += '</label>';
            });

            html += '</div>';
            
            container.html(html);
            $('#shipping-options-section').removeClass('d-none');

            // Bind change event to shipping options
            $(document).off('change', '.shipping-option').on('change', '.shipping-option', function() {
                var serviceLevel = $(this).data('service-level');
                var shippingCost = $(this).data('shipping-cost');
                console.log('Shipping option changed to:', {serviceLevel, shippingCost});
                selectShippingOption(serviceLevel, shippingCost);
            });

            // Auto-select first option
            if (options.length > 0) {
                var selectedOption = options[selectedIndex] || options[0];
                console.log('Auto-selecting shipping option:', selectedOption);
                selectShippingOption(selectedOption.service_level, selectedOption.shipping_cost);
            }

            initialServiceLevel = '';
        }

        function selectShippingOption(serviceLevel, shippingCost) {
            // Update the display
            console.log('selectShippingOption called with:', {serviceLevel, shippingCost});
            
            $('#checkout-shipping').text('L' + parseFloat(shippingCost).toFixed(2));
            $('[name="shipping_cost"]').val(shippingCost);
            $('[name="service_level"]').val(serviceLevel);

            // Verify values were set
            console.log('Form values after update:', {
                service_level: $('[name="service_level"]').val(),
                shipping_cost: $('[name="shipping_cost"]').val(),
            });

            // Recalculate with the selected shipping option
            var countryId = $('[name="country_id"]').val();
            var stateId = $('[name="state_id"]').val();
            var cityId = $('[name="city_id"]').val();

            console.log('Address data:', {countryId, stateId, cityId});

            var payload = {
                country_id: countryId,
                state_id: stateId,
                city_id: cityId,
                service_level: serviceLevel
            };

            if (csrfParam && csrfToken) {
                payload[csrfParam] = csrfToken;
            }

            console.log('Sending payload to shipping endpoint:', payload);

            var ajaxSettings = {
                url: calculateShippingUrl,
                type: 'POST',
                data: payload,
                dataType: 'json',
            };

            $('#place-order-btn').prop('disabled', true);
            $.ajax(ajaxSettings)
                .always(function() {
                    $('#place-order-btn').prop('disabled', false);
                })
                .done(function(response) {
                    console.log('Calculate shipping response:', response);
                    if (response.success && response.data) {
                        // Accept both payloads: new shape `data=<order>` and legacy `data.order=<order>`.
                        var order = response.data.order || response.data;

                        if (!order || typeof order !== 'object') {
                            return;
                        }

                        console.log('Updating totals from order:', {
                            subtotal: order.subtotal_amount,
                            shipping: order.shipping_amount,
                            tax: order.tax_amount,
                            discount: order.discount_amount,
                            total: order.total_amount
                        });
                        
                        updateCheckoutTotals(order);

                        if (typeof jQuery !== 'undefined') {
                            jQuery(document).trigger('cartDataUpdated', [order]);
                        }
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error calculating shipping:', {status, error, response: xhr.responseText});
                    $('#place-order-btn').prop('disabled', false);
                })
                .always(function() {
                    // Note: button may be disabled by fail() if error occurs
                });
        }

        // Sync billing whenever shipping fields change and calculate shipping
        $(document).on('change', '[name="first_name"], [name="last_name"], [name="email"], [name="phone"], [name="address_1"], [name="zip"], [name="country_id"], [name="state_id"], [name="city_id"]', function() {
            syncShippingToBilling();
            // Calculate shipping when address changes
            if ($(this).attr('name').match(/country_id|state_id|city_id/)) {
                calculateShipping();
            }
        });

        $('#use_billing_address').on('change', toggleBillingAddressFields);
        toggleBillingAddressFields();

        setTimeout(function() {
            calculateShipping();
        }, 900);

        $(document).on('click', '#place-order-btn', function(e) {
            e.preventDefault();

            var form = $('#checkout-form');

            if (!form.length) {
                alert($errorProcessingCheckoutJson);
                return;
            }

            if (form[0] && !form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            // Ensure billing address is synced with shipping before submitting
            syncShippingToBilling();

            var btn = $(this);
            var originalLabel = btn.html();
            btn.prop('disabled', true);

            var formData = form.serialize();

            $.ajax({
                url: processCheckoutUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message || 'OK');
                            btn.prop('disabled', false).html(originalLabel);
                        }
                    } else {
                        alert(response.message || $errorProcessingCheckoutJson);
                        btn.prop('disabled', false).html(originalLabel);
                    }
                },
                error: function() {
                    alert($errorProcessingCheckoutJson);
                    btn.prop('disabled', false).html(originalLabel);
                }
            });
        });

        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();

            var itemId = $(this).data('item-id');
            if (!itemId) {
                return;
            }

            if (confirm($confirmRemoveItemJson)) {
                var payload = {
                    item_id: itemId
                };

                var ajaxSettingsRemove = {
                    url: removeItemUrl,
                    type: 'POST',
                    data: payload,
                    dataType: 'json'
                };

                // Add CSRF token as request header for AJAX
                if (csrfParam && csrfToken) {
                    ajaxSettingsRemove.headers = {};
                    ajaxSettingsRemove.headers[csrfParam] = csrfToken;
                }

                $.ajax(ajaxSettingsRemove)
                    .done(function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.message || $errorProcessingCheckoutJson);
                        }
                    })
                    .fail(function() {
                        alert($errorProcessingCheckoutJson);
                    });
            }
        });
    });
JS);
?>
<?php endif; ?>

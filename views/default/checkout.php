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

use yii\helpers\Json;
use yii\helpers\Url;
use diincompany\shop\assets\ShopAsset;

$this->title = Yii::t('shop', 'checkout');
ShopAsset::register($this);
$module = Yii::$app->controller->module;
$breadcrumbsView = is_object($module) && method_exists($module, 'getBreadcrumbsView')
    ? $module->getBreadcrumbsView()
    : null;
$moduleRoute = '/' . trim((string) ($module->id ?? 'shop'), '/');

$gaTrackerClass = 'diincompany\\yii2googleanalytics\\EcommerceTracker';
$gaItems = [];

if (class_exists($gaTrackerClass) && !empty($items)) {
    foreach ($items as $item) {
        $gaItems[] = $gaTrackerClass::buildItem(
            (string) ($item['sku'] ?? $item['product_id'] ?? ''),
            (string) ($item['product_name'] ?? ''),
            (float) ($item['price_amount'] ?? $item['price'] ?? 0),
            max(1, (int) ($item['quantity'] ?? 1)),
            (string) ($item['category_name'] ?? ''),
            'StreetID',
            (string) ($item['variant_name'] ?? $item['product_variant_name'] ?? '')
        );
    }
}

$this->params['breadcrumbs'][] = ['label' => Yii::t('shop', 'Cart'), 'url' => [$moduleRoute . '/cart']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shop-module shop-checkout-page">
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
</div>

<?php if (empty($error) && !empty($items)): ?>
<?php
$this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [
    'integrity' => 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=',
    'crossorigin' => 'anonymous',
]);
$this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [
    'position' => \yii\web\View::POS_HEAD,
    'integrity' => 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=',
    'crossorigin' => 'anonymous',
]);

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
$codAvailableTextJson = json_encode(Yii::t('shop', 'cash_on_delivery_available'));
$pickupTextJson = json_encode(Yii::t('shop', 'Pickup'));
$pickupAddressTextJson = json_encode(Yii::t('shop', 'Pickup Address'));
$warehouseTextJson = json_encode(Yii::t('shop', 'Warehouse'));
$initialServiceLevelJson = json_encode((string) ($shippingServiceLevel ?? ''));
$initialProviderCodeJson = json_encode((string) (($persistedShipping['provider_code'] ?? '')));
$initialShippingOptionJson = json_encode((array) ($persistedShipping ?? []));
$initialSelectedOptionJson = json_encode((array) (($persistedShipping['selected_option'] ?? [])));
$gaCheckoutItemsJson = Json::htmlEncode($gaItems);

if (class_exists($gaTrackerClass) && !empty($gaItems)) {
    $this->registerJs(
        'if (typeof window.gtag === "function") { ' . $gaTrackerClass::beginCheckoutJs($gaItems, (float) $grandTotal, (string) ($couponCode ?? ''), 'HNL') . ' }',
        \yii\web\View::POS_END,
        'shop-ga4-begin-checkout'
    );
}

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
        var codAvailableText = $codAvailableTextJson;
        var pickupText = $pickupTextJson;
        var pickupAddressText = $pickupAddressTextJson;
        var warehouseText = $warehouseTextJson;
        var uiLang = (($('html').attr('lang') || 'es').toString().toLowerCase().indexOf('en') === 0) ? 'en' : 'es';
        var initialServiceLevel = ($initialServiceLevelJson || '').toString().trim();
        var initialProviderCode = ($initialProviderCodeJson || '').toString().trim();
        var initialShippingOption = $initialShippingOptionJson || {};
        var initialSelectedOption = $initialSelectedOptionJson || {};
        var gaCheckoutItems = $gaCheckoutItemsJson || [];
        var shippingOptionsRequestSeq = 0;

        if (initialServiceLevel !== '') {
            $('[name="service_level"]').val(initialServiceLevel);
        }

        if (initialProviderCode !== '') {
            $('[name="provider_code"]').val(initialProviderCode);
        }

        if (initialSelectedOption && Object.keys(initialSelectedOption).length > 0) {
            $('[name="selected_option"]').val(JSON.stringify(initialSelectedOption));
        }

        function getCurrentShippingSelection() {
            var selectedOptionRaw = $.trim(($('[name="selected_option"]').val() || '').toString());
            var parsedSelectedOption = null;

            if (selectedOptionRaw !== '') {
                try {
                    parsedSelectedOption = JSON.parse(selectedOptionRaw);
                } catch (error) {
                    console.warn('Unable to parse current selected_option payload', error);
                }
            }

            return {
                provider_code: $.trim(($('[name="provider_code"]').val() || '').toString()),
                service_level: $.trim(($('[name="service_level"]').val() || '').toString()),
                selected_option: parsedSelectedOption
            };
        }

        function matchesSelectedCourier(optionPayload, currentSelection) {
            if (!currentSelection || !currentSelection.selected_option || typeof currentSelection.selected_option !== 'object') {
                return false;
            }

            var currentCourier = currentSelection.selected_option.selected_option || currentSelection.selected_option;
            var optionCourier = optionPayload && optionPayload.selected_option && typeof optionPayload.selected_option === 'object'
                ? optionPayload.selected_option
                : {};
            var currentCourierId = (currentCourier.id || currentCourier.courier_id || '').toString().trim();
            var optionCourierId = (optionCourier.id || optionCourier.courier_id || '').toString().trim();

            if (currentCourierId !== '' && optionCourierId !== '') {
                return currentCourierId === optionCourierId;
            }

            var currentCourierName = (currentCourier.name || currentCourier.courier_name || '').toString().trim().toLowerCase();
            var optionCourierName = (optionCourier.name || optionCourier.courier_name || '').toString().trim().toLowerCase();
            var currentDeliveryType = (currentCourier.delivery_type || currentCourier.courier_delivery_type || '').toString().trim().toLowerCase();
            var optionDeliveryType = (optionCourier.delivery_type || optionCourier.courier_delivery_type || '').toString().trim().toLowerCase();

            return currentCourierName !== ''
                && optionCourierName !== ''
                && currentCourierName === optionCourierName
                && currentDeliveryType === optionDeliveryType;
        }

        function isPickupOptionPayload(optionPayload) {
            if (!optionPayload || typeof optionPayload !== 'object') {
                return false;
            }

            var selectedOption = optionPayload.selected_option && typeof optionPayload.selected_option === 'object'
                ? optionPayload.selected_option
                : optionPayload;
            var deliveryType = (selectedOption.delivery_type || selectedOption.courier_delivery_type || optionPayload.courier_delivery_type || '').toString().trim().toLowerCase();
            var deliveryTypeKey = (selectedOption.delivery_type_key || optionPayload.courier_delivery_type_key || '').toString().trim().toLowerCase();

            return deliveryType === 'pickup' || deliveryTypeKey === 'pickup';
        }

        function getOptionWarehouse(optionPayload) {
            if (!optionPayload || typeof optionPayload !== 'object') {
                return null;
            }

            if (optionPayload.warehouse && typeof optionPayload.warehouse === 'object') {
                return optionPayload.warehouse;
            }

            if (optionPayload.selected_option && typeof optionPayload.selected_option === 'object' && optionPayload.selected_option.warehouse && typeof optionPayload.selected_option.warehouse === 'object') {
                return optionPayload.selected_option.warehouse;
            }

            var warehouseId = optionPayload.warehouse_id || (optionPayload.selected_option && optionPayload.selected_option.warehouse_id) || null;
            var warehouseName = optionPayload.warehouse_name || (optionPayload.selected_option && optionPayload.selected_option.warehouse_name) || null;

            if (!warehouseId && !warehouseName) {
                return null;
            }

            return {
                id: warehouseId || null,
                name: warehouseName || null,
                geolocation: null
            };
        }

        function getWarehouseAddressText(warehouse) {
            if (!warehouse || typeof warehouse !== 'object') {
                return '';
            }

            return [warehouse.address_1 || '', warehouse.zipcode || '']
                .filter(function(part) { return $.trim((part || '').toString()) !== ''; })
                .join(', ');
        }

        function getWarehouseGeolocation(warehouse) {
            if (!warehouse || typeof warehouse !== 'object') {
                return null;
            }

            var geolocation = warehouse.geolocation && typeof warehouse.geolocation === 'object'
                ? warehouse.geolocation
                : warehouse;
            var latitude = geolocation.latitude;
            var longitude = geolocation.longitude;

            if (latitude === null || typeof latitude === 'undefined' || latitude === '' || longitude === null || typeof longitude === 'undefined' || longitude === '') {
                return null;
            }

            return {
                latitude: latitude,
                longitude: longitude
            };
        }

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
            $('[name="provider_code"]').val('');
            $('[name="selected_option"]').val('');
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

        function getShippingGeolocationPayload() {
            var latitude = $.trim(($('[name="shipping_latitude"]').val() || '').toString());
            var longitude = $.trim(($('[name="shipping_longitude"]').val() || '').toString());

            if (latitude === '' || longitude === '') {
                return null;
            }

            return {
                latitude: latitude,
                longitude: longitude
            };
        }

        function updateShippingGeolocation(lat, lng) {
            $('[name="shipping_latitude"]').val(lat);
            $('[name="shipping_longitude"]').val(lng);
            updateLocationCoordsText(lat, lng);
        }

        function getShippingOptions(countryId, stateId, cityId) {
            var requestId = ++shippingOptionsRequestSeq;
            var payload = {
                country_id: countryId,
                state_id: stateId,
                city_id: cityId
            };

            var currentProviderCode = $.trim(($('[name="provider_code"]').val() || '').toString());
            var currentServiceLevel = $.trim(($('[name="service_level"]').val() || '').toString());

            if (currentProviderCode !== '') {
                payload.provider_code = currentProviderCode;
            }

            if (currentServiceLevel !== '') {
                payload.service_level = currentServiceLevel;
            }

            var geolocation = getShippingGeolocationPayload();
            if (geolocation) {
                payload.latitude = geolocation.latitude;
                payload.longitude = geolocation.longitude;
            }

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
                    if (requestId !== shippingOptionsRequestSeq) {
                        console.log('Ignoring stale shipping options response', {
                            requestId: requestId,
                            latestRequestId: shippingOptionsRequestSeq
                        });
                        return;
                    }

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
                            displayShippingOptions(data, options, data.currency);
                        } else if (!noShippingAvailable && initialShippingOption && initialShippingOption.service_level) {
                            displayShippingOptions(data, [initialShippingOption], data.currency || 'HNL');
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

        function deliveryTypeToServiceLevel(deliveryType) {
            var normalized = (deliveryType || '').toString().toLowerCase().trim();

            if (normalized === 'same-day' || normalized === 'express') {
                return 'express';
            }

            return 'standard';
        }

        function buildProviderGroups(providerGroups, normalizedOptions) {
            var groupsByCode = {};

            (Array.isArray(providerGroups) ? providerGroups : []).forEach(function(providerGroup) {
                var providerCode = (providerGroup.provider_code || '').toString().toLowerCase().trim();

                if (providerCode === '') {
                    return;
                }

                groupsByCode[providerCode] = {
                    provider_code: providerCode,
                    provider_name: (providerGroup.provider_name || providerCode || 'Proveedor').toString(),
                    logo_url: (providerGroup.logo_url || providerGroup.provider_logo_url || providerGroup.img_logo_url || '').toString(),
                    options: []
                };
            });

            normalizedOptions.forEach(function(option) {
                var providerCode = (option.provider_code || 'standard').toString().toLowerCase().trim();

                if (!groupsByCode[providerCode]) {
                    groupsByCode[providerCode] = {
                        provider_code: providerCode,
                        provider_name: (option.provider_name || providerCode || 'Proveedor').toString(),
                        logo_url: (option.provider_logo_url || '').toString(),
                        options: []
                    };
                }

                if (!groupsByCode[providerCode].logo_url && option.provider_logo_url) {
                    groupsByCode[providerCode].logo_url = option.provider_logo_url.toString();
                }

                groupsByCode[providerCode].options.push(option);
            });

            return Object.keys(groupsByCode).map(function(providerCode) {
                return groupsByCode[providerCode];
            }).sort(function(a, b) {
                return a.provider_name.localeCompare(b.provider_name);
            });
        }

        function formatCodCommissionPercent(value) {
            var parsed = parseFloat(value);

            if (!Number.isFinite(parsed) || parsed <= 0) {
                return null;
            }

            if (Math.abs(parsed - Math.round(parsed)) < 0.0001) {
                return Math.round(parsed).toString();
            }

            return parsed.toFixed(2).replace(/\.?0+$/, '');
        }

        function buildCodMeta(option) {
            var selectedOption = option && option.selected_option && typeof option.selected_option === 'object'
                ? option.selected_option
                : {};
            var rawSupportsCod = selectedOption.supports_cod;

            if (typeof rawSupportsCod === 'undefined' || rawSupportsCod === null) {
                rawSupportsCod = option.supports_cod;
            }

            var rawCommission = selectedOption.cod_commission_percent;

            if (typeof rawCommission === 'undefined' || rawCommission === null || rawCommission === '') {
                rawCommission = selectedOption.client_cod_commission;
            }

            if (typeof rawCommission === 'undefined' || rawCommission === null || rawCommission === '') {
                rawCommission = option.cod_commission_percent;
            }

            if (typeof rawCommission === 'undefined' || rawCommission === null || rawCommission === '') {
                rawCommission = option.client_cod_commission;
            }

            var parsedCommission = parseFloat(rawCommission);
            var supportsCod = false;

            if (typeof rawSupportsCod !== 'undefined' && rawSupportsCod !== null && rawSupportsCod !== '') {
                if (typeof rawSupportsCod === 'boolean') {
                    supportsCod = rawSupportsCod;
                } else {
                    supportsCod = ['1', 'true', 'yes'].indexOf(rawSupportsCod.toString().toLowerCase()) !== -1;
                }
            } else if (Number.isFinite(parsedCommission) && parsedCommission > 0) {
                supportsCod = true;
            }

            return {
                supports_cod: supportsCod,
                cod_commission_percent: Number.isFinite(parsedCommission) ? parsedCommission : null
            };
        }

        function displayShippingOptions(data, options, currency) {
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

            var providers = Array.isArray(data && data.providers) ? data.providers : [];
            var normalizedOptions = [];

            if (providers.length > 0) {
                providers.forEach(function(providerGroup) {
                    var providerCode = (providerGroup.provider_code || '').toString();
                    var providerName = (providerGroup.provider_name || providerCode || 'Proveedor').toString();
                    var providerOptions = Array.isArray(providerGroup.options) ? providerGroup.options : [];

                    providerOptions.forEach(function(option) {
                        var baseOption = $.extend({}, option, {
                            provider_code: option.provider_code || providerCode,
                            provider_name: option.provider_name || providerName,
                            provider_logo_url: option.provider_logo_url || providerGroup.logo_url || providerGroup.provider_logo_url || providerGroup.img_logo_url || null
                        });

                        var couriers = Array.isArray(baseOption.courier_options) ? baseOption.courier_options : [];
                        var hasCourierSelected = Boolean(baseOption.courier_id || (baseOption.selected_option && (baseOption.selected_option.id || baseOption.selected_option.courier_id)));

                        if (couriers.length > 0 && !hasCourierSelected) {
                            couriers.forEach(function(courier) {
                                var courierCost = parseFloat(courier.client_price || courier.shipping_cost || baseOption.shipping_cost || 0);
                                var courierCodMeta = buildCodMeta($.extend({}, baseOption, {
                                    selected_option: courier
                                }));
                                normalizedOptions.push($.extend({}, baseOption, {
                                    service_level: deliveryTypeToServiceLevel(courier.delivery_type),
                                    shipping_cost: courierCost,
                                    supports_cod: courierCodMeta.supports_cod,
                                    cod_commission_percent: courierCodMeta.cod_commission_percent,
                                    selected_option: {
                                        id: courier.id || null,
                                        courier_id: courier.id || null,
                                        name: courier.name || null,
                                        courier_name: courier.name || null,
                                        delivery_type: courier.delivery_type || null,
                                        courier_delivery_type: courier.delivery_type || null,
                                        client_price: courierCost,
                                        shipping_cost: courierCost,
                                        service_level: deliveryTypeToServiceLevel(courier.delivery_type),
                                        supports_cod: courierCodMeta.supports_cod,
                                        cod_commission_percent: courierCodMeta.cod_commission_percent,
                                        client_cod_commission: courierCodMeta.cod_commission_percent
                                    },
                                    courier_name: courier.name || null,
                                    courier_delivery_type: courier.delivery_type || null
                                }));
                            });
                        } else {
                            normalizedOptions.push(baseOption);
                        }
                    });
                });
            } else {
                normalizedOptions = options.map(function(option) {
                    return $.extend({}, option, {
                        provider_code: option.provider_code || 'standard',
                        provider_name: option.provider_name || ((option.provider_code || 'standard').toString().toUpperCase()),
                        provider_logo_url: option.provider_logo_url || null
                    });
                });
            }

            var currentSelection = getCurrentShippingSelection();
            var effectiveProviderCode = currentSelection.provider_code || initialProviderCode;
            var effectiveServiceLevel = currentSelection.service_level || initialServiceLevel;
            var selectedIndex = 0;
            var matchedIndex = normalizedOptions.findIndex(function(option) {
                var optionProviderCode = ((option.provider_code || '').toString().trim());
                var optionServiceLevel = ((option.service_level || '').toString().trim());
                var providerMatch = !effectiveProviderCode || optionProviderCode === effectiveProviderCode;
                var levelMatch = !effectiveServiceLevel || optionServiceLevel === effectiveServiceLevel;

                if (!providerMatch || !levelMatch) {
                    return false;
                }

                if (matchesSelectedCourier(option, currentSelection)) {
                    return true;
                }

                return !currentSelection.selected_option;
            });

            if (matchedIndex < 0 && effectiveProviderCode) {
                matchedIndex = normalizedOptions.findIndex(function(option) {
                    return ((option.provider_code || '').toString().trim()) === effectiveProviderCode
                        && matchesSelectedCourier(option, currentSelection);
                });
            }

            if (matchedIndex < 0 && (effectiveServiceLevel || effectiveProviderCode)) {
                matchedIndex = normalizedOptions.findIndex(function(option) {
                    var levelMatch = !effectiveServiceLevel || ((option.service_level || '').toString().trim() === effectiveServiceLevel);
                    var providerMatch = !effectiveProviderCode || ((option.provider_code || '').toString().trim() === effectiveProviderCode);
                    return levelMatch && providerMatch;
                });
            }

            if (matchedIndex >= 0) {
                selectedIndex = matchedIndex;
            }

            var groupedProviders = buildProviderGroups(providers, normalizedOptions);
            var optionsMarkup = $('<div>', {
                'class': 'shipping-provider-groups'
            });

            normalizedOptions.forEach(function(option, index) {
                console.log('Processing option ' + index + ':', {
                    provider_code: option.provider_code,
                    service_level: option.service_level,
                    shipping_cost: option.shipping_cost,
                    free_shipping: option.free_shipping
                });

                var daysDisplay = '';
                var providerCode = (option.provider_code || '').toString().toLowerCase().trim();
                var isPickupOption = isPickupOptionPayload(option);
                var warehouse = getOptionWarehouse(option);
                var warehouseAddress = getWarehouseAddressText(warehouse);
                var shouldShowEstimatedDays = providerCode !== 'boxful' && !isPickupOption;
                if (shouldShowEstimatedDays && option.estimated_days_min && option.estimated_days_max) {
                    daysDisplay = ' (' + option.estimated_days_min + '-' + option.estimated_days_max + ' ' + daysText + ')';
                }
                
                var costText = option.free_shipping ? 'Gratis' : 'L' + parseFloat(option.shipping_cost).toFixed(2);
                var providerName = (option.provider_name || option.provider_code || 'Proveedor').toString();
                var courierName = (option.courier_name || (option.selected_option && option.selected_option.name) || '').toString().trim();
                var deliveryType = (option.courier_delivery_type || (option.selected_option && option.selected_option.delivery_type) || '').toString().trim();
                var deliveryLabelEs = (option.courier_delivery_type_label_es || (option.selected_option && option.selected_option.delivery_type_label_es) || '').toString().trim();
                var deliveryLabelEn = (option.courier_delivery_type_label_en || (option.selected_option && option.selected_option.delivery_type_label_en) || '').toString().trim();
                var deliveryLabel = uiLang === 'en' ? (deliveryLabelEn || deliveryType) : (deliveryLabelEs || deliveryType);
                var optionTitle = option.service_level.charAt(0).toUpperCase() + option.service_level.slice(1);
                if (isPickupOption) {
                    optionTitle = pickupText;
                } else if (courierName) {
                    optionTitle = courierName + (deliveryLabel ? ' (' + deliveryLabel + ')' : '');
                }
                var selectedPayload = {
                    provider_code: option.provider_code || null,
                    provider_name: option.provider_name || null,
                    service_level: option.service_level || null,
                    shipping_cost: option.shipping_cost || 0,
                    shipping_rate_id: option.shipping_rate_id || null,
                    shipping_zone_id: option.shipping_zone_id || null,
                    warehouse_id: option.warehouse_id || null,
                    warehouse_name: option.warehouse_name || null,
                    warehouse: warehouse || null,
                    selected_option: option.selected_option || {
                        id: option.courier_id || null,
                        courier_id: option.courier_id || null,
                        name: courierName || null,
                        courier_name: courierName || null,
                        delivery_type: deliveryType || null,
                        courier_delivery_type: deliveryType || null,
                        delivery_type_label_es: deliveryLabelEs || null,
                        delivery_type_label_en: deliveryLabelEn || null,
                        client_price: option.shipping_cost || 0,
                        shipping_cost: option.shipping_cost || 0,
                        service_level: option.service_level || null,
                        supports_cod: option.supports_cod || false,
                        cod_commission_percent: option.cod_commission_percent || null,
                        client_cod_commission: option.cod_commission_percent || null,
                        warehouse_id: option.warehouse_id || null,
                        warehouse_name: option.warehouse_name || null,
                        warehouse: warehouse || null
                    },
                    courier_options: option.courier_options || [],
                    supports_cod: option.supports_cod || false,
                    cod_commission_percent: option.cod_commission_percent || null,
                    client_cod_commission: option.cod_commission_percent || null
                };
                option.__selectedPayload = selectedPayload;
                option.__title = optionTitle;
                option.__daysDisplay = daysDisplay;
                option.__costText = costText;
                option.__codMeta = buildCodMeta(option);
                option.__isSelected = index === selectedIndex;
                option.__isPickup = isPickupOption;
                option.__warehouseName = (warehouse && warehouse.name ? warehouse.name.toString() : (option.warehouse_name || '').toString());
                option.__warehouseAddress = warehouseAddress;
            });

            groupedProviders.forEach(function(providerGroup, providerIndex) {
                var providerOptions = Array.isArray(providerGroup.options) ? providerGroup.options : [];

                if (providerOptions.length === 0) {
                    return;
                }

                var providerBlock = $('<div>', {
                    'class': 'shipping-provider-group'
                });
                var providerHeader = $('<div>', {
                    'class': 'shipping-provider-group__header'
                });
                var providerBrand = $('<div>', {
                    'class': 'shipping-provider-group__brand'
                });
                var providerLogoUrl = (providerGroup.logo_url || providerOptions[0].provider_logo_url || '').toString().trim();

                if (providerLogoUrl !== '') {
                    providerBrand.append($('<img>', {
                        src: providerLogoUrl,
                        alt: providerGroup.provider_name,
                        'class': 'shipping-provider-group__logo'
                    }));
                }

                providerBrand.append($('<span>', {
                    'class': 'shipping-provider-group__title'
                }));
                providerHeader.append(providerBrand);
                providerBlock.append(providerHeader);

                var providerOptionGroup = $('<div>', {
                    'class': 'btn-group-vertical w-100 shipping-provider-group__options',
                    'role': 'group'
                });

                providerOptions.forEach(function(option, optionIndex) {
                    var globalIndex = normalizedOptions.indexOf(option);
                    var inputId = 'shipping_' + providerIndex + '_' + optionIndex + '_' + globalIndex;
                    var radio = $('<input>', {
                        type: 'radio',
                        'class': 'btn-check shipping-option',
                        name: 'shipping_option',
                        value: option.service_level,
                        id: inputId,
                        'data-provider-code': option.provider_code || '',
                        'data-service-level': option.service_level,
                        'data-shipping-cost': option.shipping_cost
                    });

                    if (option.__isSelected) {
                        radio.prop('checked', true);
                    }

                    radio.data('selected-option', option.__selectedPayload);

                    var label = $('<label>', {
                        'class': 'btn btn-outline-primary w-100 text-start shipping-option-label',
                        'for': inputId
                    });
                    var row = $('<div>', {
                        'class': 'd-flex justify-content-between align-items-center gap-3 w-100'
                    });
                    var left = $('<div>');
                    left.append($('<strong>').text(option.__title + option.__daysDisplay));
                    if (option.__isPickup) {
                        if (option.__warehouseName) {
                            left.append($('<div>', {
                                'class': 'small text-muted mt-1'
                            }).text(warehouseText + ': ' + option.__warehouseName));
                        }

                        if (option.__warehouseAddress) {
                            left.append($('<div>', {
                                'class': 'small text-muted'
                            }).text(pickupAddressText + ': ' + option.__warehouseAddress));
                        }
                    }
                    if (option.__codMeta && option.__codMeta.supports_cod) {
                        var codBadgeRow = $('<div>', {
                            'class': 'small text-muted mt-1'
                        });
                        codBadgeRow.append(
                            $('<span>', {
                                'class': 'badge rounded-pill text-bg-success-subtle text-success-emphasis border border-success-subtle me-2'
                            }).text(codAvailableText)
                        );

                        left.append(codBadgeRow);
                    }
                    var right = $('<div>', {
                        'class': 'shipping-option-label__price'
                    }).text(option.__costText);

                    row.append(left, right);
                    label.append(row);
                    providerOptionGroup.append(radio, label);
                });

                providerBlock.append(providerOptionGroup);
                optionsMarkup.append(providerBlock);
            });

            container.empty().append(optionsMarkup);
            $('#shipping-options-section').removeClass('d-none');

            // Bind change event to shipping options
            $(document).off('change', '.shipping-option').on('change', '.shipping-option', function() {
                var providerCode = $(this).data('provider-code');
                var serviceLevel = $(this).data('service-level');
                var shippingCost = $(this).data('shipping-cost');
                var selectedOption = $(this).data('selected-option') || null;
                console.log('Shipping option changed to:', {providerCode, serviceLevel, shippingCost, selectedOption});
                selectShippingOption(providerCode, serviceLevel, shippingCost, selectedOption);
            });

            // Auto-select first option
            if (normalizedOptions.length > 0) {
                var selectedOption = normalizedOptions[selectedIndex] || normalizedOptions[0];
                console.log('Auto-selecting shipping option:', selectedOption);
                selectShippingOption(
                    selectedOption.provider_code || '',
                    selectedOption.service_level,
                    selectedOption.shipping_cost,
                    {
                        provider_code: selectedOption.provider_code || null,
                        provider_name: selectedOption.provider_name || null,
                        service_level: selectedOption.service_level || null,
                        shipping_cost: selectedOption.shipping_cost || 0,
                        shipping_rate_id: selectedOption.shipping_rate_id || null,
                        shipping_zone_id: selectedOption.shipping_zone_id || null,
                        warehouse_id: selectedOption.warehouse_id || null,
                        warehouse_name: selectedOption.warehouse_name || null,
                        warehouse: getOptionWarehouse(selectedOption),
                        selected_option: selectedOption.selected_option || null,
                        courier_options: selectedOption.courier_options || [],
                        supports_cod: selectedOption.supports_cod || false,
                        cod_commission_percent: selectedOption.cod_commission_percent || null,
                        client_cod_commission: selectedOption.cod_commission_percent || null
                    }
                );
            }

            initialServiceLevel = '';
            initialProviderCode = '';
        }

        function selectShippingOption(providerCode, serviceLevel, shippingCost, selectedOptionPayload) {
            // Update the display
            console.log('selectShippingOption called with:', {providerCode, serviceLevel, shippingCost, selectedOptionPayload});
            
            $('#checkout-shipping').text('L' + parseFloat(shippingCost).toFixed(2));
            $('[name="shipping_cost"]').val(shippingCost);
            $('[name="service_level"]').val(serviceLevel);
            $('[name="provider_code"]').val(providerCode || '');
            $('[name="selected_option"]').val(selectedOptionPayload ? JSON.stringify(selectedOptionPayload) : '');

            if (selectedOptionPayload && isPickupOptionPayload(selectedOptionPayload)) {
                var pickupWarehouse = getOptionWarehouse(selectedOptionPayload);
                var pickupGeolocation = getWarehouseGeolocation(pickupWarehouse);

                if (pickupGeolocation) {
                    updateShippingGeolocation(pickupGeolocation.latitude, pickupGeolocation.longitude);
                }
            }

            if (typeof window.gtag === 'function' && gaCheckoutItems.length > 0) {
                window.gtag('event', 'add_shipping_info', {
                    currency: 'HNL',
                    value: parseCurrencyAmount($('#checkout-grand-total').text()),
                    shipping: parseFloat(shippingCost || 0),
                    shipping_tier: (serviceLevel || '').toString(),
                    items: gaCheckoutItems
                });
            }

            // Verify values were set
            console.log('Form values after update:', {
                provider_code: $('[name="provider_code"]').val(),
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
                service_level: serviceLevel,
                provider_code: providerCode || ''
            };

            if (selectedOptionPayload) {
                payload.selected_option = selectedOptionPayload;
            }

            var geolocation = getShippingGeolocationPayload();
            if (geolocation) {
                payload.latitude = geolocation.latitude;
                payload.longitude = geolocation.longitude;
            }

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

        function updateLocationCoordsText(lat, lng) {
            $('#shipping-location-coords').text('Lat: ' + parseFloat(lat).toFixed(6) + ', Lng: ' + parseFloat(lng).toFixed(6));
        }

        function setLocationAndRequote(lat, lng) {
            updateShippingGeolocation(lat, lng);
            calculateShipping();
        }

        function initShippingLocationMap() {
            if (typeof L === 'undefined') {
                console.warn('Leaflet no disponible para mapa de geolocalizacion');
                return;
            }

            var defaultLat = 15.5053;
            var defaultLng = -88.0250;
            var initialLat = parseFloat($('[name="shipping_latitude"]').val() || defaultLat);
            var initialLng = parseFloat($('[name="shipping_longitude"]').val() || defaultLng);

            var map = L.map('shipping-location-map').setView([initialLat, initialLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
            updateLocationCoordsText(initialLat, initialLng);

            marker.on('dragend', function(e) {
                var position = e.target.getLatLng();
                setLocationAndRequote(position.lat, position.lng);
            });

            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                setLocationAndRequote(e.latlng.lat, e.latlng.lng);
            });

            var currentLat = $.trim(($('[name="shipping_latitude"]').val() || '').toString());
            var currentLng = $.trim(($('[name="shipping_longitude"]').val() || '').toString());
            if (currentLat !== '' && currentLng !== '') {
                setLocationAndRequote(parseFloat(currentLat), parseFloat(currentLng));
            }

            function centerMapToCoordinates(lat, lng, shouldRequote) {
                marker.setLatLng([lat, lng]);
                map.setView([lat, lng], 14);
                if (shouldRequote) {
                    setLocationAndRequote(lat, lng);
                } else {
                    updateShippingGeolocation(lat, lng);
                }
            }

            if (navigator.geolocation && currentLat === '' && currentLng === '') {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    centerMapToCoordinates(lat, lng, true);
                }, function(error) {
                    console.warn('Geolocation unavailable for shipping optimization:', error && error.message ? error.message : error);
                }, {
                    enableHighAccuracy: false,
                    timeout: 6000,
                    maximumAge: 300000
                });
            }
        }

        setTimeout(function() {
            initShippingLocationMap();
        }, 100);

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

            if (typeof window.gtag === 'function' && gaCheckoutItems.length > 0) {
                var selectedProvider = ($('[name="provider_code"]').val() || '').toString().trim();
                var paymentType = selectedProvider !== '' ? selectedProvider : 'checkout_submit';

                window.gtag('event', 'add_payment_info', {
                    currency: 'HNL',
                    value: parseCurrencyAmount($('#checkout-grand-total').text()),
                    payment_type: paymentType,
                    items: gaCheckoutItems
                });
            }

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

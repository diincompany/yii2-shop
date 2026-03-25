<?php
/**
 * Billing Address Form
 * @var yii\web\View $this
 * @var array $countries
 * @var array $address
 * @var string $moduleRoute
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="col-12 mt-4 d-none" id="billing-address-fields">
    <h5 class="border-bottom pb-2 mb-3"><?= Yii::t('shop', 'billing_address') ?></h5>
    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'first_name') ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control billing-input" name="billing_first_name" value="<?= Html::encode($address['first_name'] ?? '') ?>">
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'last_name') ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control billing-input" name="billing_last_name" value="<?= Html::encode($address['last_name'] ?? '') ?>">
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'email_address') ?> <span class="text-danger">*</span></label>
            <input type="email" class="form-control billing-input" name="billing_email" value="<?= Html::encode($address['email'] ?? '') ?>">
        </div>
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'phone_number') ?> <span class="text-danger">*</span></label>
            <input type="tel" class="form-control billing-input" name="billing_phone" value="<?= Html::encode($address['phone_number'] ?? '') ?>">
        </div>
        <div class="col-12 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'street_address') ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control billing-input" name="billing_street" value="<?= Html::encode($address['address_1'] ?? '') ?>">
        </div>

        <!-- Country Select -->
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'country') ?> <span class="text-danger">*</span></label>
            <select class="form-select billing-country billing-input" name="billing_country_id">
                <option value=""><?= Yii::t('shop', 'select_country') ?></option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= Html::encode($country['id'] ?? '') ?>" <?= (($address['country_id'] ?? '') == ($country['id'] ?? '') ? 'selected' : '') ?>>
                        <?= Html::encode($country['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- State Select -->
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'state_department') ?> <span class="text-danger">*</span></label>
            <select class="form-select billing-state billing-input" name="billing_state_id" <?= empty($address['country_id']) ? 'disabled' : '' ?>>
                <option value=""><?= Yii::t('shop', 'Select a state') ?></option>
                <?php if (!empty($address['state_id']) && !empty($address['state_name'])): ?>
                    <option value="<?= Html::encode($address['state_id']) ?>" selected><?= Html::encode($address['state_name']) ?></option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= Yii::t('shop', 'Please select a country first') ?></small>
        </div>

        <!-- City Select -->
        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'city') ?> <span class="text-danger">*</span></label>
            <select class="form-select billing-city billing-input" name="billing_city_id" <?= empty($address['state_id']) ? 'disabled' : '' ?>>
                <option value=""><?= Yii::t('shop', 'Select a city') ?></option>
                <?php if (!empty($address['city_id']) && !empty($address['city_name'])): ?>
                    <option value="<?= Html::encode($address['city_id']) ?>" selected><?= Html::encode($address['city_name']) ?></option>
                <?php endif; ?>
            </select>
            <small class="text-muted"><?= Yii::t('shop', 'Please select a state first') ?></small>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label"><?= Yii::t('shop', 'zip_code') ?></label>
            <input type="text" class="form-control billing-input" name="billing_zip" value="<?= Html::encode($address['zipcode'] ?? '') ?>">
        </div>
    </div>
</div>

<?php
// Register JavaScript for cascading selects
$getStatesUrl = Url::to([$moduleRoute . '/default/get-states']);
$getCitiesUrl = Url::to([$moduleRoute . '/default/get-cities']);
$selectStateText = Yii::t('shop', 'Select a state');
$selectCityText = Yii::t('shop', 'Select a city');

$this->registerJs(<<<JS
    jQuery(function($) {
        // Handle country change
        $(document).on('change', '.billing-country', function() {
            var countryId = $(this).val();
            var stateSelect = $('.billing-state');
            var citySelect = $('.billing-city');

            // Reset city select
            citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);

            if (!countryId) {
                stateSelect.html('<option value="">' + '$selectStateText' + '</option>').prop('disabled', true);
                return;
            }

            // Fetch states
            $.ajax({
                url: '$getStatesUrl',
                type: 'GET',
                data: { country_id: countryId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">' + '$selectStateText' + '</option>';
                        response.data.forEach(function(state) {
                            options += '<option value="' + state.id + '">' + state.name + '</option>';
                        });
                        stateSelect.html(options).prop('disabled', false);
                    } else {
                        stateSelect.html('<option value="">' + '$selectStateText' + '</option>').prop('disabled', true);
                        citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                    }
                },
                error: function() {
                    stateSelect.html('<option value="">' + '$selectStateText' + '</option>').prop('disabled', true);
                    citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                }
            });
        });

        // Handle state change
        $(document).on('change', '.billing-state', function() {
            var stateId = $(this).val();
            var citySelect = $('.billing-city');

            if (!stateId) {
                citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                return;
            }

            // Fetch cities
            $.ajax({
                url: '$getCitiesUrl',
                type: 'GET',
                data: { state_id: stateId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">' + '$selectCityText' + '</option>';
                        response.data.forEach(function(city) {
                            options += '<option value="' + city.id + '">' + city.name + '</option>';
                        });
                        citySelect.html(options).prop('disabled', false);
                    } else {
                        citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                    }
                },
                error: function() {
                    citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                }
            });
        });

        // Trigger country change on page load if country is selected
        var selectedCountry = $('.billing-country').val();
        var selectedState = $('.billing-state').val();
        
        if (selectedCountry) {
            $('.billing-country').trigger('change');
            // After loading states, trigger state change to load cities
            setTimeout(function() {
                if (selectedState) {
                    $('.billing-state').trigger('change');
                }
            }, 500);
        }
    });
JS, \yii\web\View::POS_END);
?>

<?php
/**
 * Shipping Address Form
 * @var yii\web\View $this
 * @var array $countries
 * @var array $address
 * @var string $orderNotes
 * @var string $moduleRoute
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="row">
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'first_name') ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="first_name" value="<?= Html::encode($address['first_name'] ?? '') ?>" required>
    </div>
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'last_name') ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="last_name" value="<?= Html::encode($address['last_name'] ?? '') ?>" required>
    </div>
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'email_address') ?> <span class="text-danger">*</span></label>
        <input type="email" class="form-control" name="email" value="<?= Html::encode($address['email'] ?? '') ?>" required>
    </div>
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'phone_number') ?> <span class="text-danger">*</span></label>
        <input type="tel" class="form-control" name="phone" value="<?= Html::encode($address['phone_number'] ?? '') ?>" required>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'street_address') ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="address_1" value="<?= Html::encode($address['address_1'] ?? '') ?>" required>
    </div>
    
    <!-- Country Select -->
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'country') ?> <span class="text-danger">*</span></label>
        <select class="form-select shipping-country" name="country_id" required>
            <option value=""><?= Yii::t('shop', 'select_country') ?></option>
            <?php foreach ($countries as $country): ?>
                <option value="<?= Html::encode($country['id'] ?? '') ?>" <?= (($address['country_id'] ?? 99) == ($country['id'] ?? '') ? 'selected' : '') ?>>
                    <?= Html::encode($country['name'] ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- State Select -->
    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'state_department') ?> <span class="text-danger">*</span></label>
        <select class="form-select shipping-state" name="state_id" required <?= empty($address['country_id']) ? 'disabled' : '' ?>>
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
        <select class="form-select shipping-city" name="city_id" required <?= empty($address['state_id']) ? 'disabled' : '' ?>>
            <option value=""><?= Yii::t('shop', 'Select a city') ?></option>
            <?php if (!empty($address['city_id']) && !empty($address['city_name'])): ?>
                <option value="<?= Html::encode($address['city_id']) ?>" selected><?= Html::encode($address['city_name']) ?></option>
            <?php endif; ?>
        </select>
        <small class="text-muted"><?= Yii::t('shop', 'Please select a state first') ?></small>
    </div>

    <div class="col-sm-6 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'zip_code') ?></label>
        <input type="text" class="form-control" name="zip" value="<?= Html::encode($address['zipcode'] ?? '') ?>">
    </div>

    <div class="col-12 mb-3">
        <label class="form-label"><?= Yii::t('shop', 'order_notes') ?></label>
        <textarea class="form-control" name="notes" rows="3" placeholder="<?= Yii::t('shop', 'order_notes_placeholder') ?>"><?= Html::encode($orderNotes ?? '') ?></textarea>
    </div>
</div>

<?php
// Register JavaScript for cascading selects
$getStatesUrl = Url::to([$moduleRoute . '/default/get-states']);
$getCitiesUrl = Url::to([$moduleRoute . '/default/get-cities']);
$selectStateText = Yii::t('shop', 'Select a state');
$selectCityText = Yii::t('shop', 'Select a city');
$initialStateIdJson = json_encode((string) ($address['state_id'] ?? ''));
$initialCityIdJson = json_encode((string) ($address['city_id'] ?? ''));

$this->registerJs(<<<JS
    jQuery(function($) {
        var initialStateId = ($initialStateIdJson || '').toString();
        var initialCityId = ($initialCityIdJson || '').toString();

        // Handle country change
        $(document).on('change', '.shipping-country', function() {
            var countryId = $(this).val();
            var stateSelect = $('.shipping-state');
            var citySelect = $('.shipping-city');

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
                            var stateId = (state.id || '').toString();
                            var selected = (initialStateId && stateId === initialStateId) ? ' selected' : '';
                            options += '<option value="' + state.id + '"' + selected + '>' + state.name + '</option>';
                        });
                        stateSelect.html(options).prop('disabled', false);

                        if (initialStateId) {
                            stateSelect.val(initialStateId).trigger('change');
                            initialStateId = '';
                        }
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
        $(document).on('change', '.shipping-state', function() {
            var stateId = $(this).val();
            var citySelect = $('.shipping-city');

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
                            var cityId = (city.id || '').toString();
                            var selected = (initialCityId && cityId === initialCityId) ? ' selected' : '';
                            options += '<option value="' + city.id + '"' + selected + '>' + city.name + '</option>';
                        });
                        citySelect.html(options).prop('disabled', false);

                        if (initialCityId) {
                            citySelect.val(initialCityId).trigger('change');
                            initialCityId = '';
                        }
                    } else {
                        citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                    }
                },
                error: function() {
                    citySelect.html('<option value="">' + '$selectCityText' + '</option>').prop('disabled', true);
                }
            });
        });

        // Trigger country change on page load to hydrate state/city selectors once
        var selectedCountry = $('.shipping-country').val();
        if (selectedCountry) {
            $('.shipping-country').trigger('change');
        }
    });
JS, \yii\web\View::POS_END);
?>

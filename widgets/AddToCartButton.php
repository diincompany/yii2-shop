<?php
namespace diincompany\shop\widgets;

use diincompany\shop\Module as ShopModule;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

class AddToCartButton extends Widget
{
    public $params;
    public $productId;
    public $quantity = 1;
    public $buttonClass = 'btn btn-mode me-3';
    public $buttonLabel = null;

    public function init() {
        parent::init();
        ShopModule::registerTranslations();

        $this->params = $this->params ?: [];
    }

    public function run() {
        if (!$this->productId) {
            return Html::tag('div', Yii::t('shop', 'Product ID is required'), [
                'class' => 'alert alert-danger',
            ]);
        }

        $buttonId = 'add-to-cart-' . $this->productId;
        $buttonLabel = $this->buttonLabel ?? Yii::t('shop', 'Agregar al carrito');
        
        $buttonContent = $buttonLabel;
        
        $content = Html::button($buttonContent, [
            'class' => $this->buttonClass,
            'id' => $buttonId,
            'data-product-id' => $this->productId,
            'data-quantity' => $this->quantity,
            'type' => 'button',
        ]);

        $this->registerClientScript($buttonId);

        return $content;
    }

    private function registerClientScript($buttonId) {
        $moduleRoute = $this->resolveModuleRoute();
        // Ensure absolute URL by always prefixing with /shop route
        $addToCartUrl = Url::to(['/shop/cart/add-item']);
        
        $js = <<<JS
            $('#$buttonId').off('click').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var productId = btn.data('product-id');
                
                // Read quantity from .cart-qty-input in the parent container
                var qtyInput = btn.closest('.product-detail-actions').find('.cart-qty-input');
                var quantity = qtyInput.length > 0 ? parseInt(qtyInput.val()) || 1 : (btn.data('quantity') || 1);
                var selectedVariantRadio = btn.closest('.product-detail').find('.product-variant-radio:checked').first();
                var variantSelect = btn.closest('.product-detail').find('.product-variant-select').first();
                var variantId = null;

                if (selectedVariantRadio.length > 0) {
                    var selectedVariantFromRadio = String(selectedVariantRadio.val() || '').trim();
                    variantId = selectedVariantFromRadio === '' || selectedVariantFromRadio === '0' ? null : selectedVariantFromRadio;
                } else if (variantSelect.length > 0) {
                    var selectedVariant = String(variantSelect.val() || '').trim();
                    variantId = selectedVariant === '' || selectedVariant === '0' ? null : selectedVariant;
                }
                
                var originalText = btn.text();
                
                // Disable button and show loading state
                btn.prop('disabled', true).text('Procesando...');

                var requestData = {
                    product_id: productId,
                    quantity: quantity,
                    _csrf: $('[name="_csrf"]').val()
                };

                if (variantId) {
                    requestData.variant_id = variantId;
                }
                
                $.ajax({
                    url: '$addToCartUrl',
                    type: 'POST',
                    data: requestData,
                    dataType: 'json',
                    success: function(response) {
                        function escapeHtml(text) {
                            return $('<div>').text(String(text || '')).html();
                        }

                        if (response.success) {
                            const htmlMessage = '<div class="text-center"><i class="bi bi-check-circle-fill text-success fs-4"></i><div class="mt-2">Producto agregado al carrito con éxito</div></div>';
                            bootbox.alert(htmlMessage);
                            
                            // Auto-remove alert after 5 seconds
                            setTimeout(function() {
                                bootbox.hideAll();
                            }, 5000);
                            
                            // Emit custom event for cart updates with response data
                            $(document).trigger('cartItemAdded', [productId, quantity, response.data, variantId]);
                        } else {
                            var errorMessage = (response && response.message)
                                ? response.message
                                : 'Error al agregar el producto al carrito';
                            const htmlMessage = '<div class="text-center"><i class="bi bi-x-circle-fill text-danger fs-4"></i><div class="mt-2">' + escapeHtml(errorMessage) + '</div></div>';
                            // Show error message
                            bootbox.alert(htmlMessage);
                        }
                    },
                    error: function() {
                        const htmlMessage = '<div class="text-center"><i class="bi bi-x-circle-fill text-danger fs-4"></i><div class="mt-2">Ocurrió un error al agregar el producto al carrito. Por favor, inténtelo de nuevo.</div></div>';
                        // Show generic error message
                        bootbox.alert(htmlMessage);
                    },
                    complete: function() {
                        // Restore button state
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        JS;

        $this->getView()->registerJs($js, View::POS_END);
    }

    private function resolveModuleRoute(): string
    {
        $moduleId = null;

        if (Yii::$app->controller !== null && Yii::$app->controller->module !== null) {
            $moduleId = Yii::$app->controller->module->id;
        }

        if (is_string($moduleId) && $moduleId !== '') {
            return '/' . trim($moduleId, '/');
        }

        return '/shop';
    }
}
<?php

namespace diincompany\shop\controllers;

use diincompany\shop\contracts\ShopApiClientInterface;
use diincompany\shop\contracts\ShopLoggerInterface;
use diincompany\shop\contracts\ShopSessionContextInterface;
use diincompany\shop\Module as ShopModule;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\helpers\VarDumper;

/**
 * Default controller for the `store` module
 */
class DefaultController extends Controller
{
    private const ACTIVE_CART_STATUSES = ['cart'];
    private const TERMINAL_ORDER_STATUSES = ['pending', 'paid', 'completed', 'processing'];

    private function shopModule(): ShopModule
    {
        $module = $this->module;

        if (!$module instanceof ShopModule) {
            throw new \yii\base\InvalidConfigException('DefaultController must run inside DiinCompany\\Yii2Shop\\Module.');
        }

        return $module;
    }

    private function apiClient(): ShopApiClientInterface
    {
        return $this->shopModule()->getApiClient();
    }

    private function logger(): ShopLoggerInterface
    {
        return $this->shopModule()->getLogger();
    }

    private function sessionContext(): ShopSessionContextInterface
    {
        return $this->shopModule()->getSessionContext();
    }

    private function moduleRoute(string $route = ''): string
    {
        $moduleId = trim($this->shopModule()->id, '/');

        if ($route === '') {
            return '/' . $moduleId;
        }

        return '/' . $moduleId . '/' . ltrim($route, '/');
    }

    private function moduleRouteParams(string $route = '', array $params = []): array
    {
        return array_merge([$this->moduleRoute($route)], $params);
    }

    /**
     * Hide internal API errors from end users while keeping friendly business errors.
     *
     * @param array $apiResponse
     * @param string $fallbackTranslationKey
     * @return string
     */
    private function getSafeApiMessage(array $apiResponse, string $fallbackTranslationKey): string
    {
        $fallbackMessage = Yii::t('shop', $fallbackTranslationKey);
        $message = trim((string) ($apiResponse['message'] ?? ''));

        if ($message === '') {
            return $fallbackMessage;
        }

        if ($this->isShippingRateNotFoundMessage($message)) {
            return Yii::t('shop', 'shipping_options_unavailable_fallback');
        }

        // Avoid exposing backend internals (stack traces, PHP errors, SQL errors, etc.)
        $unsafePatterns = [
            'undefined constant',
            'exception',
            'stack trace',
            'sqlstate',
            'syntax error',
            'failed opening',
            'fatal error',
            'warning:',
            'notice:',
        ];

        $loweredMessage = strtolower($message);
        foreach ($unsafePatterns as $pattern) {
            if (strpos($loweredMessage, $pattern) !== false) {
                return $fallbackMessage;
            }
        }

        return $message;
    }

    /**
     * Detect API messages that indicate shipping rates are unavailable.
     *
     * @param string $message
     * @return bool
     */
    private function isShippingRateNotFoundMessage(string $message): bool
    {
        $normalized = strtolower(trim($message));

        return strpos($normalized, 'shipping_rate_not_found') !== false
            || strpos($normalized, 'shipping rate not found') !== false
            || strpos($normalized, 'shipping-rate-not-found') !== false;
    }

    /**
     * Detect shipping-rate-not-found responses from different API payload shapes.
     *
     * @param array $apiResponse
     * @return bool
     */
    private function isShippingRateNotFoundResponse(array $apiResponse): bool
    {
        $rawMessage = trim((string) ($apiResponse['message'] ?? ''));
        if ($this->isShippingRateNotFoundMessage($rawMessage)) {
            return true;
        }

        $serialized = strtolower((string) json_encode($apiResponse));

        return strpos($serialized, 'shipping_rate_not_found') !== false
            || strpos($serialized, 'shipping rate not found') !== false;
    }

    /**
     * Normalize address name fields to keep first/last/full name in sync.
     *
     * @param array $address
     * @return array
     */
    private function normalizeAddressNameParts(array $address): array
    {
        $firstName = trim((string) ($address['first_name'] ?? ''));
        $lastName = trim((string) ($address['last_name'] ?? ''));
        $fullName = trim((string) ($address['full_name'] ?? ''));

        if (($firstName === '' || $lastName === '') && $fullName !== '') {
            $parts = preg_split('/\s+/', $fullName) ?: [];

            if ($firstName === '' && !empty($parts)) {
                $firstName = trim((string) array_shift($parts));
            }

            if ($lastName === '' && !empty($parts)) {
                $lastName = trim(implode(' ', $parts));
            }
        }

        if ($fullName === '' && ($firstName !== '' || $lastName !== '')) {
            $fullName = trim($firstName . ' ' . $lastName);
        }

        $address['first_name'] = $firstName;
        $address['last_name'] = $lastName;
        $address['full_name'] = $fullName;

        return $address;
    }

    /**
     * Get current cart for session using allowed statuses.
     *
     * @param ShopApiClientInterface $diinApi
     * @param string $sessionId
     * @return array
     */
    private function getCurrentCartByStatus(ShopApiClientInterface $diinApi, string &$sessionId): array
    {
        foreach (self::ACTIVE_CART_STATUSES as $status) {
            $response = $diinApi->getOrder([
                'session_id' => $sessionId,
                'type' => 'cart',
                'status' => $status,
            ]);

            if (!isset($response['data'])) {
                continue;
            }

            $orderStatus = (string) ($response['data']['status'] ?? '');

            if ($orderStatus === '' || in_array($orderStatus, self::ACTIVE_CART_STATUSES, true)) {
                return $response;
            }

            if (in_array($orderStatus, self::TERMINAL_ORDER_STATUSES, true)) {
                $oldSessionId = $sessionId;
                $sessionId = $this->sessionContext()->getAnonymousSessionId(true);

                $this->logger()->info('Regenerated cart session for closed order status', [
                    'old_session_id' => $oldSessionId,
                    'new_session_id' => $sessionId,
                    'order_status' => $orderStatus,
                ]);

                return [];
            }
        }

        // Defensive fallback: API may hide non-cart statuses when filtering by `status=cart`.
        // Check current session without filters and reset it if order is no longer editable.
        $rawSessionOrderResponse = $diinApi->getOrder([
            'session_id' => $sessionId,
            'type' => 'cart',
        ]);

        if (isset($rawSessionOrderResponse['data'])) {
            $rawOrderStatus = (string) ($rawSessionOrderResponse['data']['status'] ?? '');

            if ($rawOrderStatus === '' || $rawOrderStatus === 'cart') {
                return $rawSessionOrderResponse;
            }

            if (in_array($rawOrderStatus, self::TERMINAL_ORDER_STATUSES, true)) {
                $oldSessionId = $sessionId;
                $sessionId = $this->sessionContext()->getAnonymousSessionId(true);

                $this->logger()->info('Regenerated cart session after raw session status check', [
                    'old_session_id' => $oldSessionId,
                    'new_session_id' => $sessionId,
                    'order_status' => $rawOrderStatus,
                ]);
            }
        }

        return [];
    }

    /**
     * Disable CSRF validation for specific actions
     */
    public function beforeAction($action)
    {
        // Disable CSRF validation for AJAX endpoints
        if (in_array($action->id, ['calculate-shipping', 'get-shipping-options'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Renders the index view for the module
     * Redirects to products page
     * @return Response
     */
    public function actionIndex()
    {
        return $this->redirect($this->moduleRouteParams('products/index'));
    }

    /**
     * Display shopping cart page
     * @return string
     */
    public function actionCart()
    {
        $sessionId = $this->sessionContext()->getAnonymousSessionId();
        
        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get editable cart data for current session
            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);
            
            $cart = $cartResponse['data'] ?? [];

            $items = $cart['items'] ?? [];
            $subtotal = (float) ($cart['subtotal_amount'] ?? 0);
            $taxes = (float) ($cart['tax_amount'] ?? 0);
            $discountAmount = (float) ($cart['discount_amount'] ?? 0);
            $grandTotal = (float) ($cart['total_amount'] ?? 0);
            $couponCode = (string) ($cart['coupon_code'] ?? ($cart['coupon']['code'] ?? ''));
            
            return $this->render('cart', [
                'items' => $items,
                'subtotal' => $subtotal,
                'taxes' => $taxes,
                'discountAmount' => $discountAmount,
                'grandTotal' => $grandTotal,
                'couponCode' => $couponCode,
            ]);
            
        } catch (\Exception $e) {
            $this->logger()->error('Error loading cart page: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return $this->render('cart', [
                'items' => [],
                'subtotal' => 0,
                'taxes' => 0,
                'discountAmount' => 0,
                'grandTotal' => 0,
                'couponCode' => '',
            ]);
        }
    }

    /**
     * Renders the checkout page with cart items
     * @return string
     */
    public function actionCheckout()
    {
        try {
            $sessionId = $this->sessionContext()->getAnonymousSessionId();

            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get editable cart data for current session
            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

            $cart = $cartResponse['data'] ?? [];

            $items = $cart['items'] ?? [];
            $subtotal = (float) ($cart['subtotal_amount'] ?? 0);
            $taxes = (float) ($cart['tax_amount'] ?? 0);
            $discountAmount = (float) ($cart['discount_amount'] ?? 0);
            $shipping = (float) ($cart['shipping_amount'] ?? 0);
            $grandTotal = (float) ($cart['total_amount'] ?? 0);
            $couponCode = (string) ($cart['coupon_code'] ?? ($cart['coupon']['code'] ?? ''));
            $persistedShipping = (array) (
                $cart['shipping']
                ?? $cart['metadata']['shipping']
                ?? []
            );
            $shippingServiceLevel = (string) (
                $cart['shipping']['service_level']
                ?? $cart['metadata']['shipping']['service_level']
                ?? ''
            );
            $orderNotes = $cart['comment'] ?? '';
            
            // Extract shipping and billing addresses if they exist
            $shippingAddress = $cart['shipping_address'] ?? [];
            $billingAddress = $cart['billing_address'] ?? [];
            
            // Extract email from customer if available
            $customerEmail = '';
            if (!empty($cart['customer']['email'])) {
                $customerEmail = $cart['customer']['email'];
            }
            
            // Add email to addresses if available and missing in address payload
            if (!empty($customerEmail)) {
                if (empty($shippingAddress['email'])) {
                    $shippingAddress['email'] = $customerEmail;
                }

                if (empty($billingAddress['email'])) {
                    $billingAddress['email'] = $customerEmail;
                }
            }
            
            // Map nested location structure to flat country_id, state_id, city_id for forms
            if (!empty($shippingAddress) && !empty($shippingAddress['location'])) {
                $location = $shippingAddress['location'];
                $shippingAddress['country_id'] = (int)($location['country']['id'] ?? '');
                $shippingAddress['state_id'] = (int)($location['state']['id'] ?? '');
                $shippingAddress['state_name'] = $location['state']['name'] ?? '';
                $shippingAddress['city_id'] = (int)($location['city']['id'] ?? '');
                $shippingAddress['city_name'] = $location['city']['name'] ?? '';
            }
            
            if (!empty($billingAddress) && !empty($billingAddress['location'])) {
                $location = $billingAddress['location'];
                $billingAddress['country_id'] = (int)($location['country']['id'] ?? '');
                $billingAddress['state_id'] = (int)($location['state']['id'] ?? '');
                $billingAddress['state_name'] = $location['state']['name'] ?? '';
                $billingAddress['city_id'] = (int)($location['city']['id'] ?? '');
                $billingAddress['city_name'] = $location['city']['name'] ?? '';
            }

            $shippingAddress = $this->normalizeAddressNameParts($shippingAddress);
            $billingAddress = $this->normalizeAddressNameParts($billingAddress);
            
            // Get countries list
            $countries = [];
            try {
                $countriesResponse = $diinApi->getCountries([
                    'allowedCountries' => 'true'
                ]);
                $countries = $countriesResponse['data'] ?? [];
            } catch (\Exception $e) {
                $this->logger()->error('Error loading countries list: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                $countries = [];
            }
            
            return $this->render('checkout', [
                'items' => $items,
                'subtotal' => $subtotal,
                'taxes' => $taxes,
                'discountAmount' => $discountAmount,
                'shipping' => $shipping,
                'grandTotal' => $grandTotal,
                'couponCode' => $couponCode,
                'persistedShipping' => $persistedShipping,
                'shippingServiceLevel' => $shippingServiceLevel,
                'countries' => $countries,
                'shippingAddress' => $shippingAddress,
                'billingAddress' => $billingAddress,
                'orderNotes' => $orderNotes,
                'error' => null,
            ]);
        } catch (\Exception $e) {
            $this->logger()->error('Error loading checkout page: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Return checkout with error message instead of redirecting
            return $this->render('checkout', [
                'items' => [],
                'subtotal' => 0,
                'taxes' => 0,
                'discountAmount' => 0,
                'shipping' => 0,
                'grandTotal' => 0,
                'couponCode' => '',
                'persistedShipping' => [],
                'shippingServiceLevel' => '',
                'countries' => [],
                'error' => Yii::t('shop', 'error_loading_checkout'),
            ]);
        }
    }
    
    /**
     * Processes the checkout form submission
     * @return Response
     */
    public function actionProcessCheckout()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }
        
        $sessionId = $this->sessionContext()->getAnonymousSessionId();
        $request = Yii::$app->request;
        $diinpayUrl = Yii::$app->params['diinpay']['url'] ?? '';
        
        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get current editable cart data
            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);
            
            if (!isset($cartResponse['data'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }

            // Parse different billing toggle strictly; unchecked checkboxes are omitted from POST.
            $useDifferentBilling = (string) $request->post('use_billing_address', '') === '1';

            $shippingFirstName = trim((string) $request->post('first_name', ''));
            $shippingLastName = trim((string) $request->post('last_name', ''));
            $shippingAddress = [
                'type' => 'shipping',
                'first_name' => $shippingFirstName,
                'last_name' => $shippingLastName,
                'full_name' => trim($shippingFirstName . ' ' . $shippingLastName),
                'phone_number' => trim((string) $request->post('phone', '')),
                'email' => trim((string) $request->post('email', '')),
                'address_1' => trim((string) $request->post('address_1', '')),
                'city_id' => (int) $request->post('city_id', 0),
                'state_id' => (int) $request->post('state_id', 0),
                'zipcode' => trim((string) $request->post('zip', '')),
                'country_id' => (int) $request->post('country_id', 99),
            ];

            // Default billing to shipping to avoid missing billing payloads.
            $billingAddress = $shippingAddress;

            if ($useDifferentBilling) {
                $billingFirstName = trim((string) $request->post('billing_first_name', ''));
                $billingLastName = trim((string) $request->post('billing_last_name', ''));

                $billingAddress = [
                    'type' => 'billing',
                    'first_name' => $billingFirstName,
                    'last_name' => $billingLastName,
                    'full_name' => trim($billingFirstName . ' ' . $billingLastName),
                    'email' => trim((string) $request->post('billing_email', '')),
                    'phone_number' => trim((string) $request->post('billing_phone', '')),
                    'address_1' => trim((string) $request->post('billing_street', '')),
                    'city_id' => (int) $request->post('billing_city_id', 0),
                    'state_id' => (int) $request->post('billing_state_id', 0),
                    'zipcode' => trim((string) $request->post('billing_zip', '')),
                    'country_id' => (int) $request->post('billing_country_id', 0),
                ];

                // Keep billing valid even if some billing fields arrive empty/disabled in the form.
                foreach (['city_id', 'state_id', 'country_id'] as $locationKey) {
                    if (empty($billingAddress[$locationKey])) {
                        $billingAddress[$locationKey] = $shippingAddress[$locationKey];
                    }
                }

                foreach (['first_name', 'last_name', 'email', 'phone_number', 'address_1'] as $textKey) {
                    if (trim((string) $billingAddress[$textKey]) === '') {
                        $billingAddress[$textKey] = $shippingAddress[$textKey];
                    }
                }

                $billingAddress['full_name'] = trim((string) ($billingAddress['first_name'] ?? '') . ' ' . (string) ($billingAddress['last_name'] ?? ''));
            }

            $shippingAddress['type'] = 'shipping';
            $billingAddress['type'] = 'billing';

            // Keep shipping valid even if some shipping fields arrive empty/disabled in the form.
            foreach (['city_id', 'state_id', 'country_id'] as $locationKey) {
                if (empty($shippingAddress[$locationKey])) {
                    $shippingAddress[$locationKey] = $billingAddress[$locationKey] ?? 0;
                }
            }

            foreach (['first_name', 'last_name', 'email', 'phone_number', 'address_1'] as $textKey) {
                if (trim((string) ($shippingAddress[$textKey] ?? '')) === '') {
                    $shippingAddress[$textKey] = $billingAddress[$textKey] ?? '';
                }
            }

            $shippingAddress['full_name'] = trim((string) ($shippingAddress['first_name'] ?? '') . ' ' . (string) ($shippingAddress['last_name'] ?? ''));

            $currentCart = $cartResponse['data'];
            $couponCode = trim((string) ($currentCart['coupon_code'] ?? ($currentCart['coupon']['code'] ?? '')));

            // Prepare order data (no monetary fields or local calculations)
            $orderData = [
                'session_id' => $sessionId,
                'type' => 'cart',
                'items' => $currentCart['items'] ?? [],
                'comment' => $request->post('notes'),
                'customer' => [
                    'name' => $shippingAddress['full_name'],
                    'first_name' => $shippingAddress['first_name'],
                    'last_name' => $shippingAddress['last_name'],
                    'email' => $shippingAddress['email'],
                    'phone' => $shippingAddress['phone_number'],
                    'phone_number' => $shippingAddress['phone_number'],
                ],
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
            ];

            if ($couponCode !== '') {
                $orderData['coupon_code'] = $couponCode;
            }

            $this->logger()->info('Processing checkout', [
                'use_different_billing' => (bool) $useDifferentBilling,
                'order_data' => $orderData,
            ]);

            // Update order
            $orderResponse = $diinApi->putOrder($currentCart['id'], $orderData);
            
            if (isset($orderResponse['data'])) {
                $orderId = (int) ($orderResponse['data']['id'] ?? $currentCart['id']);
                $serviceLevel = trim((string) ($request->post('service_level')
                    ?? $orderResponse['data']['shipping']['service_level']
                    ?? $currentCart['shipping']['service_level']
                    ?? $currentCart['metadata']['shipping']['service_level']
                    ?? ''));

                // Enforce shipping address as the main address on the order.
                $shippingPatchPayload = [
                    'shipping_address' => $shippingAddress,
                ];

                if ($serviceLevel !== '') {
                    $shippingPatchPayload['service_level'] = $serviceLevel;
                }

                $shippingUpdateResponse = $diinApi->updateOrderShipping($orderId, $shippingPatchPayload);

                $this->logger()->info('Shipping address enforced after checkout', [
                    'order_id' => $orderId,
                    'shipping_patch_payload' => $shippingPatchPayload,
                    'shipping_update_response' => $shippingUpdateResponse,
                ]);

                if (!isset($shippingUpdateResponse['data'])) {
                    return [
                        'success' => false,
                        'message' => $this->getSafeApiMessage($shippingUpdateResponse, 'shipping_address_required'),
                    ];
                }

                if (isset($shippingUpdateResponse['data'])) {
                    $orderResponse['data'] = $shippingUpdateResponse['data'];
                }

                if (empty($orderResponse['data']['shipping_address'])) {
                    return [
                        'success' => false,
                        'message' => Yii::t('shop', 'shipping_address_required'),
                    ];
                }

                // Clear cart
                // $cartId = $cartResponse['data']['id'];
                // $diinApi->deleteOrder($cartId);
                
                // Get order hash/ID for payment
                $orderHash = $orderResponse['data']['hash'] ?? '';
                
                // Log order data for debugging
                $this->logger()->info('Order updated successfully', [
                    'order_id' => $orderResponse['data']['id'] ?? null,
                    'order_hash' => $orderHash,
                    'order_data' => $orderResponse['data']
                ]);
                
                // Build URLs for diinpay-app
                $webhookUrl = Yii::$app->urlManager->createAbsoluteUrl($this->moduleRouteParams('default/payment-webhook'));
                $returnUrl = Yii::$app->urlManager->createAbsoluteUrl($this->moduleRouteParams('default/payment-return', ['hash' => $orderHash]));
                
                // Build payment URL with webhook and return URLs
                $paymentUrl = "{$diinpayUrl}/p/{$orderHash}?"
                    . "webhook_url=" . urlencode($webhookUrl)
                    . "&return_url=" . urlencode($returnUrl);
                
                return [
                    'success' => true,
                    'message' => Yii::t('shop', 'order_placed_successfully'),
                    'order_id' => $orderResponse['data']['id'],
                    'redirect' => $paymentUrl,
                ];
            }
            
            $this->logger()->error('Error creating order: ' . ($orderResponse['message'] ?? 'Unknown error'), [
                'order_response' => $orderResponse
            ]);

            return [
                'success' => false,
                'message' => $this->getSafeApiMessage($orderResponse, 'error_processing_order'),
            ];
            
        } catch (\Exception $e) {
            $this->logger()->error('Error processing checkout: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_processing_checkout'),
            ];
        }
    }

    /**
     * Confirmation page after successful order placement
     */
    public function actionConfirmation($hash)
    {
        $diinapi = $this->apiClient();

        $order = $diinapi->getOrder(['hash' => $hash]);

        $this->logger()->info('Confirmation page - API response', [
            'hash' => $hash,
            'response_status' => $order['status'] ?? 'unknown',
            'has_data' => isset($order['data']) ? 'yes' : 'no',
            'order_data' => $order['data'] ?? null,
        ]);

        // If order is non-editable, clear the cart session (only if session_id matches)
        if (isset($order['data'])) {
            $orderStatus = $order['data']['status'] ?? null;
            $orderSessionId = $order['data']['session_id'] ?? null;
            $currentSessionId = $this->sessionContext()->getAnonymousSessionId();
            
            $this->logger()->info('Confirmation page - session check', [
                'hash' => $hash,
                'order_status' => $orderStatus,
                'order_session_id' => $orderSessionId,
                'current_session_id' => $currentSessionId,
                'sessions_match' => $orderSessionId === $currentSessionId ? 'yes' : 'no',
            ]);
            
            if (in_array($orderStatus, self::TERMINAL_ORDER_STATUSES, true) && $orderSessionId === $currentSessionId) {
                $this->logger()->info('Order confirmation page - non-editable order status with matching session, clearing cart', [
                    'hash' => $hash,
                    'order_status' => $orderStatus,
                    'session_id' => $currentSessionId,
                ]);
                
                $this->clearCartSession();
            } elseif (in_array($orderStatus, self::TERMINAL_ORDER_STATUSES, true) && $orderSessionId !== $currentSessionId) {
                $this->logger()->warning('Order confirmation page - non-editable order status but session does not match', [
                    'hash' => $hash,
                    'order_status' => $orderStatus,
                    'order_session_id' => $orderSessionId,
                    'current_session_id' => $currentSessionId,
                ]);
            }
        } else {
            $this->logger()->error('Confirmation page - no order data returned from API', [
                'hash' => $hash,
                'full_response' => $order,
            ]);
        }

        return $this->render('confirmation', [
            'order' => $order['data'] ?? null,
        ]);
    }

    /**
     * Export confirmation order data as PDF.
     *
     * @param string $hash
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionConfirmationPdf($hash)
    {
        $mpdfClass = '\\Mpdf\\Mpdf';
        $destinationClass = '\\Mpdf\\Output\\Destination';

        if (!class_exists($mpdfClass) || !class_exists($destinationClass)) {
            $this->logger()->error('mPDF package is missing for confirmation PDF export', [
                'hash' => $hash,
            ]);

            throw new ServerErrorHttpException(Yii::t('shop', 'PDF export is currently unavailable.'));
        }

        /** @var DiinApi $diinapi */
        $diinapi = $this->apiClient();
        $orderResponse = $diinapi->getOrder(['hash' => $hash]);
        $order = $orderResponse['data'] ?? null;

        if (empty($order) || !is_array($order)) {
            $this->logger()->warning('PDF export requested but order was not found', [
                'hash' => $hash,
                'response' => $orderResponse,
            ]);

            throw new NotFoundHttpException(Yii::t('shop', 'Unable to retrieve your order details.'));
        }

        $html = $this->renderPartial('confirmation-pdf', [
            'order' => $order,
        ]);

        $tempDir = Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            $this->logger()->error('Unable to create mPDF temp directory', [
                'hash' => $hash,
                'temp_dir' => $tempDir,
            ]);

            throw new ServerErrorHttpException(Yii::t('shop', 'PDF export is currently unavailable.'));
        }

        if (!is_writable($tempDir)) {
            $this->logger()->error('mPDF temp directory is not writable', [
                'hash' => $hash,
                'temp_dir' => $tempDir,
            ]);

            throw new ServerErrorHttpException(Yii::t('shop', 'PDF export is currently unavailable.'));
        }

        try {
            $mpdf = new $mpdfClass([
                'format' => 'A4',
                'margin_top' => 12,
                'margin_right' => 12,
                'margin_bottom' => 12,
                'margin_left' => 12,
                'tempDir' => $tempDir,
            ]);

            $orderNumber = trim((string) ($order['order_number'] ?? $order['id'] ?? ''));
            $fileSuffix = $orderNumber !== '' ? $orderNumber : (string) $hash;
            $fileName = 'order-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $fileSuffix) . '.pdf';

            $mpdf->SetTitle(Yii::t('shop', 'Order Confirmation') . ' #' . $fileSuffix);
            $mpdf->WriteHTML($html);

            $destination = constant($destinationClass . '::STRING_RETURN');
            $pdfContent = $mpdf->Output($fileName, $destination);
        } catch (\Throwable $exception) {
            $this->logger()->error('mPDF confirmation export failed', [
                'hash' => $hash,
                'temp_dir' => $tempDir,
                'error' => $exception->getMessage(),
            ]);

            throw new ServerErrorHttpException(Yii::t('shop', 'PDF export is currently unavailable.'));
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/pdf');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $pdfContent;
    }

    /**
     * Get states by country ID (AJAX)
     * @return array
     */
    public function actionGetStates()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
                'data' => [],
            ];
        }

        $countryId = Yii::$app->request->get('country_id');

        if (!$countryId) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'country_id_required'),
                'data' => [],
            ];
        }

        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $response = $diinApi->getStatesByCountry($countryId);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'message' => $response['message'] ?? 'States fetched successfully',
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? Yii::t('shop', 'error_fetching_states'),
                'data' => [],
            ];
        } catch (\Exception $e) {
            $this->logger()->error('Error fetching states: ' . $e->getMessage(), [
                'exception' => $e,
                'country_id' => $countryId,
            ]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_fetching_states'),
                'data' => [],
            ];
        }
    }

    /**
     * Get cities by state ID (AJAX)
     * @return array
     */
    public function actionGetCities()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
                'data' => [],
            ];
        }

        $stateId = Yii::$app->request->get('state_id');

        if (!$stateId) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'state_id_required'),
                'data' => [],
            ];
        }

        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $response = $diinApi->getCitiesByState($stateId);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'message' => $response['message'] ?? 'Cities fetched successfully',
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? Yii::t('shop', 'error_fetching_cities'),
                'data' => [],
            ];
        } catch (\Exception $e) {
            $this->logger()->error('Error fetching cities: ' . $e->getMessage(), [
                'exception' => $e,
                'state_id' => $stateId,
            ]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_fetching_cities'),
                'data' => [],
            ];
        }
    }

    /**
     * Handle payment webhook from diinpay-app
     * This endpoint is called by diinpay-app after successful payment
     * @return array
     */
    public function actionPaymentWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Only POST requests are allowed',
            ];
        }

        try {
            // Get webhook payload
            $payload = Yii::$app->request->post();
            
            $this->logger()->info('Payment webhook received', [
                'payload' => $payload,
            ]);

            // Verify webhook signature (if implemented)
            // $signature = Yii::$app->request->headers->get('X-Webhook-Signature');
            // if (!$this->verifyWebhookSignature($payload, $signature)) {
            //     return ['success' => false, 'message' => 'Invalid signature'];
            // }

            // Extract order hash from payload
            $orderHash = $payload['order_hash'] ?? $payload['hash'] ?? null;
            $paymentStatus = $payload['status'] ?? $payload['payment_status'] ?? null;
            $orderId = $payload['order_id'] ?? $payload['id'] ?? null;

            if (!$orderHash || !$orderId) {
                $this->logger()->error('Invalid webhook payload - missing order_hash or order_id', [
                    'payload' => $payload,
                ]);

                return [
                    'success' => false,
                    'message' => 'Missing required fields: order_hash, order_id',
                ];
            }

            // Check if payment was successful
            if (!in_array($paymentStatus, ['paid', 'success', '1', 1])) {
                $this->logger()->error('Payment webhook received but payment not successful', [
                    'order_hash' => $orderHash,
                    'payment_status' => $paymentStatus,
                ]);

                return [
                    'success' => true,
                    'message' => 'Webhook received but payment not successful',
                ];
            }

            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            // Optional: Try to record payment in API
            try {
                $paymentData = [
                    'gateway_code' => $payload['gateway_code'] ?? 'stripe',
                    'transaction_id' => $payload['transaction_id'] ?? $payload['payment_intent'] ?? null,
                    'amount' => $payload['amount'] ?? null,
                    'currency' => $payload['currency'] ?? 'HNL',
                    'raw_payload' => $payload,
                ];

                $recordResult = $diinApi->recordPayment($orderId, $paymentData);
                
                $this->logger()->info('Payment recorded in API', [
                    'order_id' => $orderId,
                    'result' => $recordResult,
                ]);
            } catch (\Exception $e) {
                $this->logger()->warning('Could not record payment in API: ' . $e->getMessage());
                // Continue anyway - payment is already recorded in diinpay-app
            }

            // Clear cart for this session
            $this->clearCartSession();

            $this->logger()->info('Cart cleared after successful payment', [
                'order_hash' => $orderHash,
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully, cart cleared',
                'order_hash' => $orderHash,
                'order_id' => $orderId,
            ];

        } catch (\Exception $e) {
            $this->logger()->error('Error processing payment webhook: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => 'Error processing webhook',
            ];
        }
    }

    /**
     * Handle payment return/redirect from diinpay-app
     * User is redirected here after paying on diinpay-app
     * @param string $hash Order hash
     * @return Response
     */
    public function actionPaymentReturn($hash = null)
    {
        // Get hash from URL parameter
        if (!$hash) {
            $hash = Yii::$app->request->get('hash') ?? Yii::$app->request->get('order_hash');
        }

        if (!$hash) {
            $this->logger()->warning('Payment return called without hash');
            return $this->redirect($this->moduleRouteParams('products/index'));
        }

        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            // Get order details to verify payment status
            $orderResponse = $diinApi->getOrderByHash($hash);

            if (!isset($orderResponse['data'])) {
                $this->logger()->error('Order not found on payment return', [
                    'hash' => $hash,
                ]);

                return $this->redirect($this->moduleRouteParams('cart'));
            }

            $order = $orderResponse['data'];
            $orderStatus = $order['status'] ?? null;

            // Non-editable statuses must leave the cart flow and open confirmation.
            if (in_array($orderStatus, self::TERMINAL_ORDER_STATUSES, true)) {
                // Clear cart session and create new one
                $this->clearCartSession();

                $this->logger()->info('Payment return - non-editable order status, cart session cleared', [
                    'order_hash' => $hash,
                    'order_id' => $order['id'] ?? null,
                    'order_status' => $orderStatus,
                ]);

                // Redirect to confirmation page
                return $this->redirect($this->moduleRouteParams('default/confirmation', ['hash' => $hash]));
            } else {
                $this->logger()->warning('Payment return - order not paid', [
                    'hash' => $hash,
                    'order_status' => $orderStatus,
                ]);

                // Payment not successful, redirect to cart
                return $this->redirect($this->moduleRouteParams('cart'));
            }

        } catch (\Exception $e) {
            $this->logger()->error('Error processing payment return: ' . $e->getMessage(), [
                'exception' => $e,
                'hash' => $hash,
            ]);

            return $this->redirect($this->moduleRouteParams('cart'));
        }
    }

    /**
     * Clear cart session and reset cart session ID
     * Creates a fresh new session ID for the next cart
     */
    private function clearCartSession()
    {
        try {
            // Get the current session ID (cart being cleared)
            $oldSessionId = $this->sessionContext()->getAnonymousSessionId();

            $this->logger()->info('Clearing cart session', [
                'session_id' => $oldSessionId,
            ]);

            // Try to delete the old cart order from API (optional)
            try {
                /** @var ShopApiClientInterface $diinApi */
                $diinApi = $this->apiClient();

                $cartResponse = $diinApi->getOrder([
                    'session_id' => $oldSessionId,
                    'type' => 'cart'
                ]);

                if (isset($cartResponse['data']['id'])) {
                    $orderStatus = (string) ($cartResponse['data']['status'] ?? '');

                    if ($orderStatus === '' || $orderStatus === 'cart') {
                        $diinApi->deleteOrder($cartResponse['data']['id']);
                        
                        $this->logger()->info('Old cart deleted from API', [
                            'order_id' => $cartResponse['data']['id'],
                            'session_id' => $oldSessionId,
                            'order_status' => $orderStatus,
                        ]);
                    } else {
                        $this->logger()->info('Skipped deleting non-cart order while clearing session', [
                            'order_id' => $cartResponse['data']['id'],
                            'session_id' => $oldSessionId,
                            'order_status' => $orderStatus,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger()->warning('Could not delete old cart: ' . $e->getMessage());
            }

            // Regenerate session ID to get a fresh new one
            $newSessionId = $this->sessionContext()->getAnonymousSessionId(true);

            $this->logger()->info('New cart session created', [
                'old_session_id' => $oldSessionId,
                'new_session_id' => $newSessionId,
            ]);

        } catch (\Exception $e) {
            $this->logger()->error('Error clearing cart session: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Don't throw - just log and continue
        }
    }

    /**
     * Debug action to verify routing
     */
    public function actionDebug()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'success' => true,
            'message' => 'Debug endpoint works',
            'controller' => 'DefaultController',
            'action' => 'debug',
        ];
    }

    /**
     * Calculate shipping cost for current cart
     * @return array
     */
    public function actionCalculateShipping()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $this->logger()->info('actionCalculateShipping called');

        if (!Yii::$app->request->isPost && !Yii::$app->request->isPut) {
            $this->logger()->error('Invalid request method', [
                'isPost' => Yii::$app->request->isPost,
                'isPut' => Yii::$app->request->isPut,
            ]);
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }

        try {
            $sessionId = $this->sessionContext()->getAnonymousSessionId();
            $request = Yii::$app->request;

            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get current editable cart
            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

            if (!isset($cartResponse['data'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }

            $cartId = $cartResponse['data']['id'];

            // Build shipping address from request
            $shippingAddress = [
                'country_id' => (int) ($request->post('country_id') ?? $request->post('billing_country_id')),
                'state_id' => (int) ($request->post('state_id') ?? $request->post('billing_state_id')),
                'city_id' => (int) ($request->post('city_id') ?? $request->post('billing_city_id')),
            ];

            $shippingFirstName = trim((string) ($request->post('first_name') ?? $request->post('billing_first_name', '')));
            $shippingLastName = trim((string) ($request->post('last_name') ?? $request->post('billing_last_name', '')));
            $shippingPhone = trim((string) ($request->post('phone') ?? $request->post('billing_phone', '')));
            $shippingEmail = trim((string) ($request->post('email') ?? $request->post('billing_email', '')));

            if ($shippingFirstName !== '') {
                $shippingAddress['first_name'] = $shippingFirstName;
            }

            if ($shippingLastName !== '') {
                $shippingAddress['last_name'] = $shippingLastName;
            }

            $shippingFullName = trim($shippingFirstName . ' ' . $shippingLastName);
            if ($shippingFullName !== '') {
                $shippingAddress['full_name'] = $shippingFullName;
            }

            if ($shippingPhone !== '') {
                $shippingAddress['phone_number'] = $shippingPhone;
            }

            if ($shippingEmail !== '') {
                $shippingAddress['email'] = $shippingEmail;
            }

            // Add optional address fields if present
            $address1 = $request->post('address_1') ?? $request->post('billing_street');
            if ($address1) {
                $shippingAddress['address_1'] = $address1;
            }

            $zip = $request->post('zip') ?? $request->post('billing_zip');
            if ($zip) {
                $shippingAddress['zipcode'] = $zip;
            }

            $serviceLevel = $request->post('service_level');

            $this->logger()->info('Calculate shipping - Request', [
                'cart_id' => $cartId,
                'service_level' => $serviceLevel,
                'shipping_address' => $shippingAddress,
                'all_post_data' => $request->post(),
            ]);

            // Build payload for POST /shipping/quote (only location IDs, no address fields)
            $quotePayload = [
                'service_level' => $serviceLevel,
                'shipping_address' => [
                    'country_id' => $shippingAddress['country_id'],
                    'state_id' => $shippingAddress['state_id'],
                    'city_id' => $shippingAddress['city_id'],
                ],
            ];

            // Call API to calculate shipping
            $shippingResponse = $diinApi->calculateShippingQuote($cartId, $quotePayload);

            $this->logger()->info('Calculate shipping - Quote Response', [
                'shipping_response' => $shippingResponse,
            ]);

            if (!isset($shippingResponse['data'])) {
                return [
                    'success' => false,
                    'message' => $this->getSafeApiMessage($shippingResponse, 'error_calculating_shipping'),
                ];
            }

            // Build payload for PATCH /shipping (includes address fields)
            $patchPayload = [
                'service_level' => $serviceLevel,
                'shipping_address' => $shippingAddress, // This includes address_1 and zipcode if present
            ];

            // Now apply the shipping selection using PATCH endpoint
            $updateResponse = $diinApi->updateOrderShipping($cartId, $patchPayload);

            $this->logger()->info('Update shipping - Response', [
                'update_response' => $updateResponse,
                'patch_payload' => $patchPayload,
            ]);

            if (!isset($updateResponse['data'])) {
                return [
                    'success' => false,
                    'message' => $this->getSafeApiMessage($updateResponse, 'error_calculating_shipping'),
                ];
            }

            // Return the API response directly - all calculations are done by the API
            $updatedOrder = $updateResponse['data'];
            
            return [
                'success' => true,
                'message' => Yii::t('shop', 'shipping_calculated'),
                'data' => $updatedOrder,
            ];

        } catch (\Exception $e) {
            $this->logger()->error('Error calculating shipping: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_calculating_shipping'),
            ];
        }
    }

    /**
     * Get available shipping options for current cart
     * @return array
     */
    public function actionGetShippingOptions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $this->logger()->info('actionGetShippingOptions called');

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }

        try {
            $sessionId = $this->sessionContext()->getAnonymousSessionId();
            $request = Yii::$app->request;

            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get current editable cart
            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

            if (!isset($cartResponse['data'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }

            $cartId = $cartResponse['data']['id'];

            // Build shipping address from request
            $shippingAddress = [
                'country_id' => (int) $request->post('country_id'),
                'state_id' => (int) $request->post('state_id'),
                'city_id' => (int) $request->post('city_id'),
            ];

            // Validate address fields
            if (!$shippingAddress['country_id'] || !$shippingAddress['state_id'] || !$shippingAddress['city_id']) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'shipping_address_required'),
                ];
            }

            $this->logger()->info('Get shipping options - Request', [
                'cart_id' => $cartId,
                'shipping_address' => $shippingAddress,
            ]);

            // Call API to get shipping options
            $optionsResponse = $diinApi->getShippingOptions($cartId, [
                'shipping_address' => $shippingAddress,
            ]);

            $this->logger()->info('Get shipping options - Response', [
                'options_response' => $optionsResponse,
                'options_array' => isset($optionsResponse['data']['options']) ? $optionsResponse['data']['options'] : 'NO OPTIONS',
                'options_count' => isset($optionsResponse['data']['options']) ? count($optionsResponse['data']['options']) : 0,
            ]);

            if (!isset($optionsResponse['data'])) {
                if ($this->isShippingRateNotFoundResponse($optionsResponse)) {
                    return [
                        'success' => true,
                        'message' => Yii::t('shop', 'shipping_options_unavailable_fallback'),
                        'data' => [
                            'order_id' => $cartId,
                            'currency' => 'HNL',
                            'options' => [],
                            'no_shipping_available' => true,
                        ],
                    ];
                }

                return [
                    'success' => false,
                    'message' => $this->getSafeApiMessage($optionsResponse, 'error_getting_shipping_options'),
                ];
            }

            $optionsData = $optionsResponse['data'];
            $normalizedOptions = [];

            if (isset($optionsData['options']) && is_array($optionsData['options'])) {
                $normalizedOptions = $optionsData['options'];
            } elseif (isset($optionsData['shipping']['options']) && is_array($optionsData['shipping']['options'])) {
                $normalizedOptions = $optionsData['shipping']['options'];
            } elseif (array_values($optionsData) === $optionsData) {
                $normalizedOptions = $optionsData;
            }

            if (empty($normalizedOptions) && !empty($cartResponse['data']['shipping']) && is_array($cartResponse['data']['shipping'])) {
                $normalizedOptions = [
                    $cartResponse['data']['shipping'],
                ];
            }

            $noShippingAvailable = empty($normalizedOptions);

            return [
                'success' => true,
                'message' => Yii::t('shop', $noShippingAvailable ? 'shipping_options_unavailable_fallback' : 'shipping_options_found'),
                'data' => [
                    'order_id' => $optionsData['order_id'] ?? $cartId,
                    'currency' => $optionsData['currency'] ?? 'HNL',
                    'options' => $normalizedOptions,
                    'no_shipping_available' => $noShippingAvailable,
                ],
            ];

        } catch (\Exception $e) {
            $this->logger()->error('Error getting shipping options: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_getting_shipping_options'),
            ];
        }
    }
}


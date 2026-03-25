<?php
namespace diincompany\shop\controllers;

use diincompany\shop\contracts\ShopApiClientInterface;
use diincompany\shop\contracts\ShopLoggerInterface;
use diincompany\shop\contracts\ShopSessionContextInterface;
use diincompany\shop\Module as ShopModule;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class CartController extends Controller
{
    private const ACTIVE_CART_STATUSES = ['cart'];
    private const TERMINAL_ORDER_STATUSES = ['pending', 'paid', 'completed', 'processing'];

    private function shopModule(): ShopModule
    {
        $module = $this->module;

        if (!$module instanceof ShopModule) {
            throw new \yii\base\InvalidConfigException('CartController must run inside DiinCompany\\Yii2Shop\\Module.');
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

    /**
     * Get editable cart for current session.
     *
     * Only carts in `cart` status are editable. If API returns a pending or
     * paid/completed/processing order for this session, force a new session id.
     *
     * @param ShopApiClientInterface $diinApi
     * @param string $sessionId
     * @return array
     */
    private function getEditableCart(ShopApiClientInterface $diinApi, string &$sessionId): array
    {
        $response = $diinApi->getOrder([
            'session_id' => $sessionId,
            'type' => 'cart',
            'status' => 'cart',
        ]);

        if (isset($response['data'])) {
            $orderStatus = (string) ($response['data']['status'] ?? '');

            if ($orderStatus === '' || $orderStatus === 'cart') {
                return $response;
            }

            if (in_array($orderStatus, self::TERMINAL_ORDER_STATUSES, true)) {
                $oldSessionId = $sessionId;
                $sessionId = $this->sessionContext()->getAnonymousSessionId(true);

                $this->logger()->info('Regenerated cart session for non-editable order status', [
                    'old_session_id' => $oldSessionId,
                    'new_session_id' => $sessionId,
                    'order_status' => $orderStatus,
                ]);

                return [];
            }

            return [];
        }

        // Defensive fallback: API may hide non-cart statuses when filtering by `status=cart`.
        // Check the session without status filter to avoid reusing a paid order session.
        $rawSessionOrderResponse = $diinApi->getOrder([
            'session_id' => $sessionId,
            'type' => 'cart',
        ]);

        if (isset($rawSessionOrderResponse['data'])) {
            $rawOrderStatus = (string) ($rawSessionOrderResponse['data']['status'] ?? '');

            if ($rawOrderStatus !== '' && $rawOrderStatus !== 'cart') {
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
     * Get cart summary for async UI rendering
     * @return array
     */
    public function actionSummary()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $sessionId = $this->sessionContext()->getAnonymousSessionId();

            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get editable cart for current session
            $response = $this->getCurrentCartByStatus($diinApi, $sessionId);

            $cartData = $response['data'] ?? [];

            return [
                'success' => true,
                'data' => $cartData,
            ];
        } catch (\Throwable $e) {
            $this->logger()->error('Error loading cart summary: ' . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_loading_cart'),
                'data' => [],
            ];
        }
    }

    /**
     * Add item to cart
     * @return array
     */
    public function actionAddItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        
        if (!$request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'Invalid request method'),
            ];
        }

        $productId = (int) $request->post('product_id');
        $quantity = (int) $request->post('quantity', 1);
        $normalizeVariantId = static function ($value): string {
            if ($value === null) {
                return '';
            }

            $rawValue = trim((string) $value);
            if ($rawValue === '' || $rawValue === '0') {
                return '';
            }

            return $rawValue;
        };

        $variantId = $normalizeVariantId($request->post('variant_id', ''));
        $sessionId = $this->sessionContext()->getAnonymousSessionId();

        $extractOrderItemError = static function (array $apiResponse): array {
            $rawMessage = trim((string) ($apiResponse['message'] ?? ''));
            $errorCode = $rawMessage;
            $clearMessage = $rawMessage;
            $availableStock = null;

            // The API may return errors nested as errors.errors (older format)
            // or flat as errors directly (newer format) — handle both.
            $errorsNested = $apiResponse['errors']['errors'] ?? null;
            $errorsFlat = is_array($apiResponse['errors'] ?? null) ? $apiResponse['errors'] : null;
            $errors = is_array($errorsNested) ? $errorsNested : $errorsFlat;

            if (is_array($errors)) {
                $quantityErrors = $errors['quantity'] ?? null;
                if (is_array($quantityErrors) && !empty($quantityErrors)) {
                    $firstError = trim((string) $quantityErrors[0]);
                    if ($firstError !== '') {
                        $clearMessage = $firstError;
                    }
                }

                if (array_key_exists('available_stock', $errors) && is_numeric($errors['available_stock'])) {
                    $availableStock = (int) $errors['available_stock'];
                }
            }

            // When no descriptive message was extracted, map known error codes to friendly translations.
            if ($clearMessage === $rawMessage) {
                $knownErrorMessages = [
                    'INVALID_ORDER_ITEM' => Yii::t('shop', 'invalid_order_item_stock'),
                ];
                if (isset($knownErrorMessages[$errorCode])) {
                    $clearMessage = $knownErrorMessages[$errorCode];
                }
            }

            if ($clearMessage === '') {
                $clearMessage = Yii::t('shop', 'Failed to add product to cart');
            }

            return [
                'message' => $clearMessage,
                'error_code' => $errorCode,
                'available_stock' => $availableStock,
            ];
        };

        $this->logger()->info('Add item to cart request', [
            'product_id' => $productId,
            'quantity' => $quantity,
            'variant_id' => $variantId,
            'session_id' => $sessionId,
        ]);

        if ($productId <= 0) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'Product ID is required'),
            ];
        }

        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_quantity'),
            ];
        }

        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $getOrderResponse = $this->getEditableCart($diinApi, $sessionId);

            $this->logger()->info('Get active cart response', [
                'session_id' => $sessionId,
                'response_data' => $getOrderResponse,
            ]);

            if(isset($getOrderResponse['data'])) {
                $orderId = $getOrderResponse['data']['id'];
                $items = $getOrderResponse['data']['items'] ?? [];
                $productFound = false;

                // Keep existing item structure (including item ids) and increment first match
                foreach ($items as &$item) {
                    $existingVariantId = $normalizeVariantId($item['product_variant_id'] ?? ($item['variant_id'] ?? ''));

                    if ((int) ($item['product_id'] ?? 0) === $productId && $existingVariantId === $variantId) {
                        $item['quantity'] = (int) ($item['quantity'] ?? 0) + $quantity;
                        $productFound = true;
                        break;
                    }
                }
                unset($item);

                // If product wasn't found, add as a new line item
                if (!$productFound) {
                    $newItem = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ];

                    if ($variantId !== '') {
                        $newItem['product_variant_id'] = $variantId;
                    }

                    $items[] = $newItem;
                }

                $payload = [
                    'session_id' => $sessionId,
                    'type' => 'cart',
                    'items' => $items,
                ];

                $this->logger()->info('Putting order (updating cart)', [
                    'order_id' => $orderId,
                    'payload' => $payload,
                ]);

                $response = $diinApi->putOrder($orderId, $payload);
            } else {
                $payload = [
                    'session_id' => $sessionId,
                    'type' => 'cart',
                    'items' => []
                ];

                $newItem = [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $quantity,
                ];

                if ($variantId !== '') {
                    $newItem['product_variant_id'] = $variantId;
                }

                $payload['items'][] = $newItem;

                $this->logger()->info('Posting new order (creating cart)', [
                    'session_id' => $sessionId,
                    'payload' => $payload,
                ]);

                $response = $diinApi->postOrder($payload);
            }

            $this->logger()->info('Add item response', [
                'response' => $response,
            ]);

            if (isset($response['data'])) {
                // For new carts (POST), the response should already have the items
                // No need for an additional GET request which might not return items immediately
                if (!empty($response['data']['items'])) {
                    $this->logger()->info('Response has items, returning immediately', [
                        'item_count' => count($response['data']['items']),
                    ]);

                    return [
                        'success' => true,
                        'message' => Yii::t('shop', 'Product added to cart successfully'),
                        'data' => $response['data'],
                    ];
                }
                
                // If the response doesn't have items, try to get fresh data from the API
                $this->logger()->info('Response missing items, fetching fresh cart data', [
                    'session_id' => $sessionId,
                ]);

                // Add a small delay to allow the API to process the order
                usleep(200000); // 200ms

                $freshCartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

                $this->logger()->info('Fresh cart response', [
                    'response' => $freshCartResponse,
                ]);

                return [
                    'success' => true,
                    'message' => Yii::t('shop', 'Product added to cart successfully'),
                    'data' => $freshCartResponse['data'] ?? $response['data'],
                ];
            }

            $this->logger()->error('Failed to add product to cart - no data in response', [
                'response' => $response,
                'payload' => $payload ?? null,
            ]);

            $orderItemError = $extractOrderItemError($response);

            return [
                'success' => false,
                'message' => $orderItemError['message'],
                'error_code' => $orderItemError['error_code'],
                'available_stock' => $orderItemError['available_stock'],
            ];

        } catch (\Exception $e) {
            $this->logger()->error('Error adding product to cart: ' . $e->getMessage(), ['exception' => $e]);
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'An error occurred while adding the product'),
            ];
        }
    }
    
    /**
     * Update item quantity in cart
     * @return array
     */
    public function actionUpdateQuantity()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }
        
        $itemId = Yii::$app->request->post('item_id');
        $quantity = (int) Yii::$app->request->post('quantity', 1);
        $sessionId = $this->sessionContext()->getAnonymousSessionId();
        
        if (!$itemId) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'item_id_required'),
            ];
        }
        
        if ($quantity < 1) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_quantity'),
            ];
        }
        
        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();
            
            // Get current cart
            $cartResponse = $this->getEditableCart($diinApi, $sessionId);
            
            if (!isset($cartResponse['data'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }
            
            $cart = $cartResponse['data'];
            $items = $cart['items'] ?? [];
            
            // Update item quantity
            foreach ($items as &$item) {
                if ($item['id'] == $itemId) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
            
            // Update cart
            $response = $diinApi->putOrder($cart['id'], [
                'session_id' => $sessionId,
                'type' => 'cart',
                'items' => $items
            ]);
            
            if (isset($response['data'])) {
                $freshCartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

                return [
                    'success' => true,
                    'message' => Yii::t('shop', 'quantity_updated'),
                    'data' => $freshCartResponse['data'] ?? $response['data'],
                ];
            }
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_updating_quantity'),
            ];
            
        } catch (\Exception $e) {
            $this->logger()->error('Error updating quantity: ' . $e->getMessage(), ['exception' => $e]);
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_updating_quantity'),
            ];
        }
    }

    /**
     * Update multiple item quantities in cart atomically
     * @return array
     */
    public function actionUpdateItems()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }

        $requestedItems = Yii::$app->request->post('items', []);
        $sessionId = $this->sessionContext()->getAnonymousSessionId();

        if (!is_array($requestedItems) || empty($requestedItems)) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'item_id_required'),
            ];
        }

        $updatesByItemId = [];

        foreach ($requestedItems as $requestedItem) {
            if (!is_array($requestedItem)) {
                continue;
            }

            $itemId = (int) ($requestedItem['item_id'] ?? 0);
            $quantity = (int) ($requestedItem['quantity'] ?? 0);

            if ($itemId <= 0) {
                continue;
            }

            if ($quantity < 1) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'invalid_quantity'),
                ];
            }

            $updatesByItemId[$itemId] = $quantity;
        }

        if (empty($updatesByItemId)) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'item_id_required'),
            ];
        }

        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $cartResponse = $this->getEditableCart($diinApi, $sessionId);

            if (!isset($cartResponse['data'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }

            $cart = $cartResponse['data'];
            $items = $cart['items'] ?? [];
            $matchedItems = 0;

            foreach ($items as &$item) {
                $itemId = (int) ($item['id'] ?? 0);

                if ($itemId > 0 && array_key_exists($itemId, $updatesByItemId)) {
                    $item['quantity'] = $updatesByItemId[$itemId];
                    $matchedItems++;
                }
            }
            unset($item);

            if ($matchedItems === 0) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'item_id_required'),
                ];
            }

            $response = $diinApi->putOrder($cart['id'], [
                'session_id' => $sessionId,
                'type' => 'cart',
                'items' => $items,
            ]);

            if (isset($response['data'])) {
                $freshCartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);

                return [
                    'success' => true,
                    'message' => Yii::t('shop', 'quantity_updated'),
                    'data' => $freshCartResponse['data'] ?? $response['data'],
                ];
            }

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_updating_quantity'),
            ];
        } catch (\Exception $e) {
            $this->logger()->error('Error updating cart items: ' . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_updating_quantity'),
            ];
        }
    }
    
    /**
     * Remove item from cart
     * @return array
     */
    public function actionRemoveItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }
        
        $itemId = Yii::$app->request->post('item_id');
        $sessionId = $this->sessionContext()->getAnonymousSessionId();
        
        if (!$itemId) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'item_id_required'),
            ];
        }
        
        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $response = $diinApi->deleteOrderItem((int) $itemId);

            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'message' => Yii::t('shop', 'item_removed'),
                    'data' => $response['data'],
                ];
            }

            $this->logger()->error('Failed to remove item from cart', [
                'item_id' => $itemId,
                'session_id' => $sessionId,
                'response' => $response,
            ]);

            return [
                'success' => false,
                'message' => $response['message'] ?? Yii::t('shop', 'error_removing_item'),
            ];
            
        } catch (\Exception $e) {
            $this->logger()->error('Error removing item: ' . $e->getMessage(), ['exception' => $e]);
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_removing_item'),
            ];
        }
    }
    
    /**
     * Apply coupon code
     * @return array
     */
    public function actionApplyCoupon()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => Yii::t('shop', 'invalid_request_method'),
            ];
        }
        
        $couponCode = trim((string) Yii::$app->request->post('coupon_code', ''));
        $sessionId = $this->sessionContext()->getAnonymousSessionId();
        
        try {
            /** @var ShopApiClientInterface $diinApi */
            $diinApi = $this->apiClient();

            $cartResponse = $this->getCurrentCartByStatus($diinApi, $sessionId);
            if (!isset($cartResponse['data']['id'])) {
                return [
                    'success' => false,
                    'message' => Yii::t('shop', 'cart_not_found'),
                ];
            }

            $orderId = (int) $cartResponse['data']['id'];
            $currentCart = $cartResponse['data'];
            $updateResponse = $diinApi->putOrder($orderId, [
                'session_id' => $sessionId,
                'type' => 'cart',
                'items' => $currentCart['items'] ?? [],
                'coupon_code' => $couponCode,
            ]);

            if (!isset($updateResponse['data'])) {
                return [
                    'success' => false,
                    'message' => $updateResponse['message'] ?? Yii::t('shop', 'error_applying_coupon'),
                ];
            }

            $updatedCart = $updateResponse['data'];

            for ($attempt = 0; $attempt < 3; $attempt++) {
                if ($attempt > 0) {
                    usleep(150000);
                }

                $freshCartResponse = $diinApi->getOrderById($orderId);
                if (isset($freshCartResponse['data'])) {
                    $updatedCart = $freshCartResponse['data'];
                    if (array_key_exists('total_amount', $updatedCart)) {
                        break;
                    }
                }
            }

            $message = $couponCode === ''
                ? Yii::t('shop', 'coupon_removed')
                : Yii::t('shop', 'coupon_applied');
            
            return [
                'success' => true,
                'message' => $message,
                'data' => $updatedCart,
            ];
            
        } catch (\Exception $e) {
            $this->logger()->error('Error applying coupon: ' . $e->getMessage(), ['exception' => $e]);
            
            return [
                'success' => false,
                'message' => Yii::t('shop', 'error_applying_coupon'),
            ];
        }
    }
}

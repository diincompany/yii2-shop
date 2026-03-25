<?php
namespace diincompany\shop\components;

use GuzzleHttp\Client;
use Yii;
use yii\base\Component;
use yii\helpers\VarDumper;
use diincompany\shop\contracts\ShopApiClientInterface;

class DiinApi extends Component implements ShopApiClientInterface
{
    public string $host;
    public string $merchantCode;
    public string $audience;
    public string $env;
    public string $version = 'v1';
    public int $merchantCacheTtl = 900;
    public int $categoriesCacheTtl = 900;
    
    private object $logger;
    private string $token = '';
    private ?array $tokenData = null;
    

    public function init()
    {
        parent::init();

        $this->host = empty($this->host) ? Yii::$app->params['diinpay']['api']['host'] : $this->host;
        $this->merchantCode = empty($this->merchantCode) ? Yii::$app->params['diinpay']['merchantCode'] : $this->merchantCode;
        $this->audience = empty($this->audience) ? Yii::$app->params['diinpay']['audience'] : $this->audience;
        $this->merchantCacheTtl = (int) (Yii::$app->params['diinpay']['merchantCacheTtl'] ?? $this->merchantCacheTtl);
        $this->categoriesCacheTtl = (int) (Yii::$app->params['diinpay']['categoriesCacheTtl'] ?? $this->categoriesCacheTtl);

        $this->logger = Yii::$app->logtail;
        
        // Generate a seed token from merchantCode (used for first auth request)
        $this->token = $this->generateSeedToken();
        
        // Ensure token is valid on initialization
        $this->ensureValidToken();
    }
    
    /**
     * Generate initial seed token from merchant credentials
     * Returns empty string to force immediate token refresh
     */
    private function generateSeedToken(): string
    {
        // Return empty string to force token refresh on first use
        // The refreshToken() method will get a real token from Auth0
        return '';
    }
    
    /**
     * Check if current token is expired or invalid
     */
    private function isTokenExpired(): bool
    {
        // No token or invalid token format
        if (empty($this->token) || $this->token === 'null') {
            return true;
        }
        
        // No token data or missing expiration
        if (!$this->tokenData || !isset($this->tokenData['expires_at'])) {
            return true;
        }
        
        // Refresh if expires in less than 5 minutes
        return time() >= ($this->tokenData['expires_at'] - 300);
    }
    
    /**
     * Ensure we have a valid token, refresh if needed
     */
    private function ensureValidToken()
    {
        if ($this->isTokenExpired()) {
            $this->refreshToken();
        }
    }
    
    /**
     * Refresh token from Auth0 endpoint
     */
    private function refreshToken()
    {
        // Validate merchant code before attempting refresh
        if (empty($this->merchantCode)) {
            $errorMsg = 'MERCHANT_ID environment variable is not set or is empty';
            $this->logger->error('Cannot refresh token - missing MERCHANT_ID', [
                'merchant_code' => $this->merchantCode,
                'error' => $errorMsg,
            ]);
            throw new \Exception($errorMsg);
        }
        
        if (!preg_match('/^mer_[a-f0-9-]+$/i', $this->merchantCode)) {
            $errorMsg = 'MERCHANT_ID format is invalid. Expected format: mer_[uuid]';
            $this->logger->error('Invalid MERCHANT_ID format', [
                'merchant_code' => $this->merchantCode,
                'expected_format' => 'mer_[uuid]',
                'error' => $errorMsg,
            ]);
            throw new \Exception($errorMsg);
        }

        $client = new Client();
        $tokenEndpoint = $this->host . '/v1/auth/token';
        
        // Don't send Authorization header if we don't have a valid token
        // The auth endpoint should work without it for initial token request
        $headers = [
            'Content-Type' => 'application/json',
        ];
        
        // Only add Authorization if we have a valid existing token
        if (!empty($this->token) && $this->token !== 'null') {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        
        $config = [
            'headers' => $headers,
            'json' => [
                'merchant_code' => $this->merchantCode,
                'grant_type' => 'client_credentials',
                'audience' => $this->audience,
            ],
            'http_errors' => false,
        ];
        
        try {
            $response = $client->request('POST', $tokenEndpoint, $config);
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            // Check HTTP status code
            if ($statusCode !== 200) {
                $errorMsg = $body['message'] ?? "HTTP $statusCode error";
                $this->logger->error('Auth API returned error response', [
                    'status_code' => $statusCode,
                    'merchant_code' => $this->merchantCode,
                    'message' => $errorMsg,
                    'response' => $body,
                    'endpoint' => $tokenEndpoint,
                ]);
                throw new \Exception("Authentication failed: $errorMsg (HTTP $statusCode)");
            }
            
            // Check response structure
            if (!isset($body['data']['access_token'])) {
                $this->logger->error('Invalid response structure from auth endpoint', [
                    'merchant_code' => $this->merchantCode,
                    'status' => $body['status'] ?? 'unknown',
                    'message' => $body['message'] ?? 'No message',
                    'response' => $body,
                    'endpoint' => $tokenEndpoint,
                ]);
                throw new \Exception('Authentication response missing access_token');
            }
            
            $this->token = $body['data']['access_token'];
            
            // Store token with expiration info
            $tokenData = [
                'token' => $body['data']['access_token'],
                'expires_in' => $body['data']['expires_in'] ?? 86400,
                'expires_at' => time() + ($body['data']['expires_in'] ?? 86400),
                'token_type' => $body['data']['token_type'] ?? 'Bearer',
                'refreshed_at' => date('Y-m-d H:i:s'),
            ];
            $this->tokenData = $tokenData;
            
            $this->logger->info('Token refreshed successfully', [
                'merchant_code' => $this->merchantCode,
                'expires_in' => $tokenData['expires_in'],
                'expires_at' => date('Y-m-d H:i:s', $tokenData['expires_at']),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Exception while refreshing authentication token', [
                'merchant_code' => $this->merchantCode,
                'error' => $e->getMessage(),
                'endpoint' => $tokenEndpoint,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getSlides($sliderKey = 'home')
    {
        $endpoint = "home-slider/{$sliderKey}";
        
        $response = $this->request($endpoint);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'slider_key' => $sliderKey,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching slides',
                'data' => [],
                'error' => $response['name'] ?? 'unknown',
            ];
        }

        // Extraer los items del slider
        $items = $response['data']['items'] ?? [];
        
        // Filtrar solo items activos y ordenar por posición
        $activeItems = array_filter($items, function($item) {
            return isset($item['is_active']) && $item['is_active'] == 1;
        });
        
        usort($activeItems, function($a, $b) {
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });

        return [
            'success' => true,
            'data' => $activeItems,
        ];
    }

    public function getCountries($payload = null)
    {
        $endpoint = 'countries';
        
        if ($payload === null) {
            $payload = [];
        }
        
        $response = $this->request($endpoint, $payload);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching countries',
                'data' => [],
            ];
        }

        return $response;
    }

    public function getStatesByCountry($countryId)
    {
        $endpoint = "states/by-country/{$countryId}";
        
        $response = $this->request($endpoint);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'country_id' => $countryId,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching states',
                'data' => [],
            ];
        }

        return $response;
    }

    public function getCitiesByState($stateId)
    {
        $endpoint = "cities/by-state/{$stateId}";
        
        $response = $this->request($endpoint);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'state_id' => $stateId,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching cities',
                'data' => [],
            ];
        }

        return $response;
    }

    public function getMerchant(bool $forceRefresh = false)
    {
        $cache = Yii::$app->cache;
        $cacheKey = [__CLASS__, 'merchant', $this->merchantCode];
        $backupKey = [__CLASS__, 'merchant', 'backup', $this->merchantCode];

        if (!$forceRefresh) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached) && isset($cached['data'])) {
                return $cached;
            }
        }

        $endpoint = 'merchant';
        $response = $this->request($endpoint);

        if (!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'response' => $response,
            ]);

            $backup = $cache->get($backupKey);
            if (is_array($backup) && isset($backup['data'])) {
                return $backup;
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching merchant',
                'data' => [],
            ];
        }

        $ttl = max(60, $this->merchantCacheTtl);
        $cache->set($cacheKey, $response, $ttl);
        $cache->set($backupKey, $response);

        return $response;
    }

    public function getMerchantData(bool $forceRefresh = false): array
    {
        $merchant = $this->getMerchant($forceRefresh);
        return (isset($merchant['data']) && is_array($merchant['data'])) ? $merchant['data'] : [];
    }

    public function clearMerchantCache(): void
    {
        Yii::$app->cache->delete([__CLASS__, 'merchant', $this->merchantCode]);
    }

    public function getCategories($payload = null, bool $forceRefresh = false)
    {
        $endpoint = 'categories';

        $normalizedPayload = $this->normalizeCachePayload($payload);
        $payloadHash = md5(json_encode($normalizedPayload));
        $cache = Yii::$app->cache;
        $cacheKey = [__CLASS__, 'categories', $this->merchantCode, $payloadHash];
        $backupKey = [__CLASS__, 'categories', 'backup', $this->merchantCode, $payloadHash];

        if (!$forceRefresh) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached) && isset($cached['data'])) {
                return $cached;
            }
        }

        $response = $this->request($endpoint, $payload);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'], [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
            ]);

            $backup = $cache->get($backupKey);
            if (is_array($backup) && isset($backup['data'])) {
                return $backup;
            }
            
            return [
                'success' => false,
                'message' => $response['message'],
                'data' => [],
                'error' => $response['name'],
            ];
        }

        $ttl = max(60, $this->categoriesCacheTtl);
        $cache->set($cacheKey, $response, $ttl);
        $cache->set($backupKey, $response);

        return $response;
    }

    public function clearCategoriesCache($payload = null): void
    {
        $normalizedPayload = $this->normalizeCachePayload($payload);
        $payloadHash = md5(json_encode($normalizedPayload));

        Yii::$app->cache->delete([__CLASS__, 'categories', $this->merchantCode, $payloadHash]);
    }

    private function normalizeCachePayload($payload): array
    {
        if ($payload === null) {
            return [];
        }

        if (!is_array($payload)) {
            return ['raw' => (string) $payload];
        }

        return $this->sortArrayRecursive($payload);
    }

    private function sortArrayRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortArrayRecursive($item);
            }
        }

        ksort($value);

        return $value;
    }

    public function getCategory(array $params = [])
    {
        $endpoint = 'category';

        if(isset($params['slug']))
            $endpoint .= "/{$params['slug']}";
        
        if(isset($params['id']))
            $endpoint .= "/{$params['id']}";
        
        $response = $this->request($endpoint);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'], [
                'endpoint' => $endpoint,
                'params' => $params,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'],
                'data' => [],
            ];
        }

        return $response;
    }

    public function getProducts($payload = null)
    {
        $endpoint = 'products';

        $payload = $this->normalizeProductFilters($payload);

        $response = $this->request($endpoint, $payload);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'], [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'],
                'data' => [],
                'error' => $response['name'],
            ];
        }

        return $response;
    }

    /**
     * Normalize products filters to keep compatibility with price and sort aliases.
     * Supports min/max as independent values or full range.
     */
    private function normalizeProductFilters($payload)
    {
        if ($payload === null) {
            return null;
        }

        if (!is_array($payload)) {
            return $payload;
        }

        $minPriceValue = null;
        $maxPriceValue = null;

        if (array_key_exists('min_price', $payload) && is_numeric($payload['min_price'])) {
            $minPriceValue = (float)$payload['min_price'];
        } elseif (array_key_exists('price_min', $payload) && is_numeric($payload['price_min'])) {
            $minPriceValue = (float)$payload['price_min'];
        } elseif (array_key_exists('min', $payload) && is_numeric($payload['min'])) {
            $minPriceValue = (float)$payload['min'];
        }

        if (array_key_exists('max_price', $payload) && is_numeric($payload['max_price'])) {
            $maxPriceValue = (float)$payload['max_price'];
        } elseif (array_key_exists('price_max', $payload) && is_numeric($payload['price_max'])) {
            $maxPriceValue = (float)$payload['price_max'];
        } elseif (array_key_exists('max', $payload) && is_numeric($payload['max'])) {
            $maxPriceValue = (float)$payload['max'];
        }

        if ($minPriceValue !== null) {
            $payload['min_price'] = $minPriceValue;
        }

        if ($maxPriceValue !== null) {
            $payload['max_price'] = $maxPriceValue;
        }

        if (array_key_exists('sort', $payload) && is_scalar($payload['sort'])) {
            $sortRaw = strtolower(trim((string)$payload['sort']));
            $sortKey = preg_replace('/[^a-z0-9]+/', '_', $sortRaw);
            $sortKey = trim((string)$sortKey, '_');

            $sortAliases = [
                'featured' => 'featured',
                'destacados' => 'featured',
                'best_selling' => 'best_selling',
                'best_seller' => 'best_selling',
                'best_sellers' => 'best_selling',
                'sales' => 'sales',
                'mas_vendidos' => 'best_selling',
                'price_asc' => 'price_asc',
                'price_low_to_high' => 'price_asc',
                'precio_menor_a_mayor' => 'price_asc',
                'price_desc' => 'price_desc',
                'price_high_to_low' => 'price_desc',
                'precio_mayor_a_menor' => 'price_desc',
                'date_desc' => 'date_desc',
                'newest' => 'newest',
                'fecha_mas_reciente' => 'date_desc',
                'date_asc' => 'date_asc',
                'oldest' => 'oldest',
                'fecha_mas_antiguo' => 'date_asc',
            ];

            if (isset($sortAliases[$sortRaw])) {
                $payload['sort'] = $sortAliases[$sortRaw];
            } elseif (isset($sortAliases[$sortKey])) {
                $payload['sort'] = $sortAliases[$sortKey];
            }
        }

        // Support on-sale aliases while keeping canonical `on_sale` expected by API.
        $onSaleValue = null;
        if (array_key_exists('on_sale', $payload)) {
            $onSaleValue = $payload['on_sale'];
        } elseif (array_key_exists('sale', $payload)) {
            $onSaleValue = $payload['sale'];
        } elseif (array_key_exists('is_on_sale', $payload)) {
            $onSaleValue = $payload['is_on_sale'];
        }

        if ($onSaleValue !== null) {
            if (is_bool($onSaleValue)) {
                $payload['on_sale'] = $onSaleValue ? 1 : 0;
            } elseif (is_numeric($onSaleValue)) {
                $payload['on_sale'] = ((int)$onSaleValue) === 1 ? 1 : 0;
            } elseif (is_scalar($onSaleValue)) {
                $onSaleRaw = strtolower(trim((string)$onSaleValue));
                if (in_array($onSaleRaw, ['1', 'true', 'yes'], true)) {
                    $payload['on_sale'] = 1;
                } elseif (in_array($onSaleRaw, ['0', 'false', 'no'], true)) {
                    $payload['on_sale'] = 0;
                }
            }
        }

        // Support featured aliases while keeping canonical `featured` expected by API.
        $featuredValue = null;
        if (array_key_exists('featured', $payload)) {
            $featuredValue = $payload['featured'];
        } elseif (array_key_exists('is_featured', $payload)) {
            $featuredValue = $payload['is_featured'];
        }

        if ($featuredValue !== null) {
            if (is_bool($featuredValue)) {
                $payload['featured'] = $featuredValue ? 1 : 0;
            } elseif (is_numeric($featuredValue)) {
                $payload['featured'] = ((int)$featuredValue) === 1 ? 1 : 0;
            } elseif (is_scalar($featuredValue)) {
                $featuredRaw = strtolower(trim((string)$featuredValue));
                if (in_array($featuredRaw, ['1', 'true', 'yes'], true)) {
                    $payload['featured'] = 1;
                } elseif (in_array($featuredRaw, ['0', 'false', 'no'], true)) {
                    $payload['featured'] = 0;
                }
            }
        }

        return $payload;
    }

    public function getProduct(array $params = [])
    {
        $endpoint = 'product';
        $payload = null;

        // El endpoint acepta el ID o slug en la URL
        if(isset($params['id'])) {
            $endpoint .= "/{$params['id']}";
        }
        
        if(isset($params['slug'])) {
            $endpoint .= "/{$params['slug']}";
        }

        $response = $this->request($endpoint, $payload);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'params' => $params,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching product',
                'data' => [],
            ];
        }

        return $response;
    }

    public function getPromoBoxes($key = 'home')
    {
        $endpoint = "home-promo-boxes";
        
        $response = $this->request($endpoint, ['key' => $key]);

        if(!isset($response['data'])) {
            $this->logger->error($response['message'] ?? 'Unknown error', [
                'endpoint' => $endpoint,
                'key' => $key,
                'response' => $response,
            ]);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Error fetching promo boxes',
                'data' => [],
                'error' => $response['name'] ?? 'unknown',
            ];
        }

        return $response;
    }

    public function postOrder(array $payload)
    {
        $endpoint = 'orders';

        $data = $this->request($endpoint, $payload, 'POST');

        $statusCode = (int) ($data['status_code'] ?? 0);
        $status = strtolower((string) ($data['status'] ?? ''));
        $isSuccessful = $status === 'success' || $statusCode === 200 || $statusCode === 201;

        if ($isSuccessful) {
            return $this->normalizeOrderResponse($data);
        }

        // If the primary endpoint returned a detailed validation/business error
        // (indicated by a non-empty errors payload), return it directly — no need
        // to retry with the fallback endpoint which would discard that detail.
        if (!empty($data['errors'])) {
            return $this->normalizeOrderResponse($data);
        }

        // Backward-compatible fallback while APIs are migrating from /order to /orders.
        $fallbackEndpoint = 'order';
        $fallbackData = $this->request($fallbackEndpoint, $payload, 'POST');

        $this->logger->warning('postOrder fallback endpoint used', [
            'primary_endpoint' => $endpoint,
            'fallback_endpoint' => $fallbackEndpoint,
            'primary_response' => $data,
        ]);

        return $this->normalizeOrderResponse($fallbackData);
    }

    /**
     * Get order by ID, hash, session_id, or other parameters
     * Supports multiple query parameters for flexible filtering
     * @param array $payload Query parameters (session_id, type, hash, id, etc.)
     * @return array
     */
    public function getOrder(array $payload)
    {
        $endpoint = 'order';

        $data = $this->request($endpoint, $payload, 'GET');

        return $this->normalizeOrderResponse($data);
    }

    /**
     * Get order by hash (used for payment confirmation)
     * @param string $hash Order hash
     * @return array
     */
    public function getOrderByHash(string $hash)
    {
        return $this->getOrder(['hash' => $hash]);
    }

    /**
     * Get order by ID
     * @param int $orderId Order ID
     * @return array
     */
    public function getOrderById(int $orderId)
    {
        return $this->getOrder(['id' => $orderId]);
    }

    /**
     * Get cart by session ID
     * @param string $sessionId Session ID
     * @return array
     */
    public function getCartBySession(string $sessionId)
    {
        return $this->getOrder([
            'session_id' => $sessionId,
            'type' => 'cart'
        ]);
    }

    public function putOrder(int $orderId, array $payload)
    {
        $endpoint = "order/{$orderId}";

        $data = $this->request($endpoint, $payload, 'PUT');

        return $this->normalizeOrderResponse($data);
    }

    /**
     * Delete an entire order (cart)
     * @param int $orderId Order ID
     * @return array
     */
    public function deleteOrder(int $orderId)
    {
        $endpoint = "order/{$orderId}";

        return $this->request($endpoint, null, 'DELETE');
    }

    public function deleteOrderItem(int $itemId)
    {
        $endpoint = "order/item/{$itemId}";

        return $this->request($endpoint, null, 'DELETE');
    }

    /**
     * Update order status (for payment webhook)
     * @param int $orderId Order ID
     * @param string $status New status (paid, processing, completed, etc.)
     * @return array
     */
    public function updateOrderStatus(int $orderId, string $status)
    {
        $endpoint = "order/{$orderId}/status";

        return $this->request($endpoint, ['status' => $status], 'PUT');
    }

    /**
     * Mark order as paid (webhook notification)
     * @param int $orderId Order ID
     * @param array $paymentData Payment information
     * @return array
     */
    public function markOrderAsPaid(int $orderId, array $paymentData = [])
    {
        $endpoint = "order/{$orderId}/mark-paid";

        $payload = array_merge(['status' => 'paid'], $paymentData);

        return $this->request($endpoint, $payload, 'POST');
    }

    /**
     * Record payment for order (from webhook)
     * @param int $orderId Order ID
     * @param array $paymentInfo Payment information
     * @return array
     */
    public function recordPayment(int $orderId, array $paymentInfo)
    {
        $endpoint = "order/{$orderId}/payment";

        return $this->request($endpoint, $paymentInfo, 'POST');
    }

    private function request($endpoint, $payload = null, $method = 'GET')
    {
        // Ensure we have a valid token before making any request
        $this->ensureValidToken();
        
        $client = new Client();
        $host = $this->host.'/'.$this->version.'/'.$endpoint;

        $config = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->token,
            ],
            'http_errors' => false,
        ];

        if(in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if ($payload !== null) {
                $config['json'] = $payload;
                $config['headers']['Content-Type'] = 'application/json';
            }
        } else {
            $config['query'] = $payload;
        }

        $response = $client->request($method, $host, $config);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response;
    }
    
    /**     * Calculate shipping quote for an order
     * @param int $orderId Order ID
     * @param array $payload Shipping address and service level
     * @return array
     */
    public function calculateShippingQuote(int $orderId, array $payload)
    {
        $endpoint = "order/{$orderId}/shipping/quote";

        $data = $this->request($endpoint, $payload, 'POST');

        return $data;
    }

    /**
     * Update shipping selection for order
     * @param int $orderId Order ID
     * @param array $payload Shipping selection data
     * @return array
     */
    public function updateOrderShipping(int $orderId, array $payload)
    {
        $endpoint = "order/{$orderId}/shipping";

        $data = $this->request($endpoint, $payload, 'PATCH');

        return $this->normalizeOrderResponse($data);
    }

    /**
     * Normalize order responses so `data` always contains the order object.
     *
     * Legacy payloads may wrap order data as: data.order.
     */
    private function normalizeOrderResponse(array $response): array
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            return $response;
        }

        $data = $response['data'];
        if (!isset($data['order']) || !is_array($data['order'])) {
            return $response;
        }

        $order = $data['order'];

        if (!isset($order['shipping']) && isset($data['shipping']) && is_array($data['shipping'])) {
            $order['shipping'] = $data['shipping'];
        }

        $response['data'] = $order;

        return $response;
    }

    /**
     * Get available shipping options for an order
     * @param int $orderId Order ID
     * @param array $payload Shipping address data
     * @return array
     */
    public function getShippingOptions(int $orderId, array $payload)
    {
        $endpoint = "order/{$orderId}/shipping/options";

        $data = $this->request($endpoint, $payload, 'POST');

        return $data;
    }
    
    /**     * Get current access token (for debugging/testing purposes)
     * @return string
     */
    public function getAccessToken(): string
    {
        $this->ensureValidToken();
        return $this->token;
    }
    
    /**
     * Get token data including expiration info
     * @return array|null
     */
    public function getTokenData(): ?array
    {
        $this->ensureValidToken();
        return $this->tokenData;
    }
}

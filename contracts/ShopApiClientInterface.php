<?php

namespace diincompany\shop\contracts;

interface ShopApiClientInterface
{
    public function getSlides($sliderKey = 'home');

    public function getCountries($payload = null);

    public function getStatesByCountry($countryId);

    public function getCitiesByState($stateId);

    public function getMerchant(bool $forceRefresh = false);

    public function getMerchantData(bool $forceRefresh = false): array;

    public function clearMerchantCache(): void;

    public function getCategories($payload = null, bool $forceRefresh = false);

    public function clearCategoriesCache($payload = null): void;

    public function getCategory(array $params = []);

    public function getProducts($payload = null);

    public function getProduct(array $params = []);

    public function getPromoBoxes($key = 'home');

    public function postOrder(array $payload);

    public function getOrder(array $payload);

    public function findOrderByNumberAndEmail(string $orderNumber, string $email);

    public function getOrderByHash(string $hash);

    public function getOrderById(int $orderId);

    public function getCartBySession(string $sessionId);

    public function putOrder(int $orderId, array $payload);

    public function deleteOrder(int $orderId);

    public function deleteOrderItem(int $itemId);

    public function updateOrderStatus(int $orderId, string $status);

    public function markOrderAsPaid(int $orderId, array $paymentData = []);

    public function recordPayment(int $orderId, array $paymentData);

    public function calculateShippingQuote(int $orderId, array $payload);

    public function updateOrderShipping(int $orderId, array $payload);

    public function getShippingOptions(int $orderId, array $payload);

    public function getAccessToken(): string;

    public function getTokenData(): ?array;
}

<?php
return [
    // Debug route
    'shop/debug' => 'shop/default/debug',
    
    // Specific routes first (more specific to less specific)
    'shop' => 'shop/products/index',
    'shop/cart' => 'shop/default/cart',
    'shop/checkout' => 'shop/default/checkout',
    'shop/confirmation/<hash>/pdf' => 'shop/default/confirmation-pdf',
    'shop/confirmation/<hash>' => 'shop/default/confirmation',
    'shop/get-states' => 'shop/default/get-states',
    'shop/get-cities' => 'shop/default/get-cities',
    'shop/calculate-shipping' => 'shop/default/calculate-shipping',
    'shop/get-shipping-options' => 'shop/default/get-shipping-options',
    'shop/process-checkout' => 'shop/default/process-checkout',
    'shop/quote' => 'shop/quote/index',
    'shop/category' => 'shop/products/category',
    'shop/products' => 'shop/products/index',
    
    // Cart routes
    'shop/cart/add-item' => 'shop/cart/add-item',
    'shop/cart/summary' => 'shop/cart/summary',
    'shop/cart/update-quantity' => 'shop/cart/update-quantity',
    'shop/cart/update-items' => 'shop/cart/update-items',
    'shop/cart/remove-item' => 'shop/cart/remove-item',
    'shop/cart/apply-coupon' => 'shop/cart/apply-coupon',
    
    // Generic routes (less specific - at the end)
    'shop/category/<slug>' => 'shop/products/category',
    'shop/products/<slug>' => 'shop/products/view',
    'shop/<slug>' => 'shop/products/view',
];
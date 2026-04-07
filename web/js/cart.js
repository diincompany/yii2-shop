/**
 * Cart functionality
 * Handles cart interactions (add, remove, update quantity)
 * Currently using dummy data - will be connected to backend later
 */

document.addEventListener('DOMContentLoaded', function() {
    const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfParam = csrfParamMeta ? csrfParamMeta.getAttribute('content') : null;
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;
    const cartConfig = window.shopCartConfig || {};
    const cartMessages = window.shopCartI18n || {};
    const t = (key, fallback) => {
        return Object.prototype.hasOwnProperty.call(cartMessages, key) ? cartMessages[key] : fallback;
    };

    const cartSummaryUrl = cartConfig.cartSummaryUrl || '/shop/cart/summary';
    const updateQuantityUrl = cartConfig.updateQuantityUrl || '/shop/cart/update-quantity';
    const removeItemUrl = cartConfig.removeItemUrl || '/shop/cart/remove-item';
    const cartUrl = cartConfig.cartUrl || '/shop/cart';
    const productViewUrlTemplate = cartConfig.productViewUrlTemplate || '/shop/products/view?id=__PRODUCT_ID__';
    const currencySymbol = cartConfig.currencySymbol || 'L';

    function productViewUrl(productId) {
        return productViewUrlTemplate.replace('__PRODUCT_ID__', encodeURIComponent(String(productId)));
    }

    // Hydrate cart UI asynchronously to avoid blocking server-side rendering
    loadCartSummary();

    // Listen for cart item added event (jQuery event from AddToCartButton)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('cartItemAdded', function(e, productId, quantity, cartData) {
            if (cartData) {
                updateCartUI(cartData);
                updateCartTotals(cartData);
            }
        });

        jQuery(document).on('cartDataUpdated', function(e, cartData) {
            if (cartData) {
                updateCartUI(cartData);
                updateCartTotals(cartData);
            }
        });
    }

    // Handle quantity changes
    attachQuantityChangeListeners();
    
    // Handle remove item  
    attachRemoveItemListeners();

    function attachQuantityChangeListeners() {
        const quantitySelects = document.querySelectorAll('.cart-quantity');
        quantitySelects.forEach(select => {
            select.addEventListener('change', function() {
                const itemId = this.dataset.itemId;
                const newQuantity = parseInt(this.value);
                
                console.log(`Update item ${itemId} quantity to ${newQuantity}`);
                
                postForm(updateQuantityUrl, {
                    item_id: itemId,
                    quantity: newQuantity
                }).then(response => {
                    if (response.success) {
                        // Update totals from API response
                        updateCartTotals(response.data || null);
                        showNotification(t('Quantity updated', 'Cantidad actualizada'), 'success');
                    } else {
                        showNotification(t('Error updating quantity', 'Error al actualizar cantidad'), 'error');
                        // Revert select to previous value if needed
                    }
                }).catch(() => {
                    showNotification(t('Error updating quantity', 'Error al actualizar cantidad'), 'error');
                });
            });
        });
    }

    function attachRemoveItemListeners() {
        const removeButtons = document.querySelectorAll('.cart-remove');
        removeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.dataset.itemId;
                
                if (confirm(t('Confirm remove cart item', 'Deseas eliminar este producto del carrito?'))) {
                    const itemElement = this.closest('li');
                    
                    console.log(`Remove item ${itemId} from cart`);
                    
                    // Add removing animation
                    itemElement.style.opacity = '0.5';
                    itemElement.style.pointerEvents = 'none';
                    
                    postForm(removeItemUrl, {
                        item_id: itemId
                    }).then(response => {
                        if (response.success) {
                            // Remove item from DOM
                            itemElement.style.transition = 'all 0.3s ease';
                            itemElement.style.transform = 'translateX(100%)';
                            itemElement.style.opacity = '0';
                            
                            setTimeout(() => {
                                itemElement.remove();
                                updateCartTotals(response.data || null);
                                
                                // Check if cart is empty
                                const cartList = document.querySelector('.offcanvas-body ul');
                                if (cartList && cartList.children.length === 0) {
                                    showEmptyCart();
                                }
                                
                                showNotification(t('Product removed from cart', 'Producto eliminado del carrito'), 'success');
                            }, 300);
                        } else {
                            // Restore item if error
                            itemElement.style.opacity = '1';
                            itemElement.style.pointerEvents = 'auto';
                            showNotification(t('Error removing product', 'Error al eliminar producto'), 'error');
                        }
                    }).catch(() => {
                        itemElement.style.opacity = '1';
                        itemElement.style.pointerEvents = 'auto';
                        showNotification(t('Error removing product', 'Error al eliminar producto'), 'error');
                    });
                }
            });
        });
    }

    function postForm(url, data) {
        const payload = new URLSearchParams(data);

        if (csrfParam && csrfToken) {
            payload.append(csrfParam, csrfToken);
        }

        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: payload.toString(),
            credentials: 'same-origin'
        }).then(response => response.json());
    }

    function loadCartSummary() {
        fetch(cartSummaryUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success && response.data) {
                updateCartUI(response.data);
                updateCartTotals(response.data);
            }
        })
        .catch(() => {
            // Keep default UI if async hydration fails
        });
    }
    
    /**
     * Update cart totals from API response data
     */
    function updateCartTotals(cartData) {
        if (!cartData) {
            return;
        }

        const subtotal = parseFloat(cartData.subtotal_amount || 0);
        const shipping = parseFloat(cartData.shipping_amount || ((cartData.shipping && cartData.shipping.shipping_cost) || 0) || 0);
        const taxes = parseFloat(cartData.tax_amount || 0);
        const total = parseFloat(cartData.total_amount || 0);
        const items = Array.isArray(cartData.items) ? cartData.items : [];
        const totalQuantity = getTotalItemsQuantity(items);

        const subtotalValue = document.querySelector('.offcanvas-footer .cart-subtotal-value');
        const shippingValue = document.querySelector('.offcanvas-footer .cart-shipping-value');
        const taxValue = document.querySelector('.offcanvas-footer .cart-tax-value');
        const totalValue = document.querySelector('.offcanvas-footer .cart-total-value');
        const shippingRow = document.querySelector('.offcanvas-footer .cart-shipping-row');
        const taxRow = document.querySelector('.offcanvas-footer .cart-tax-row');

        if (subtotalValue) {
            subtotalValue.textContent = `${currencySymbol} ${formatPrice(subtotal)}`;
        }

        if (shippingValue) {
            shippingValue.textContent = `${currencySymbol} ${formatPrice(shipping)}`;
        }

        if (shippingRow) {
            shippingRow.style.display = shipping > 0 ? '' : 'none';
        }

        if (taxValue) {
            taxValue.textContent = `${currencySymbol} ${formatPrice(taxes)}`;
        }

        if (taxRow) {
            taxRow.style.display = taxes > 0 ? '' : 'none';
        }

        if (totalValue) {
            totalValue.textContent = `${currencySymbol} ${formatPrice(total)}`;
        }
        
        // Update badge with total quantity from API
        updateCartBadge(totalQuantity);

        // Update modal title
        const cartTitle = document.querySelector('#modalMiniCartLabel');
        if (cartTitle) {
            cartTitle.textContent = `${t('Your Cart', 'Tu Carrito')} (${totalQuantity})`;
        }
        
        console.log('Totals updated:', { subtotal, shipping, taxes, total });
    }
    
    /**
     * Update cart badge counter
     */
    function updateCartBadge(itemCount) {
        // Get or create badge element
        const navLink = document.querySelector('a[aria-controls="modalMiniCart"]');
        if (!navLink) return;

        if (itemCount && itemCount > 0) {
            let badge = navLink.querySelector('.badge');
            if (!badge) {
                // Create badge if it doesn't exist
                badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                badge.innerHTML = `${itemCount}<span class="visually-hidden">${t('items in cart', 'items in cart')}</span>`;
                navLink.appendChild(badge);
            } else {
                // Update existing badge
                badge.innerHTML = `${itemCount}<span class="visually-hidden">${t('items in cart', 'items in cart')}</span>`;
            }
        } else {
            // Remove badge if cart is empty
            const badge = navLink.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
        }
    }

    function getTotalItemsQuantity(items) {
        return items.reduce((total, item) => {
            return total + parseInt(item.quantity || 0, 10);
        }, 0);
    }

    function asObject(value) {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            return value;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (_error) {
                return {};
            }
        }

        return {};
    }

    function asArray(value) {
        if (Array.isArray(value)) {
            return value;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (_error) {
                return [];
            }
        }

        return [];
    }

    function firstPositiveNumber(...values) {
        for (const value of values) {
            if (value === null || value === undefined || value === '') {
                continue;
            }

            const normalized = typeof value === 'string' ? value.replace(/[\s,]/g, '') : value;
            const numeric = Number(normalized);

            if (Number.isFinite(numeric) && numeric > 0) {
                return numeric;
            }
        }

        return 0;
    }

    function optionValuesToText(optionValues) {
        const values = asArray(optionValues);
        if (!values.length) {
            return '';
        }

        const parts = values
            .filter(optionValue => optionValue && typeof optionValue === 'object')
            .map(optionValue => {
                const optionName = String(optionValue.option_name || '').trim();
                const value = String(optionValue.value || '').trim();

                if (!optionName && !value) {
                    return '';
                }

                return optionName && value ? `${optionName}: ${value}` : value;
            })
            .filter(Boolean);

        return parts.join(' / ');
    }

    function getVariantLabel(item) {
        if (!item || typeof item !== 'object') {
            return '';
        }

        const variant = asObject(item.variant);
        const variantSnapshot = asObject(item.variant_snapshot);
        const productVariantSnapshot = asObject(item.product_variant_snapshot);
        const effectiveSnapshot = Object.keys(variantSnapshot).length ? variantSnapshot : productVariantSnapshot;

        if (item.variant_name) {
            return String(item.variant_name).trim();
        }

        if (item.product_variant_name) {
            return String(item.product_variant_name).trim();
        }

        if (variant.name) {
            return String(variant.name).trim();
        }

        if (Object.keys(effectiveSnapshot).length) {
            const variantName = String(effectiveSnapshot.name || '').trim();
            const optionsText = optionValuesToText(effectiveSnapshot.option_values);

            if (variantName && optionsText) {
                return `${variantName} - ${optionsText}`;
            }

            if (variantName) {
                return variantName;
            }

            if (optionsText) {
                return optionsText;
            }
        }

        if (item.variant_sku) {
            return String(item.variant_sku).trim();
        }

        if (effectiveSnapshot.sku) {
            return String(effectiveSnapshot.sku).trim();
        }

        const optionLabel = optionValuesToText(item.option_values);
        if (optionLabel) {
            return optionLabel;
        }

        return '';
    }

    function getItemDisplayPrice(item) {
        if (!item || typeof item !== 'object') {
            return 0;
        }

        const variantSnapshot = asObject(item.variant_snapshot);
        const productVariantSnapshot = asObject(item.product_variant_snapshot);
        const effectiveSnapshot = Object.keys(variantSnapshot).length ? variantSnapshot : productVariantSnapshot;
        const quantity = Math.max(parseInt(item.quantity || 0, 10), 1);
        const lineTotal = Number(item.total_amount || 0);
        const unitFromLineTotal = Number.isFinite(lineTotal) && lineTotal > 0 ? lineTotal / quantity : 0;
        const variantId = Number(item.product_variant_id || item.variant_id || effectiveSnapshot.variant_id || 0);
        const hasVariants = Number(item.has_variants || item.product_has_variants || 0);
        const variantPricingMode = Number(item.variant_pricing_mode || item.product_variant_pricing_mode || 0);
        const variantMetadataMissing = hasVariants <= 0 && variantPricingMode <= 0;
        const useVariantPricing = variantId > 0 && (
            (hasVariants === 1 && variantPricingMode === 2) ||
            variantMetadataMissing
        );

        const variantPrice = firstPositiveNumber(
            effectiveSnapshot.sale_price,
            effectiveSnapshot.sale_price_amount,
            item.variant_sale_price,
            item.variant_sale_price_amount,
            item.product_variant_sale_price,
            item.product_variant_sale_price_amount,
            effectiveSnapshot.price,
            effectiveSnapshot.price_amount,
            effectiveSnapshot.variant_price,
            item.variant_price,
            item.variant_price_amount,
            item.product_variant_price,
            item.product_variant_price_amount
        );

        if (useVariantPricing && variantPrice > 0) {
            return variantPrice;
        }

        if (useVariantPricing && unitFromLineTotal > 0) {
            return unitFromLineTotal;
        }

        return firstPositiveNumber(
            item.sale_price,
            item.sale_price_amount,
            item.price_amount,
            item.price,
            unitFromLineTotal
        );
    }
    
    /**
     * Show empty cart message
     */
    function showEmptyCart() {
        const offcanvasBody = document.querySelector('.offcanvas-body');
        const offcanvasFooter = document.querySelector('.offcanvas-footer');
        
        if (offcanvasBody) {
            offcanvasBody.innerHTML = `
                <div class="text-center py-5">
                    <i class="fi-shopping-cart fs-1 text-muted"></i>
                    <p class="text-muted mt-3">${t('Your cart is empty', 'Tu carrito esta vacio')}</p>
                </div>
            `;
        }
        
        // Hide footer
        if (offcanvasFooter) {
            offcanvasFooter.style.display = 'none';
        }
    }
    
    /**
     * Format price for display
     */
    function formatPrice(price) {
        return price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Show notification (simple alert for now)
     * TODO: Replace with toast notification
     */
    function showNotification(message, type) {
        console.log(`[${type.toUpperCase()}] ${message}`);
        // In future, implement a toast notification system
    }

    /**
     * Update cart UI with fresh data from server
     */
    function updateCartUI(cartData) {
        const offcanvasBody = document.querySelector('.offcanvas-body');
        const offcanvasFooter = document.querySelector('.offcanvas-footer');
        const modalTitle = document.querySelector('#modalMiniCartLabel');
        const cartItems = cartData.items || [];
        const totalQuantity = getTotalItemsQuantity(cartItems);

        if (!offcanvasBody) return;

        // Update title with count
        if (modalTitle) {
            modalTitle.textContent = `${t('Your Cart', 'Tu Carrito')} (${totalQuantity})`;
        }

        if (cartItems.length === 0) {
            showEmptyCart();
            return;
        }

        // Build items HTML
        let itemsHtml = '<ul class="list-unstyled m-0 p-0">';
        cartItems.forEach(item => {
            const variantLabel = getVariantLabel(item);
            const variantHtml = variantLabel
                ? `<span class="m-0 text-muted small w-100 d-block">${t('Variant', 'Variante')}: ${escapeHtml(variantLabel)}</span>`
                : '';
            const itemDisplayPrice = getItemDisplayPrice(item);

            itemsHtml += `
                <li class="py-2">
                    <div class="row align-items-center">
                        <div class="col-3">
                            <a href="${productViewUrl(item.product_id)}">
                                <img src="${item.main_image || 'https://placehold.net/product.png'}?tr=w-300,h-300" 
                                     class="img-fluid" 
                                     alt="${escapeHtml(item.product_name || '')}">
                            </a>
                        </div>
                        <div class="col-9">
                            <p class="mb-2">
                                <a class="text-mode fw-500" href="${productViewUrl(item.product_id)}">${escapeHtml(item.product_name)}</a>
                                ${variantHtml}
                                <span class="m-0 text-muted w-100 d-block">${currencySymbol} ${formatPrice(itemDisplayPrice)}</span>
                            </p>
                            <div class="d-flex align-items-center">
                                <select class="form-select form-select-sm w-auto cart-quantity" data-item-id="${item.id}">
                                    ${Array.from({length: 10}, (_, i) => `<option value="${i + 1}" ${i + 1 === item.quantity ? 'selected' : ''}>${i + 1}</option>`).join('')}
                                </select>
                                <a class="small text-mode ms-auto cart-remove" href="#!" data-item-id="${item.id}">
                                    <i class="bi bi-x"></i> ${t('Remove', 'Eliminar')}
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
            `;
        });
        itemsHtml += '</ul>';

        // Update body content
        offcanvasBody.innerHTML = itemsHtml;

        // Rebuild footer completely
        if (offcanvasFooter) {
            const shippingAmount = parseFloat(cartData.shipping_amount || ((cartData.shipping && cartData.shipping.shipping_cost) || 0) || 0);
            const taxesAmount = parseFloat(cartData.tax_amount || 0);
            const shippingRowHtml = shippingAmount > 0 ? `
                <div class="row g-0 py-2 cart-shipping-row">
                    <div class="col-8">
                        <span class="text-mode">${t('shipping', 'Envio')}:</span>
                    </div>
                    <div class="col-4 text-end">
                        <span class="ml-auto cart-shipping-value">${currencySymbol} ${formatPrice(shippingAmount)}</span>
                    </div>
                </div>
            ` : '';
            const taxRowHtml = taxesAmount > 0 ? `
                <div class="row g-0 py-2 cart-tax-row">
                    <div class="col-8">
                        <span class="text-mode">${t('Taxes', 'Impuestos')}:</span>
                    </div>
                    <div class="col-4 text-end">
                        <span class="ml-auto cart-tax-value">${currencySymbol} ${formatPrice(taxesAmount)}</span>
                    </div>
                </div>
            ` : '';

            offcanvasFooter.innerHTML = `
                <div class="row g-0 py-2">
                    <div class="col-8">
                        <span class="text-mode">${t('Subtotal', 'Subtotal')}</span>
                    </div>
                    <div class="col-4 text-end">
                        <span class="ml-auto cart-subtotal-value">${currencySymbol} ${formatPrice(cartData.subtotal_amount || 0)}</span>
                    </div>
                </div>
                ${shippingRowHtml}
                ${taxRowHtml}
                <div class="row g-0 pt-2 mt-2 border-top fw-bold text-mode">
                    <div class="col-8">
                        <span class="text-mode">${t('Total', 'Total')}</span>
                    </div>
                    <div class="col-4 text-end">
                        <span class="ml-auto cart-total-value">${currencySymbol} ${formatPrice(cartData.total_amount || 0)}</span>
                    </div>
                </div>
                <div class="pt-4">
                    <a href="${cartUrl}" class="btn btn-block btn-primary w-100">${t('View Cart', 'Ver Carrito')}</a>
                </div>
            `;
            offcanvasFooter.style.display = 'block';
        }

        // Update navbar badge
        updateCartBadge(totalQuantity);

        // Re-attach event listeners to new elements
        attachQuantityChangeListeners();
        attachRemoveItemListeners();
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
});

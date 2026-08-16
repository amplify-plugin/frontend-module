@php
    $isMaster = ! empty($product->has_sku) && empty($product->parent_id);
@endphp

@include($layout === 'minimal'
    ? 'widget::product.purchased-together.minimal'
    : 'widget::product.purchased-together.card', [
        'product' => $product,
        'key' => $key,
        'showTopDiscountBadge' => $showTopDiscountBadge ?? false,
        'showPrice' => $showPrice ?? true,
        'showGuestPrice' => $showGuestPrice ?? false,
        'displayManufacturer' => $displayManufacturer ?? false,
        'displayShortDescription' => $displayShortDescription ?? false,
        'displayProductCode' => $displayProductCode ?? true,
        'showCartBtn' => $showCartBtn ?? true,
        'showOrderList' => $showOrderList ?? false,
        'cartButtonLabel' => $cartButtonLabel ?? 'Add To Cart',
        'detailButtonLabel' => $detailButtonLabel ?? 'View Details',
        'orderListLabel' => $orderListLabel ?? 'Order List',
        'isMasterProduct' => fn ($product) => ! empty($product->has_sku) && empty($product->parent_id),
    ])

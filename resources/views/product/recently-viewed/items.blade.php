@foreach ($products as $key => $product)
    @if (! empty($allowRemove))
        <div class="recently-viewed-grid-item" data-product-id="{{ $product->id }}">
            <button type="button"
                class="recently-viewed-remove-btn"
                data-product-id="{{ $product->id }}"
                title="{{ __('Remove') }}"
                aria-label="{{ __('Remove') }}">
                &times;
            </button>

            @include('widget::product.recently-viewed.item', [
                'product' => $product,
                'key' => $key,
                'layout' => $layout,
                'showTopDiscountBadge' => $showTopDiscountBadge,
                'showPrice' => $showPrice,
                'showGuestPrice' => $showGuestPrice,
                'displayManufacturer' => $displayManufacturer,
                'displayShortDescription' => $displayShortDescription,
                'displayProductCode' => $displayProductCode,
                'showCartBtn' => $showCartBtn,
                'showOrderList' => $showOrderList,
                'cartButtonLabel' => $cartButtonLabel,
                'detailButtonLabel' => $detailButtonLabel,
                'orderListLabel' => $orderListLabel,
            ])
        </div>
    @else
        <div class="grid-item">
            @include('widget::product.recently-viewed.item', [
                'product' => $product,
                'key' => $key,
                'layout' => $layout,
                'showTopDiscountBadge' => $showTopDiscountBadge,
                'showPrice' => $showPrice,
                'showGuestPrice' => $showGuestPrice,
                'displayManufacturer' => $displayManufacturer,
                'displayShortDescription' => $displayShortDescription,
                'displayProductCode' => $displayProductCode,
                'showCartBtn' => $showCartBtn,
                'showOrderList' => $showOrderList,
                'cartButtonLabel' => $cartButtonLabel,
                'detailButtonLabel' => $detailButtonLabel,
                'orderListLabel' => $orderListLabel,
            ])
        </div>
    @endif
@endforeach

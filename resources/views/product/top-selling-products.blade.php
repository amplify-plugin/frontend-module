<div {!! $htmlAttributes !!}>
    @if ($products->isNotEmpty())
        <div class="top-selling-products-section">
            @if ($showTitle)
                <h3 class="product-slider-title mb-3">
                    {{ __($title) }}
                </h3>
            @endif

            <div class="owl-carousel" data-owl-carousel="{{ $carouselOptions() }}">
                @foreach ($products as $key => $product)
                    <div class="grid-item">
                        @include('widget::product.top-selling-products.item', [
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
                @endforeach
            </div>
        </div>
    @endif
</div>

@pushonce('internal-style')
    @include('widget::product.top-selling-products.styles')
@endpushonce

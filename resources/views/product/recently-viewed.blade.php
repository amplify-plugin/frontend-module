<div {!! $htmlAttributes !!}
    data-recently-viewed-widget="carousel"
    data-recently-viewed-settings="{{ $widgetSettings() }}"
    data-recently-viewed-authenticated="{{ customer_check() ? '1' : '0' }}">
    @if ($showTitle || $hasSeeAllLink())
        <div class="recently-viewed-header mb-3">
            @if ($showTitle)
                <h3 class="product-slider-title recently-viewed-title mb-0">
                    {{ __($title) }}
                </h3>
            @else
                <span class="recently-viewed-title-spacer" aria-hidden="true"></span>
            @endif

            @if ($hasSeeAllLink())
                <a class="recently-viewed-see-all"
                   href="{{ $seeAllUrl() }}"
                   title="{{ __($seeAllLabel) }}">
                    <span>{{ __($seeAllLabel) }}</span>
                    <span class="recently-viewed-see-all-icon" aria-hidden="true">&rarr;</span>
                </a>
            @endif
        </div>
    @endif

    <div class="recently-viewed-section {{ customer_check() && $products->isEmpty() ? 'd-none' : '' }}">
        @if (customer_check() && $products->isNotEmpty())
            <div class="owl-carousel" data-owl-carousel="{{ $carouselOptions() }}">
                @foreach ($products as $key => $product)
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
                @endforeach
            </div>
        @else
            <div class="owl-carousel recently-viewed-guest-carousel d-none" data-owl-carousel="{{ $carouselOptions() }}"></div>
        @endif
    </div>
</div>

@pushonce('internal-style')
    @include('widget::product.recently-viewed.styles')
@endpushonce

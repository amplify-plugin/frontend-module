<div {!! $htmlAttributes !!}
    data-recently-viewed-widget="page"
    data-recently-viewed-settings="{{ $widgetSettings() }}"
    data-recently-viewed-authenticated="{{ customer_check() ? '1' : '0' }}">
    <div class="recently-viewed-page-section">
        @if ($showTitle)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h4 mb-0">{{ __($title) }}</h2>

                @if ($allowClear)
                    <button type="button"
                        class="btn btn-outline-danger btn-sm recently-viewed-clear-btn {{ customer_check() && $products->isEmpty() ? 'd-none' : '' }}">
                        {{ __('Clear All') }}
                    </button>
                @endif
            </div>
        @endif

        @if (customer_check())
            @if ($products->isNotEmpty())
                <div class="recently-viewed-grid">
                    @foreach ($products as $key => $product)
                        <div class="recently-viewed-grid-item" data-product-id="{{ $product->id }}">
                            @if ($allowRemove)
                                <button type="button"
                                    class="recently-viewed-remove-btn"
                                    data-product-id="{{ $product->id }}"
                                    title="{{ __('Remove') }}"
                                    aria-label="{{ __('Remove') }}">
                                    &times;
                                </button>
                            @endif

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
                <div class="alert alert-info mb-0">
                    {{ __('You have not viewed any products recently.') }}
                </div>
            @endif
        @else
            <div class="recently-viewed-guest-page {{ $products->isNotEmpty() ? '' : 'd-none' }}">
                <div class="recently-viewed-grid recently-viewed-guest-grid"></div>
            </div>
            <div class="alert alert-info mb-0 recently-viewed-guest-empty {{ $products->isNotEmpty() ? 'd-none' : '' }}">
                {{ __('You have not viewed any products recently.') }}
            </div>
        @endif
    </div>
</div>

@pushonce('internal-style')
    @include('widget::product.recently-viewed.styles')
@endpushonce

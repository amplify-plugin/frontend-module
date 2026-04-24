<div {!! $htmlAttributes !!}>
    @if($products->isNotEmpty())
        @if($showTitle)
            <h3 class="product-slider-title">
                {{ __($title) }}
            </h3>
        @endif

        <div class="owl-carousel" data-owl-carousel="{{ $carouselOptions() }}">
            <!-- Product-->
            @foreach ($products as $key => $product)
                <div class="grid-item">
                    <div class="product-card">
                        @if ($showTopDiscountBadge && $showPrice)
                            <div class="product-badge text-danger">
                                {{ discount_badge_label($product->price, $product->old_price) }}
                            </div>
                        @endif
                        <a class="product-thumb" href="{{ $product->detail_link }}">
                            <img src="{{ $product->image }}"
                                 alt="{{ __($product->name ?? '') }}">
                        </a>
                        <div class="product-body">
                            <div class="product-description {{ $showPrice ? "slider-product-info": ""}}">
                                @if ($displayManufacturer)
                                    <a class="manufacturer-name text-decoration-none mb-2"
                                       href="{{ $product->detail_link }}">
                                        {{ $product->manufacturer ?? "" }}
                                    </a>
                                @endif

                                @if($displayShortDescription && !empty($product->short_description))
                                    <small class="short-desc d-block">
                                        {!! $product->short_description ?? "" !!}
                                    </small>
                                @endif
                                <p class="product-title mb-0">
                                    <a href="{{ $product->detail_link }}"
                                       title="{{ __($product->name ?? '') }}">
                                        {{ __($product->name ?? '') }}
                                    </a>
                                </p>

                                @if ($displayProductCode)
                                    <p class="product-code">
                                        <span>{{ __('Product Code:') }}</span> {{ $product->product_code }}</p>
                                @endif

                                @if($showPrice && ($showGuestPrice || customer_check()))
                                    <x-product.price
                                            element="h4"
                                            class="product-price d-flex w-100 justify-content-center"
                                            :product="$product"
                                            :value="$product->price"
                                            :uom="$product->uom"
                                            :std-price="$showTopDiscountBadge ? $product->old_price : null"
                                    />
                                @endif
                                <x-product-hidden-fields :product="$product" :input="$key"/>
                                <input id="product_qty_{{ $key }}" type="hidden" name="qty[]" value="1"
                                       min="1" max="" class="form-control">
                            </div>
                            @if ($showOrderList || $showCartBtn)
                                <div class="product-buttons d-flex justify-content-between">
                                    @if ($showCartBtn)
                                        <a class="btn btn-outline-primary btn-block @if ($smallButton) btn-sm @endif"
                                           href="{{ $product->detail_link }}">
                                            {{ __($cartButtonLabel) }}
                                        </a>
                                    @endif
                                    @if ($showOrderList)
                                        <x-product-shopping-list
                                                :product-id="$product->id"
                                                :index="$key"
                                                :widget-title="$orderListLabel"
                                                :addLabel="'Add to '.$orderListLabel"
                                        />
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

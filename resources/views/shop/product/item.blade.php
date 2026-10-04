@php

    $diff = null;
    $msrp =  $product?->Msrp?->toFloat() ?? null;
    $price =  $product?->ERP?->Price ?? null;

    if ($price != null && $msrp != null) {
        $diff = (abs($msrp - $price) * 100)/$msrp;
    }

@endphp

@if($productView =='list')
    <x-product.main-image :product="$product" :seo-path="$seoPath" :wrap-link="true">
        @if(!$showDiscountBadge)
            @if(!$product->in_stock)
                <div class="product-badge product-start text-white bg-success"
                     data-toggle="tooltip" title="Inventory Status">
                    <i class="icon-circle-check" style="margin-top: -3px"></i> In Stock
                </div>
            @endif
            {{--            @if(is_numeric($diff))--}}
            {{--                <div class="product-badge product-end">--}}
            {{--                    {{ \Illuminate\Support\Number::percentage($diff) }} Off--}}
            {{--                </div>--}}
            {{--            @endif--}}
        @endif
    </x-product.main-image>

    <div class="product-info">
        @if($allowDisplayProductCode())
            <x-product.item-number
                    :product="$product"
                    format="<a href='{{ frontendSingleProductURL($product, $seoPath) }}'><b>{product_code}</b></a>"
                    element="span"/>
        @endif
        <x-product.name element="span" :product="$product" class="product-title d-block"/>

        <x-product.price
                element="div"
                class="w-100 d-flex justify-content-md-start justify-content-center product-price"
                :product="$product"
                :value="$product->ERP?->Price"
                :uom="$product->ERP?->UnitOfMeasure ?? 'EA'"
        />

        <div class="widget-tags justify-content-center justify-content-md-start">

            @if($product->total_quantity_available > 1)
                <x-product.availability
                        :product="$product" :value="$product->total_quantity_available"
                        data-toggle="tooltip" title="Stock Inventory"
                        element="a" class="tag">
                    <x-slot:prefix>
                        <i class="icon-layers font-weight-bold"></i>
                    </x-slot:prefix>
                    <x-slot:suffix>
                        <span>available</span>
                    </x-slot:suffix>
                </x-product.availability>
            @endif

            @if(!empty($product->min_order_qty))
                <a href="#" class="tag"
                   data-toggle="tooltip" title="Minimum Order Quantity">
                    MOQ: {{ number_format($product->min_order_qty) }}
                </a>
            @endif

            @if($product->is_ncnr)
                <a href="#" class="tag"
                   data-toggle="tooltip" title="Non-Cancellable, Non-Returnable">
                    <i class="icon-bell font-weight-bold text-danger" style="margin-top: -3px"></i>
                    NCNR
                </a>
            @endif
        </div>
        @if(!empty($product->ship_restriction))
            <p class="mb-2">
                {!! $product->ship_restriction ?? '' !!}
            </p>
        @endif

        <x-product.short-description :content="$product->short_description" :lines="2" class="py-2 w-100"/>

    </div>

    <x-product.quick-action
            :cart-label="$cartButtonLabel"
            :detail-label="$detailButtonLabel"
            :product="$product"
            :seo-path="$seoPath"
            :index="$loop->index"
            order-list-label="List1"
    />
@else
    <x-product.main-image :product="$product" :seo-path="$seoPath" :wrap-link="true">
        @if(!$showDiscountBadge)
            @if(!$product->in_stock)
                <div class="product-badge product-start text-white bg-success"
                     data-toggle="tooltip" title="Inventory Status">
                    <i class="icon-circle-check" style="margin-top: -3px"></i> In Stock
                </div>
            @endif
            {{--            @if(is_numeric($diff))--}}
            {{--                <div class="product-badge product-end">--}}
            {{--                    {{ \Illuminate\Support\Number::percentage($diff) }} Off--}}
            {{--                </div>--}}
            {{--            @endif--}}
        @endif

        @if(!$showFavourite)
            <div class="product-badge" style="right: 0">
                {{--                @if(class_exists(\Amplify\Wishlist\Widgets\WishlistButton::class))--}}
                {{--                    <x-wishlist-button :product="$product" class="btn-wishlist">--}}
                {{--                        <x-slot:add-label>--}}
                {{--                            <i title="Add to Favorites" class="icon-file-add text-primary"></i>--}}
                {{--                        </x-slot>--}}
                {{--                        <x-slot:remove-label>--}}
                {{--                            <i title="Remove From Favorites" class="icon-file-subtract"></i>--}}
                {{--                        </x-slot>--}}
                {{--                    </x-wishlist-button>--}}
                {{--                @endif--}}
            </div>
        @endif
    </x-product.main-image>
    @if($allowDisplayProductCode())
        <x-product.item-number
                :product="$product"
                format="<a href='{{ frontendSingleProductURL($product, $seoPath) }}'><b>{product_code}</b></a>"
                element="span"/>
    @endif
    <x-product.name element="span" :product="$product" class="product-title d-block text-center"/>
    <x-product.price
            element="div"
            class="d-block w-100 d-flex justify-content-center product-price"
            :product="$product"
            :value="$product->ERP?->Price"
            :uom="$product->ERP?->UnitOfMeasure ?? 'EA'"
    />

    <div class="widget-tags justify-content-center justify-content-md-start">
        @if($product->total_quantity_available > 1)
            <x-product.availability
                    :product="$product" :value="$product->total_quantity_available"
                    data-toggle="tooltip" title="Stock Inventory"
                    element="a" class="tag">
                <x-slot:prefix>
                    <i class="icon-layers font-weight-bold"></i>
                </x-slot:prefix>
                <x-slot:suffix>
                    <span>available</span>
                </x-slot:suffix>
            </x-product.availability>
        @endif

        @if(!empty($product->min_order_qty))
            <a href="#" class="tag"
               data-toggle="tooltip" title="Minimum Order Quantity">
                MOQ: {{ number_format($product->min_order_qty) }}
            </a>
        @endif
    </div>

    @if(!empty($product->ship_restriction))
        <p class="mb-2">
            {!! $product->ship_restriction ?? '' !!}
        </p>
    @endif

    <x-product.quick-action
            :cart-label="$cartButtonLabel"
            :detail-label="$detailButtonLabel"
            :product="$product"
            :seo-path="$seoPath"
            :index="$loop->index"
            order-list-label="List"
    />
@endif

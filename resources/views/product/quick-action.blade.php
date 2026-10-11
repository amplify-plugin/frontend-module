<div {!! $htmlAttributes !!}>
    @if($isMasterProduct($product))
        <a href="{{ frontendSingleProductURL($product, $seoPath) }}?has_sku=1"
           class="btn btn-primary btn-sm m-0">
            {{ __($detailLabel) }}
        </a>
        @if($allowSkuQuickOrder)
            <button class="btn btn-sm btn-secondary m-0">
                Quick View
            </button>
        @endif
    @else
        @if($allowAddToCart)
            <x-cart.quantity-update :product="$product" :index="$index"/>
            <button
                    data-warehouse="{{ $defaultWarehouse }}"
                    onclick="Amplify.addSingleItemToCart(this, '#cart-item-{{$index}}', {{ json_encode($extras) }})"
                    data-options="{{ json_encode($cartData) }}"
                    class="btn btn-sm btn-primary m-0 btn-cart">
                {{ __($cartLabel) }}
            </button>
        @else
            <a href="{{ frontendSingleProductURL($product, $seoPath) }}"
               class="btn m-0 btn-primary btn-sm">
                {{ __($detailLabel) }}
            </a>
        @endif

        @if($orderList)
            <x-product-shopping-list
                    :product-id="$product->Amplify_Id ?? $product->id"
                    class="w-100 m-0"
                    :index="$product->Amplify_Id ?? $product->id"
                    :add-label="'Save to ' . $orderListLabel"
                    :widget-title="$orderListLabel"/>
        @endif
        {{--                @if(class_exists(\Amplify\Wishlist\Widgets\WishlistButton::class) || $wishList)--}}
        {{--                    <x-wishlist-button :product="$product" class="btn-wishlist mx-0 my-0 btn-sm">--}}
        {{--                        <x-slot:add-label>--}}
        {{--                            <i class="icon-bell"></i> Add to Wishlist--}}
        {{--                        </x-slot>--}}
        {{--                        <x-slot:remove-label>--}}
        {{--                            <i class="icon-bell"></i> Remove from Wishlist--}}
        {{--                        </x-slot>--}}
        {{--                    </x-wishlist-button>--}}
        {{--                @endif--}}
    @endif
</div>

<article class="product-card purchased-together-card h-100">
    @if ($showTopDiscountBadge && $showPrice)
        <div class="product-badge text-danger">
            {{ discount_badge_label($product->ERP?->Price, $product->msrp) }}
        </div>
    @endif

    <a class="product-thumb purchased-together-thumb"
        href="{{ frontendSingleProductURL($product) }}"
        title="{{ $product->product_name ?? '' }}">
        <img src="{{ $product->productImage?->main ?? asset(config('amplify.frontend.fallback_image_path')) }}"
            alt="{{ $product->product_name ?? '' }}" loading="lazy" class="product-image">
    </a>

    <div class="product-body purchased-together-body">
        <div @class([
            'product-description purchased-together-info',
            'slider-product-info' => $showPrice,
        ])>
            @if ($displayManufacturer && !empty($product->manufacturerRelation?->name))
                <a class="manufacturer-name text-decoration-none"
                    href="{{ frontendSingleProductURL($product) }}">
                    {{ $product->manufacturerRelation->name }}
                </a>
            @endif

            @if ($displayShortDescription && !empty($product->short_description))
                <small class="short-desc d-block">
                    {!! $product->short_description !!}
                </small>
            @endif

            <x-product.name element="h4" class="product-title purchased-together-name"
                :product="$product" :max-line="2" />

            @if ($displayProductCode)
                <x-product.item-number :product="$product" format="<span>{product_code}</span>"
                    element="p" class="product-code purchased-together-code" />
            @endif

            @if ($showPrice && ($showGuestPrice || customer_check()))
                <x-product.price element="p" class="product-price purchased-together-price"
                    :product="$product" :value="$product->ERP?->Price" :uom="$product->ERP?->UnitOfMeasure ?? ($product->uom ?? 'EA')" :std-price="$showTopDiscountBadge ? $product->msrp : null" />
            @endif
        </div>

        @if ($showCartBtn || $showOrderList)
            <div class="product-buttons purchased-together-actions">
                <x-product.quick-action :product="$product" :index="$key" :cart-label="$cartButtonLabel"
                    :detail-label="$detailButtonLabel" :add-to-cart="$showCartBtn && !$isMasterProduct($product)" :order-list="$showOrderList"
                    :order-list-label="$orderListLabel" />
            </div>
        @endif
    </div>
</article>

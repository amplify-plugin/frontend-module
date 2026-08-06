<article class="product-card purchased-together-card-minimal h-100">
    <a class="purchased-together-minimal-thumb"
        href="{{ frontendSingleProductURL($product) }}"
        title="{{ $product->product_name ?? '' }}">
        <img src="{{ $product->productImage?->main ?? asset(config('amplify.frontend.fallback_image_path')) }}"
            alt="{{ $product->product_name ?? '' }}" loading="lazy" class="product-image">
    </a>

    <div class="purchased-together-minimal-content">
        <div class="purchased-together-minimal-info">
            @if ($displayManufacturer && !empty($product->manufacturerRelation?->name))
                <span class="purchased-together-minimal-brand">
                    {{ $product->manufacturerRelation->name }}
                </span>
            @endif

            <x-product.name element="h4" class="product-title purchased-together-minimal-name"
                :product="$product" :max-line="2" />

            <div class="purchased-together-minimal-meta">
                @if ($displayProductCode)
                    <x-product.item-number :product="$product" format="<span>{product_code}</span>"
                        element="span" class="product-code purchased-together-minimal-code" />
                @endif

                @if ($showPrice && ($showGuestPrice || customer_check()))
                    @if ($displayProductCode)
                        <span class="purchased-together-minimal-divider" aria-hidden="true"></span>
                    @endif

                    <x-product.price element="span" class="product-price purchased-together-minimal-price"
                        :product="$product" :value="$product->ERP?->Price" :uom="$product->ERP?->UnitOfMeasure ?? ($product->uom ?? 'EA')" :std-price="$showTopDiscountBadge ? $product->msrp : null" />
                @endif
            </div>
        </div>

        @if ($showCartBtn || $showOrderList)
            <div class="purchased-together-minimal-actions">
                <x-product.quick-action :product="$product" :index="$key" :cart-label="$cartButtonLabel"
                    :detail-label="$detailButtonLabel" :add-to-cart="$showCartBtn && !$isMasterProduct($product)" :order-list="$showOrderList"
                    :order-list-label="$orderListLabel" />
            </div>
        @endif
    </div>
</article>

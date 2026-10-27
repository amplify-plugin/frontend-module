<div {!! $htmlAttributes !!}>
    <div class="widget widget-featured-products">
        @if($showTitle)
            <h3 class="widget-title">
                {{ __($title) }}
            </h3>
        @endif

        @foreach ($products as $product)
            <div class="entry">
                <div class="entry-thumb">
                    <a href="{{ $product->detail_link }}">
                        <img src="{{ $product->image }}" alt="{{ __($product->name ?? '') }}">
                    </a>
                </div>
                <div class="entry-content">
                    <p class="entry-title">
                        <a href="{{ $product->detail_link }}"
                           title="{{ __($product->name ?? '') }}" class="text-capitalize">
                            {{ Str::lower($product->name ?? '') }}
                        </a>
                    </p>
                    @if ($showPrice)
                        <span class="entry-meta">
                        {{ $product->price ?? '' }}
                        <span @class(['entry-old-price', 'd-none' => !$showDiscountPrice]) >
                            {{ $product->old_price ?? '' }}
                        </span>
                    </span>
                @endif
                <x-product-comparison-manage :product="$product" type="button" class="btn btn-sm btn-outline-primary mt-2"/>
            </div>
        </div>
    @endforeach
</div>
</div>

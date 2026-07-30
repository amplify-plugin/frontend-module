<div {!! $htmlAttributes !!}>
    @if ($products->isNotEmpty())
        <div class="purchased-together-section">
            @if($showTitle)
                <h3 class="product-slider-title mb-3">
                    {{ __($title) }}
                </h3>
            @endif

            <div class="owl-carousel" data-owl-carousel="{{ $carouselOptions() }}">
                @foreach ($products as $key => $product)
                    <div class="grid-item">
                        @include($isMinimalLayout()
                            ? 'widget::product.purchased-together.minimal'
                            : 'widget::product.purchased-together.card', [
                                'product' => $product,
                                'key' => $key,
                            ])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@pushonce('internal-style')
    <style>
        /* Card layout (default) */
        .x-purchased-together .purchased-together-card,
        .x-product-frequently-purchased-together .purchased-together-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid rgba(74, 74, 74, 0.35);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
            transition: box-shadow 0.25s ease, border-color 0.25s ease, transform 0.25s ease;
        }

        .x-purchased-together .purchased-together-card:hover,
        .x-product-frequently-purchased-together .purchased-together-card:hover {
            border-color: rgba(181, 18, 27, 0.18);
            box-shadow: 0 10px 28px rgba(31, 53, 103, 0.08);
            transform: translateY(-2px);
        }

        .x-purchased-together .purchased-together-thumb,
        .x-product-frequently-purchased-together .purchased-together-thumb {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 180px;
            max-height: 180px;
            padding: 1rem 1.25rem;
            margin: 0;
            border-bottom: 1px solid rgba(74, 74, 74, 0.25);
            background: #fafafa;
        }

        .x-purchased-together .purchased-together-thumb .product-image,
        .x-product-frequently-purchased-together .purchased-together-thumb .product-image {
            width: 100%;
            height: 148px;
            object-fit: contain;
        }

        .x-purchased-together .purchased-together-body,
        .x-product-frequently-purchased-together .purchased-together-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 1rem;
            padding: 1rem 1.125rem 1.25rem;
        }

        .x-purchased-together .purchased-together-info,
        .x-product-frequently-purchased-together .purchased-together-info {
            flex: 1;
            text-align: left;
        }

        .x-purchased-together .purchased-together-name,
        .x-product-frequently-purchased-together .purchased-together-name {
            margin-bottom: 0.375rem !important;
            font-size: 0.9375rem;
            font-weight: 600;
            line-height: 1.45;
            text-align: left !important;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            min-height: 2.85em;
        }

        .x-purchased-together .purchased-together-name > a,
        .x-product-frequently-purchased-together .purchased-together-name > a {
            color: #374250;
            text-decoration: none;
        }

        .x-purchased-together .purchased-together-name > a:hover,
        .x-product-frequently-purchased-together .purchased-together-name > a:hover {
            color: #b5121b;
        }

        .x-purchased-together .purchased-together-code,
        .x-product-frequently-purchased-together .purchased-together-code {
            margin-bottom: 0.5rem !important;
            font-size: 0.8125rem;
            color: #475467;
            text-align: left !important;
        }

        .x-purchased-together .purchased-together-code span,
        .x-product-frequently-purchased-together .purchased-together-code span {
            font-weight: 500;
        }

        .x-purchased-together .purchased-together-price,
        .x-product-frequently-purchased-together .purchased-together-price {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #b5121b;
            text-align: left !important;
            justify-content: flex-start !important;
        }

        .x-purchased-together .purchased-together-actions,
        .x-product-frequently-purchased-together .purchased-together-actions {
            margin-top: auto;
            padding-top: 0.25rem;
        }

        .x-purchased-together .purchased-together-actions .x-product-quick-action,
        .x-product-frequently-purchased-together .purchased-together-actions .x-product-quick-action {
            width: 100%;
        }

        .x-purchased-together .purchased-together-actions .d-grid,
        .x-product-frequently-purchased-together .purchased-together-actions .d-grid {
            gap: 0.75rem !important;
        }

        .x-purchased-together .purchased-together-actions .x-cart-quantity-update,
        .x-product-frequently-purchased-together .purchased-together-actions .x-cart-quantity-update {
            width: 100%;
            justify-content: center;
            margin-bottom: 0;
        }

        .x-purchased-together .purchased-together-actions .btn-primary,
        .x-product-frequently-purchased-together .purchased-together-actions .btn-primary {
            min-height: 38px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0.01em;
            border-radius: 8px;
        }

        /* Minimal layout */
        .x-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal {
            display: flex;
            align-items: stretch;
            gap: 0.875rem;
            height: 100%;
            padding: 0.875rem;
            border: 1px solid #e4e7ec;
            border-radius: 10px;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal:hover,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal:hover {
            border-color: rgba(181, 18, 27, 0.22);
            box-shadow: 0 6px 18px rgba(31, 53, 103, 0.07);
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 84px;
            height: 84px;
            border: 1px solid #eef0f3;
            border-radius: 8px;
            background: #fafafa;
            overflow: hidden;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb .product-image,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 0.375rem;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-content,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-content {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: space-between;
            gap: 0.875rem;
            min-width: 0;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-info,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-info {
            flex: 1;
            min-width: 0;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-brand,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-brand {
            display: block;
            margin-bottom: 0.125rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #667085;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name {
            margin: 0 0 0.375rem !important;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.35;
            text-align: left !important;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name > a,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name > a {
            color: #1f2937;
            text-decoration: none;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name > a:hover,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-name > a:hover {
            color: #b5121b;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-meta,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-code,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-code {
            font-size: 0.75rem;
            font-weight: 500;
            color: #667085;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-divider,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-divider {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-price,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-price {
            font-size: 0.875rem;
            font-weight: 700;
            color: #b5121b;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions {
            flex-shrink: 0;
            width: 148px;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .d-grid,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .d-grid {
            gap: 0.5rem !important;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .x-cart-quantity-update,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .x-cart-quantity-update {
            justify-content: center;
        }

        .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .btn-primary,
        .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions .btn-primary {
            min-height: 34px;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }

        @media (max-width: 991px) {
            .x-purchased-together .purchased-together-thumb,
            .x-product-frequently-purchased-together .purchased-together-thumb {
                min-height: 150px;
                max-height: 150px;
                padding: 0.875rem 1rem;
            }

            .x-purchased-together .purchased-together-thumb .product-image,
            .x-product-frequently-purchased-together .purchased-together-thumb .product-image {
                height: 118px;
            }

            .x-purchased-together .purchased-together-body,
            .x-product-frequently-purchased-together .purchased-together-body {
                padding: 0.875rem 1rem 1rem;
                gap: 0.875rem;
            }

            .x-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal,
            .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-card-minimal {
                flex-direction: column;
            }

            .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb,
            .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-thumb {
                width: 100%;
                height: 72px;
            }

            .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-content,
            .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-content {
                flex-direction: column;
                align-items: stretch;
            }

            .x-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions,
            .x-product-frequently-purchased-together.purchased-together-layout-minimal .purchased-together-minimal-actions {
                width: 100%;
            }
        }
    </style>
@endpushonce

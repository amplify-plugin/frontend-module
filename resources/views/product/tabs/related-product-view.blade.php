<div class="product-related-table" id="related-products-content">
    @if (!empty($relationTypes) && $relationTypes->isNotEmpty())
        <div class="mb-2">
            <p class="mb-2">Relation Type</p>
            <div class="relation-type-group">
                @foreach ($relationTypes as $rt)
                    @php
                        $active = (int) ($selectedRelationType ?? $relationTypes->first()->id) === (int) $rt->id;
                    @endphp
                    <div class="form-check form-check-inline">
                        <input
                                class="form-check-input relation-type-radio"
                                type="radio"
                                name="relation_type"
                                id="relation_type_{{ $rt->id }}"
                                value="{{ $rt->id }}"
                                data-url="{{ route('frontend.shop.relatedProducts', ['product' => $product->id, 'relation_type'=> $rt->id]) }}"
                                @checked($active)>
                        <label class="form-check-label fs-16 text-black" for="relation_type_{{ $rt->id }}">
                            {{ $rt->name }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @empty($relatedProducts)

    @else
        <ul class="related-products list-group-flush pl-0"
            style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
            @foreach ($relatedProducts as $rp)
                @php $idx = $loop->index + 2; @endphp
                <li class="product-sku-item list-group-item">
                    <div class="w-100 d-flex  align-items-sm-center gap-3 flex-column flex-sm-row">
                        <div class="product-img"
                             style="height: 105px; width: 120px; border-radius: 8px; padding: 8px;">
                            <img src="{{ $rp?->productImage?->main ?? asset(config('amplify.frontend.fallback_image_path')) }}"
                                 alt="Product"
                                 style="height: 100%; width: 100%; object-fit: contain;">
                        </div>
                        <div class="product-info">
                            <a href="{{ frontendSingleProductURL($rp ?? '#') }}"
                               target="_blank" class="text-decoration-none">
                                <x-product.item-number
                                        :product="$product" format="{product_code}"
                                        element="strong"
                                        class="text-primary"/>
                            </a>
                            <div class="mb-2 font-roboto" style="max-width: 700px;">
                                <a href="{{ frontendSingleProductURL($rp ?? '#') }}" target="_blank"
                                   style="text-decoration: none"
                                   class="text-decoration-none font-weight-bold text-black">
                                    {{ $rp->product_name ?? '' }}
                                </a>
                            </div>

                            @php
                                $specs = collect($rp->specifications ?? []);
                                $first = $specs->slice(0, 2);
                                $second = $specs->slice(2, 2);
                            @endphp
                            <div class="specs-container d-flex gap-3">
                                {{--                            first spec column (always present to reserve space)--}}
                                <div class="spec-column" style="min-width:180px;">
                                    @foreach($first as $s)
                                        <div class="text-nowrap">{{ $s->name ?? ($s['name'] ?? '') }} :</div>
                                        <strong class="font-roboto text-nowrap">{{ $s->value ?? ($s['value'] ?? '') }}</strong>
                                    @endforeach
                                </div>

                                {{--                            second spec column (always present to reserve space)--}}
                                <div class="spec-column" style="min-width:180px;">
                                    @foreach($second as $s)
                                        <div class="text-nowrap">{{ $s->name ?? ($s['name'] ?? '') }} :</div>
                                        <strong class="font-roboto text-nowrap">{{ $s->value ?? ($s['value'] ?? '') }}</strong>
                                    @endforeach
                                </div>

                                {{--                            availability / price column (always rendered in third position)--}}
                                <div class="spec-column" style="min-width:200px;">
                                    <div class="text-nowrap">Available Qyt :</div>
                                    <div class="font-roboto text-nowrap">
                                        @if ($rp->assembled)
                                            Assembled Item
                                        @else
                                            <x-product.availability :product="$rp"
                                                                    :value="$rp->total_quantity_available"/>
                                        @endif
                                    </div>

                                    <div class="text-nowrap">Price :</div>

                                    <x-product.price element="div" :product="$rp"
                                                     class="font-roboto text-nowrap d-flex"
                                                     :value="$rp->ERP?->Price"
                                                     :uom="$rp->ERP?->UnitOfMeasure ?? 'EA'"/>
                                </div>
                            </div>
                        </div>
                        <div class="product-buttons">
                            <x-product.quick-action :product="$product" class="d-grid gap-2"/>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-4 w-100 flex-wrap">
                        <div>
                            {!! $rp->ship_restriction ?? null !!}
                        </div>

                        <div>
                            <x-product.ncnr-item-flag :product="$rp" :show-full-form="true"/>
                        </div>

                        <div class="d-flex gap-2">
                            <x-product.default-document-link :document="$rp->default_document"
                                                             class="list_shop_datasheet_product"/>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($relatedProducts->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $relatedProducts->withQueryString()->links() }}
            </div>
        @endif
    @endempty
</div>
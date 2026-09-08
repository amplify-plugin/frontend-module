@php

    $diff = null;
    $msrp =  $product?->Msrp?->toFloat() ?? null;
    $price =  $product?->ERP?->Price ?? null;

    if ($price != null && $msrp != null) {
        $diff = (abs($msrp - $price) * 100)/$msrp;
    }

@endphp
<div {!! $htmlAttributes !!}>
    <div class="row">

        <x-product.product-gallery class="col-md-4 col-12" :image="$product?->product_image">
            <x-slot:before>
                {{--                    @if(!$showDiscountBadge)--}}
                @if(!$product->in_stock)
                    <div class="product-badge product-start text-white bg-success"
                         data-toggle="tooltip" title="Inventory Status">
                        <i class="icon-circle-check" style="margin-top: -3px"></i> In Stock
                    </div>
                @endif
                @if(is_numeric($diff))
                    <div class="product-badge product-end text-danger">
                        {{ \Illuminate\Support\Number::percentage($diff) }} Off
                    </div>
                @endif
                {{--                    @endif--}}
            </x-slot:before>
        </x-product.product-gallery>

        <div class="col-md-8">
            {!! $before ?? null !!}
            <div class="d-flex gap-3 justify-content-between align-items-center mt-2">
                <x-product.item-number :product="$product" format="{product_code}" element="h4"
                                       class="text-primary font-weight-bold mb-0"/>

                <x-product-social-media-link :product="$product" class="btn btn-outline-secondary"/>
            </div>

            <h3 class="product-title">{{ $product->Product_Name ?? '' }}</h3>

            <x-product.price
                    element="div"
                    class="w-100 product-price d-flex"
                    :product="$product"
                    :value="$product->ERP?->Price"
                    :uom="$product->ERP?->UnitOfMeasure ?? 'EA'"
                    :std-price="$product->Msrp->toFloat()"/>

            <div class="d-flex w-100 justify-content-start mb-2">

                <x-product-manufacture-image :product="$product" class="tag border-info" title="Brand"
                                             data-toggle="tooltip">
                    <x-slot:prefix>
                        <i class="icon-star font-weight-bold" style="margin-top: -3px"></i>
                    </x-slot:prefix>
                </x-product-manufacture-image>

                @if($product->total_quantity_available > 1)
                    <x-product.availability
                            :product="$product" :value="$product->total_quantity_available"
                            data-toggle="tooltip" title="Quantity Available"
                            element="a" class="tag border-primary font-weight-bold">
                        <x-slot:prefix>
                            <i class="icon-layers font-weight-bold" style="margin-top: -3px"></i>
                        </x-slot:prefix>
                        <x-slot:suffix>
                            <span>available</span>
                        </x-slot:suffix>
                    </x-product.availability>
                @endif

                @if(!empty($product->min_order_qty))
                    <a href="#" class="tag border-warning font-weight-bold"
                       data-toggle="tooltip" title="Minimum Order Quantity">
                        MOQ: {{ $product->min_order_qty }}
                        @if($product->min_order_qty > 1)
                            pieces
                        @else
                            piece
                        @endif
                    </a>
                @endif

                @if($product->is_ncnr)
                    <a href="#" class="tag border-danger font-weight-bold"
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

            <x-product.short-description :content="$product->short_description ?? ''" :lines="3" class="py-2"/>

            @if(!empty($eaAttributes))
                <div class="row margin-top-1x">
                    @foreach($eaAttributes as $attribute)
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="size">{{ $attribute->getName() }}</label>
                                <select class="form-control" id="size">
                                    <option selected value="">Choose {{ $attribute->getName() }}</option>
                                    @foreach($attribute->getFullList() as $attributeValue)
                                        <option value="">{{ $attributeValue->getDisplayName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {!! $middle ?? null !!}

            <hr class="mb-3">

            <div class="d-grid d-md-flex justify-content-end gap-2 product-card border-0 p-0">
                @if(!empty($product->ERP->FutureReceiptDetails))

                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                        <tr>
                            <th colspan="2" class="text-center">Future Availability</th>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <th>Available By Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($product->ERP->FutureReceiptDetails as $entry)
                            <tr>
                                <th>{{ \Carbon\CarbonImmutable::parse($entry['schDeliveryDate'])->format('d M Y') }}</th>
                                <td class="text-center">{{ $entry['openQty'] ?? null }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                <div class="product-buttons">
                    <x-product.quick-action :product="$product"/>
                </div>
            </div>

            {!! $after ?? null !!}
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <x-product.information-tabs
                    :product="$product"
                    :header-class="''"
                    :tabs="[
                'description',
                 'sku' => ['label' => 'Products'],
                'feature' => ['label' => 'Features', 'style' => 'list'],
                'specification' => ['label' => 'Specifications', 'style' => 'list'],
                'document',
                'related-products' => ['label' => 'Related'],
            ]"/>
        </div>
    </div>
</div>

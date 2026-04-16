<div {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-body">
            <x-site.data-table-wrapper id="order-table">
                <table class="products-table table table-bordered table-hover" id="order-table">
                    <thead>
                    <tr>
                        <th>{{ __('Items') }}</th>
                    </tr>
                    </thead>
                    <tbody class="accordion" id="sku_details_table_body">
                    @foreach($products as $index => $product)
                        <tr class="sku-item" data-product-code="{{ $product->product_code }}"
                            data-product-id="{{ $product->id }}" data-qty="" data-warehouse="">
                            <td data-sort="{{ $product->local_product_name }}">
                                <div
                                    class="border-bottom gap-3 p-3 d-flex flex-wrap flex-md-nowrap justify-content-between">
                                    <div class="d-flex gap-3">
                                        <a href="{{ frontendSingleProductURL($product) . "?has_sku={$product->hasSku}" }}">
                                            <img class="w-120" src="{{ $product->productImage?->main ?? '' }}" alt="product">
                                        </a>
                                        <div>
                                            <p class="text-uppercase mb-0">
                                                <a class="text-decoration-none"
                                                   href="{{ frontendSingleProductURL($product) . "?has_sku={$product->hasSku}" }}"><span
                                                        class="d-block d-md-inline font-weight-bold mr-md-2 mb-2 mb-md-0">{{ $product->local_product_name }}</span></a>
                                                {!! $product->local_short_description !!}
                                            </p>
                                            <p><b>{{ __('Product Code') }}:</b> {{ $product->product_code }}</p>

                                            <div class="d-flex flex-wrap gap-col-3">
                                                @foreach ($product->attributes as $attribute)
                                                    @php $attrVal = json_decode($attribute->pivot->attribute_value, true)['en'] ?? ""; @endphp
                                                    <p class="mb-1"
                                                       filter-attribute="{{ $attribute->local_name.'-'.$attrVal }}">
                                                        <b>{{ $attribute->local_name }}:</b> {{ $attrVal }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-3">
                                        @if ($product?->ERP)
                                            <div class="w-260 flex-shrink-0">
                                                @for ($i = 1; $i <= 6; $i++)
                                                    @if ($product?->ERP["QtyPrice_{$i}"])
                                                        <p class="d-flex justify-content-between mb-2">
                                                            <span>{{ $product?->ERP["QtyBreak_{$i}"] }}+</span>
                                                            <span>{{ price_format($product?->ERP["QtyPrice_{$i}"] ?? 0) }}</span>
                                                        </p>
                                                    @endif
                                                @endfor

                                                <p class="d-flex justify-content-between mb-2">
                                                    <span><b>{{ __('Your Price') }}</b></span>
                                                    <x-product.price
                                                        element="span"
                                                        :product="$product"
                                                        :value="$product->ERP?->Price"
                                                        :uom="$product->ERP?->UnitOfMeasure ?? 'EA'"/>
                                                </p>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <b>{{ __('Quantity') }}</b>
                                                    <x-cart.quantity-update :product="$product" :index="$index"/>
                                                </div>

                                                <button class="btn btn-danger"
                                                        data-warehouse="{{ $product->ERP->WarehouseID ?? \ErpApi::getCustomerDetail()->DefaultWarehouse }}"
                                                        data-options="{{ json_encode(['code' => $product->product_code]) }}"
                                                        onclick="Amplify.addSingleItemToCart(this, '#cart-item-{{ $index }}')">
                                                    {{ __('Add to Cart') }}
                                                </button>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                                <div class="card">
                                    <div class="py-0 bg-transaprent" id="heading{{$index}}">
                                        <button class="btn-sm btn my-0 btn-block button-outline-primary collapsed"
                                                type="button"
                                                data-toggle="collapse" data-target="#collapse{{$index}}"
                                                aria-expanded="true" aria-controls="collapse{{$index}}">
                                            {{ __('View History') }}
                                        </button>
                                    </div>

                                    <div id="collapse{{$index}}" class="collapse" aria-labelledby="heading{{$index}}"
                                         data-parent="#sku_details_table_body">
                                        <div class="card-body">
                                            <table class="table table-bordered view-history">
                                                <thead>
                                                <tr>
                                                    <th>{{ __('Order date') }}</th>
                                                    <th>{{ __('Order Invoice Num.') }}</th>
                                                    <th width="30">{{ __('UOM') }}</th>
                                                    <th>{{ __('Qty') }}</th>
                                                    <th>{{ __('Price') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>

                                                @foreach($product['orderInfo'] as $index => $orderItems)
                                                    <tr>
                                                        <td data-order="{{$orderItems['Date']}}">{{carbon_date($orderItems['Date'])}}</td>
                                                        <td
                                                            data-order="{{$orderItems['InvoiceNum']}}"
                                                        >
                                                            <a
                                                                href="{{route('frontend.invoices.show', $orderItems['InvoiceNum'].'-0')}}"
                                                            >
                                                                {{$orderItems['InvoiceNum']}}
                                                            </a>
                                                        </td>
                                                        <td>{{$orderItems['UnitOfMeasure']}}</td>
                                                        <td>{{$orderItems['Quantity']}}</td>
                                                        <td>{{price_format($orderItems['Price'])}}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-site.data-table-wrapper>
        </div>
    </div>
</div>

@pushonce("footer-script")
    <script>
        $(document).ready(function () {
            $("#order-table").DataTable();
            var table =  $(".view-history").DataTable();
            table.order([0, 'desc']).draw();
        });
    </script>
@endpushonce

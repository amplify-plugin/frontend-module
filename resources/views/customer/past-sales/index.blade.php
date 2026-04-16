<div {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-body">
            <x-site.data-table-wrapper id="order-table">
                <table class="products-table table table-bordered table-hover" id="order-table">
                    <thead>
                    <tr>
                        <th>Items</th>
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
                                            <p><b>Product Code:</b> {{ $product->product_code }}</p>

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
                                                    <span><b>Your Price</b>/ {{ $product->ERP->PricingUnitOfMeasure }}</span>
                                                    <span>
                                                    @if ($product?->campaignProduct)
                                                            <del>
                                                            {{ price_format($product->ERP->Price) }}
                                                        </del>
                                                            <b>{{ price_format($product->campaignProduct->discount) }}</b>
                                                        @else
                                                            {{ price_format($product->ERP->Price) }}
                                                        @endif
                                                    </span>
                                                </p>

                                                <div class="d-flex align-items-center justify-content-between cs-w-420">
                                                    <div><b>Quantity/{{ $product->ERP->PricingUnitOfMeasure }}</b></div>
                                                    <div class="align-items-center d-flex gap-2 qty-section">
                                                        <button type="button"
                                                                onclick="Amplify.handleQuantityChange('#product_qty_{{ $index }}', 'decrement');"
                                                                class="operator">-
                                                        </button>
                                                        <input type="text" class="qty-input" id="product_qty_{{ $index }}" name="qty" value="1"
                                                               data-min-order-qty="1" data-qty-interval="1"
                                                               min="1" step="1" style="width: 50px; text-align: center;"/>
                                                        <button type="button"
                                                                onclick="Amplify.handleQuantityChange('#product_qty_{{ $index }}', 'increment');"
                                                                class="operator operator-dark">+
                                                        </button>
                                                    </div>
                                                </div>
                                                <input id="product_code_{{ $index }}" name="product_code[]"
                                                       type="hidden" value="{{ $product->product_code }}">
                                                <input id="{{ 'product_warehouse_' . $index }}" type="hidden"
                                                       value="{{ optional(optional(customer(true))->warehouse)->code }}" />

                                                <button class="btn btn-danger" id="add_to_order_btn_{{$index}}"
                                                        onclick="addSingleProductToOrder({{$index}})">
                                                    Add to Cart
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
                                            View History
                                        </button>
                                    </div>

                                    <div id="collapse{{$index}}" class="collapse" aria-labelledby="heading{{$index}}"
                                         data-parent="#sku_details_table_body">
                                        <div class="card-body">
                                            <table class="table table-bordered view-histroy">
                                                <thead>
                                                <tr>
                                                    <th>Order date</th>
                                                    <th>Order Invoice Num.</th>
                                                    <th width="30">UOM</th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
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
<style>
    .qty-plus {
        background-color: #202549 !important;
    }
</style>
@php
    push_js(function () {
        return <<<HTML
        const ORDER_DATE_RANGER = '#created_date_range';

        $(document).ready(function () {
            var startDate = $("#created_start_date").val();
            var endDate = $("#created_end_date").val();
            var table =  $(".view-histroy").DataTable();
            table.order([0, 'desc']).draw();

            initOrderCreatedDateRangePicker(startDate, endDate);
        });
HTML;}, 'footer-script');
@endphp

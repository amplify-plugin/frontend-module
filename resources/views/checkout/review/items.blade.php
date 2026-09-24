@foreach($items as $item)
    <li @class(["media border-bottom p-2", "rounded border border-warning" => $item->additional_info['is_ncnr']])>
        <div class="d-flex mr-2" style="width: 72px; height: 72px">
            <img class="w-100 h-100 object-contain"
                 alt="{{ $item->product_name   }}"
                 src="{{ assets_image($item->product_image) }}"/>
        </div>
        <div class="media-body">
            <a href="{{ frontendSingleProductURL($item->product) }}"
               class="text-decoration-none text-black d-block text-truncate mb-1">
                {{ $item->product_name }}
            </a>
            <div class="d-grid gap-2 d-md-flex justify-content-between">
                <div>
                    <section class="d-flex flex-wrap gap-2">
                    <span class="tag mr-0 ">
                        <span>Product Code: </span>
                        {{ $item->product_code }}
                    </span>
                        <span class="tag mr-0 active">
                        <span>Quantity: </span>
                        <b>{{ round($item->quantity) }}</b>
                    </span>
                        <x-product.price
                                element="span"
                                class="tag mr-0  d-flex justify-content-end"
                                :product="$item->product"
                                :value="$item->unitprice"
                                :uom="$item->uom"
                                :std-price="floatval($item->product->msrp)"
                        >
                            <span>Price:&nbsp;&nbsp;</span>
                        </x-product.price>
                        <span class="tag mr-0 ">
                        <span>Warehouse: </span>
                        {{ $item->product_warehouse_code }}
                    </span>
                        @if($item->additional_info['in_stock'] ?? false)
                            <span class="tag mr-0 ">
                        <span>Status: </span>
                        In-Stock
                    </span>
                        @endif

                        @if($item->additional_info['is_ncnr'] ?? false)
                            <span class="tag mr-0  bg-warning" data-toggle="tooltip"
                                  title="Non-Cancelable, Non-Returnable">
                        <span><i class="icon-bell" style="margin-top: -4px"></i> </span>
                        NCNR
                    </span>
                        @endif
                    </section>

                    @if(!empty($item->additional_info['ship_restriction'] ?? ''))
                        <p class="font-weight-bold mb-0">{{ $item->additional_info['ship_restriction'] ?? '' }}</p>
                    @endif
                </div>
                <section class="d-grid gap-2 text-right">
                    <strong>{{ currency_format($item->subtotal, withSymbol: true) }}</strong>
                </section>
            </div>
        </div>
    </li>
@endforeach
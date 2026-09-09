<div {!! $htmlAttributes !!}>
    <div class="card my-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <span class="text-lg font-weight-bold">{{ __($title) }}</span>
            @if ($dateRangeLabel)
                <span class="text-muted small">{{ __($dateRangeLabel) }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if ($products->isEmpty())
                <div class="p-4 text-center text-muted">
                    {{ __('No selling products found for this period.') }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Unit') }}</th>
                                <th class="text-right">{{ __('Units Sold') }}</th>
                                <th class="text-right">{{ __('Revenue') }}</th>
                                <th class="text-right">{{ __('Orders') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $row)
                                <tr>
                                    <td>
                                        @php
                                            $productName = $row->product?->product_name ?: ($row->product_name ?: $row->product_code);
                                        @endphp
                                        @if ($row->product)
                                            <a class="text-decoration-none font-weight-bold"
                                               href="{{ frontendSingleProductURL($row->product) }}">
                                                {{ $productName }}
                                            </a>
                                            <div class="small text-muted">{{ $row->product_code }}</div>
                                        @else
                                            <span class="font-weight-bold">{{ $productName }}</span>
                                            @if ($row->product_name && $row->product_name !== $row->product_code)
                                                <div class="small text-muted">{{ $row->product_code }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $row->unit_code ?: '—' }}</td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $row->units_sold, 2, '.', ','), '0'), '.') }}</td>
                                    <td class="text-right">{{ price_format($row->revenue) }}</td>
                                    <td class="text-right">{{ $row->order_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

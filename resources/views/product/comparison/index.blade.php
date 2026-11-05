<div id="product-comparison" {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">{{ __('Product Comparison') }}</h4>
                <span class="text-muted mb-0">{{ __('Find and select products to see the differences and similarities between them') }}</span>
            </div>
            @if(count($products) > 1)
                <label class="mb-0">
                    <input type="checkbox" id="compare-differences-only">
                    {{ __('Show only differences') }}
                </label>
            @endif
        </div>
        <div class="card-body p-3">
            @if($products === [])
                <p class="text-center text-muted mb-0">{{ __('Your product comparison list is empty.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th class="align-bottom border-top-0">{{ __('Specification') }}</th>
                            @foreach($products as $product)
                                <td width="22%" class="border-top-0 text-center" data-compare-column="{{ $product['id'] }}">
                                    <div class="w-100 mb-2" style="height: 160px">
                                        <a href="{{ $product['href'] }}">
                                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                                                 style="width: 100%; height: 100%; object-fit: contain">
                                        </a>
                                    </div>
                                    <a href="{{ $product['href'] }}" class="text-decoration-none">
                                        <h4 class="h6 mb-1">{{ $product['name'] }}</h4>
                                    </a>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0"
                                            onclick="Amplify.compareProducts(this, {{ $product['id'] }}, 'remove'); return false;">
                                        {{ __('Remove') }}
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr data-compare-differs="{{ $row['differs'] ? '1' : '0' }}">
                                <th>{{ $row['label'] }}</th>
                                @foreach($products as $product)
                                    <td data-compare-column="{{ $product['id'] }}">{{ $row['values'][$product['id']] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@if(count($products) > 1)
    <script>
        document.getElementById('compare-differences-only')?.addEventListener('change', function (event) {
            document.querySelectorAll('#product-comparison [data-compare-differs="0"]').forEach(function (row) {
                row.hidden = event.target.checked;
            });
        });
    </script>
@endif

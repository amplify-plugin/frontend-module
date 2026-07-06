@php
    $keys = array_keys($items);

    $attributes = array_keys($items[$keys[0]]);

@endphp
<div {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-header">
            <h4>{{ __('Product Comparison') }}</h4>
            <span class="text-muted mb-0">{{ __('Find and select products to see the differences and similarities between them') }}</span>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th class="align-baseline border-top-0">{{ __('You can add Max 4 Products') }}</th>
                        @foreach($items as $item)
                            <td width="22%" class="border-top-0">
                                <div class="form-group">
                                    <input class="form-control" type="search" placeholder="Search and Select Product">
                                </div>
                                <div class="w-100" style="height: 200px">
                                    <img src="{{ $item['image'] }}" class="card-img-top" alt="{{ $item['name'] }}"
                                         style="width: 100%; height: 100%; object-fit: contain">
                                </div>
                                <h4 class="lead text-center">{{ $item['code'] }}</h4>
                                <p class="text-muted">{{ $item['name'] }}</p>
                            </td>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($attributes as $attribute)
                        <tr>
                            <th>{{ $attribute }}</th>
                            @foreach($items as $item)
                                <td>{{ $item[$attribute] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

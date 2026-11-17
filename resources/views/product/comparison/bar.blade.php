<div id="product-compare-bar"
     data-max="{{ $max }}"
     @if($items === []) hidden @endif>
    <div class="product-compare-bar__inner">
        <div class="product-compare-bar__items" data-compare-items>
            @foreach($items as $item)
                <div class="product-compare-bar__item" data-compare-id="{{ $item['id'] }}">
                    <img src="{{ $item['image'] }}" alt="">
                    <span class="product-compare-bar__name">{{ $item['name'] }}</span>
                    <button type="button"
                            class="product-compare-bar__remove"
                            aria-label="{{ __('Remove') }}"
                            data-compare-remove="{{ $item['id'] }}">&times;</button>
                </div>
            @endforeach
            @if(count($items) === 1)
                <div class="product-compare-bar__slot">{{ __('Add more') }}</div>
            @endif
        </div>
        <div class="product-compare-bar__actions">
            <button type="button"
                    class="product-compare-bar__minimize"
                    data-compare-minimize
                    aria-expanded="true"
                    aria-label="{{ __('Minimize comparison') }}">
                <span class="product-compare-bar__minimize-open" aria-hidden="true">&#9660;</span>
                <span class="product-compare-bar__minimize-closed">
                    {{ __('Compare') }} (<span data-compare-minimized-count>{{ count($items) }}</span>/{{ $max }})
                </span>
            </button>
            <span class="text-muted small text-nowrap">
                <span data-compare-count>{{ count($items) }}</span>/{{ $max }}
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-compare-clear>
                {{ __('Clear All') }}
            </button>
            <a class="btn btn-sm btn-primary {{ count($items) < 2 ? 'disabled' : '' }}"
               data-compare-open
               href="{{ $compareUrl }}"
               @if(count($items) < 2) aria-disabled="true" tabindex="-1" @endif>
                {{ __('Compare') }}
            </a>
        </div>
    </div>
</div>

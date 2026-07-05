<h6 class="dropdown-header px-0">
    Product Comparison
    @if(!empty($items))
        ({{ count($items) }})
    @endif
</h6>
<div class="dropdown-divider"></div>
@if(empty($items))
    <p class="text-center align-self-center">{{ __('Your product comparison list is empty!') }}</p>
@else
    <ul class="list-group-flush pl-0" style="max-height: 300px; overflow-y: auto; overflow-x: hidden">
        @foreach($items as $index => $item)
            <li @class(["list-group-item p-2", 'border-top-0' => $index === 0])>
                <div class="media">
                    <img class="d-flex rounded-circle align-self-center mr-1"
                         src="{{ $item['image'] }}" width="64" alt="Media">
                    <div class="media-body">
                        <strong class="mt-0 mb-1">{{ $item['code'] }}</strong>
                        <a href="{{ $item['href'] }}" class="text-decoration-none" target="_blank" title="{{ $item['name'] }}">
                            <p class="text-sm mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden">
                                {{ $item['name'] }}
                            </p>
                        </a>
                    </div>
                    <a href="javascript:void(0);" title="Remove from comparison"
                       onclick="Amplify.compareProducts(this,{{ $item['id'] }}, 'remove'); return false;"
                       class="text-decoration-none align-self-center ml-2" style="width: 20px; text-align: center">
                        <i class="icon-trash text-danger" style="font-size: 150%"></i>
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="dropdown-divider"></div>
    <div class="d-flex justify-content-between">
        <button class="btn btn-sm btn-secondary"
                type="button" onclick="Amplify.compareProducts(this, null, 'clear')">
            Clear
        </button>
        <a class="btn btn-sm btn-primary" href="#">
            Compare Now
        </a>
    </div>
@endif
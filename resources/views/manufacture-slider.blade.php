<div {!! $htmlAttributes !!}>
    @if($showTitle)
        <h3 class="text-center mb-30 pb-2">{{ __($heading) }}</h3>
    @endif
    <div class="manufacture-slider owl-carousel"
         data-owl-carousel="{{ $carouselOptions() }}">
        @foreach(($manufactureData ?? []) as $manufacturer)
            <a href="{{ route('frontend.shop.index') }}?search=1&q={{ $manufacturer->code ?? '' }}"
               title="{{ __($manufacturer->name ?? '') }}"
               class="text-decoration-none">
                <img class="w-100 h-100 object-fit-contain"
                     src="{{ $manufacturer->image }}"
                     alt="{{ __($manufacturer->name ?? '') }}"/>
                @if ($showName)
                    <p class="text-center mt-2">{{ __($manufacturer->name ?? '') }}</p>
                @endif
            </a>
        @endforeach
    </div>
</div>

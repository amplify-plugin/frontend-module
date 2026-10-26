<div {!! $htmlAttributes !!}>
    @if($showTitle)
        <h3 class="text-center mb-30 pb-2">{{ __($heading) }}</h3>
    @endif
    <div class="manufacture-slider owl-carousel"
         data-owl-carousel="{{ $carouselOptions() }}">
        @foreach(($manufactureData ?? []) as $manufacturer)
            <a href="{{ route('frontend.shop.index') }}?search=1&q={{ $manufacturer->code ?? '' }}"
               title="{{ __($manufacturer->name ?? '') }}"
               class="text-decoration-none manufacturer-item">
                <div class="manufacturer-img-container">
                    <img class="object-fit-contain"
                         src="{{ $manufacturer->image }}"
                         alt="{{ __($manufacturer->name ?? '') }}"/>
                </div>
                @if ($showName)
                    <span class="manufacturer-name">{{ __($manufacturer->name ?? '') }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>

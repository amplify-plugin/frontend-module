<div {!! $htmlAttributes !!}>
    <template id="cart-single-item-template">
        {!! $itemRow ?? '' !!}
    </template>
    @if ($cartItemCount > 0)
        <div id="app">
            <{{ $componentName }}
                    @foreach($props(get_defined_vars()) as $name => $value)
                        :{{ \Illuminate\Support\Str::kebab($name) }}='@json($value)'
                    @endforeach
            >
                {!! $slot !!}
            </{{ $componentName }}>
        </div>
    @else
        @include('widget::checkout.inc.empty-checkout')
    @endif
</div>

@pushonce('footer-script')
    @empty($assetUrl)
        <script src="{{ mix("js/main.js", 'vendor/widget') }}"></script>
    @else
        <script src="{{ mix($assetUrl) }}"></script>
    @endempty
@endpushonce

<div {!! $htmlAttributes !!}>
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
    <script>
        window.addEventListener('pageshow', function (event) {
            const navigation = performance.getEntriesByType('navigation')[0];

            if (
                event.persisted ||
                navigation?.type === 'back_forward'
            ) {
                window.location.reload();
            }
        });
    </script>
@endpushonce

@pushonce('custom-script')
    @foreach($gatewayAssets() as $assetUrl)
        <script defer src="{{ $assetUrl }}"></script>
    @endforeach
@endpushonce

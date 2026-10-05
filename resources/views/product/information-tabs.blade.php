<div {!! $htmlAttributes !!}>

    {!!  $before  ?? '' !!}

    @foreach($entries as $view => $entry)
        @include($view, [
            'tab' => $entry,
            'product' => $product,
        ])
    @endforeach

    <ul class="nav nav-tabs {{ $headerClass }}" id="product-information-tabs" role="tablist">
        @stack('title')
    </ul>

    <div class="tab-content">
        @stack('content')
        {!! $slot ?? '' !!}
    </div>
    {!!  $after ?? '' !!}
</div>

@push('footer-script')
    <script>
        document.addEventListener("DOMContentLoaded", (event) => {
            let tab = document.querySelector('.x-product-information-tabs .nav-link:first-of-type');
            if (tab) {
                tab.dispatchEvent(new MouseEvent("click", {
                    bubbles: true,
                    cancelable: true,
                    view: window
                }));
            }

            if (window.jQuery) {
                jQuery('.x-product-information-tabs .document-accordion').on('shown.bs.collapse hidden.bs.collapse', function (collapseEvent) {
                    const button = this.querySelector('[data-target="#' + collapseEvent.target.id + '"]');
                    if (button) {
                        const isOpen = collapseEvent.type === 'shown';
                        button.setAttribute(
                            'title',
                            isOpen
                                ? (button.getAttribute('data-close-hint') || '')
                                : (button.getAttribute('data-open-hint') || '')
                        );
                    }

                    if (collapseEvent.type !== 'shown') {
                        return;
                    }

                    const object = collapseEvent.target.querySelector('object.iframe-style');
                    if (! object) {
                        return;
                    }

                    const src = object.getAttribute('data');
                    if (src) {
                        object.setAttribute('data', src);
                    }
                });
            }

            setTimeout(() => {
                const html = document.querySelector('.x-product-information-tabs #product-information-tabs').innerHTML.toString().trim();
                if(html.length === 0){
                    document.querySelector('.x-product-information-tabs').remove();
                }

            }, 100);

        });
    </script>
@endpush


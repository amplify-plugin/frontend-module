<div {!! $htmlAttributes !!}>

    {!!  $before  ?? '' !!}

    @foreach($entries as $view => $entry)
        @include($view, [
            'tab' => $entry,
            'product' => $product,
            'documentLayout' => $documentLayout ?? 'flat',
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

<style>
    .document-subtabs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin: 0 0 10px;
        padding: 0;
    }

    .document-subtab {
        border: 1px solid #d0d0d0;
        background: #fff;
        color: #222;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 13px;
        line-height: 1.3;
        cursor: pointer;
    }

    .document-subtab.active,
    .document-subtab:hover {
        background: var(--brand-primary, #bc2127);
        border-color: var(--brand-primary, #bc2127);
        color: #fff;
    }

    .x-product-information-tabs .has-document-subtabs .document-subtab-content {
        padding: 0;
        border: 0;
        border-radius: 0;
        overflow: visible;
    }

    .x-product-information-tabs .has-document-subtabs .document-subtab-content > .tab-pane,
    .x-product-information-tabs .has-document-subtabs .iframe-style {
        margin: 0;
        padding: 0;
        width: 100%;
    }
</style>

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
        });
    </script>
@endpush


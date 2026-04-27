<div {!! $htmlAttributes !!}>
    <div class="modal-dialog container">
        <div class="modal-content">
            <div class="modal-body">
                <p @class(['modal-title', 'd-none' => empty($title)])>
                    {{ $title }}
                </p>
                @empty($content)
                    <script> alert('Warning!! Cookie Consent Content is not setup yet.'); </script>
                @else
                    {!! $content !!}
                    <p class="my-2">{!! __('Use the <b>“I understand”</b> button to give consent.') !!}</p>
                @endempty
                <div class="modal-actions">
                    <button type="button" class="btn btn-consent"
                            onclick="acceptCookie();">
                        {{ __('I understand') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@pushonce('footer-script')
    <script>
        function acceptCookie() {
            window.localStorage.setItem('consented', 'true');
            $('#cookie-consent-modal').modal('hide');
        }

        $(function () {
            let consent = window.localStorage.getItem('consented');
            if (consent == null || consent === 'false') {
                $('#cookie-consent-modal').modal('toggle');
            }
        });
    </script>
@endpushonce

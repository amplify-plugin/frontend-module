<script>
    @if(!empty($level))
    document.addEventListener('DOMContentLoaded', function () {
        @if($alert)
        @php
            // Admin-entered message: allow line breaks (<br>, <p> or newlines) but strip everything else.
            $htmlMessage = nl2br(strip_tags((string) $message, '<br><p><b><strong><i><em>'));
        @endphp

        window.Amplify.alert({!! json_encode($htmlMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}, '{{$title}}', {icon: '{{$level}}'});
        @else
        window.Amplify.notify('{{$level}}', '{{$message}}', '{{$title}}');
        @endif
    });
    @endif
</script>


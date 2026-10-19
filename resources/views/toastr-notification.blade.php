<script>
    @if(!empty($level))
    document.addEventListener('DOMContentLoaded', function () {
        @if($alert)
        window.Amplify.alert({!! json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}, '{{$title}}', {icon: '{{$level}}'});
        @else
        window.Amplify.notify('{{$level}}', '{{$message}}', '{{$title}}');
        @endif
    });
    @endif
</script>

<script>
    @if(!empty($level))
    document.addEventListener('DOMContentLoaded', function () {
        @if($alert)
        window.Amplify.alert('{{$message}}', '{{$title}}', {icon: '{{$level}}'});
        @else
        window.Amplify.notify('{{$level}}', '{{$message}}', '{{$title}}');
        @endif
    });
    @endif
</script>


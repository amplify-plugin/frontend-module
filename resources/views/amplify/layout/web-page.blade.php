<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-amplify.head/>
    <title>{{ $pageTitle }}</title>
    {{--    @livewireStyles--}}
    {!! $head ?? '' !!}
</head>
<body {!! $htmlAttributes !!}>
    @stack('off-canvas-menu')
    {!! $slot !!}
    <x-scroll-to-top/>
    <x-amplify.foot/>
    {!! $foot ?? '' !!}
    {{--    @livewireScripts--}}
</body>
</html>

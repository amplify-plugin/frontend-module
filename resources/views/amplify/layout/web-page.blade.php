<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-amplify.head/>
    <title>{{ $pageTitle }}</title>
    {{--    @livewireStyles--}}
    {!! $head ?? '' !!}
</head>
<body {!! $htmlAttributes !!}>
@if($tag_manager_id)
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $tag_manager_id }}"
                height="0"
                width="0"
                style="display:none;visibility:hidden"></iframe>
    </noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
@stack('off-canvas-menu')
{!! $slot !!}
<x-scroll-to-top/>
<x-amplify.foot/>
{!! $foot ?? '' !!}
{{--    @livewireScripts--}}
</body>
</html>

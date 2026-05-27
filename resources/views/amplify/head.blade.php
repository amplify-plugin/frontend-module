@php $meta_data = (isset($meta_data)) ? $meta_data : [] @endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<x-site.meta-tags :tags="$meta_data"/>
<x-site.favicon/>
@stack('plugin-style')
@stack('template-style')
@stack('custom-style')
@stack('internal-style')
@stack('head-script')

<x-google-analytic/>

<x-site.script-manager position="header"/>


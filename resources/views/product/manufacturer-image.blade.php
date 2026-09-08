<a {!! $htmlAttributes !!}>
    {!! $prefix ?? null !!}
    @if(isset($product->manufacturer->image) && $product->manufacturer->image != null)
        <img src="{{ $product->manufacturer->image }}"
             class="h-100" style="object-fit: contain"
             alt="{{ $product->manufacturer->name }}">
    @else
        <strong>{{ $product->manufacturer?->name }}</strong>
    @endif
    {!! $suffix ?? null !!}
</a>

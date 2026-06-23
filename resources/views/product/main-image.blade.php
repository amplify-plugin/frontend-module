<a {!! $htmlAttributes !!}>
    {!!  $slot ?? '' !!}
    <img src="{{ $product->Product_Image ?? '' }}"
         class="product-image object-contain"
         loading="lazy"
         alt="{{ $product->Product_Name }}">
</a>

<div {!! $htmlAttributes !!}>
    <a class="product-thumb" href="{url}">
        <img src="{image}" class="img-thumbnail" alt="{name}">
    </a>
    <div class="product-info">
        <a href="{url}" class="product-link">
            <p class="product-title">
                {name}
            </p>
        </a>
        {!! $slot !!}
    </div>
</div>

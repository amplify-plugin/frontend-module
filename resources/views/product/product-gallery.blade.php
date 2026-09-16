<div {!! $htmlAttributes !!}>
    <div class="product-gallery">

        {!! $before ?? '' !!}
        <div @class(['d-flex gap-2 justify-content-start' => $thumbnailPosition == 'left'])>
            <div class="product-carousel owl-carousel gallery-wrapper">

                <div class="gallery-item" data-hash="item-0">
                    <a href="{{ assets_image($productImage->main ?? '') }}">
                        <img src="{{ assets_image($productImage->main ?? '') }}" alt="Product" class="img-fluid">
                    </a>
                </div>

                @foreach ($additionalItems as $key => $image)
                    @if (str_contains($image, 'youtube.com') !== false)
                        @php
                            preg_match('/\/embed\/([a-zA-Z0-9_-]+)/', $image, $matches);
                            $videoId = $matches[1];
                        @endphp
                        <div class="gallery-item video-btn text-center" data-hash="{{ 'item-' . ($key + 1) }}">
                            <a data-toggle="tooltip" data-type="video" data-size="1920x1080"
                               data-video="&lt;div class=&quot;wrapper&quot;&gt;&lt;div class=&quot;video-wrapper&quot;&gt;&lt;iframe class=&quot;pswp__video&quot; width=&quot;960&quot; height=&quot;640&quot; src=&quot;{{ $image }}&quot; frameborder=&quot;0&quot; autoplay&gt; allowfullscreen&gt;&lt;/iframe&gt;&lt;/div&gt;&lt;/div&gt;"
                               href="https://img.youtube.com/vi/{{ $videoId }}/hqdefault.jpg">
                                <img src="https://img.youtube.com/vi/{{ $videoId }}/hqdefault.jpg" alt="Product"
                                     class="img-fluid">
                            </a>
                        </div>
                    @else
                        <div class="gallery-item" data-hash="{{ 'item-' . ($key + 1) }}">
                            <a href="{{ assets_image($image ?? '') }}">
                                <img src="{{ assets_image($image ?? '') }}" alt="Product" class="img-fluid">
                            </a>
                        </div>
                    @endif
                @endforeach

                @foreach ($erpItems as $key => $additionalImage)
                    <div class="gallery-item" data-hash="{{ 'item-' . ($extraItemCount + $key + 1) }}">
                        <a href="{{ assets_image($additionalImage['value']) }}">
                            <img src="{{ assets_image($additionalImage['value']) }}" alt="Product"
                                 class="img-fluid">
                        </a>
                    </div>
                @endforeach
            </div>
            <ul class="product-thumbnails owl-carousel"
                @style(['order: -1' => $thumbnailPosition == 'left']) data-owl-carousel="{{ $thumbnailCarouselConfig() }}">

                <li class="item active" data-gallery-index="0">
                    <a class="product-thumbnail" href="#item-0">
                        <img src="{{ assets_image($productImage->main ?? '') }}" alt="Product" class="img-fluid"/>
                    </a>
                </li>

                @foreach ($additionalItems as $key => $image)
                    @if (str_contains($image, 'youtube.com') !== false)
                        @php
                            preg_match('/\/embed\/([a-zA-Z0-9_-]+)/', $image, $matches);
                            $videoId = $matches[1] ?? '';
                        @endphp
                        <li class="item" data-gallery-index="{{$key + 1}}">
                            <a class="product-thumbnail video-thumbnail" href="#{{ 'item-' . ($key + 1) }}">
                                <img src="https://img.youtube.com/vi/{{ $videoId }}/hqdefault.jpg" alt="Product"
                                     class="img-fluid">
                            </a>
                        </li>
                    @else
                        <li class="item" data-gallery-index="{{$key + 1}}">
                            <a class="product-thumbnail" href="#{{ 'item-' . ($key + 1) }}">
                                <img src="{{ assets_image($image ?? '') }}" alt="Product" class="img-fluid"/>
                            </a>
                        </li>
                    @endif
                @endforeach

                @foreach ($erpItems as $key => $additionalImage)
                    <li class="item">
                        <a class="product-thumbnail" href="#{{ 'item-' . ($extraItemCount + $key + 1) }}">
                            <img src="{{ assets_image($additionalImage['value']) }}" alt="Product"
                                 class="img-fluid"/>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        {!! $after ?? '' !!}

    </div>
</div>

@push('html-default')
    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="pswp__bg"></div>
        <div class="pswp__scroll-wrap">
            <div class="pswp__container">
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
            </div>
            <div class="pswp__ui pswp__ui--hidden">
                <div class="pswp__top-bar">
                    <div class="pswp__counter"></div>
                    <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
                    <button class="pswp__button pswp__button--share" title="Share"></button>
                    <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                    <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
                    <div class="pswp__preloader">
                        <div class="pswp__preloader__icn">
                            <div class="pswp__preloader__cut">
                                <div class="pswp__preloader__donut"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                    <div class="pswp__share-tooltip"></div>
                </div>
                <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>
                <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>
                <div class="pswp__caption">
                    <div class="pswp__caption__center"></div>
                </div>
            </div>
        </div>
    </div>
@endpush

@pushonce('footer-script')
    <script>
        $(function () {
            Amplify.initPhotoSwipeFromDOM('.gallery-wrapper');
            Amplify.productSlider('.product-carousel');
            Amplify.thumbnailCarousel('product-gallery ul.product-thumbnails');
        });
    </script>
@endpushonce

<div {!! $htmlAttributes !!}>
    <div>
        {!! $content ?? '' !!}
    </div>
</div>

@pushonce('internal-style')
    <style>
        .x-product-short-description > div {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: {{ $lines }};
            overflow: hidden;
        }

        .x-product-short-description p {
            margin-bottom: 0 !important;
        }

        .x-product-short-description.expanded > div {
            -webkit-line-clamp: unset;
        }
    </style>
@endpushonce

@pushonce('footer-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            let containers = document.querySelectorAll('.x-product-short-description > div');

            for (const container of containers) {
                console.log(container.clientHeight, container.clientWidth, container.offsetHeight);
            }

            /*
                        $('.x-product-short-description').each(function () {

                            let container = $(this);
                            let content = container.children('div:first');

                            // measure clamped height
                            let clampedHeight = content[0];
                            console.log(clampedHeight.offsetHeight, clampedHeight.clientHeight);

                            // remove clamp
                            content.css('-webkit-line-clamp', 'unset');

                            // force reflow
                            content[0].offsetHeight;

                            // measure full height
                            let fullHeight = content[0].getBoundingClientRect();

                            // restore clamp
                            content.css('-webkit-line-clamp', '{{ $lines }}');

                console.log({ clampedHeight, fullHeight });

                if (fullHeight > clampedHeight) {
                    container.append('<a class="read-toggle">{{ __("Read more") }}</a>');
                }
            });

            $(document).on('click', '.read-toggle', function () {
                let container = $(this).closest('.x-product-short-description');
                container.toggleClass('expanded');

                $(this).text(
                    container.hasClass('expanded')
                        ? '{{ __("Show less") }}'
                        : '{{ __("Read more") }}'
                );
            });
            */
        });
    </script>
@endpushonce

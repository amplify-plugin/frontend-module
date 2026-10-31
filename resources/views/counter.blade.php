<div {!! $htmlAttributes !!}>
    @foreach($entries as $item)
        <div class="counter">
            <div class="counter-heading" style="font-size: {{ $counterFontSize }}; color: {{ $fontColor }}">
                    <span class="count-scroller"
                          data-min="0"
                          data-max="{{ $item->number ?? '' }}"
                          data-delay="2"
                          data-increment="{{$item->increment}}"
                          data-format="{{ $item->format }}"
                    >0</span>
                {!! str_contains($item->suffix, '+') ? $item->suffix : "<sub class='counter-suffix'>". e($item->suffix). "</sub>" !!}
            </div>
            <div class="counter-label" style="font-size: {{ $labelFontSize }}; color: {{ $fontColor }}">
                {{ $item->name ?? '' }}
            </div>
        </div>
    @endforeach
</div>
@pushOnce('footer-script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.count-scroller');
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;

                    const element = entry.target;
                    observer.unobserve(element);
                    const min = Number(element.dataset.min ?? 0);
                    const max = Number(element.dataset.max ?? 100);
                    const delay = Number(element.dataset.delay ?? 5);
                    const increment = Number(element.dataset.increment ?? 1);
                    const format = element.dataset.format ?? 'decimal';

                    let current = min;

                    let options = {
                        notation: format,
                        maximumFractionDigits: 2
                    };
                    if (format === 'currency') {
                        options.currency = Amplify.config.currency ?? 'USD';
                    }

                    const formatNumber = (value) => {
                        return new Intl.NumberFormat(`${Amplify.config.language}-US`, options).format(value);
                    };

                    element.textContent = current.toLocaleString();

                    const animate = () => {
                        current = Math.min(current + increment, max);
                        element.textContent = current.toLocaleString();

                        (current < max)
                            ? setTimeout(animate, delay)
                            : element.textContent = formatNumber(max);
                    };

                    animate();
                });
            }, {
                threshold: 0.3
            });
            elements.forEach(element => observer.observe(element));
        });
    </script>
@endpushOnce
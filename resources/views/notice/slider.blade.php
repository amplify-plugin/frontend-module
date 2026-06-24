<div {!! $htmlAttributes !!}>
    <div class="container">
        <div class="row">
            <div class="col-12 notice-box">
                <ul class="notice-list" id="{{ $id }}">
                    @foreach($notices as $notice)
                        <li class="notice-item">
                            <span>{{ $notice->title }}</span>
                            <a href="{{ route('frontend.notices.index', ['id' => $notice->id]) }}" class="notice-link">{{ __('Read More') }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@pushonce('footer-script')
    <script>
        const list = document.getElementById('{{ $id }}');

        setInterval(() => {
            list.style.transition = 'transform 1s ease';
            list.style.transform = `translateY(-40px)`;

            list.addEventListener('transitionend', function handler() {

                    list.appendChild(list.firstElementChild);
                    list.style.transition = 'none';
                    list.style.transform = 'translateY(0)';
                    void list.offsetHeight;
                    list.removeEventListener('transitionend', handler);

                },
                {once: true});
        }, 3000);
    </script>
@endpushonce

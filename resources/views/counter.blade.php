<div {!! $htmlAttributes !!}>
    @foreach($entries as $item)
        @php
            // Calculate the data increment based on the item number
            $dataIncrement = ($item->number < 100 || $item->number < 1000 ) ? 1 : (floor($item->number / 1000));
            // Check if the suffix contains a plus sign
            $suffix = str_contains($item->suffix, '+') ? $item->suffix : "<sub class='counter-suffix'>". e($item->suffix). "</sub>";
        @endphp
        <div class="counter">
            <div class="counter-heading" style="font-size: {{ $counterFontSize }}; color: {{ $fontColor }}">
                    <span class="numscroller" data-min="0"
                          data-max="{{ $item->number ?? '' }}" data-delay="2"
                          data-increment="{{$dataIncrement}}">0</span>
                {!! $suffix !!}
            </div>
            <div class="counter-label" style="font-size: {{ $labelFontSize }}; color: {{ $fontColor }}">
                {{ $item->name ?? '' }}
            </div>
        </div>
    @endforeach
</div>
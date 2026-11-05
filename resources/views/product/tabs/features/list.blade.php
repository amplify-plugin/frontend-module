@php
    $group = json_decode(json_encode($group));
@endphp

<div @class(["col-md-6", "d-none" => empty($group?->group_items) ])>
    @if(!empty( $group->group_name))
        <h4>{{ $group->group_name ?? '' }}</h4>
    @endif
    <ul>
        @foreach ($group?->group_items ?? [] as $item)
            <li>
                @if(!empty($item->name))
                    <b class="mr-2">{!! $item->name ?? null  !!}:</b>
                @endif
                {!! $item->value ?? null !!}
            </li>
        @endforeach
    </ul>
</div>

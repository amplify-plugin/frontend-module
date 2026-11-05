@php
    $group = json_decode(json_encode($group));
@endphp

<div @class(["col-md-6", "d-none" => empty($group->group_items) ])>
    @if(!empty( $group->group_name))
        <h4>{{ $group->group_name ?? '' }}</h4>
    @endif
    <table class="table table-striped">
        @foreach ($group->group_items ?? [] as $item)
            <tr>
                @if(!empty($item->name))
                    <th>{!! $item->name ?? null !!}</th>
                @endif
                <td>{!! $item->value ?? null !!}</td>
            </tr>
        @endforeach
    </table>
</div>

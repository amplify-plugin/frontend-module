@pushonce('plugin-style')
    <link type="stylesheet" href="https://cdn.datatables.net/v/bs4/dt-1.13.1/datatables.min.css"/>

@endpushonce

@pushonce('plugin-script')
    <script src="https://cdn.datatables.net/v/bs4/dt-1.13.1/datatables.min.js"></script>
@endpushonce

<div class="row">
    @if($dataTableOptions['searching'])
        <div class="col-md-4 my-2 mb-md-0">
            <div id="search_filter" class="d-flex justify-content-center justify-content-md-start"></div>
        </div>
    @endif

    @if(isset($rightside))
        <div class="col-md-8 mb-2 mb-md-0 my-2">
            {!! $rightside ?? '' !!}
        </div>
    @endif

    @if(isset($fullsection))
        <div class="col-12">
            {!! $fullsection ?? '' !!}
        </div>
    @endif
</div>

<div class="table-responsive pb-4 pb-md-0">
    {!! $slot !!}
</div>

@if(isset($id))
    @push('footer-script')
        <script>
            $(document).ready(function () {

                const table = $('#{{ $id }}').DataTable(@json($dataTableOptions));

                @if($dataTableOptions['searching'])
                $('#{{ $id }}_filter').appendTo($('#search_filter'));
                @endif
            });
        </script>
    @endpush
@else
    <h3>Table id not passed to component</h3>
@endif

<div {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-body">
            <form id="customer-item-list-search-form" method="get" action="{{ route('frontend.order-lists.index') }}">
                {{-- <input type="hidden" name="filtered_start_date"
                       value="{{ request('filtered_start_date',now(config('app.timezone'))->subDays(29)->format('Y-m-d')) }}"
                       id="filtered_start_date">

                <input type="hidden" name="filtered_end_date"
                       value="{{ request('filtered_end_date', now(config('app.timezone'))->format('Y-m-d')) }}"
                       id="filtered_end_date"> --}}

                <div class="d-flex justify-content-between row">
                    <div class="col-md-6 d-flex mb-md-0 my-2 sp-buttons">
                        <label>
                            <input type="text" name="search" id="search"
                                   class="form-control form-control-sm" placeholder="Search...."
                                   value="{{ request('search') }}">
                        </label>

                        @if (!empty(request()->all()))
                            <label>
                                <a class="border btn btn-sm" href="{{ Request::url() }}">
                                    Reset
                                </a>
                            </label>
                        @endif
                    </div>
                    <div class="col-md-6 mb-2 mb-md-0 my-2">
                        <div class="d-flex justify-content-center justify-content-md-end">
                            @if (customer(true)->can('order-list.list') && !$singleType)
                                <label>
                                    <select name="type" onchange="$('#customer-item-list-search-form').submit();"
                                            class="form-control  form-control-sm">
                                        <option {{ request('type') == '' ? 'selected' : '' }} value="">
                                            All Type
                                        </option>
                                        @foreach($listTypes as $type => $label)
                                            <option value="{{ $type }}" @selected(request('type') == $type)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            {{-- <label class="ml-0 ml-md-4">
                                <div id="filtered_date_range" class="border form-control form-control-sm py-2 d-flex">
                                    <i class="mr-2 pe-7s-date"
                                       style="font-weight: bold; font-size: 1.25rem;"></i><span></span>
                                </div>
                            </label> --}}
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive-md pb-4 pb-md-0">
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-bordered table-striped table-hover my-1">
                                        <thead>
                                        <tr>
                                            <th width="20">#</th>
                                            @if ($columns['list_type'] && !$singleType)
                                                <th>Type</th>
                                            @endif

                                            @if ($columns['name'])
                                                <th class="35%">Name</th>
                                            @endif


                                            @if ($columns['description'])
                                                <th>{{ __('Description') }}</th>
                                            @endif

                                            @if ($columns['product_count'])
                                                <th>{{ __('Items') }}</th>
                                            @endif

                                            <th width="125">Last Changed</th>
                                            @if (customer(true)->canAny([
                                                    'order-list.view',
                                                    'order-list.update',
                                                    'order-list.delete',
                                                ]))
                                                <th>{{ __('Actions') }}</th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse ($orderLists as $key => $orderList)
                                            <tr>
                                                <th scope="row">
                                                    {{ $orderLists->firstItem() + $key }}
                                                </th>

                                                @if ($columns['list_type'] && !$singleType)
                                                    <td width="100">{{ $orderList->list_type_label }}</td>
                                                @endif

                                                @if ($columns['name'])
                                                    <td>{{ $orderList->name }}</td>
                                                @endif

                                                @if ($columns['description'])
                                                    <td>
                                                        <p class="cs-truncate-1">
                                                            {{ $orderList->description }}
                                                        </p>
                                                    </td>
                                                @endif

                                                @if ($columns['product_count'])
                                                    <td> {{ $orderList->orderListItems->count() }}</td>
                                                @endif

                                                <td>{{ carbon_date($orderList->updated_at) }}</td>

                                                @if (customer(true)->canAny([
                                                    'order-list.view',
                                                    'order-list.update',
                                                    'order-list.delete',
                                                ]))
                                                    <td width="125">
                                                        <div class="btn-group m-0">
                                                            <button type="button"
                                                                    class="btn btn-outline-warning mx-0 dropdown-toggle btn-sm"
                                                                    data-toggle="dropdown" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                @if (customer(true)->can('order-list.view'))
                                                                    <a class="dropdown-item"
                                                                       href="{{ route('frontend.order-lists.show', $orderList->id) }}">
                                                                        <i class="icon-eye mr-1"></i> {{ __('Preview') }}
                                                                    </a>
                                                                @endif
                                                                @if (customer(true)->can('order-list.update'))
                                                                    <a class="dropdown-item"
                                                                       href="javascript:void(0)"
                                                                       onclick="Amplify.manageOrderList(this, '{{ $widgetTitle }}', {{ $orderList->id }});"
                                                                       data-action="{{ route('frontend.order-lists.update', $orderList->id) }}">
                                                                        <i class="pe-7s-edit font-weight-bolder mr-1"></i> {{ __('Update') }}
                                                                    </a>
                                                                @endif
                                                                @if (customer(true)->can('order-list.delete'))
                                                                    <a class="dropdown-item"
                                                                       href="javascript:void(0)"
                                                                       data-action="{{ route('frontend.order-lists.destroy', $orderList->id) }}"
                                                                       onclick="Amplify.deleteConfirmation(this, '{{ $widgetTitle }}')">
                                                                        <i class="icon-trash mr-1"></i> {{ __('Delete') }}
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    No data available in table
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-sm-12 col-md-5">
                                    <label
                                            class="d-flex justify-content-center justify-content-md-start align-items-center"
                                            style="font-weight: 200;">
                                        Show
                                        <select name="per_page"
                                                onchange="$('#customer-item-list-search-form').submit();"
                                                class="form-control form-control-sm mx-1"
                                                style="width: 80px; background-position: 85%;">
                                            @foreach (getPaginationLengths() as $length)
                                                <option value="{{ $length }}"
                                                        @if ($length == request('per_page')) selected @endif>
                                                    {{ $length }}
                                                </option>
                                            @endforeach
                                        </select>
                                        entries
                                    </label>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    {!! $orderLists->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('internal-style')
    <style>
        .options > * {
            margin-bottom: 3px !important;
        }
    </style>
@endpush

@push('footer-script')
    <script>
        function debounce(callback, ms) {
            var timer = 0;
            return function () {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    callback.apply(context, args);
                }, ms || 0);
            };
        }

        $(document).on("keyup change", "#search", debounce(function (e) {
            $("#customer-item-list-search-form").submit();
        }, 500));
    </script>
@endpush

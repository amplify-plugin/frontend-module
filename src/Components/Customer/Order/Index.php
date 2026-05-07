<?php

namespace Amplify\Frontend\Components\Customer\Order;

use Amplify\ErpApi\ErpApiService;
use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->can('order.list');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $data = [
            'lookup_type' => ErpApiService::LOOKUP_OPEN_ORDER,
            'start_date' => request()->has('created_start_date') ? request('created_start_date') : now(config('app.timezone'))->subDays(7)->format('Y-m-d'),
            'end_date' => request()->has('created_end_date') ? request('created_end_date') : now(config('app.timezone'))->format('Y-m-d'),
            'contact_id' => request()->has('type') && request('type') == 'all_order' ? null : (customer(true)->contact_code ?: null),
            'transaction_types' => ErpApiService::TRANSACTION_TYPES_ORDER,
        ];

        if (request()->has('created_start_date') && request()->has('created_end_date')) {
            $data['lookup_type'] = ErpApiService::LOOKUP_DATE_RANGE;
        }

        $orders = ErpApi::getOrderList($data);

        return view('widget::customer.order.index', [
            'orders' => $orders,
        ]);
    }

    public function orderStatusOptions(): array
    {
        if (ErpApi::currentErp() == 'facts-erp') {
            return [
                'In Process' => 'In Process',
                'Ordered' => 'Ordered',
                'Picked' => 'Picked',
                'Shipped' => 'Shipped',
                'Invoiced' => 'Invoiced',
                'Paid' => 'Paid',
                'Cancelled' => 'Cancelled',
            ];
        } else {
            return [
                'Ordered' => 'Ordered',
                'Picked' => 'Picked',
                'Shipped' => 'Shipped',
                'Invoiced' => 'Invoiced',
                'Paid' => 'Paid',
                'Cancelled' => 'Cancelled',
            ];
        }
    }
}

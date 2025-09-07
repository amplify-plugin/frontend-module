<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

use Amplify\System\OrderRule\Models\CustomerOrderRuleTrack;

trait OrderWaitingApprovalTrait
{
    public function getOrderWaitingApprovalData(&$data)
    {

        $customer = $data['customer'] = customer(true);
        $data['is_quotation'] = $this->page->page_type === 'quotation';
        $data['perPage'] = request()->has('per_page')
                                    ? request()->per_page
                                    : 10;
        $filter_status = $data['filter_status'] = request()->has('filter_status')
                                    ? request()->filter_status
                                    : '';
        $search = $data['search'] = request()->has('search')
                                    ? request()->search
                                    : '';
        $data['order_type'] = $this->page->page_type === 'quotation'
                                    ? '1'
                                    : '0';

        $to = now()->format('Y-m-d');

        $from = now()->subDays(29)->format('Y-m-d');

        if (request()->has('created_start_date')) {
            $from = request('created_start_date');
        }

        if (request()->has('created_end_date')) {
            $to = request('created_end_date');
        }

        $order_waiting_approval = CustomerOrderRuleTrack::with('orderRule')->where('approver_id', $customer->id)->orderBy('id', 'DESC')->get();
        $data['order_waiting_approval'] = $order_waiting_approval;
    }
}

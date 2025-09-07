<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

trait QuotationTrait
{
    public function getQuotationData(&$data)
    {
        $customer = $data['customer'] = customer(true);
        $data['perPage'] = request()->has('per_page')
                                    ? request()->per_page
                                    : 10;
        $filter_status = $data['filter_status'] = request()->has('filter_status')
                                    ? request()->filter_status
                                    : '';
        $search = $data['search'] = request()->has('search')
                                    ? request()->search
                                    : '';

        $to = now()->format('Y-m-d');

        $from = now()->subDays(29)->format('Y-m-d');

        if (request()->has('created_start_date')) {
            $from = request('created_start_date');
        }

        if (request()->has('created_end_date')) {
            $to = request('created_end_date');
        }

        $quotations = \ErpApi::getQuotationList(['start_date' => $from, 'end_date' => $to]);
        $data['quotations'] = $quotations;
    }
}

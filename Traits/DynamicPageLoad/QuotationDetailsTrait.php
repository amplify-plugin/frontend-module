<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\Product;

trait QuotationDetailsTrait
{
    public function getQuotationDetailsData($param, &$data)
    {

        $data['customer'] = customer(true);
        $data['perPage'] = request()->has('per_page')
            ? request()->per_page
            : 10;
        $search = $data['search'] = request()->has('search')
            ? request()->search
            : '';

        $quotation = \ErpApi::getQuotationDetail(['quote_number' => $param]);

        if ($quotation->QuoteNumber != $param) {
            abort(404, 'Invalid Quotation List Id');
        }

        $quotation['localQuotation'] = optional(CustomerOrder::firstWhere('erp_order_id', $param));

        $products = Product::whereIn('product_code', $quotation->QuoteDetail->map(function ($item) {
            return $item['ItemNumber'];
        }))->get();

        for ($key = 0; $key < count($quotation['QuoteDetail']); $key++) {
            $quotation['QuoteDetail'][$key]['product'] = $products->where('product_code', $quotation['QuoteDetail'][$key]['ItemNumber'])->first();
        }

        $data['quotation'] = $quotation;
        $data['quotation_lines'] = $quotation['QuoteDetail'];

    }
}

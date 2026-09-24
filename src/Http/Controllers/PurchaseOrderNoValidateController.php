<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Traits\HasDynamicPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderNoValidateController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (!config('amplify.frontend.guest_checkout')) {

            $this->middleware('customers');
        }
    }

    /**
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'po_number' => 'required',
        ]);

        try {

            if ($validator->fails()) {
                throw new \ErrorException($validator->errors()->first());
            }

            if (!method_exists(ErpApi::init()->serviceInstance, 'getPODetails')) {
                throw new \ErrorException('Current ERP does not support PO number verification.');
            }

            $erpPODetail = ErpApi::getPODetails($request->all());

            return $this->apiResponse(true, ' Purchase Order Number Verified', 200, ['data' => $erpPODetail]);
        } catch (\Exception $e) {
            return $this->apiResponse(false, $e->getMessage(), 500);
        }

    }
}

<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\UpdateCustomerPartNumberRequest;
use App\Http\Controllers\Controller;
use App\Models\CustomPartNumber;

class CustomerPartNumberController extends Controller
{
    public function __invoke(UpdateCustomerPartNumberRequest $request)
    {
        $inputs = $request->validated();

        if (empty($inputs['customer_id'])) {
            $customer = customer();
            $inputs['customer_id'] = $customer->getKey();
            $inputs['customer_product_uom'] = 'EA';
        }

        $customerPartNumber = CustomPartNumber::where('customer_id', $inputs['customer_id'])
            ->where('product_id', $inputs['product_id'])
            ->first();
        if ($customerPartNumber) {
            $customerPartNumber->update($inputs);

            $customerPartNumber->refresh();
        } else {
            $customerPartNumber = CustomPartNumber::create($inputs);
        }

        return response()->json(['message' => 'Customer Part Number updated successfully.', 'data' => $customerPartNumber]);
    }
}

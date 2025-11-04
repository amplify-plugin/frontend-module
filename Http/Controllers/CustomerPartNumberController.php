<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\CustomerPartNumberRequest;
use Amplify\System\Backend\Models\CustomPartNumber;
use App\Http\Controllers\Controller;

class CustomerPartNumberController extends Controller
{
    public function store(CustomerPartNumberRequest $request)
    {
        try {
            $inputs = $request->validated();

            $customerPartNumber = CustomPartNumber::where('customer_id', $inputs['customer_id'])
                ->where('product_id', $inputs['product_id'])
                ->first();

            if ($customerPartNumber) {
                $customerPartNumber->update($inputs);
                $customerPartNumber->refresh();
            } else {
                $customerPartNumber = CustomPartNumber::create($inputs);
            }

            return response()->json(['message' => $customerPartNumber->wasRecentlyCreated ? 'New customer part number added successfully.' : 'Customer part number updated successfully.']);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function destroy(CustomerPartNumberRequest $request)
    {
        try {

            $inputs = $request->validated();

            $customerPartNumber = CustomPartNumber::where('customer_id', $inputs['customer_id'])
                ->where('product_id', $inputs['product_id'])
                ->first();
            if ($customerPartNumber) {
                $customerPartNumber->delete();
            }
            return response()->json(['message' => 'Customer part number removed successfully.']);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}

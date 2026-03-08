<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\CustomerPartNumberRequest;
use Amplify\System\Backend\Models\CustomPartNumber;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Factories\NotificationFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CustomerPartNumberController extends Controller
{
    public function store(CustomerPartNumberRequest $request)
    {
        $inputs = $request->validated();

        $product = Product::find($inputs['product_id']);

        $payloads = [
            'customer_number' => customer()->erp_id,
            'customer_product_code' => $inputs['customer_product_code'],
            'item_number' => $product->product_code,
            'item_uom' => $inputs['customer_product_uom'],
            'action' => 'change'
        ];

        DB::beginTransaction();
        try {

            CustomPartNumber::create($inputs);

            $response = ErpApi::createUpdateCustomerPartNumber($payloads);

            if ($response['success']) {
                DB::commit();
            } else {
                DB::rollBack();
                throw new \Exception($response['error']);
            }

            return response()->json(['message' => __('New customer part number added successfully.')]);

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
                $data = $customerPartNumber->toArray();


                $response = ErpApi::createUpdateCustomerPartNumber([
                    'customer_number' => customer()->erp_id,
                    'customer_product_code' => $inputs['customer_product_code'],
                    'item_number' => $customerPartNumber->product->product_code,
                    'item_uom' => $customerPartNumber->customer_product_uom,
                    'action' => 'delete'
                ]);

                if ($response['success']) {
                    $customerPartNumber->delete();
                    NotificationFactory::call(Event::CUSTOMER_PART_NUMBER_DELETED, $data);
                } else {
                    throw  new \ErrorException($response['error'] ?? 'Failed to remove the customer part number.');
                }
            }

            return response()->json(['message' => __('Customer part number removed successfully.')]);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}

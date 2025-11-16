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

        $customerPartNumber = CustomPartNumber::where('customer_id', $inputs['customer_id'])
            ->where('product_id', $inputs['product_id'])
            ->first();

        $payloads = [
            'customer_number' => customer()->erp_id,
            'customer_product_code' => $inputs['customer_product_code'],
            'item_number' => $product->product_code,
            'item_uom' => $inputs['customer_product_uom'],
            'action' => 'change'
        ];

        DB::beginTransaction();

        try {

            if ($customerPartNumber) {
                $customerPartNumber->update($inputs);
                $customerPartNumber->refresh();
            } else {
                $customerPartNumber = CustomPartNumber::create($inputs);
            }

            $message = $customerPartNumber->wasRecentlyCreated
                ? __('New customer part number added successfully.')
                : __('Customer part number updated successfully.');

            $response = ErpApi::createUpdateCustomerPartNumber($payloads);

            if ($response['success']) {
                DB::commit();
                if (!$customerPartNumber->wasRecentlyCreated) {
                    NotificationFactory::call(Event::CUSTOMER_PART_NUMBER_DELETED, $customerPartNumber->toArray());
                }
            } else {
                DB::rollBack();
                throw new \Exception($response['error']);
            }

            return response()->json(['message' => $message]);

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
                NotificationFactory::call(Event::CUSTOMER_PART_NUMBER_DELETED, $data);

                $response = ErpApi::createUpdateCustomerPartNumber([
                    'customer_number' => customer()->erp_id,
                    'customer_product_code' => $inputs['customer_product_code'],
                    'item_number' => $customerPartNumber->product->product_code,
                    'item_uom' => $customerPartNumber->customer_product_uom,
                    'action' => 'delete'
                ]);

                $customerPartNumber->delete();
            }

            return response()->json(['message' => __('Customer part number removed successfully.')]);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}

<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\CustomerPartNumberRequest;
use Amplify\System\Backend\Models\CustomPartNumber;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Factories\NotificationFactory;
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

            $message = $customerPartNumber->wasRecentlyCreated
                ? __('New customer part number added successfully.')
                : __('Customer part number updated successfully.');

            if (!$customerPartNumber->wasRecentlyCreated) {
                NotificationFactory::call(Event::CUSTOMER_PART_NUMBER_DELETED, $customerPartNumber->toArray());
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

                $customerPartNumber->delete();

                NotificationFactory::call(Event::CUSTOMER_PART_NUMBER_DELETED, $data);
            }
            return response()->json(['message' => __('Customer part number removed successfully.')]);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}

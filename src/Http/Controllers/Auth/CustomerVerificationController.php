<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Country;
use Amplify\System\Backend\Models\State;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerVerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $inputs = $request->all();

        $validation = ErpApi::contactValidation($inputs);

        if (! empty($validation->CustomerCountry)) {
            if ($country = Country::where('name', $validation->CustomerCountry)
                ->orWhere('iso2', $validation->CustomerCountry)
                ->orWhere('iso3', $validation->CustomerCountry)
                ->first()) {
                $validation->CustomerCountry = $country->name;

                if (! empty($validation->CustomerState)) {
                    if ($state = State::where('country_id', $country->getKey())
                        ->where(function ($query) use ($validation) {
                            return $query->where('name', $validation->CustomerState)
                                ->orWhere('iso2', $validation->CustomerState);
                        })
                        ->first()) {
                        $validation->CustomerState = $state->name;
                    }
                }
            }
        }

        foreach ($validation as $key => $value) {
            $key = Str::snake($key);
            $validation->{$key} = $value;
        }

        $validation->customer_account_number = $validation->CustomerNumber;

        $response['status'] = $validation->ValidCombination == 'Y';
        $response['data'] = $validation->toArray();
        $response['message'] = ($response['status'])
            ? 'Customer profile found with given information.'
            : 'No customer profile found with given information.';

        return response()->json($response);
    }
}

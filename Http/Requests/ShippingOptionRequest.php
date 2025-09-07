<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_address_one' => 'required|string|max:255',
            'customer_address_two' => 'nullable|string|max:255',
            'customer_address_three' => 'nullable|string|max:255',
            'customer_city' => 'required|string|max:255',
            'customer_state' => 'required|string|max:255',
            'customer_zipcode' => 'required',
        ];
    }
}

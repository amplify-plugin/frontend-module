<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipToAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->method() == 'POST' && customer(true)->can('ship-to-addresses.add')) {
            return true;
        }

        if ($this->method() == 'PUT' && customer(true)->can('ship-to-addresses.update')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'customer_id' => 'integer|exists:customers,id',
            'address_name' => 'required|string|max:255',
            //'address_code' => ['required', 'string', 'max:255'],
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'address_3' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'state' => 'nullable|string|size:2',
            'country_code' => 'nullable|string|size:2',
        ];

        // if ($this->method() == 'PUT') {
        //     $rules['address_code'][] = Rule::unique('customer_addresses', 'address_code')->where(function ($query) {
        //         return $query->where('customer_id', customer()->getKey());
        //     })->ignore($this->route('address'));
        // } else {
        //     $rules['address_code'][] = Rule::unique('customer_addresses', 'address_code')->where(function ($query) {
        //         return $query->where('customer_id', customer()->getKey());
        //     });
        // }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['customer_id' => customer()->getKey()]);
    }
}

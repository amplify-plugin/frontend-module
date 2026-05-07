<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\Frontend\Http\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipToAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return customer(true)->canAny('address.create', 'address.update');
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
            'address_name' => 'required|string|max:255|ascii',
            'address_code' => ['nullable', 'string', 'max:255', 'ascii'],
            'address_1' => 'required|string|max:255|ascii',
            'address_2' => 'nullable|string|max:255|ascii',
            'address_3' => 'nullable|string|max:255|ascii',
            'city' => 'required|string|max:255|ascii',
            'zip_code' => 'nullable|string|max:255|ascii',
            'state' => 'nullable|string|size:2|ascii',
            'country_code' => 'nullable|string|size:2|ascii',
            'phone' => ['nullable', new PhoneNumberRule()],
        ];

        if ($this->method() == 'PUT') {
            $rules['address_code'][] = Rule::unique('customer_addresses', 'address_code')->where(function ($query) {
                return $query->where('customer_id', customer()->getKey());
            })->ignore($this->route('address'));
        } else {
            $rules['address_code'][] = Rule::unique('customer_addresses', 'address_code')->where(function ($query) {
                return $query->where('customer_id', customer()->getKey());
            });
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['customer_id' => customer()->getKey()]);
    }
}

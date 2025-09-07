<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPartNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|integer|exists:customers,id',
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'customer_product_code' => ['required', 'string'],
            'customer_product_uom' => ['nullable', 'string'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
        ];
    }
}

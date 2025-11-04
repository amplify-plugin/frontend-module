<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPartNumberRequest extends FormRequest
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
        $rules = [
            'customer_id' => 'nullable|integer|exists:customers,id',
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'customer_product_code' => ['string'],
            'customer_product_uom' => ['nullable', 'string'],
            'customer_address_id' => ['nullable', 'integer'],
        ];

        $rules['customer_product_code'][] = ($this->method() == 'POST')
            ? 'required'
            : 'nullable';

        return $rules;
    }

    protected function prepareForValidation()
    {
        return $this->mergeIfMissing([
            'customer_id' => customer(true)->customer_id,
            'customer_address_id' => customer(true)->customer_address_id,
            'customer_product_uom' => 'EA'
        ]);
    }
}

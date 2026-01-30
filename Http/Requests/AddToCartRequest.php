<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return haveAnyPermissions(['shop.add-to-cart', 'order.add-to-cart']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'products' => 'required|array|min:1',
            'products.*.product_code' => 'required',
            'products.*.product_warehouse_code' => 'nullable',
            'products.*.qty' => 'required|numeric|min:1',
            'products.*.source_type' => 'nullable|string|in:Quote,Promo',
            'products.*.source' => 'required_if:source_type,Quote,Promo',
            'products.*.expiry_date' => 'required_if:source_type,Quote,Promo',
            'products.*.additional_info' => 'required_if:source_type,Quote,Promo|array',
        ];
    }

    public function attributes()
    {
        return [
            'products.*.product_code' => 'product code',
            'products.*.qty' => 'quantity',
            'products.*.source_type' => 'source',
        ];
    }
}

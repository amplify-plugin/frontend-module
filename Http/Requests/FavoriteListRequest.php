<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\Frontend\Http\Rules\FavoriteListRule;
use Amplify\Frontend\Http\Rules\FavoriteListUniqueRule;
use Illuminate\Foundation\Http\FormRequest;

class FavoriteListRequest extends FormRequest
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
            'type' => ['string', 'in:cart,order,product,invoice'],
            'list_id' => ['nullable','integer'],
            'cart_id' => ['integer', 'required_if:type,cart'],
            'product_id' => ['integer', 'required_if:type,product', new FavoriteListRule],
            'product_qty' => ['integer'],
            'list_type' => ['required_if:list_id,null', new FavoriteListUniqueRule],
            'is_shopping_list'=> ['nullable', 'boolean'],
            'list_name' => ['required_if:list_id,null', 'nullable', 'string', 'min:2', 'max:255'],
            'list_desc' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->mergeIfMissing([
            'type' => 'product',
            'is_shopping_list' => false,
            'title' => 'Order List'
        ]);
    }
}

<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuickListRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|min:5|max:255|string',
            'description' => 'nullable|min:3|max:255|string',
            'contact_id' => 'required|min:1|integer',
            'order_list_items' => 'array|nullable',
            'order_list_items.*.product_id' => 'integer|required',
            'order_list_items.*.qty' => 'integer|required|min:1',
        ];
    }

    public function messages()
    {
        return [
            'order_list_items.*.product_id.required' => 'The product field is required.',
            'order_list_items.*.qty.required' => 'The quantity field is required.',
            'order_list_items.*.qty.min' => 'The quantity minimum value is 1.',
        ];
    }
}

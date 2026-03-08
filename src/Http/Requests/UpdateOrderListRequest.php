<?php

namespace Amplify\Frontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderListRequest extends FormRequest
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
            'customer_id' => ['integer', 'nullable'],
            'name' => ['string', 'required', 'min:2'],
            'list_type' => ['string', 'required', 'in:personal,global,quick-list'],
            'description' => ['string', 'nullable'],
        ];
    }
}

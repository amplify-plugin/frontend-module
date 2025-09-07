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
            'list_id' => 'nullable',
            'product_id' => ['required', new FavoriteListRule],
            'list_type' => ['required', new FavoriteListUniqueRule],
        ];
    }
}

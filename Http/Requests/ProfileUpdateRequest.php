<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\System\Helpers\SecurityHelper;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $minPassLen = SecurityHelper::passwordLength();

        return [
            'name' => 'required',
            'phone' => 'nullable',
            'new_password' => "nullable|min:$minPassLen|required_with:confirm_password|same:confirm_password",
            'confirm_password' => 'required_with:new_password',
            'redirect_route' => 'nullable|string',
        ];
    }
}

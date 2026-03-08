<?php

namespace Amplify\Frontend\Http\Requests\Auth;

use Amplify\System\Helpers\SecurityHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        \session()->flash('tab', $this->input('tab'));

        $minPassLen = SecurityHelper::passwordLength();

        $rules = [
            'name' => 'required|string|max:255|clean_string',
            'company_name' => 'required|string|max:255|clean_string',
            'email' => [
                'required',
                'email:dns,rfc',
                'ascii',
                Rule::unique('customers'),
                Rule::unique('contacts'),
            ],
            'phone_number' => 'required|string|max:255|clean_string|phone_number',
            'industry_classification_id' => 'nullable|integer|exists:industry_classifications,id',
            'address_name' => 'nullable|string|max:255|clean_string',
            'address_1' => 'required|string|max:255|clean_string',
            'address_2' => 'nullable|string|max:255|clean_string',
            'address_3' => 'nullable|string|max:255|clean_string',
            'city' => 'required|string|max:255|clean_string',
            'state' => 'required|string|max:255|clean_string',
            'zip_code' => 'required|string|max:20|ascii|postal_code',
            'password' => "required|min:$minPassLen",
            'contact_account_title' => 'integer|nullable',
            'newsletter' => 'string|in:yes,no',
            'accept_term' => 'string|in:yes,no',
        ];

        if (config('amplify.basic.captcha_status')) {
            if ($this->has('captcha')) {
                $rules['captcha'] = 'required|captcha';
            }
        }

        if ($this->has('password_confirmation')) {
            $rules['password_confirmation'] = 'required|same:password';
        }

        foreach ($this->input('required', []) as $field) {
            if (!isset($rules[$field])) {
                $rules[$field] = 'required';
            } else {
                $rules[$field] .= '|required';
            }
        }

        return $rules;
    }

    /**
     * Custom error messages for validation.
     */
    public function messages()
    {
        return [
            'account_number.required' => 'The Account Number is required.',
            'name.required' => 'The name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'The password is required.',
            'password_confirmation.same' => 'The password & retype password does not match.',
            'password.min' => "The password must be at least ". SecurityHelper::passwordLength(). " characters.",
            'industry_classification_id.required' => 'The industry classification is required.',
            'industry_classification_id.exists' => 'The selected industry classification is invalid.',

            // Additional messages for address validation
            'address.*.address_name.required' => 'Address name is required.',
            'address.*.address_1.required' => 'Address line 1 is required.',
            'address.*.city.required' => 'City is required.',
            'address.*.state.required' => 'State is required.',
        ];
    }
}

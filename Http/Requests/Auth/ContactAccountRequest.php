<?php

namespace Amplify\Frontend\Http\Requests\Auth;

use Amplify\System\Helpers\SecurityHelper;
use Illuminate\Foundation\Http\FormRequest;

class ContactAccountRequest extends FormRequest
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
        $minPassLen = SecurityHelper::passwordLength();

        $rules = [
            'search_account_number' => 'required|string|max:255|ascii',
            'customer_account_number' => 'string|max:255|ascii',
            'contact_name' => 'required|string|max:255|ascii',
            'contact_email' => 'required|email:dns,rfc|unique:contacts,email|ascii',
            'contact_phone_number' => 'required|string|max:255|ascii|phone_number',
            'contact_password' => 'required|confirmed|min:' . $minPassLen,
            'contact_newsletter' => 'string|in:yes,no',
            'contact_accept_term' => 'string|in:yes,no',
            'contact_account_title' => 'integer|nullable',
        ];

        if (!config('captcha.disable')) {
            if ($this->has('contact_captcha')) {
                $rules['contact_captcha'] = 'required|captcha';
            }
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

    public function attributes()
    {
        return [
            'contact_account_number' => 'account number',
            'contact_name' => 'name',
            'contact_email' => 'email',
            'contact_password' => 'password',
            'contact_newsletter' => 'newsletter',
            'contact_accept_term' => 'terms and conditions',
            'contact_captcha' => 'captcha',
        ];
    }

    public function messages()
    {
        return [
            'contact_account_number.required' => 'The account number is required.',
            'contact_email.unique' => 'This email address is already registered.',
            'contact_password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}

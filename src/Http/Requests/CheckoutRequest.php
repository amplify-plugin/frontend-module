<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\Frontend\Http\Rules\PhoneNumberRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'amounts' => 'required|array',
            'amounts.subtotal' => 'required|numeric|min:0',
            'amounts.tax' => 'nullable|present|numeric|min:0',
            'amounts.total' => 'nullable|present|numeric|min:0',
            'amounts.shipping' => 'nullable|present|numeric|min:0',
            'amounts.additional' => 'array',
            'amounts.additional.*.field' => 'required|string',
            'amounts.additional.*.label' => 'required|string',
            'amounts.additional.*.value' => 'present|nullable',
            'amounts.additional.*.source' => 'present|nullable',
            'amounts.additional.*.metadata' => 'nullable|array',

            'checkout' => 'required|array',
            'checkout.version' => 'required|integer|min:1',
            'checkout.channel' => 'required|string|in:web,api,admin',
            'checkout.type' => 'required|string|in:order,quotation,draft',

            'contact' => 'required|array',
            'contact.name' => 'required|string',
            'contact.email' => 'required|email:rfs,dns',
            'contact.phone' => ['required','string', 'min:10', new PhoneNumberRule],

            'customer' => 'required|array',
            'customer.name' => 'required|string',
            'customer.number' => config('amplify.frontend.guest_checkout')
                ? customer_check()
                    ? 'required|exists:customers,customer_code'
                    : 'present'
                : 'required|exists:customers,customer_code',
            'customer.address1' => 'required|string',
            'customer.address2' => 'present|nullable',
            'customer.address3' => 'present|nullable',
            'customer.city' => 'required|string',
            'customer.state' => 'required|string|size:2',
            'customer.country' => 'required|string|size:2',
            'customer.zip_code' => 'required|string|min:4',

            'shipping' => 'required|array',
            'shipping.name' => 'required|string',
            'shipping.number' => 'required|string',
            'shipping.address1' => 'required|string',
            'shipping.address2' => 'present|nullable',
            'shipping.address3' => 'present|nullable',
            'shipping.city' => 'required|string',
            'shipping.state' => 'required|string|size:2',
            'shipping.country' => 'required|string|size:2',
            'shipping.zip_code' => 'required|string|min:4',
            'shipping.method' => 'required|array',
            'shipping.method.code' => 'required',
            'shipping.method.label' => 'required|string',
            'shipping.method.amount' => 'required|numeric|min:0',
            'shipping.instructions' => 'present|nullable',
            'shipping.additional' => 'array',
            'shipping.additional.*.field' => 'required|string',
            'shipping.additional.*.label' => 'required|string',
            'shipping.additional.*.value' => 'present|nullable',
            'shipping.additional.*.source' => 'present|nullable',
            'shipping.additional.*.metadata' => 'nullable|array',

            'items' => 'present|array',

            'payment' => 'required|array',
            'payment.gateway' => ['required', 'string', Rule::in(array_keys(config('amplify.payment.labels', [])))],
            'payment.method' => 'present|string',
            'payment.name' => 'required|string',
            'payment.address' => 'present|nullable',
            'payment.city' => 'required|string',
            'payment.state' => 'required|string|size:2',
            'payment.country' => 'required|string|size:2',
            'payment.zip_code' => 'required|string|min:4',
            'payment.phone' => ['present','string', 'min:10', new PhoneNumberRule],
            'payment.metadata' => 'present|array',
            'payment.credentials' => 'required|array',
        ];
    }
}

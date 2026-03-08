<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\System\Cms\Models\Form;
use Amplify\System\Cms\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;

class FormResponseRequest extends FormRequest
{
    private ?Form $form;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $form = $this->route('form_code');

        if ($form) {
            $this->form = Form::with('formFields')->whereCode($form->code)->first();

            return $this->form->enabled;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->form->allow_captcha && config('amplify.basic.captcha_status') && $this->filled('captcha_name')) {
            $rules[$this->input('captcha_name', 'captcha')] = 'required|captcha';
        }

        foreach ($this->form->formFields as $field) {
            $rule = '';
            $rule .= $field->validation;

            if ($field->is_reuired && ! str_contains($field->validation, 'required')) {
                $rule = 'required|';
            }

            if (in_array($field->type, ['rSelect', 'rCheckbox', 'rRadio'])) {
                $rule .= $this->parseOptions($field);
            }

            if (in_array($field->type, ['rRange', 'rSelectRange'])) {
                $rule .= $this->parseRange($field);
            }

            if ($field->type == 'rFile') {
                $rule .= 'file|';
            }

            if ($field->type == 'rImage') {
                $rule .= 'image|';
            }

            $rules[$field->name] = trim(str_replace('||', '|', $rule), '|');
        }

        return $rules;
    }

    private function parseOptions(FormField $field): string
    {
        if (! $field->options) {
            return '';
        }

        $options = json_decode($field->options, true);

        if (! $options) {
            return '';
        }

        $labels = [];

        foreach ($options as $option) {
            $labels[] = $option['option'];
        }

        return 'in:'.implode(',', $labels).'|';
    }

    private function parseRange(FormField $field): string
    {
        $rule = [];

        if ($field->minimum) {
            $rule[] = 'min:'.$field->minimum;
        }

        if ($field->maximum) {
            $rule[] = 'max:'.$field->maximum;
        }

        return implode('|', $rule).'|';
    }
}

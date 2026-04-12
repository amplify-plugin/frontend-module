<?php

namespace Amplify\Frontend\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    public function __construct(private int $minDigits = 10)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! preg_match('/^[0-9\-\(\)\/\s]+$/', $value)) {
            $fail('Phone number contains invalid characters.');
            return;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) < $this->minDigits) {
            $fail("Phone number must contain at least {$this->minDigits} digits.");
        }
    }
}

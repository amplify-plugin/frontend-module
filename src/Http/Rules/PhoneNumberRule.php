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

        // Strip separators the form input allows (spaces, hyphens,
        // parentheses, slashes) before validating the digits.
        $digits = preg_replace('/[\s\-\(\)\/]/', '', $value);

        if ($digits === '' || ! ctype_digit($digits)) {
            $fail('Phone number must contain digits only.');
            return;
        }

        if (strlen($digits) < $this->minDigits) {
            $fail("Phone number must contain at least {$this->minDigits} digits.");
        }
    }
}

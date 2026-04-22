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

        if (! ctype_digit($value)) {
            $fail('Phone number must contain digits only.');
            return;
        }

        if (strlen($value) < $this->minDigits) {
            $fail("Phone number must contain at least {$this->minDigits} digits.");
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class E164Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $value)) {
            $fail('The :attribute must be a valid E.164 phone number (e.g., +14155552671).');
        }
    }
}

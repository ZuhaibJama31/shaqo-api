<?php 
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SomaliPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Regex for Somali prefixes: 61, 62, 63, 64, 65, 66, 68, 69, 71, 77, 90
        // Allows optional +252 or 00252 prefix followed by exactly 9 digits
        $pattern = '/^(\+252|00252)?(61|62|63|64|65|66|68|69|71|77|90)\d{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('The :attribute must be a valid Somali phone number (e.g., 61XXXXXXX).');
        }
    }
}

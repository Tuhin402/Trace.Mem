<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class StrictEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid email address.');
            return;
        }

        // 1. Explicitly reject forbidden characters that could indicate multiple recipients or injection
        $forbiddenChars = [',', ';', "\n", "\t", " ", '"', "'", '<', '>', "\r"];
        foreach ($forbiddenChars as $char) {
            if (str_contains($value, $char)) {
                $fail('The :attribute contains invalid characters.');
                return;
            }
        }

        // 2. Reject multiple @ symbols (strictly one @ allowed)
        if (substr_count($value, '@') !== 1) {
            $fail('The :attribute must contain exactly one @ symbol.');
            return;
        }

        // 3. Fallback to strict PHP filter validation (RFC compliant)
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('The :attribute is not a valid email format.');
        }
    }
}

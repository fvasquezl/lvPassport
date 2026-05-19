<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class Slug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (str_contains($value, '_')) {
            $fail(__(':validation.no_underscores.'));
        }
        if (str_starts_with($value, '-')) {
            $fail(__(':validation.no_starting_dashes.'));
        }
        if (str_ends_with($value, '-')) {
            $fail(__(':validation.no_ending_dashes.'));
        }
    }
}

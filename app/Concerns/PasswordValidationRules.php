<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * Rules are enforced in ALL environments (local, staging, production)
     * to ensure dev/prod parity and catch weak-password issues early.
     *
     * Requirements:
     *   - Minimum 12 characters
     *   - At least one uppercase letter
     *   - At least one lowercase letter
     *   - At least one number
     *   - At least one symbol / special character
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols(),
            'confirmed',
        ];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}

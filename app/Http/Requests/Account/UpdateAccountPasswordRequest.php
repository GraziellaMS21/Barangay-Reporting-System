<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['sometimes', 'nullable', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:10',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = (string) ($this->user()?->email ?? '');
                    if ($email !== '' && strcasecmp((string) $value, $email) === 0) {
                        $fail('The password must be different from your email address.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password you entered is incorrect.',
            'password.min' => 'Your new password must be at least 10 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}

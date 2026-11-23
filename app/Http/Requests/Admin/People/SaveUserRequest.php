<?php

namespace App\Http\Requests\Admin\People;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('users.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $managedUser */
        $managedUser = $this->route('user');
        $isUpdate = $managedUser instanceof User;

        $emailRule = Rule::unique('users', 'email');
        if ($isUpdate) {
            $emailRule->ignore($managedUser->id);
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', $emailRule],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => [
                $isUpdate ? 'nullable' : 'nullable',
                'string',
                'min:10',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = (string) $this->input('email', '');
                    if ($value && $email !== '' && strcasecmp((string) $value, $email) === 0) {
                        $fail('The password must be different from the email address.');
                    }
                },
            ],
            'is_active' => ['sometimes', 'boolean'],
            'must_change_password' => ['sometimes', 'boolean'],
            'preset' => ['nullable', 'string', Rule::in(array_keys(config('permission_presets.presets', [])))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'slug')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the user full name.',
            'email.required' => 'Enter an email address.',
            'email.unique' => 'A user account with this email address already exists.',
            'password.min' => 'The password must be at least 10 characters long.',
        ];
    }
}

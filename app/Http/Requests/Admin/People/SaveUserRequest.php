<?php

namespace App\Http\Requests\Admin\People;

use App\Models\User;
use App\Support\UserRole;
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
            'role' => ['required', Rule::in(UserRole::all())],
            'position_id' => [
                Rule::prohibitedIf(fn () => $this->input('role') === UserRole::AGENCY),
                'nullable',
                Rule::exists('positions', 'id'),
            ],
            'office_id' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::AGENCY),
                Rule::prohibitedIf(fn () => in_array($this->input('role'), [UserRole::ADMIN, UserRole::STAFF], true)),
                'nullable',
                Rule::exists('offices', 'id')->where(fn ($q) => $q->where('is_external', true)->orWhere('type', 'external')),
            ],
            'password' => [
                'nullable',
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
            'preset' => [
                Rule::prohibitedIf(fn () => $this->input('role') === UserRole::AGENCY),
                Rule::requiredIf(fn () => $this->input('role') === UserRole::STAFF && ! $isUpdate),
                'nullable',
                'string',
                Rule::in(array_keys(config('permission_presets.presets', []))),
            ],
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
            'office_id.required' => 'An external partner office is required for agency accounts.',
            'office_id.prohibited' => 'Internal staff and administrator accounts cannot be assigned to an agency office.',
            'office_id.exists' => 'The selected office must be an active external partner office.',
            'preset.required' => 'A permission preset is required for staff accounts.',
            'preset.prohibited' => 'Permission presets are only applicable to staff accounts.',
            'position_id.prohibited' => 'Agency accounts cannot hold a barangay position.',
        ];
    }
}

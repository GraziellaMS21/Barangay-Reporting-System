<?php

namespace App\Http\Requests\Admin\People;

use App\Models\Personnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('personnel.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Personnel|null $personnel */
        $personnel = $this->route('personnel');
        $createLogin = $this->boolean('create_login');

        $emailUniqueRule = Rule::unique('users', 'email');
        if ($personnel?->user_id) {
            $emailUniqueRule->ignore($personnel->user_id);
        }

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'contact_number' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^(\+63|0)[0-9\s\-]{9,13}$/',
            ],
            'email' => [
                $createLogin ? 'required' : 'nullable',
                'email',
                'max:150',
                ...($createLogin ? [$emailUniqueRule] : []),
            ],
            'term_start' => ['required', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            'is_signatory' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'signature' => ['nullable', 'file', 'image', 'mimes:png', 'max:1024'],
            'remove_signature' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'create_login' => ['sometimes', 'boolean'],
            'role' => ['nullable', Rule::in(['admin', 'staff'])],
            'preset' => ['nullable', 'string', Rule::in(array_keys(config('permission_presets.presets', [])))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Enter the official full name.',
            'full_name.max' => 'Full name cannot exceed 120 characters.',
            'position_id.required' => 'Select a barangay position.',
            'contact_number.regex' => 'Enter a valid Philippine phone number (e.g. 0917-123-4567 or +639171234567).',
            'email.required' => 'An email address is required when creating a login account.',
            'email.unique' => 'This email address is already used by another user account.',
            'term_end.after_or_equal' => 'The term end date must be on or after the term start date.',
            'signature.mimes' => 'The signature image must be a PNG file.',
            'signature.max' => 'The signature image cannot exceed 1 MB.',
        ];
    }
}

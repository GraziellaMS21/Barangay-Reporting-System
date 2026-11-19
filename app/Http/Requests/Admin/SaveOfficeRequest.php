<?php

namespace App\Http\Requests\Admin;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_active && $this->user()?->isAdmin());
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && ! $this->has('contact_phone')) {
            $this->merge(['contact_phone' => $this->input('phone')]);
        }

        if ($this->has('email') && ! $this->has('contact_email')) {
            $this->merge(['contact_email' => $this->input('email')]);
        }

        if (! $this->has('type') && $this->has('is_internal')) {
            $this->merge([
                'type' => $this->boolean('is_internal') ? 'internal' : 'external',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Office|null $office */
        $office = $this->route('office');
        $nameRule = Rule::unique('offices', 'name');
        if ($office instanceof Office) {
            $nameRule->ignore($office->id);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_internal' => ['sometimes', 'boolean'],
            'contact' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'default_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['admin', 'staff'])->where('is_active', true)),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An office with this name already exists.',
            'type.required' => 'Choose an office type.',
            'contact_email.email' => 'Enter a valid email address.',
            'default_assignee_id.exists' => 'Choose an active staff member as the default assignee.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedAttributes(?Office $office = null): array
    {
        $validated = $this->validated();
        $type = $validated['type'];

        if ($type === 'internal') {
            $isInternal = true;
            $isExternal = false;
        } elseif ($type === 'external') {
            $isInternal = false;
            $isExternal = true;
        } else {
            $isInternal = $this->has('is_internal')
                ? $this->boolean('is_internal')
                : ($office?->is_internal ?? true);
            $isExternal = ! $isInternal;
        }

        $isActive = $this->has('is_active')
            ? $this->boolean('is_active')
            : ($office?->is_active ?? true);

        $contactSummary = $validated['contact'] ?? null;
        if (($contactSummary === null || $contactSummary === '') && ! empty($validated['contact_email'])) {
            $contactSummary = $validated['contact_email'];
        } elseif (($contactSummary === null || $contactSummary === '') && ! empty($validated['contact_phone'])) {
            $contactSummary = $validated['contact_phone'];
        }

        return [
            'name' => $validated['name'],
            'type' => $type,
            'description' => $validated['description'] ?? null,
            'is_internal' => $isInternal,
            'is_external' => $isExternal,
            'contact' => $contactSummary,
            'contact_person' => $validated['contact_person'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'address' => $validated['address'] ?? null,
            'default_assignee_id' => $isInternal && ! empty($validated['default_assignee_id'])
                ? (int) $validated['default_assignee_id']
                : null,
            'is_active' => $isActive,
        ];
    }
}

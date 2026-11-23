<?php

namespace App\Http\Requests\Admin\People;

use Illuminate\Foundation\Http\FormRequest;

class DeactivatePersonnelRequest extends FormRequest
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
        return [
            'term_end' => ['nullable', 'date'],
            'note' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Provide a reason for deactivating this official.',
            'note.min' => 'The deactivation note must be at least 5 characters.',
        ];
    }
}

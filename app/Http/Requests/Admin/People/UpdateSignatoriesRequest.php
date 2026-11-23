<?php

namespace App\Http\Requests\Admin\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSignatoriesRequest extends FormRequest
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
            'assignments' => ['required', 'array'],
            'assignments.*' => ['array'],
            'assignments.*.*' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'confirm_all' => ['sometimes', 'boolean'],
        ];
    }
}

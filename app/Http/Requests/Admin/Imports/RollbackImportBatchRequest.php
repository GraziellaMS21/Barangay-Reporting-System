<?php

namespace App\Http\Requests\Admin\Imports;

use Illuminate\Foundation\Http\FormRequest;

class RollbackImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('imports.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rollback_note' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}

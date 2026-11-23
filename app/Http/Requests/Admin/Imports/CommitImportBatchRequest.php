<?php

namespace App\Http\Requests\Admin\Imports;

use Illuminate\Foundation\Http\FormRequest;

class CommitImportBatchRequest extends FormRequest
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
            'include_warnings' => ['nullable', 'boolean'],
            'skip_row_ids' => ['nullable', 'array'],
            'skip_row_ids.*' => ['integer'],
            'delete_source_file' => ['nullable', 'boolean'],
        ];
    }
}

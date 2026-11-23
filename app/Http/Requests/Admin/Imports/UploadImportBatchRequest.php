<?php

namespace App\Http\Requests\Admin\Imports;

use Illuminate\Foundation\Http\FormRequest;

class UploadImportBatchRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $ext = strtolower($value->getClientOriginalExtension() ?: '');
                    if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
                        $fail('The uploaded file must be an Excel (.xlsx) or CSV (.csv) spreadsheet.');
                    }
                },
            ],
            'target_fiscal_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'source_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}

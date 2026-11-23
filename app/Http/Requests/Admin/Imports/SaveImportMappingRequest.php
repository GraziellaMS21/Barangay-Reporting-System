<?php

namespace App\Http\Requests\Admin\Imports;

use Illuminate\Foundation\Http\FormRequest;

class SaveImportMappingRequest extends FormRequest
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
            'mapping' => ['required', 'array'],
            'mapping.case_number' => ['required', 'string', 'max:120'],
            'mapping.filed_on' => ['required', 'string', 'max:120'],
            'mapping.complainant_full_name' => ['required', 'string', 'max:120'],
            'mapping.respondent_full_name' => ['required', 'string', 'max:120'],
            'mapping.narrative' => ['required', 'string', 'max:120'],
            'status_mapping' => ['nullable', 'array'],
            'status_mapping.*' => ['nullable', 'string', 'max:50'],
            'save_mapping_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mapping.case_number.required' => 'Please map a source column for Case / Docket Number.',
            'mapping.filed_on.required' => 'Please map a source column for Filed Date.',
            'mapping.complainant_full_name.required' => 'Please map a source column for Complainant Full Name.',
            'mapping.respondent_full_name.required' => 'Please map a source column for Respondent Full Name.',
            'mapping.narrative.required' => 'Please map a source column for Complaint / Blotter Narrative.',
        ];
    }
}

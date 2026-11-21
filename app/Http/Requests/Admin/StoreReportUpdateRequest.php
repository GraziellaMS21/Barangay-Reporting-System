<?php

namespace App\Http\Requests\Admin;

use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === 'staff') {
            /** @var Report|null $report */
            $report = $this->route('report');
            if ($report && isset($user->office_id) && $user->office_id && $report->office_id) {
                return (int) $user->office_id === (int) $report->office_id;
            }

            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $proofConfig = config('reports.proof_photos', [
            'max_files' => 5,
            'max_size_kb' => 5120,
            'mimes' => 'jpeg,png,jpg,webp',
        ]);

        return [
            'update_type' => ['required', 'string', Rule::in(array_keys(Report::UPDATE_TYPES))],
            'message' => [
                Rule::requiredIf(function () {
                    $type = $this->input('update_type');
                    $requires = ['needs_resident_input', 'referred_agency', 'cannot_resolve'];

                    return in_array($type, $requires, true);
                }),
                'nullable',
                'string',
                'max:2000',
            ],
            'expected_completion_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'visible_to_resident' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:'.$proofConfig['max_files']],
            'photos.*' => [
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:'.$proofConfig['max_size_kb'],
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'update_type.required' => 'Please select an update type.',
            'update_type.in' => 'The selected update type is invalid.',
            'message.required' => 'A message to the resident is required for this update type.',
            'expected_completion_date.after_or_equal' => 'The expected completion date must be today or a future date.',
            'photos.max' => 'You may attach at most 5 photos.',
            'photos.*.image' => 'Each attached file must be an image.',
            'photos.*.mimes' => 'Photos must be in JPG, PNG, or WebP format.',
            'photos.*.max' => 'Each photo must not exceed 5 MB.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin\People;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePositionRequest extends FormRequest
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
        /** @var Position|null $position */
        $position = $this->route('position');
        $codeRule = Rule::unique('positions', 'code');
        if ($position instanceof Position) {
            $codeRule->ignore($position->id);
        }

        return [
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/', $codeRule],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_signatory_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_confirmed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a position title.',
            'name.max' => 'Position title cannot exceed 80 characters.',
            'code.required' => 'Enter a position code.',
            'code.regex' => 'Position code must use lowercase letters, digits, and underscores only (starting with a letter).',
            'code.unique' => 'A position with this code already exists.',
        ];
    }
}

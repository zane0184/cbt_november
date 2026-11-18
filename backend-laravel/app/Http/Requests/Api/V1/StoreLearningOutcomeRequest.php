<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('learning_outcome.manage');
    }

    public function rules(): array
    {
        return [
            'curriculum_id' => [
                'required', 'uuid',
                Rule::exists('curricula', 'id')->where('tenant_id', $this->user()->tenant_id)
            ],
            'phase_id' => [
                'required', 'uuid',
                Rule::exists('phases', 'id')->where('tenant_id', $this->user()->tenant_id)
            ],
            'subject_id' => [
                'required', 'uuid',
                Rule::exists('subjects', 'id')->where('tenant_id', $this->user()->tenant_id)
            ],
            'element_id' => [
                'nullable', 'uuid',
                Rule::exists('elements', 'id')->where('tenant_id', $this->user()->tenant_id)
            ],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('learning_outcomes', 'code')->where('tenant_id', $this->user()->tenant_id)
            ],
            // Version fields
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'version' => 'nullable|string|max:10'
        ];
    }
}

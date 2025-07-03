<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by route middleware (auth:sanctum)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'template' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'data' => [
                'sometimes',
                'array',
            ],
            'data.*' => [
                'nullable',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Template name is required for preview.',
            'template.regex' => 'Template name can only contain letters, numbers, underscores, and hyphens.',
            'data.array' => 'Template data must be an object/array.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'template' => 'template name',
            'data' => 'template data',
        ];
    }
}

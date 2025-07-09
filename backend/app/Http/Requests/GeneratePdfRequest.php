<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePdfRequest extends FormRequest
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
                'required',
                'array',
            ],
            'data.*' => [
                'nullable',
            ],
            'options' => [
                'sometimes',
                'array',
            ],
            'options.format' => [
                'sometimes',
                'string',
                Rule::in(['A4', 'Letter', 'Legal', 'A3', 'A5']),
            ],
            'options.orientation' => [
                'sometimes',
                'string',
                Rule::in(['portrait', 'landscape']),
            ],
            'options.margins' => [
                'sometimes',
                'array',
            ],
            'options.margins.top' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'options.margins.right' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'options.margins.bottom' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'options.margins.left' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'filename' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-\.\s]+$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Template name is required.',
            'template.regex' => 'Template name can only contain letters, numbers, underscores, and hyphens.',
            'data.required' => 'Template data is required.',
            'data.array' => 'Template data must be an object/array.',
            'options.format.in' => 'Paper format must be one of: A4, Letter, Legal, A3, A5.',
            'options.orientation.in' => 'Orientation must be either portrait or landscape.',
            'filename.regex' => 'Filename contains invalid characters.',
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
            'options' => 'PDF options',
            'options.format' => 'paper format',
            'options.orientation' => 'page orientation',
            'filename' => 'filename',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation can be added here
            // For example, template-specific data validation

            if (! $validator->errors()->has('template') && ! $validator->errors()->has('data')) {
                $template = $this->input('template');
                $data = $this->input('data', []);

                // You could add template-specific validation here
                // For now, we'll let the service layer handle it
            }
        });
    }
}

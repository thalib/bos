<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateResourceRequest extends FormRequest
{
    protected $modelClass;

    protected $model;

    protected $resource;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You can add authorization logic here if needed
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $this->resolveModel();

        if (! $this->model) {
            return [];
        }

        $fillable = $this->model->getFillable();
        $casts = $this->model->getCasts();
        $rules = [];
        $resourceId = $this->route('id');

        foreach ($fillable as $field) {
            // Handle password field specially
            if ($field === 'password') {
                $rules[$field] = 'sometimes|nullable|string|min:8';

                continue;
            }

            // Handle email fields
            if (Str::contains($field, 'email')) {
                $rules[$field] = 'sometimes|email|unique:'.$this->model->getTable().','.$field.','.$resourceId;

                continue;
            }

            // Get the cast type for this field
            $castType = $casts[$field] ?? null;

            // Set validation rules based on cast type
            if ($castType) {
                switch ($castType) {
                    case 'boolean':
                    case 'bool':
                        $rules[$field] = 'sometimes|nullable|boolean';
                        break;
                    case 'integer':
                    case 'int':
                        $rules[$field] = 'sometimes|nullable|integer';
                        break;
                    case 'decimal':
                    case 'float':
                    case 'double':
                    case 'real':
                        $rules[$field] = 'sometimes|nullable|numeric';
                        break;
                    case 'array':
                    case 'json':
                        $rules[$field] = 'sometimes|nullable|array';
                        break;
                    case 'datetime':
                    case 'timestamp':
                        $rules[$field] = 'sometimes|nullable|date';
                        break;
                    case 'date':
                        $rules[$field] = 'sometimes|nullable|date_format:Y-m-d';
                        break;
                    default:
                        $rules[$field] = 'sometimes|nullable|string';
                        break;
                }
            } else {
                // No cast defined, infer from field name or default to string
                if (Str::contains($field, ['quantity', 'count', 'number', 'threshold']) && ! Str::contains($field, ['_ids'])) {
                    $rules[$field] = 'sometimes|nullable|integer';
                } elseif (Str::endsWith($field, '_id') || Str::contains($field, ['class_id'])) {
                    $rules[$field] = 'sometimes|nullable|integer';
                } elseif (Str::contains($field, ['price', 'cost', 'amount', 'rate', 'weight', 'length', 'width', 'height'])) {
                    $rules[$field] = 'sometimes|nullable|numeric';
                } elseif (Str::contains($field, ['enabled', 'active', 'track', 'required', 'taxable', 'inclusive'])) {
                    $rules[$field] = 'sometimes|nullable|boolean';
                } elseif (Str::endsWith($field, ['_ids', 'categories', 'tags', 'attributes', 'variations', 'images', 'meta_data'])) {
                    $rules[$field] = 'sometimes|nullable|array';
                } else {
                    $rules[$field] = 'sometimes|nullable|string';
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.min' => 'Password must be at least 8 characters long.',
        ];
    }

    /**
     * Get the validated data from the request with special handling.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Handle password field specially for updates
        if (array_key_exists('password', $validated)) {
            if ($validated['password'] === null || $validated['password'] === '' || empty($validated['password'])) {
                // Remove null, empty string, or empty password from update data
                unset($validated['password']);
            } else {
                // Hash the password if it has a value
                $validated['password'] = Hash::make($validated['password']);
            }
        }

        return $validated;
    }

    /**
     * Resolve the model class from route parameters.
     */
    protected function resolveModel(): void
    {
        if ($this->model) {
            return;
        }

        $route = $this->route();
        $modelName = $route->defaults['modelName'] ?? null;

        if (! $modelName) {
            return;
        }

        $className = Str::studly(Str::singular($modelName));
        $this->modelClass = "App\\Models\\{$className}";

        if (class_exists($this->modelClass) && is_subclass_of($this->modelClass, Model::class)) {
            $this->model = new $this->modelClass;
        }
    }

    /**
     * Get the model class being validated.
     */
    public function getModelClass(): ?string
    {
        $this->resolveModel();

        return $this->modelClass;
    }

    /**
     * Get the model instance being validated.
     */
    public function getModel(): ?Model
    {
        $this->resolveModel();

        return $this->model;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'error' => 'Validation failed',
            'message' => 'The given data was invalid',
            'errors' => $validator->errors(),
        ], 422));
    }
}

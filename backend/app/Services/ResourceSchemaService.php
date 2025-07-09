<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Service responsible for generating form schemas and index columns for API resources.
 * Handles field type detection, label generation, placeholder text, and type-specific properties.
 */
class ResourceSchemaService
{
    /**
     * Generate form schema for the given model.
     *
     * @param Model $model
     * @return array
     */
    public function generateAutoSchema(Model $model): array
    {
        $fields = [];
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        $table = $model->getTable();

        foreach ($fillable as $field) {
            $fieldSchema = [
                'type' => $this->detectFieldType($field, $casts),
                'label' => $this->generateLabel($field),
                'placeholder' => $this->generatePlaceholder($field),
                'required' => false // Default to optional
            ];

            // Add type-specific properties
            $fieldSchema = array_merge($fieldSchema, $this->getTypeSpecificProperties($field, $casts, $table));

            $fields[$field] = $fieldSchema;
        }

        return $fields;
    }

    /**
     * Generate minimal default index columns configuration for the given model.
     * This method is only called when the model doesn't have getIndexColumns() 
     * or it returns empty/null.
     *
     * @param Model $model
     * @return array
     */
    public function generateAutoIndexColumns(Model $model): array
    {
        $columns = [];

        // Always include ID if it exists
        $columns['id'] = [
            'label' => 'ID',
            'sortable' => true
        ];

        return $columns;
    }

    /**
     * Detect field type from field name and casts.
     *
     * @param string $field
     * @param array $casts
     * @return string
     */
    protected function detectFieldType(string $field, array $casts): string
    {
        // Check casts first for explicit type definitions
        if (isset($casts[$field])) {
            $cast = $casts[$field];

            return match ($cast) {
                'boolean', 'bool' => 'checkbox',
                'integer', 'int' => 'number',
                'float', 'double', 'real' => 'number',
                'decimal' => 'decimal',
                'datetime', 'timestamp' => 'datetime-local',
                'date' => 'date',
                'time' => 'time',
                'json', 'array', 'object' => 'textarea',
                default => 'text'
            };
        }

        // Field name pattern detection
        $field = strtolower($field);

        if (str_contains($field, 'email')) {
            return 'email';
        }

        if (str_contains($field, 'password')) {
            return 'password';
        }

        if (in_array($field, ['phone', 'mobile', 'whatsapp', 'tel'])) {
            return 'tel';
        }

        if (in_array($field, ['url', 'website', 'link'])) {
            return 'url';
        }

        if (in_array($field, ['color', 'colour'])) {
            return 'color';
        }

        if (str_contains($field, 'price') || str_contains($field, 'cost') || str_contains($field, 'amount')) {
            return 'decimal';
        }

        if (str_contains($field, 'percentage') || str_contains($field, 'percent') || str_contains($field, 'rate')) {
            return 'percentage';
        }

        if (str_contains($field, 'weight') || str_contains($field, 'height') || str_contains($field, 'width') || str_contains($field, 'length')) {
            return 'decimal';
        }

        if (str_contains($field, 'quantity') || str_contains($field, 'count') || str_contains($field, 'number')) {
            return 'number';
        }

        if (str_contains($field, 'description') || str_contains($field, 'content') || str_contains($field, 'notes')) {
            return 'textarea';
        }

        if (str_contains($field, 'image') || str_contains($field, 'photo') || str_contains($field, 'avatar')) {
            return 'file';
        }

        return 'text';
    }

    /**
     * Generate human-readable label from field name.
     *
     * @param string $field
     * @return string
     */
    protected function generateLabel(string $field): string
    {
        return Str::title(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Generate placeholder text for field.
     *
     * @param string $field
     * @return string
     */
    protected function generatePlaceholder(string $field): string
    {
        $label = $this->generateLabel($field);

        return match (true) {
            str_contains(strtolower($field), 'email') => 'Enter your email address',
            str_contains(strtolower($field), 'password') => 'Enter your password',
            str_contains(strtolower($field), 'phone') || str_contains(strtolower($field), 'mobile') => 'Enter your phone number',
            str_contains(strtolower($field), 'name') => "Enter your {$label}",
            str_contains(strtolower($field), 'description') => "Enter {$label}",
            default => "Enter {$label}"
        };
    }

    /**
     * Get type-specific properties for field.
     *
     * @param string $field
     * @param array $casts
     * @param string $table
     * @return array
     */
    protected function getTypeSpecificProperties(string $field, array $casts, string $table): array
    {
        $properties = [];
        $fieldLower = strtolower($field);

        // Common validation rules
        if (str_contains($fieldLower, 'email')) {
            $properties['unique'] = true;
            $properties['maxLength'] = 255;
        }

        if (str_contains($fieldLower, 'password')) {
            $properties['minLength'] = 8;
        }

        if (str_contains($fieldLower, 'phone') || str_contains($fieldLower, 'mobile') || str_contains($fieldLower, 'whatsapp')) {
            $properties['pattern'] = '^[0-9]{10,15}$';
            $properties['unique'] = true;
        }

        if (str_contains($fieldLower, 'username')) {
            $properties['unique'] = true;
            $properties['maxLength'] = 255;
        }

        // Type-specific properties
        if (isset($casts[$field])) {
            $cast = $casts[$field];

            if ($cast === 'decimal') {
                $properties['step'] = '0.01';
                $properties['min'] = '0';
            }

            if (in_array($cast, ['integer', 'int'])) {
                $properties['step'] = '1';
                $properties['min'] = '0';
            }

            if (in_array($cast, ['float', 'double', 'real'])) {
                $properties['step'] = '0.01';
            }
        }

        // Percentage fields
        if (str_contains($fieldLower, 'percentage') || str_contains($fieldLower, 'percent')) {
            $properties['min'] = '0';
            $properties['max'] = '100';
            $properties['step'] = '0.01';
            $properties['suffix'] = '%';
        }

        // Price/cost fields
        if (str_contains($fieldLower, 'price') || str_contains($fieldLower, 'cost') || str_contains($fieldLower, 'amount')) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['prefix'] = '₹';
        }

        // Weight/dimension fields
        if (str_contains($fieldLower, 'weight')) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['suffix'] = 'kg';
        }

        if (in_array($fieldLower, ['height', 'width', 'length'])) {
            $properties['step'] = '0.01';
            $properties['min'] = '0';
            $properties['suffix'] = 'cm';
        }

        return $properties;
    }

    /**
     * Get appropriate formatter for column based on field name and cast type.
     *
     * @param string $field
     * @param array $casts
     * @return string|null
     */
    protected function getColumnFormatter(string $field, array $casts): ?string
    {
        $fieldLower = strtolower($field);

        // Check casts first
        if (isset($casts[$field])) {
            $cast = $casts[$field];
            return match ($cast) {
                'boolean', 'bool' => 'boolean',
                'decimal' => 'currency',
                'datetime', 'timestamp' => 'datetime',
                'date' => 'date',
                default => null
            };
        }

        // Field name patterns
        if (str_contains($fieldLower, 'price') || str_contains($fieldLower, 'cost') || str_contains($fieldLower, 'amount')) {
            return 'currency';
        }

        if (str_contains($fieldLower, 'status')) {
            return 'badge';
        }

        if (str_contains($fieldLower, 'quantity') || str_contains($fieldLower, 'count')) {
            return 'number';
        }

        return null;
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait HandlesDatabaseDefaults
{
    /**
     * Get database column defaults for the model's table.
     */
    public function getDatabaseDefaults(): array
    {
        // For now, prioritize hardcoded defaults since they match the migration exactly
        $hardcodedDefaults = $this->getHardcodedDefaults();

        if (! empty($hardcodedDefaults)) {
            return $hardcodedDefaults;
        }

        // Fallback to database schema detection
        $tableName = $this->getTable();
        $defaults = [];

        try {
            // Get column information from the database
            $columns = Schema::getColumnListing($tableName);

            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($tableName, $column);

                // Get column definition to extract default value
                $columnDef = $this->getColumnDefinition($tableName, $column);

                if ($columnDef && isset($columnDef['default']) && $columnDef['default'] !== null) {
                    $defaults[$column] = $columnDef['default'];
                }
            }

            return $defaults;
        } catch (\Exception $e) {
            // If we can't get defaults from schema, return empty array
            return [];
        }
    }

    /**
     * Get column definition including default value.
     */
    protected function getColumnDefinition(string $table, string $column): ?array
    {
        try {
            // For SQLite (commonly used in development)
            if (config('database.default') === 'sqlite') {
                $query = "PRAGMA table_info({$table})";
                $columns = DB::select($query);
                foreach ($columns as $col) {
                    if ($col->name === $column) {
                        $defaultValue = $col->dflt_value;

                        // Handle SQLite string defaults that are wrapped in quotes
                        if ($defaultValue !== null) {
                            // Remove surrounding quotes if present
                            $defaultValue = trim($defaultValue, "'\"");
                        }

                        return [
                            'name' => $col->name,
                            'type' => $col->type,
                            'nullable' => ! $col->notnull,
                            'default' => $defaultValue,
                        ];
                    }
                }
            }

            // For MySQL
            if (in_array(config('database.default'), ['mysql', 'mariadb'])) {
                $query = "DESCRIBE {$table} {$column}";
                $result = DB::select($query);

                if (! empty($result)) {
                    $col = $result[0];

                    return [
                        'name' => $col->Field,
                        'type' => $col->Type,
                        'nullable' => $col->Null === 'YES',
                        'default' => $col->Default,
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get hardcoded defaults for known fields (fallback method).
     * Override this method in your model to provide specific defaults.
     */
    public function getHardcodedDefaults(): array
    {
        return [];
    }

    /**
     * Apply database defaults to data array for null/empty values.
     */
    public function applyDatabaseDefaults(array $data, bool $isUpdate = false): array
    {
        $defaults = $this->getDatabaseDefaults();
        $processedData = $data;

        foreach ($defaults as $field => $defaultValue) {
            // Skip if field is not fillable
            if (! in_array($field, $this->getFillable())) {
                continue;
            }

            // Check if the field exists in data and is null/empty
            if (array_key_exists($field, $data)) {
                $value = $data[$field];

                // Apply default for null or empty string values
                if ($this->shouldApplyDefault($value, $defaultValue)) {
                    $processedData[$field] = $this->castDefaultValue($defaultValue, $field);
                }
            } elseif (! $isUpdate) {
                // For create operations, add missing fields with defaults
                $processedData[$field] = $this->castDefaultValue($defaultValue, $field);
            }
        }

        return $processedData;
    }

    /**
     * Determine if a default value should be applied.
     *
     * @param  mixed  $value
     * @param  mixed  $defaultValue
     */
    protected function shouldApplyDefault($value, $defaultValue): bool
    {
        // Don't apply defaults if no default is set
        if ($defaultValue === null) {
            return false;
        }

        // Apply default for null values
        if ($value === null) {
            return true;
        }

        // Apply default for empty strings (but not for boolean false or 0)
        if (is_string($value) && trim($value) === '') {
            return true;
        }

        // Apply default for empty arrays
        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Cast default value to appropriate type based on model casts.
     *
     * @param  mixed  $defaultValue
     * @return mixed
     */
    protected function castDefaultValue($defaultValue, string $field)
    {
        $casts = $this->getCasts();

        // If field has a cast defined, apply it
        if (isset($casts[$field])) {
            $castType = $casts[$field];

            return match (true) {
                str_contains($castType, 'decimal') => (float) $defaultValue,
                in_array($castType, ['int', 'integer']) => (int) $defaultValue,
                in_array($castType, ['bool', 'boolean']) => (bool) $defaultValue,
                in_array($castType, ['array', 'json']) => is_string($defaultValue) ? json_decode($defaultValue, true) : $defaultValue,
                default => $defaultValue
            };
        }

        // Auto-detect type based on default value
        if (is_numeric($defaultValue)) {
            return str_contains($defaultValue, '.') ? (float) $defaultValue : (int) $defaultValue;
        }

        if (in_array(strtolower($defaultValue), ['true', 'false', '1', '0'])) {
            return in_array(strtolower($defaultValue), ['true', '1']);
        }

        return $defaultValue;
    }
}

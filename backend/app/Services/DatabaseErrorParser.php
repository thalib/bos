<?php

namespace App\Services;

use Illuminate\Database\QueryException;

/**
 * Service responsible for parsing database errors and providing user-friendly error messages.
 * Converts technical database exceptions into readable error information for API responses.
 */
class DatabaseErrorParser
{
    /**
     * Parse database error to provide more specific error information.
     */
    public static function parse(QueryException $e, array $data = []): array
    {
        $errorMessage = $e->getMessage();
        $errorCode = $e->errorInfo[1] ?? null;

        // Common database error patterns
        if (str_contains($errorMessage, 'foreign key constraint')) {
            return self::parseForeignKeyError($errorMessage);
        }

        if (str_contains($errorMessage, 'NOT NULL constraint')) {
            return self::parseNotNullError($errorMessage);
        }

        if (str_contains($errorMessage, 'UNIQUE constraint')) {
            return self::parseUniqueConstraintError($errorMessage, $data);
        }

        if (str_contains($errorMessage, 'Data too long')) {
            return self::parseDataTooLongError($errorMessage, $data);
        }

        if (str_contains($errorMessage, 'Incorrect') && str_contains($errorMessage, 'value')) {
            return self::parseDataTypeMismatchError($errorMessage, $data);
        }

        if (str_contains($errorMessage, 'Invalid JSON')) {
            return self::parseInvalidJsonError($errorMessage, $data);
        }

        // Generic database error fallback
        return self::parseGenericError($errorMessage, $errorCode);
    }

    /**
     * Parse foreign key constraint errors.
     */
    protected static function parseForeignKeyError(string $errorMessage): array
    {
        // Extract the constraint name if possible
        preg_match('/FOREIGN KEY constraint failed: (.+?)/', $errorMessage, $matches);
        $constraintInfo = $matches[1] ?? 'unknown constraint';

        return [
            'error_type' => 'foreign_key_constraint',
            'message' => 'Foreign key constraint violation',
            'constraint' => $constraintInfo,
            'suggestion' => 'Check that referenced IDs exist in related tables',
        ];
    }

    /**
     * Parse NOT NULL constraint errors.
     */
    protected static function parseNotNullError(string $errorMessage): array
    {
        // Extract field name from NOT NULL constraint error
        preg_match('/NOT NULL constraint failed: (\w+)\.(\w+)/', $errorMessage, $matches);
        $fieldName = $matches[2] ?? 'unknown field';

        return [
            'error_type' => 'required_field_missing',
            'message' => "Required field '{$fieldName}' cannot be null",
            'field' => $fieldName,
            'suggestion' => "Provide a value for the '{$fieldName}' field",
        ];
    }

    /**
     * Parse UNIQUE constraint errors.
     */
    protected static function parseUniqueConstraintError(string $errorMessage, array $data): array
    {
        // Extract field name from UNIQUE constraint error
        preg_match('/UNIQUE constraint failed: (\w+)\.(\w+)/', $errorMessage, $matches);
        $fieldName = $matches[2] ?? 'unknown field';
        $providedValue = $data[$fieldName] ?? 'unknown value';

        return [
            'error_type' => 'duplicate_value',
            'message' => "Duplicate value for field '{$fieldName}'",
            'field' => $fieldName,
            'provided_value' => $providedValue,
            'suggestion' => "The value '{$providedValue}' already exists. Use a unique value for '{$fieldName}'",
        ];
    }

    /**
     * Parse data too long errors.
     */
    protected static function parseDataTooLongError(string $errorMessage, array $data): array
    {
        // Extract field information from data too long error
        preg_match('/Data too long for column \'(\w+)\'/', $errorMessage, $matches);
        $fieldName = $matches[1] ?? 'unknown field';
        $providedValue = $data[$fieldName] ?? null;

        return [
            'error_type' => 'data_too_long',
            'message' => "Data too long for field '{$fieldName}'",
            'field' => $fieldName,
            'provided_value' => $providedValue,
            'provided_length' => is_string($providedValue) ? strlen($providedValue) : null,
            'suggestion' => "Reduce the length of data for field '{$fieldName}'",
        ];
    }

    /**
     * Parse data type mismatch errors.
     */
    protected static function parseDataTypeMismatchError(string $errorMessage, array $data): array
    {
        // Data type mismatch errors
        preg_match('/Incorrect (\w+) value: \'([^\']*)\' for column \'(\w+)\'/', $errorMessage, $matches);
        $dataType = $matches[1] ?? 'unknown type';
        $providedValue = $matches[2] ?? ($data[$matches[3]] ?? 'unknown value');
        $fieldName = $matches[3] ?? 'unknown field';

        return [
            'error_type' => 'data_type_mismatch',
            'message' => "Invalid {$dataType} value for field '{$fieldName}'",
            'field' => $fieldName,
            'expected_type' => $dataType,
            'provided_value' => $providedValue,
            'suggestion' => "Provide a valid {$dataType} value for field '{$fieldName}'",
        ];
    }

    /**
     * Parse invalid JSON errors.
     */
    protected static function parseInvalidJsonError(string $errorMessage, array $data): array
    {
        // Find which field has invalid JSON
        foreach ($data as $field => $value) {
            if (is_string($value) && ! json_decode($value) && json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error_type' => 'invalid_json',
                    'message' => "Invalid JSON format for field '{$field}'",
                    'field' => $field,
                    'provided_value' => $value,
                    'json_error' => json_last_error_msg(),
                    'suggestion' => "Provide valid JSON format for field '{$field}'",
                ];
            }
        }

        // Generic JSON error if we can't find the specific field
        return [
            'error_type' => 'invalid_json',
            'message' => 'Invalid JSON format detected',
            'suggestion' => 'Check JSON fields for proper formatting',
        ];
    }

    /**
     * Parse generic database errors.
     *
     * @param  mixed  $errorCode
     */
    protected static function parseGenericError(string $errorMessage, $errorCode): array
    {
        return [
            'error_type' => 'database_error',
            'message' => 'Database operation failed',
            'raw_error' => $errorMessage,
            'error_code' => $errorCode,
            'suggestion' => 'Check the data format and constraints',
        ];
    }

    /**
     * Get a user-friendly error message for common database constraint types.
     *
     * @param  mixed  $value
     */
    public static function getConstraintMessage(string $constraintType, string $fieldName, $value = null): string
    {
        return match ($constraintType) {
            'unique' => "The {$fieldName} '{$value}' is already taken. Please choose a different value.",
            'foreign_key' => "The selected {$fieldName} is invalid or does not exist.",
            'not_null' => "The {$fieldName} field is required and cannot be empty.",
            'data_type' => "The {$fieldName} field contains an invalid data type.",
            'max_length' => "The {$fieldName} field is too long. Please use fewer characters.",
            default => "There was an error with the {$fieldName} field."
        };
    }

    /**
     * Check if an exception is a database constraint violation.
     */
    public static function isConstraintViolation(\Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'constraint') ||
               str_contains($message, 'UNIQUE') ||
               str_contains($message, 'NOT NULL') ||
               str_contains($message, 'FOREIGN KEY');
    }

    /**
     * Extract field name from database error message.
     */
    public static function extractFieldName(string $errorMessage): ?string
    {
        // Try various patterns to extract field name
        $patterns = [
            '/column \'(\w+)\'/',                           // MySQL column errors
            '/field \'(\w+)\'/',                            // General field errors
            '/constraint failed: \w+\.(\w+)/',             // SQLite constraint errors
            '/duplicate entry .+ for key \'(\w+)\'/',      // MySQL duplicate key
            '/NOT NULL constraint failed: \w+\.(\w+)/',    // SQLite NOT NULL
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $errorMessage, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

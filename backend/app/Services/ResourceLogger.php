<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service responsible for logging resource-related operations
 */
class ResourceLogger
{
    /**
     * Log when default values are applied to resource data during create/update operations
     *
     * @param array $originalData The original data provided by the user
     * @param array $processedData The data after processing (including applied defaults)
     * @param string $operation The operation type ('create' or 'update')
     * @param string $modelName The name of the model being operated on
     * @return void
     */
    public static function logDefaultsApplication(array $originalData, array $processedData, string $operation, string $modelName): void
    {
        $appliedDefaults = [];

        foreach ($processedData as $field => $value) {
            if (!array_key_exists($field, $originalData) || $originalData[$field] !== $value) {
                // Check if this was a null/empty value that got a default applied
                $originalValue = $originalData[$field] ?? 'NOT_PROVIDED';
                if ($originalValue === null || $originalValue === '' || $originalValue === 'NOT_PROVIDED') {
                    $appliedDefaults[$field] = [
                        'original' => $originalValue,
                        'applied_default' => $value
                    ];
                }
            }
        }

        if (!empty($appliedDefaults)) {
            Log::info("Database defaults applied during {$operation} operation for {$modelName}", [
                'applied_defaults' => $appliedDefaults,
                'original_data' => $originalData,
                'processed_data' => $processedData
            ]);
        }
    }

    /**
     * Log resource operation errors
     *
     * @param string $operation The operation that failed
     * @param string $modelName The model name
     * @param \Throwable $exception The exception that occurred
     * @param array $context Additional context data
     * @return void
     */
    public static function logResourceError(string $operation, string $modelName, \Throwable $exception, array $context = []): void
    {
        Log::error("Resource operation failed: {$operation} on {$modelName}", [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context
        ]);
    }

    /**
     * Log successful resource operations
     *
     * @param string $operation The operation that succeeded
     * @param string $modelName The model name
     * @param mixed $resourceId The ID of the resource (if applicable)
     * @param array $context Additional context data
     * @return void
     */
    public static function logResourceSuccess(string $operation, string $modelName, $resourceId = null, array $context = []): void
    {
        $message = "Resource operation completed: {$operation} on {$modelName}";
        if ($resourceId) {
            $message .= " (ID: {$resourceId})";
        }

        Log::info($message, $context);
    }

    /**
     * Log resource access events (useful for auditing)
     *
     * @param string $operation The operation being performed
     * @param string $modelName The model name
     * @param mixed $userId The user performing the operation
     * @param array $context Additional context data
     * @return void
     */
    public static function logResourceAccess(string $operation, string $modelName, $userId = null, array $context = []): void
    {
        Log::info("Resource accessed: {$operation} on {$modelName}", [
            'user_id' => $userId,
            'timestamp' => now(),
            'context' => $context
        ]);
    }
}

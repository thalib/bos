<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for building response metadata for API resources.
 * Handles filters, search, sorting, schema, and columns metadata.
 */
class ResourceMetadataService
{
    /**
     * Compatibility method for tests expecting getFormSchema.
     * Calls getSchemaData for backward compatibility.
     *
     * @param Model $model
     * @return array|null
     */
    public function getFormSchema(Model $model): ?array
    {
        return $this->getSchemaData($model);
    }
    protected ResourceSchemaService $schemaService;
    protected ResourceFilterService $filterService;

    public function __construct(ResourceSchemaService $schemaService, ResourceFilterService $filterService)
    {
        $this->schemaService = $schemaService;
        $this->filterService = $filterService;
    }

    /**
     * Build comprehensive response metadata for API resources.
     *
     * @param Request $request
     * @param mixed $query
     * @param array $appliedFilters
     * @return array
     */
    public function buildResponseMetadata(Request $request, $query, array $appliedFilters = []): array
    {
        $metadata = [];

        // Get model instance for filter and search information
        $model = new ($query->getModel()::class);
        
        // Build filters object using the filter service
        $metadata['filters'] = $this->filterService->buildFilterMetadata($request, $model, $appliedFilters);

        // Add search as string value or null (DESIGN.md format)
        $metadata['search'] = $this->filterService->getSearchMetadata($request);

        // Add sorting metadata - always present at root level
        $metadata['sort'] = $this->buildSortMetadata($request);

        // Add schema data (same as schema endpoint)
        $schemaData = $this->getSchemaData($model);
        if ($schemaData !== null) {
            $metadata['schema'] = $schemaData;
        }

        // Add columns data (same as columns endpoint)
        $columnsData = $this->getColumnsData($model);
        if ($columnsData !== null) {
            $metadata['columns'] = $columnsData;
        }

        return $metadata;
    }

    /**
     * Build sorting metadata from request.
     *
     * @param Request $request
     * @return array|null
     */
    protected function buildSortMetadata(Request $request): ?array
    {
        if (!$request->has('sort')) {
            return null;
        }

        $sortFields = explode(',', $request->input('sort'));
        $directions = explode(',', $request->input('sort_dir', 'asc'));

        $sortArray = [];
        foreach ($sortFields as $index => $sortField) {
            $sortField = trim($sortField);
            $direction = isset($directions[$index]) ? trim($directions[$index]) : 'asc';
            $sortArray[] = [
                'field' => $sortField,
                'direction' => $direction
            ];
        }

        return $sortArray;
    }

    /**
     * Get schema data for a model.
     *
     * @param Model $model
     * @return array|null
     */
    public function getSchemaData(Model $model): ?array
    {
        try {
            // Check if model has custom schema method
            if (method_exists($model, 'getApiSchema')) {
                return $model->getApiSchema();
            }

            // Fallback: Auto-generate base schema from model introspection
            $autoSchema = $this->schemaService->generateAutoSchema($model);
            
            return [
                [
                    'group' => 'General Information',
                    'fields' => $autoSchema
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error generating schema data for model", [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get columns data for a model.
     *
     * @param Model $model
     * @return array|null
     */
    public function getColumnsData(Model $model): ?array
    {
        try {
            // Check if model has custom index columns method
            $customColumns = [];
            if (method_exists($model, 'getIndexColumns')) {
                $customColumns = $model->getIndexColumns();
            }

            // If no custom columns defined, auto-generate from model
            if (empty($customColumns)) {
                $customColumns = $this->schemaService->generateAutoIndexColumns($model);
            }

            return $customColumns;
        } catch (\Exception $e) {
            Log::error("Error generating columns data for model", [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

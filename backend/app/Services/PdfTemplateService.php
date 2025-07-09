<?php

namespace App\Services;

use App\Exceptions\PdfGenerationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PdfTemplateService
{
    protected array $templateRegistry = [];

    /**
     * Register a template with metadata
     *
     * @param  string  $name  Template name
     * @param  string  $viewPath  View path (e.g., 'pdf.invoice')
     * @param  array  $requiredFields  Required data fields
     */
    public function registerTemplate(string $name, string $viewPath, array $requiredFields = []): void
    {
        $this->templateRegistry[$name] = [
            'name' => $name,
            'view_path' => $viewPath,
            'required_fields' => $requiredFields,
            'registered_at' => now(),
            'metadata' => $this->extractTemplateMetadata($viewPath),
        ];

        Log::info("Template registered: {$name}", [
            'view_path' => $viewPath,
            'required_fields' => $requiredFields,
        ]);
    }

    /**
     * Get template configuration
     *
     * @param  string  $templateName  Template name
     * @return array Template configuration
     */
    public function getTemplateConfig(string $templateName): array
    {
        // Check registry first
        if (isset($this->templateRegistry[$templateName])) {
            return $this->templateRegistry[$templateName];
        }

        // Try to auto-discover template
        $viewPath = "pdf.{$templateName}";
        if (View::exists($viewPath)) {
            $config = [
                'name' => $templateName,
                'view_path' => $viewPath,
                'required_fields' => [],
                'auto_discovered' => true,
                'metadata' => $this->extractTemplateMetadata($viewPath),
            ];

            // Cache the auto-discovered template
            $this->templateRegistry[$templateName] = $config;

            return $config;
        }

        return [
            'name' => $templateName,
            'view_path' => $viewPath,
            'required_fields' => [],
            'exists' => false,
            'metadata' => [],
        ];
    }

    /**
     * Validate template data against requirements
     *
     * @param  string  $templateName  Template name
     * @param  array  $data  Data to validate
     * @return array Validation result
     *
     * @throws PdfGenerationException
     */
    public function validateTemplateData(string $templateName, array $data): array
    {
        $config = $this->getTemplateConfig($templateName);
        $requiredFields = $config['required_fields'] ?? [];
        $errors = [];
        $warnings = [];

        // Check required fields
        foreach ($requiredFields as $field) {
            if (! $this->hasNestedField($data, $field)) {
                $errors[] = "Required field missing: {$field}";
            }
        }

        // Template-specific validation
        $specificErrors = $this->validateTemplateSpecificData($templateName, $data);
        $errors = array_merge($errors, $specificErrors);

        $result = [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'template' => $templateName,
            'validated_at' => now()->toISOString(),
        ];

        if (! empty($errors)) {
            Log::warning("Template data validation failed for {$templateName}", $result);
        }

        return $result;
    }

    /**
     * Generate HTML preview of template
     *
     * @param  string  $templateName  Template name
     * @param  array  $sampleData  Sample data for preview
     * @return string HTML content
     *
     * @throws PdfGenerationException
     */
    public function getTemplatePreview(string $templateName, array $sampleData): string
    {
        $config = $this->getTemplateConfig($templateName);
        $viewPath = $config['view_path'];

        if (! View::exists($viewPath)) {
            throw new PdfGenerationException(
                "Template view not found: {$viewPath}",
                404,
                null,
                $templateName
            );
        }

        try {
            // Add preview flag to distinguish from PDF generation
            $sampleData['__preview_mode'] = true;

            return View::make($viewPath, $sampleData)->render();
        } catch (\Exception $e) {
            throw new PdfGenerationException(
                "Failed to generate template preview: {$e->getMessage()}",
                500,
                $e,
                $templateName
            );
        }
    }

    /**
     * Extract metadata from template file
     *
     * @param  string  $viewPath  View path
     * @return array Template metadata
     */
    protected function extractTemplateMetadata(string $viewPath): array
    {
        $metadata = [
            'title' => null,
            'description' => null,
            'author' => null,
            'version' => null,
            'tags' => [],
            'paper_size' => 'a4',
            'orientation' => 'portrait',
        ];

        try {
            // Convert view path to file path
            $filePath = $this->viewPathToFilePath($viewPath);

            if (File::exists($filePath)) {
                $content = File::get($filePath);

                // Extract metadata from comments
                if (preg_match('/{{--\s*@template\s+(.*?)\s*--}}/s', $content, $matches)) {
                    $metadataBlock = $matches[1];

                    // Parse metadata lines
                    $lines = explode("\n", $metadataBlock);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (preg_match('/^(\w+):\s*(.+)$/', $line, $lineMatches)) {
                            $key = $lineMatches[1];
                            $value = $lineMatches[2];

                            if ($key === 'tags') {
                                $metadata[$key] = array_map('trim', explode(',', $value));
                            } else {
                                $metadata[$key] = $value;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract template metadata', [
                'view_path' => $viewPath,
                'error' => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Convert view path to file path
     *
     * @param  string  $viewPath  Dot-notation view path
     * @return string File path
     */
    protected function viewPathToFilePath(string $viewPath): string
    {
        $path = str_replace('.', '/', $viewPath);

        return resource_path("views/{$path}.blade.php");
    }

    /**
     * Check if nested field exists in data
     *
     * @param  array  $data  Data array
     * @param  string  $field  Field path (e.g., 'company.name', 'items.0.price')
     */
    protected function hasNestedField(array $data, string $field): bool
    {
        $keys = explode('.', $field);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Template-specific validation rules
     *
     * @param  string  $templateName  Template name
     * @param  array  $data  Data to validate
     * @return array Validation errors
     */
    protected function validateTemplateSpecificData(string $templateName, array $data): array
    {
        $errors = [];

        switch ($templateName) {
            case 'invoice':
                // Invoice-specific validation
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $index => $item) {
                        if (! isset($item['description']) || empty($item['description'])) {
                            $errors[] = "Item {$index}: description is required";
                        }
                        if (! isset($item['quantity']) || ! is_numeric($item['quantity'])) {
                            $errors[] = "Item {$index}: valid quantity is required";
                        }
                        if (! isset($item['unit_price']) || ! is_numeric($item['unit_price'])) {
                            $errors[] = "Item {$index}: valid unit price is required";
                        }
                    }
                }

                if (isset($data['totals'])) {
                    if (! isset($data['totals']['total']) || ! is_numeric($data['totals']['total'])) {
                        $errors[] = 'Total amount is required and must be numeric';
                    }
                }
                break;

            case 'report':
                // Report-specific validation
                if (! isset($data['title']) || empty($data['title'])) {
                    $errors[] = 'Report title is required';
                }
                break;

            case 'receipt':
                // Receipt-specific validation
                if (! isset($data['amount']) || ! is_numeric($data['amount'])) {
                    $errors[] = 'Receipt amount is required and must be numeric';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get all registered templates
     */
    public function getAllTemplates(): array
    {
        return $this->templateRegistry;
    }

    /**
     * Clear template registry (useful for testing)
     */
    public function clearRegistry(): void
    {
        $this->templateRegistry = [];
    }

    /**
     * Check if template exists
     *
     * @param  string  $templateName  Template name
     */
    public function templateExists(string $templateName): bool
    {
        // Check registry first
        if (isset($this->templateRegistry[$templateName])) {
            return true;
        }

        // Check if view exists
        $viewPath = "pdf.{$templateName}";

        return View::exists($viewPath);
    }

    /**
     * Get template metadata
     *
     * @param  string  $templateName  Template name
     * @return array Template metadata
     *
     * @throws PdfGenerationException
     */
    public function getTemplateMetadata(string $templateName): array
    {
        if (! $this->templateExists($templateName)) {
            throw new PdfGenerationException(
                "Template '{$templateName}' not found",
                404,
                null,
                $templateName
            );
        }

        // Return from registry if available
        if (isset($this->templateRegistry[$templateName])) {
            return $this->templateRegistry[$templateName]['metadata'] ?? [];
        }

        // Extract metadata from view file
        $viewPath = "pdf.{$templateName}";

        return $this->extractTemplateMetadata($viewPath);
    }

    /**
     * Get all available templates
     *
     * @return array Available templates with metadata
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        // Add registered templates
        foreach ($this->templateRegistry as $name => $config) {
            $templates[$name] = $config['metadata'] ?? [];
        }

        // Auto-discover templates from views/pdf directory
        $pdfViewsPath = resource_path('views/pdf');
        if (File::exists($pdfViewsPath)) {
            $files = File::files($pdfViewsPath);
            foreach ($files as $file) {
                $name = $file->getFilenameWithoutExtension();
                if (! isset($templates[$name])) {
                    $viewPath = "pdf.{$name}";
                    $templates[$name] = $this->extractTemplateMetadata($viewPath);
                }
            }
        }

        return $templates;
    }
}

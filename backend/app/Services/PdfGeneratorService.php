<?php

namespace App\Services;

use App\Exceptions\PdfGenerationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PdfGeneratorService
{
    protected PdfTemplateService $templateService;

    public function __construct(PdfTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Generate PDF from template and data
     *
     * @param string $templateName Template name (e.g., 'invoice', 'report')
     * @param array $data Data to pass to the template
     * @param array $options PDF generation options
     * @return string PDF binary content
     * @throws PdfGenerationException
     */
    public function generatePdf(string $templateName, array $data, array $options = []): string
    {
        try {
            Log::info('Starting PDF generation', [
                'template' => $templateName,
                'data_keys' => array_keys($data),
                'options' => $options
            ]);

            // Validate template exists
            if (!$this->validateTemplate($templateName)) {
                throw new PdfGenerationException(
                    "Template '{$templateName}' not found",
                    404,
                    null,
                    $templateName,
                    ['available_templates' => $this->getAvailableTemplates()]
                );
            }

            // Sanitize and validate data
            $sanitizedData = $this->sanitizeData($data);
            
            // Validate template data
            $this->templateService->validateTemplateData($templateName, $sanitizedData);

            // Get template view path
            $viewPath = "pdf.{$templateName}";

            // Check if view exists
            if (!View::exists($viewPath)) {
                throw new PdfGenerationException(
                    "Template view '{$viewPath}' not found",
                    404,
                    null,
                    $templateName
                );
            }

            // Configure PDF options
            $pdfOptions = $this->configurePdfOptions($options);

            // Generate PDF
            $pdf = Pdf::loadView($viewPath, $sanitizedData);
            
            // Apply options
            if (isset($pdfOptions['paper'])) {
                $pdf->setPaper($pdfOptions['paper'], $pdfOptions['orientation'] ?? 'portrait');
            }

            if (isset($pdfOptions['options'])) {
                $pdf->setOptions($pdfOptions['options']);
            }

            $pdfContent = $pdf->output();

            Log::info('PDF generation completed successfully', [
                'template' => $templateName,
                'size' => strlen($pdfContent)
            ]);

            return $pdfContent;

        } catch (PdfGenerationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PdfGenerationException(
                "PDF generation failed: {$e->getMessage()}",
                500,
                $e,
                $templateName,
                ['original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Generate PDF (alias for generatePdf)
     *
     * @param string $templateName Template name
     * @param array $data Data to pass to the template
     * @param array $options PDF generation options
     * @return string PDF binary content
     * @throws PdfGenerationException
     */
    public function generate(string $templateName, array $data, array $options = []): string
    {
        return $this->generatePdf($templateName, $data, $options);
    }

    /**
     * Generate PDF and save to storage
     *
     * @param string $templateName Template name
     * @param array $data Template data
     * @param string $filename Filename without extension
     * @param string|null $path Storage path (null for default)
     * @return string Full storage path
     * @throws PdfGenerationException
     */
    public function generateAndSave(string $templateName, array $data, string $filename, ?string $path = null): string
    {
        $pdfContent = $this->generatePdf($templateName, $data);
        
        // Ensure filename has .pdf extension
        if (!Str::endsWith($filename, '.pdf')) {
            $filename .= '.pdf';
        }

        // Default path is 'pdfs' directory
        $storagePath = $path ? "{$path}/{$filename}" : "pdfs/{$filename}";

        // Save to storage
        Storage::put($storagePath, $pdfContent);

        Log::info('PDF saved to storage', [
            'template' => $templateName,
            'path' => $storagePath,
            'size' => strlen($pdfContent)
        ]);

        return $storagePath;
    }

    /**
     * Generate PDF and return download response
     *
     * @param string $templateName Template name
     * @param array $data Template data
     * @param string $filename Download filename
     * @return Response
     * @throws PdfGenerationException
     */
    public function generateAndDownload(string $templateName, array $data, string $filename): Response
    {
        $pdfContent = $this->generatePdf($templateName, $data);

        // Ensure filename has .pdf extension
        if (!Str::endsWith($filename, '.pdf')) {
            $filename .= '.pdf';
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Generate HTML preview of template
     *
     * @param string $templateName Template name
     * @param array $data Data to pass to the template
     * @param array $options Preview options
     * @return string HTML content
     * @throws PdfGenerationException
     */
    public function generatePreview(string $templateName, array $data, array $options = []): string
    {
        return $this->templateService->getTemplatePreview($templateName, $data);
    }

    /**
     * Save PDF content to file
     *
     * @param string $pdfContent PDF binary content
     * @param string $filePath File path to save to
     * @return bool Success status
     */
    public function savePdf(string $pdfContent, string $filePath): bool
    {
        try {
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            return file_put_contents($filePath, $pdfContent) !== false;
        } catch (\Exception $e) {
            Log::error('Failed to save PDF', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate if template exists
     *
     * @param string $templateName Template name
     * @return bool
     */
    public function validateTemplate(string $templateName): bool
    {
        $viewPath = "pdf.{$templateName}";
        return View::exists($viewPath);
    }

    /**
     * Get list of available templates
     *
     * @return array
     */
    public function getAvailableTemplates(): array
    {
        $templatesPath = resource_path('views/pdf');
        
        if (!File::isDirectory($templatesPath)) {
            return [];
        }

        $templates = [];
        $files = File::files($templatesPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $templateName = $file->getFilenameWithoutExtension();
                
                // Skip layouts directory
                if ($templateName === 'layouts') {
                    continue;
                }

                $templates[] = [
                    'name' => $templateName,
                    'path' => "pdf.{$templateName}",
                    'file' => $file->getPathname(),
                    'config' => $this->templateService->getTemplateConfig($templateName)
                ];
            }
        }

        // Also check subdirectories
        $directories = File::directories($templatesPath);
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            
            // Skip layouts directory
            if ($dirName === 'layouts') {
                continue;
            }

            $subFiles = File::files($directory);
            foreach ($subFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $templateName = "{$dirName}.{$file->getFilenameWithoutExtension()}";
                    $templates[] = [
                        'name' => $templateName,
                        'path' => "pdf.{$templateName}",
                        'file' => $file->getPathname(),
                        'config' => $this->templateService->getTemplateConfig($templateName)
                    ];
                }
            }
        }

        return $templates;
    }

    /**
     * Sanitize input data
     *
     * @param array $data Raw input data
     * @return array Sanitized data
     */
    public function sanitizeData(array $data): array
    {
        return $this->sanitizeArrayRecursive($data);
    }

    /**
     * Configure PDF options with defaults
     *
     * @param array $options User options
     * @return array Configured options
     */
    protected function configurePdfOptions(array $options): array
    {
        return array_merge([
            'paper' => 'a4',
            'orientation' => 'portrait',
            'options' => [
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'debugKeepTemp' => false,
                'debugPng' => false,
                'debugLayout' => false,
                'debugLayoutLines' => false,
                'debugLayoutBlocks' => false,
                'debugLayoutInline' => false,
                'debugLayoutPaddingBox' => false,
            ]
        ], $options);
    }

    /**
     * Recursively sanitize array data
     *
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    protected function sanitizeArrayRecursive(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $sanitizedKey = is_string($key) ? strip_tags($key) : $key;

            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArrayRecursive($value);
            } elseif (is_string($value)) {
                // Allow basic HTML tags for content formatting
                $sanitized[$sanitizedKey] = strip_tags($value, '<p><br><strong><em><u><ul><ol><li><table><tr><td><th><tbody><thead><tfoot>');
            } else {
                $sanitized[$sanitizedKey] = $value;
            }
        }

        return $sanitized;
    }
}

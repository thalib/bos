<?php

namespace App\Providers;

use App\Exceptions\PdfGenerationException;
use App\Services\PdfGeneratorService;
use App\Services\PdfTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PdfServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PdfTemplateService as singleton
        $this->app->singleton(PdfTemplateService::class, function ($app) {
            return new PdfTemplateService;
        });

        // Register PdfGeneratorService as singleton
        $this->app->singleton(PdfGeneratorService::class, function ($app) {
            return new PdfGeneratorService($app->make(PdfTemplateService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure DomPDF defaults
        $this->configureDomPdf();

        // Auto-discover and register PDF templates
        $this->autoDiscoverTemplates();

        // Bind custom exception handler for PDF errors
        $this->app->bind(PdfGenerationException::class, function ($app, $parameters) {
            return new PdfGenerationException(...$parameters);
        });

        Log::info('PdfServiceProvider booted successfully');
    }

    /**
     * Configure DomPDF with application defaults
     */
    protected function configureDomPdf(): void
    {
        // Set default DomPDF options
        $defaultOptions = [
            'font_dir' => storage_path('app/fonts/'),
            'font_cache' => storage_path('app/fonts/'),
            'temp_dir' => storage_path('app/temp/'),
            'chroot' => realpath(base_path()),
            'enable_font_subsetting' => false,
            'pdf_backend' => 'CPDF',
            'default_media_type' => 'screen',
            'default_paper_size' => 'A4',
            'default_paper_orientation' => 'portrait',
            'default_font' => 'serif',
            'dpi' => 96,
            'enable_php' => false,
            'enable_javascript' => true,
            'enable_remote' => true,
            'font_height_ratio' => 1.1,
            'enable_html5_parser' => true,
        ];

        // Create necessary directories
        $this->ensureDirectories([
            storage_path('app/fonts/'),
            storage_path('app/temp/'),
            storage_path('app/pdfs/'),
        ]);

        Log::info('DomPDF configured with default options', ['options' => array_keys($defaultOptions)]);
    }

    /**
     * Auto-discover templates from views/pdf directory
     */
    protected function autoDiscoverTemplates(): void
    {
        try {
            $templateService = $this->app->make(PdfTemplateService::class);
            $pdfViewsPath = resource_path('views/pdf');

            if (! File::exists($pdfViewsPath)) {
                Log::info('PDF views directory does not exist, skipping auto-discovery', [
                    'path' => $pdfViewsPath,
                ]);

                return;
            }

            $templates = [];
            $files = File::files($pdfViewsPath);

            foreach ($files as $file) {
                $templateName = $file->getFilenameWithoutExtension();

                if ($templateName === 'base' || str_starts_with($templateName, '_')) {
                    continue; // Skip base templates and partials
                }

                $viewPath = "pdf.{$templateName}";
                $requiredFields = $this->extractRequiredFields($file->getPathname());

                $templateService->registerTemplate($templateName, $viewPath, $requiredFields);
                $templates[] = $templateName;
            }

            Log::info('Auto-discovered PDF templates', [
                'count' => count($templates),
                'templates' => $templates,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto-discover PDF templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Extract required fields from template file comments
     */
    protected function extractRequiredFields(string $filePath): array
    {
        try {
            $content = File::get($filePath);
            $requiredFields = [];

            // Look for template metadata comments
            if (preg_match('/{{--\s*Required Fields:\s*(.+?)\s*--}}/s', $content, $matches)) {
                $fieldsString = trim($matches[1]);
                $requiredFields = array_map('trim', explode(',', $fieldsString));
            }

            return $requiredFields;

        } catch (\Exception $e) {
            Log::warning('Failed to extract required fields from template', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Ensure required directories exist
     */
    protected function ensureDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                Log::info('Created directory', ['path' => $directory]);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PdfGeneratorService::class,
            PdfTemplateService::class,
        ];
    }
}

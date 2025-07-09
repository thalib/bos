<?php

namespace App\Console\Commands;

use App\Exceptions\PdfGenerationException;
use App\Services\PdfGeneratorService;
use App\Services\PdfTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeneratePdfCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pdf:make 
                           {template : The PDF template name}
                           {--data= : JSON data for the template (file path or JSON string), defaults to sample data for testing}
                           {--output= : Output file path (default: storage/app/pdfs)}
                           {--filename= : Custom filename (without extension), defaults to template_date format}
                           {--format=A4 : Paper format (A4 [default], Letter, Legal)}
                           {--orientation=portrait : Page orientation (portrait [default], landscape)}
                           {--preview : Generate preview instead of full PDF}';

    /**
     * The console command description.
     */
    protected $description = 'Generate PDF documents from templates';

    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private PdfTemplateService $templateService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $template = $this->argument('template');
            $dataOption = $this->option('data');
            $isPreview = $this->option('preview');

            // Validate template exists
            if (! $this->templateService->templateExists($template)) {
                $this->error("Template '{$template}' not found.");
                $this->line('Use pdf:list command to see available templates.');

                return self::FAILURE;
            }

            // Get template metadata
            $metadata = $this->templateService->getTemplateMetadata($template);
            $this->info("Generating PDF using template: {$template}");
            $this->line("Description: {$metadata['description']}");

            // Process data input
            $data = $this->processDataInput($dataOption);
            if ($data === null) {
                return self::FAILURE;
            }

            // Validate data against template requirements
            if (! $this->validateTemplateData($template, $data)) {
                return self::FAILURE;
            }

            // Prepare PDF options
            $options = [
                'format' => $this->option('format'),
                'orientation' => $this->option('orientation'),
            ];

            // Generate PDF
            if ($isPreview) {
                return $this->generatePreview($template, $data, $options);
            } else {
                return $this->generatePdf($template, $data, $options);
            }

        } catch (PdfGenerationException $e) {
            $this->error("PDF Generation Error: {$e->getMessage()}");

            if ($this->output->isVerbose()) {
                $this->line('Context: '.json_encode($e->getContext(), JSON_PRETTY_PRINT));
            }

            Log::error('PDF generation command failed', [
                'template' => $template ?? null,
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Process data input from option
     */
    private function processDataInput(?string $dataOption): ?array
    {
        if (! $dataOption) {
            // Use default sample data based on template
            $template = $this->argument('template');
            $defaultDataFile = storage_path("app/demo-{$template}.json");

            if (file_exists($defaultDataFile)) {
                $this->info("Using default demo data from: {$defaultDataFile}");
                $content = file_get_contents($defaultDataFile);
                if ($content === false) {
                    $this->error("Cannot read default data file: {$defaultDataFile}");

                    return null;
                }
                $dataOption = $content;
            } else {
                $this->error('Data is required. Use --data option with JSON string or file path.');
                $this->line("Default data file not found: {$defaultDataFile}");

                return null;
            }
        }

        // Check if it's a file path
        if (is_file($dataOption)) {
            $content = file_get_contents($dataOption);
            if ($content === false) {
                $this->error("Cannot read data file: {$dataOption}");

                return null;
            }
            $dataOption = $content;
        }

        // Parse JSON
        $data = json_decode($dataOption, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data: '.json_last_error_msg());

            return null;
        }

        return $data;
    }

    /**
     * Validate data against template requirements
     */
    private function validateTemplateData(string $template, array $data): bool
    {
        try {
            $this->templateService->validateTemplateData($template, $data);
            $this->info('✓ Template data validation passed');

            return true;
        } catch (PdfGenerationException $e) {
            $this->error('✗ Template data validation failed:');
            $this->line("  {$e->getMessage()}");

            $context = $e->getContext();
            if (isset($context['missing_fields'])) {
                $this->line('  Missing required fields: '.implode(', ', $context['missing_fields']));
            }

            return false;
        }
    }

    /**
     * Generate preview
     */
    private function generatePreview(string $template, array $data, array $options): int
    {
        $this->info('Generating preview...');

        $preview = $this->pdfGenerator->generatePreview($template, $data, $options);

        $outputPath = $this->getOutputPath($template, 'preview.html');
        $this->ensureOutputDirectory($outputPath);

        if (file_put_contents($outputPath, $preview) === false) {
            $this->error("Failed to save preview to: {$outputPath}");

            return self::FAILURE;
        }

        $this->info('✓ Preview generated successfully!');
        $this->line("Preview saved to: {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * Generate PDF
     */
    private function generatePdf(string $template, array $data, array $options): int
    {
        $this->info('Generating PDF...');

        $filename = $this->option('filename') ?? $template.'_'.date('Y-m-d_H-i-s');
        $outputPath = $this->getOutputPath($template, $filename.'.pdf');

        $this->ensureOutputDirectory($outputPath);

        $pdf = $this->pdfGenerator->generate($template, $data, $options);
        $success = $this->pdfGenerator->savePdf($pdf, $outputPath);

        if (! $success) {
            $this->error("Failed to save PDF to: {$outputPath}");

            return self::FAILURE;
        }

        $this->info('✓ PDF generated successfully!');
        $this->line("PDF saved to: {$outputPath}");

        // Show file size
        $fileSize = $this->formatFileSize(filesize($outputPath));
        $this->line("File size: {$fileSize}");

        return self::SUCCESS;
    }

    /**
     * Get output path
     */
    private function getOutputPath(string $template, string $filename): string
    {
        $outputDir = $this->option('output') ?? storage_path('app/pdfs');

        return rtrim($outputDir, '/').'/'.$filename;
    }

    /**
     * Ensure output directory exists
     */
    private function ensureOutputDirectory(string $filePath): void
    {
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }
}

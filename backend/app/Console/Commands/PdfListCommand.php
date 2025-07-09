<?php

namespace App\Console\Commands;

use App\Services\PdfTemplateService;
use Illuminate\Console\Command;

class PdfListCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pdf:list';

    /**
     * The console command description.
     */
    protected $description = 'List all available PDF templates';

    public function __construct(
        private PdfTemplateService $templateService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $templates = $this->templateService->getAvailableTemplates();

        if (empty($templates)) {
            $this->warn('No templates found.');

            return self::SUCCESS;
        }

        $this->info('Available PDF Templates:');
        $this->newLine();

        $tableData = [];
        foreach ($templates as $name => $metadata) {
            $tableData[] = [
                $name,
                $metadata['category'] ?? 'General',
                $metadata['description'] ?? 'No description',
                implode(', ', $metadata['required_fields'] ?? []),
            ];
        }

        $this->table(
            ['Template', 'Category', 'Description', 'Required Fields'],
            $tableData
        );

        // Show usage examples
        $this->newLine();
        $this->info('Usage examples:');
        $this->line('  php artisan pdf:make invoice --preview                        # Generate preview with default data');
        $this->line('  php artisan pdf:make estimate --data=storage/app/my-data.json # Generate PDF with custom data');
        $this->line('  php artisan pdf:make report --orientation=landscape           # Generate in landscape orientation');

        return self::SUCCESS;
    }
}

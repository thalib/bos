<?php

namespace App\Http\Controllers;

use App\Services\PdfGeneratorService;
use App\Services\PdfTemplateService;

class TestTemplateController extends Controller
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private PdfTemplateService $templateService
    ) {}

    public function show($template)
    {
        // Load demo data
        $dataFile = storage_path("app/demo-{$template}.json");

        if (! file_exists($dataFile)) {
            return response("Demo data file not found: {$dataFile}", 404);
        }

        $dataJson = file_get_contents($dataFile);
        $data = json_decode($dataJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response('Invalid JSON data: '.json_last_error_msg(), 400);
        }

        // Generate preview HTML
        try {
            $html = $this->pdfGenerator->generatePreview($template, $data);

            return response($html);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Template preview failed: '.$e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}

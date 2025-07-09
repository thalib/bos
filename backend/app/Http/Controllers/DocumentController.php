<?php

namespace App\Http\Controllers;

use App\Exceptions\PdfGenerationException;
use App\Http\Requests\GeneratePdfRequest;
use App\Http\Requests\PreviewDocumentRequest;
use App\Services\PdfGeneratorService;
use App\Services\PdfTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private PdfTemplateService $templateService
    ) {
        // Middleware is applied at the route level
    }

    /**
     * Generate PDF document
     *
     * @return Response|JsonResponse
     */
    public function generatePdf(GeneratePdfRequest $request)
    {
        try {
            $templateName = $request->input('template');
            $data = $request->input('data', []);
            $options = $request->input('options', []);
            $filename = $request->input('filename', $templateName.'_'.date('Y-m-d_H-i-s').'.pdf');

            Log::info('PDF generation request', [
                'user_id' => Auth::id(),
                'template' => $templateName,
                'filename' => $filename,
                'data_keys' => array_keys($data),
            ]);

            // Generate PDF
            $pdfContent = $this->pdfGenerator->generatePdf($templateName, $data, $options);

            // Return PDF as download
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (PdfGenerationException $e) {
            Log::error('PDF generation failed', [
                'user_id' => Auth::id(),
                'template' => $request->input('template'),
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return $this->errorResponse($e->getMessage(), $e->getCode(), $e->getContext());

        } catch (\Exception $e) {
            Log::error('Unexpected error in PDF generation', [
                'user_id' => Auth::id(),
                'template' => $request->input('template'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get available templates
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = $this->templateService->getAvailableTemplates();

            return $this->successResponse($templates, 'Templates retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve templates', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve templates', 500);
        }
    }

    /**
     * Generate document preview
     */
    public function previewDocument(PreviewDocumentRequest $request): JsonResponse
    {
        try {
            $templateName = $request->input('template');
            $data = $request->input('data', []);

            Log::info('Document preview request', [
                'user_id' => Auth::id(),
                'template' => $templateName,
                'data_keys' => array_keys($data),
            ]);

            // Generate HTML preview
            $preview = $this->templateService->getTemplatePreview($templateName, $data);

            return $this->successResponse([
                'template' => $templateName,
                'preview' => $preview,
                'generated_at' => now()->toISOString(),
            ], 'Preview generated successfully');

        } catch (PdfGenerationException $e) {
            Log::error('Document preview failed', [
                'user_id' => Auth::id(),
                'template' => $request->input('template'),
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return $this->errorResponse($e->getMessage(), $e->getCode(), $e->getContext());

        } catch (\Exception $e) {
            Log::error('Unexpected error in document preview', [
                'user_id' => Auth::id(),
                'template' => $request->input('template'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Get template information
     */
    public function getTemplateInfo(string $template): JsonResponse
    {
        try {
            if (! $this->templateService->templateExists($template)) {
                return $this->errorResponse("Template '{$template}' not found", 404);
            }

            $metadata = $this->templateService->getTemplateMetadata($template);
            $config = $this->templateService->getTemplateConfig($template);

            return $this->successResponse([
                'template' => $template,
                'metadata' => $metadata,
                'config' => $config,
                'exists' => true,
            ], 'Template information retrieved successfully');

        } catch (PdfGenerationException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), $e->getContext());

        } catch (\Exception $e) {
            Log::error('Failed to retrieve template info', [
                'user_id' => Auth::id(),
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve template information', 500);
        }
    }

    /**
     * Validate template data
     */
    public function validateTemplateData(GeneratePdfRequest $request): JsonResponse
    {
        try {
            $templateName = $request->input('template');
            $data = $request->input('data', []);

            $validationResult = $this->templateService->validateTemplateData($templateName, $data);

            return $this->successResponse([
                'template' => $templateName,
                'validation' => $validationResult,
                'valid' => $validationResult['valid'],
            ], 'Template data validation completed');

        } catch (PdfGenerationException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), $e->getContext());

        } catch (\Exception $e) {
            Log::error('Template data validation failed', [
                'user_id' => Auth::id(),
                'template' => $request->input('template'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validation failed', 500);
        }
    }

    /**
     * Return success response
     *
     * @param  mixed  $data
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, int $code = 400, array $context = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'timestamp' => now()->toISOString(),
        ];

        if (! empty($context)) {
            $response['context'] = $context;
        }

        return response()->json($response, $code);
    }
}

<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class PdfGenerationException extends Exception
{
    protected $templateName;

    protected $context;

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, ?string $templateName = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->templateName = $templateName;
        $this->context = $context;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the exception's context information.
     */
    public function context(): array
    {
        return array_merge([
            'template_name' => $this->templateName,
            'exception_message' => $this->getMessage(),
            'exception_code' => $this->getCode(),
        ], $this->context);
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        Log::error('PDF Generation Failed', $this->context());
    }
}

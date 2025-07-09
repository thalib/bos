<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiResource
{
    /**
     * Create a new ApiResource attribute instance.
     *
     * @param  string|null  $uri  The base URI segment for this resource (e.g., 'products').
     *                            If null, it will be derived automatically (plural, kebab-case of the model name).
     * @param  string  $apiPrefix  The root API prefix (e.g., 'api'). Default to 'api'.
     * @param  string  $version  The API version segment (e.g., 'v1'). Default to 'v1'.
     */
    public function __construct(
        public ?string $uri = null,
        public string $apiPrefix = 'api',
        public string $version = 'v1'
    ) {}
}

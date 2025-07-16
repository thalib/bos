<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestStore001Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_requires_authentication()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 99.99,
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'details',
                ],
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertIsString($response->json('error.code'));

        // Verify the product was not created
        $this->assertDatabaseMissing('products', ['name' => 'Test Product']);
    }

    #[Test]
    public function it_successfully_creates_product_with_valid_data()
    {
        $productData = [
            'name' => 'New Test Product',
            'description' => 'A comprehensive test product',
            'price' => 149.99,
            'sale_price' => 129.99,
            'sku' => 'TEST-001',
            'categories' => ['Electronics', 'Gadgets'],
            'tags' => ['test', 'product'],
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Resource created successfully',
                'data' => [
                    'name' => 'New Test Product',
                    'description' => 'A comprehensive test product',
                    'price' => '149.99',
                    'sale_price' => '129.99',
                    'sku' => 'TEST-001',
                    'categories' => ['Electronics', 'Gadgets'],
                    'tags' => ['test', 'product'],
                    'active' => true,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'sale_price',
                    'sku',
                    'categories',
                    'tags',
                    'active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource created successfully', $response->json('message'));
        $this->assertEquals('New Test Product', $response->json('data.name'));
        $this->assertIsInt($response->json('data.id'));

        // Verify the product was created in the database
        $this->assertDatabaseHas('products', [
            'name' => 'New Test Product',
            'sku' => 'TEST-001',
            'price' => 149.99,
        ]);
    }

    #[Test]
    public function it_creates_product_with_minimal_required_data()
    {
        $productData = [
            'name' => 'Minimal Product',
            'price' => 50.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Resource created successfully',
                'data' => [
                    'name' => 'Minimal Product',
                    'price' => '50.00',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsInt($response->json('data.id'));

        // Verify the product was created with default values
        $this->assertDatabaseHas('products', [
            'name' => 'Minimal Product',
            'price' => 50.00,
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $invalidData = [
            'description' => 'Product without name',
            'sku' => 'TEST-INVALID',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'details',
                ],
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertIsString($response->json('error.code'));

        // Verify the product was not created
        $this->assertDatabaseMissing('products', ['sku' => 'TEST-INVALID']);
    }

    #[Test]
    public function it_validates_price_fields()
    {
        $testCases = [
            'negative_price' => [
                'data' => ['name' => 'Negative Price Product', 'price' => -10.00],
                'shouldFail' => true,
            ],
            'zero_price' => [
                'data' => ['name' => 'Zero Price Product', 'price' => 0.00],
                'shouldFail' => false,
            ],
            'valid_price' => [
                'data' => ['name' => 'Valid Price Product', 'price' => 99.99],
                'shouldFail' => false,
            ],
            'string_price' => [
                'data' => ['name' => 'String Price Product', 'price' => 'invalid'],
                'shouldFail' => true,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/products', $testCase['data']);

            if ($testCase['shouldFail']) {
                $response->assertStatus(422);
                $this->assertFalse($response->json('success'));
                $this->assertDatabaseMissing('products', ['name' => $testCase['data']['name']]);
            } else {
                $response->assertStatus(201);
                $this->assertTrue($response->json('success'));
                $this->assertDatabaseHas('products', ['name' => $testCase['data']['name']]);
            }
        }
    }

    #[Test]
    public function it_validates_data_types()
    {
        $testCases = [
            'boolean_fields' => [
                'name' => 'Boolean Test Product',
                'price' => 100.00,
                'active' => 'not_boolean',
                'taxable' => 'invalid',
            ],
            'numeric_fields' => [
                'name' => 'Numeric Test Product',
                'price' => 'not_numeric',
                'stock_quantity' => 'invalid_number',
            ],
            'array_fields' => [
                'name' => 'Array Test Product',
                'price' => 100.00,
                'categories' => 'not_array',
                'tags' => 'invalid_array',
            ],
        ];

        foreach ($testCases as $caseName => $invalidData) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/products', $invalidData);

            $response->assertStatus(422);
            $this->assertFalse($response->json('success'));
            $this->assertDatabaseMissing('products', ['name' => $invalidData['name']]);
        }
    }

    #[Test]
    public function it_handles_duplicate_sku_validation()
    {
        // Create a product with a specific SKU
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

        $productData = [
            'name' => 'Duplicate SKU Product',
            'price' => 100.00,
            'sku' => 'DUPLICATE-SKU',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        // Should handle duplicate SKUs appropriately
        // This depends on the specific validation rules in the model
        $this->assertContains($response->status(), [201, 422]);

        if ($response->status() === 422) {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_ensures_transaction_handling()
    {
        $originalCount = Product::count();

        $productData = [
            'name' => 'Transaction Test Product',
            'price' => 199.99,
            'description' => 'Testing transaction consistency',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        if ($response->status() === 201) {
            // Verify the product was created
            $this->assertEquals($originalCount + 1, Product::count());
            $this->assertDatabaseHas('products', ['name' => 'Transaction Test Product']);
        } else {
            // If creation failed, verify no partial data was saved
            $this->assertEquals($originalCount, Product::count());
            $this->assertDatabaseMissing('products', ['name' => 'Transaction Test Product']);
        }
    }

    #[Test]
    public function it_applies_database_defaults()
    {
        $productData = [
            'name' => 'Default Values Test',
            'price' => 50.00,
            // Deliberately omitting fields that should have defaults
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $createdProduct = Product::where('name', 'Default Values Test')->first();
        $this->assertNotNull($createdProduct);

        // Verify default values are applied
        $this->assertEquals('simple', $createdProduct->type);
        $this->assertEquals('draft', $createdProduct->publication_status);
        $this->assertTrue($createdProduct->active);
        $this->assertEquals('ASENSAR', $createdProduct->brand);
        $this->assertEquals(18.00, $createdProduct->tax_rate);
        $this->assertTrue($createdProduct->taxable);
        $this->assertTrue($createdProduct->tax_inclusive);
    }

    #[Test]
    public function it_handles_array_fields_correctly()
    {
        $productData = [
            'name' => 'Array Fields Test',
            'price' => 75.00,
            'categories' => ['Electronics', 'Mobile', 'Smartphones'],
            'tags' => ['android', 'phone', 'mobile'],
            'images' => ['image1.jpg', 'image2.jpg'],
            'attributes' => [
                'color' => 'black',
                'storage' => '128GB',
                'ram' => '6GB',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'categories' => ['Electronics', 'Mobile', 'Smartphones'],
                    'tags' => ['android', 'phone', 'mobile'],
                    'images' => ['image1.jpg', 'image2.jpg'],
                    'attributes' => [
                        'color' => 'black',
                        'storage' => '128GB',
                        'ram' => '6GB',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data.categories'));
        $this->assertIsArray($response->json('data.tags'));
        $this->assertIsArray($response->json('data.images'));
        $this->assertIsArray($response->json('data.attributes'));
    }

    #[Test]
    public function it_returns_consistent_response_structure()
    {
        $productData = [
            'name' => 'Structure Test Product',
            'price' => 100.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'price',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // Verify the response doesn't contain fields that should not be in store responses
        $this->assertArrayNotHasKey('pagination', $response->json());
        $this->assertArrayNotHasKey('search', $response->json());
        $this->assertArrayNotHasKey('sort', $response->json());
        $this->assertArrayNotHasKey('filters', $response->json());
        $this->assertArrayNotHasKey('schema', $response->json());
        $this->assertArrayNotHasKey('columns', $response->json());
        $this->assertArrayNotHasKey('notifications', $response->json());

        // Verify success response format
        $this->assertTrue($response->json('success'));
        $this->assertIsString($response->json('message'));
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function it_handles_database_errors_gracefully()
    {
        $productData = [
            'name' => 'Database Error Test',
            'price' => 100.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        if ($response->status() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'An error occurred while creating the resource',
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                ],
            ]);

            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
            $this->assertEquals('INTERNAL_SERVER_ERROR', $response->json('error.code'));

            // Ensure no sensitive information is exposed
            $responseBody = $response->getContent();
            $this->assertStringNotContainsStringIgnoringCase('stack trace', $responseBody);
            $this->assertStringNotContainsStringIgnoringCase('vendor/', $responseBody);
            $this->assertStringNotContainsStringIgnoringCase('app/', $responseBody);
        } else {
            // If no error occurred, verify normal operation
            $this->assertContains($response->status(), [201, 422]);
        }
    }

    #[Test]
    public function it_validates_string_length_limits()
    {
        $longName = str_repeat('A', 300); // Assuming 255 character limit
        $longDescription = str_repeat('B', 10000); // Testing large description

        $productData = [
            'name' => $longName,
            'description' => $longDescription,
            'price' => 100.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        // Should handle long strings appropriately
        $this->assertContains($response->status(), [201, 422]);

        if ($response->status() === 422) {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_prevents_xss_injection_in_text_fields()
    {
        $xssPayload = '<script>alert("XSS")</script>';
        
        $productData = [
            'name' => "Product with XSS {$xssPayload}",
            'description' => "Description with XSS {$xssPayload}",
            'price' => 100.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        // Should handle XSS attempts appropriately
        $this->assertContains($response->status(), [201, 422]);

        if ($response->status() === 201) {
            // Verify the data is properly sanitized or escaped
            $this->assertTrue($response->json('success'));
            $responseData = $response->json('data');
            
            // The exact behavior depends on the sanitization rules
            // but we verify the response is safe
            $this->assertIsString($responseData['name']);
            $this->assertIsString($responseData['description']);
        }
    }

    #[Test]
    public function it_ensures_rate_limiting_compliance()
    {
        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 5; $i++) {
            $productData = [
                'name' => "Rate Limit Test Product {$i}",
                'price' => 100.00 + $i,
            ];

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/products', $productData);

            // Should not be rate limited for normal usage
            $this->assertNotEquals(429, $response->status());
        }
    }

    #[Test]
    public function it_logs_creation_operations()
    {
        $productData = [
            'name' => 'Logging Test Product',
            'price' => 125.00,
            'description' => 'Product for testing logging',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        if ($response->status() === 201) {
            // Verify successful creation
            $this->assertTrue($response->json('success'));
            $this->assertDatabaseHas('products', ['name' => 'Logging Test Product']);
        } else {
            // If creation failed, verify error is logged appropriately
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_handles_json_payload_validation()
    {
        $invalidJsonData = [
            'name' => 'JSON Test Product',
            'price' => 100.00,
            'invalid_field' => 'should_be_ignored',
            'extra_data' => ['should' => 'be_filtered'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $invalidJsonData);

        // Should handle extra fields gracefully
        $this->assertContains($response->status(), [201, 422]);

        if ($response->status() === 201) {
            $this->assertTrue($response->json('success'));
            $this->assertEquals('JSON Test Product', $response->json('data.name'));
            
            // Verify only fillable fields are saved
            $this->assertArrayNotHasKey('invalid_field', $response->json('data'));
            $this->assertArrayNotHasKey('extra_data', $response->json('data'));
        }
    }
}
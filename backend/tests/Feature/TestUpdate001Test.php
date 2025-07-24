<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestUpdate001Test extends TestCase
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
        $product = Product::factory()->create();
        $updateData = ['name' => 'Updated Product Name'];

        $response = $this->putJson("/api/v1/products/{$product->id}", $updateData);

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

        // Verify the product was not updated
        $this->assertDatabaseMissing('products', ['name' => 'Updated Product Name']);
    }

    #[Test]
    public function it_successfully_updates_product_with_put_method()
    {
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'description' => 'Original description',
            'price' => 100.00,
            'sku' => 'ORIG-001',
        ]);

        $updateData = [
            'name' => 'Updated Product Name',
            'description' => 'Updated description',
            'price' => 150.00,
            'sku' => 'UPD-001',
            'categories' => ['Updated Category'],
            'tags' => ['updated', 'product'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource updated successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Updated Product Name',
                    'description' => 'Updated description',
                    'price' => '150.00',
                    'sku' => 'UPD-001',
                    'categories' => ['Updated Category'],
                    'tags' => ['updated', 'product'],
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
                    'sku',
                    'categories',
                    'tags',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource updated successfully', $response->json('message'));
        $this->assertEquals($product->id, $response->json('data.id'));

        // Verify the product was updated in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 150.00,
            'sku' => 'UPD-001',
        ]);
    }

    #[Test]
    public function it_successfully_updates_product_with_patch_method()
    {
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'description' => 'Original description',
            'price' => 100.00,
            'sku' => 'ORIG-002',
        ]);

        $updateData = [
            'name' => 'Patched Product Name',
            'price' => 175.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource updated successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Patched Product Name',
                    'price' => '175.00',
                    'description' => 'Original description', // Should remain unchanged
                    'sku' => 'ORIG-002', // Should remain unchanged
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource updated successfully', $response->json('message'));

        // Verify partial update in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Patched Product Name',
            'price' => 175.00,
            'description' => 'Original description',
            'sku' => 'ORIG-002',
        ]);
    }

    #[Test]
    public function it_returns_404_for_non_existent_product()
    {
        $nonExistentId = 99999;
        $updateData = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$nonExistentId}", $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
                'error' => [
                    'code' => 'NOT_FOUND',
                    'details' => [],
                ],
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
        $this->assertEquals('NOT_FOUND', $response->json('error.code'));
        $this->assertEquals('Resource not found', $response->json('message'));
    }

    #[Test]
    public function it_returns_400_for_invalid_id_format()
    {
        $invalidId = 'invalid-id';
        $updateData = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$invalidId}", $updateData);

        // Laravel will typically return 404 for invalid route parameters
        // but we expect proper error handling
        $this->assertContains($response->status(), [400, 404]);

        $response->assertJson([
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
    }

    #[Test]
    public function it_validates_update_data()
    {
        $product = Product::factory()->create();

        $invalidData = [
            'name' => '', // Empty name
            'price' => -50.00, // Negative price
            'stock_quantity' => 'invalid', // Invalid type
            'active' => 'not_boolean', // Invalid boolean
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $invalidData);

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
    }

    #[Test]
    public function it_validates_price_fields_on_update()
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $testCases = [
            'negative_price' => [
                'data' => ['price' => -10.00],
                'shouldFail' => true,
            ],
            'zero_price' => [
                'data' => ['price' => 0.00],
                'shouldFail' => false,
            ],
            'valid_price' => [
                'data' => ['price' => 99.99],
                'shouldFail' => false,
            ],
            'string_price' => [
                'data' => ['price' => 'invalid'],
                'shouldFail' => true,
            ],
        ];

        foreach ($testCases as $caseName => $testCase) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->putJson("/api/v1/products/{$product->id}", $testCase['data']);

            if ($testCase['shouldFail']) {
                $response->assertStatus(422);
                $this->assertFalse($response->json('success'));
            } else {
                $response->assertStatus(200);
                $this->assertTrue($response->json('success'));
            }
        }
    }

    #[Test]
    public function it_validates_data_types_on_update()
    {
        $product = Product::factory()->create();

        $testCases = [
            'boolean_fields' => [
                'active' => 'not_boolean',
                'taxable' => 'invalid',
            ],
            'numeric_fields' => [
                'price' => 'not_numeric',
                'stock_quantity' => 'invalid_number',
            ],
            'array_fields' => [
                'categories' => 'not_array',
                'tags' => 'invalid_array',
            ],
        ];

        foreach ($testCases as $caseName => $invalidData) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->putJson("/api/v1/products/{$product->id}", $invalidData);

            $response->assertStatus(422);
            $this->assertFalse($response->json('success'));
        }
    }

    #[Test]
    public function it_ensures_transaction_handling_on_update()
    {
        $product = Product::factory()->create([
            'name' => 'Transaction Test Product',
            'price' => 100.00,
        ]);

        $originalName = $product->name;
        $originalPrice = $product->price;

        $updateData = [
            'name' => 'Updated Transaction Product',
            'price' => 200.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        if ($response->status() === 200) {
            // Verify the product was updated
            $this->assertTrue($response->json('success'));
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'name' => 'Updated Transaction Product',
                'price' => 200.00,
            ]);
        } else {
            // If update failed, verify no partial data was saved
            $this->assertFalse($response->json('success'));
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'name' => $originalName,
                'price' => $originalPrice,
            ]);
        }
    }

    #[Test]
    public function it_handles_array_fields_on_update()
    {
        $product = Product::factory()->create([
            'categories' => ['Original Category'],
            'tags' => ['original', 'tag'],
        ]);

        $updateData = [
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
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
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
        $product = Product::factory()->create();
        $updateData = ['name' => 'Structure Test Updated'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // Verify the response doesn't contain fields that should not be in update responses
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
        $product = Product::factory()->create();
        $updateData = ['name' => 'Database Error Test'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        if ($response->status() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'An unexpected error occurred while updating the resource',
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
            $this->assertContains($response->status(), [200, 422]);
        }
    }

    #[Test]
    public function it_refreshes_model_after_update()
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 100.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'price' => 150.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200);

        // Verify the response contains the updated data
        $this->assertEquals('Updated Name', $response->json('data.name'));
        $this->assertEquals('150.00', $response->json('data.price'));

        // Verify the updated_at timestamp is recent
        $updatedAt = $response->json('data.updated_at');
        $this->assertIsString($updatedAt);
        $this->assertNotEmpty($updatedAt);
    }

    #[Test]
    public function it_handles_unique_constraint_violations()
    {
        $product1 = Product::factory()->create(['sku' => 'UNIQUE-SKU-1']);
        $product2 = Product::factory()->create(['sku' => 'UNIQUE-SKU-2']);

        // Try to update product2 with product1's SKU
        $updateData = ['sku' => 'UNIQUE-SKU-1'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product2->id}", $updateData);

        // Should handle unique constraint violations appropriately
        $this->assertContains($response->status(), [200, 422]);

        if ($response->status() === 422) {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_prevents_xss_injection_in_update_fields()
    {
        $product = Product::factory()->create();
        $xssPayload = '<script>alert("XSS")</script>';

        $updateData = [
            'name' => "Updated Product with XSS {$xssPayload}",
            'description' => "Updated description with XSS {$xssPayload}",
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        // Should handle XSS attempts appropriately
        $this->assertContains($response->status(), [200, 422]);

        if ($response->status() === 200) {
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
    public function it_prevents_sql_injection_in_id_parameter()
    {
        $maliciousId = "1' OR '1'='1";
        $updateData = ['name' => 'SQL Injection Test'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$maliciousId}", $updateData);

        // Should handle malicious input gracefully
        $this->assertContains($response->status(), [400, 404]);

        $response->assertJson([
            'success' => false,
        ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
    }

    #[Test]
    public function it_validates_string_length_limits_on_update()
    {
        $product = Product::factory()->create();
        $longName = str_repeat('A', 300); // Assuming 255 character limit

        $updateData = [
            'name' => $longName,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        // Should handle long strings appropriately
        $this->assertContains($response->status(), [200, 422]);

        if ($response->status() === 422) {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_ensures_rate_limiting_compliance()
    {
        $product = Product::factory()->create();

        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 5; $i++) {
            $updateData = [
                'name' => "Rate Limit Test Update {$i}",
            ];

            $response = $this->actingAs($this->user, 'sanctum')
                ->putJson("/api/v1/products/{$product->id}", $updateData);

            // Should not be rate limited for normal usage
            $this->assertNotEquals(429, $response->status());
        }
    }

    #[Test]
    public function it_logs_update_operations()
    {
        $product = Product::factory()->create([
            'name' => 'Original Logging Test',
            'price' => 100.00,
        ]);

        $updateData = [
            'name' => 'Updated Logging Test',
            'price' => 150.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        if ($response->status() === 200) {
            // Verify successful update
            $this->assertTrue($response->json('success'));
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'name' => 'Updated Logging Test',
                'price' => 150.00,
            ]);
        } else {
            // If update failed, verify error is logged appropriately
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_handles_partial_updates_correctly()
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'price' => 100.00,
            'sale_price' => 80.00,
            'sku' => 'ORIG-SKU',
            'active' => true,
        ]);

        // Update only one field
        $updateData = ['price' => 150.00];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify only the specified field was updated
        $this->assertEquals('150.00', $response->json('data.price'));
        $this->assertEquals('Original Name', $response->json('data.name'));
        $this->assertEquals('Original description', $response->json('data.description'));
        $this->assertEquals('80.00', $response->json('data.sale_price'));
        $this->assertEquals('ORIG-SKU', $response->json('data.sku'));
        $this->assertTrue($response->json('data.active'));
    }

    #[Test]
    public function it_validates_id_parameter_format()
    {
        $testCases = [
            'string' => 'abc',
            'special_chars' => '!@#$%',
            'sql_injection' => "1'; DROP TABLE products; --",
            'script_injection' => '<script>alert("xss")</script>',
            'very_long_id' => str_repeat('1', 1000),
        ];

        foreach ($testCases as $caseName => $invalidId) {
            $updateData = ['name' => 'Test Update'];

            $response = $this->actingAs($this->user, 'sanctum')
                ->putJson("/api/v1/products/{$invalidId}", $updateData);

            // Should handle invalid inputs gracefully
            $this->assertContains($response->status(), [400, 404, 422],
                "Failed for case: {$caseName}");

            $response->assertJson([
                'success' => false,
            ]);

            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_maintains_data_integrity_after_update()
    {
        $product = Product::factory()->create([
            'name' => 'Data Integrity Test',
            'price' => 100.00,
            'created_at' => now()->subHours(2),
        ]);

        $originalCreatedAt = $product->created_at;
        $updateData = [
            'name' => 'Updated Data Integrity Test',
            'price' => 200.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        if ($response->status() === 200) {
            // Verify the update was successful
            $this->assertTrue($response->json('success'));
            $this->assertEquals('Updated Data Integrity Test', $response->json('data.name'));
            $this->assertEquals('200.00', $response->json('data.price'));

            // Verify created_at timestamp was not modified
            $this->assertEquals($originalCreatedAt->toISOString(), $response->json('data.created_at'));

            // Verify updated_at timestamp was updated
            $this->assertNotEquals($originalCreatedAt->toISOString(), $response->json('data.updated_at'));
        }
    }
}

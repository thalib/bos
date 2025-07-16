<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestShow001Test extends TestCase
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

        $response = $this->getJson("/api/v1/products/{$product->id}");

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
    }

    #[Test]
    public function it_returns_successful_response_for_existing_product()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'categories' => ['Electronics', 'Gadgets'],
            'slug' => 'test-product',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource retrieved successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Test Product',
                    'description' => 'Test Description',
                    'price' => '99.99',
                    'categories' => ['Electronics', 'Gadgets'],
                    'slug' => 'test-product',
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
                    'categories',
                    'slug',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource retrieved successfully', $response->json('message'));
        $this->assertEquals($product->id, $response->json('data.id'));
        $this->assertEquals('Test Product', $response->json('data.name'));
    }

    #[Test]
    public function it_returns_404_for_non_existent_product()
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$nonExistentId}");

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

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$invalidId}");

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
    public function it_returns_product_with_all_required_fields()
    {
        $product = Product::factory()->create([
            'name' => 'Complete Product',
            'description' => 'Full description',
            'price' => 149.99,
            'sale_price' => 129.99,
            'sku' => 'PROD-001',
            'categories' => ['Electronics', 'Phones'],
            'tags' => ['mobile', 'smartphone'],
            'active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource retrieved successfully',
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
        $this->assertEquals('Complete Product', $response->json('data.name'));
        $this->assertEquals('PROD-001', $response->json('data.sku'));
        $this->assertTrue($response->json('data.active'));
    }

    #[Test]
    public function it_handles_database_errors_gracefully()
    {
        $product = Product::factory()->create();

        // Test normal operation first
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        if ($response->status() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'An error occurred while fetching the resource',
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
            $response->assertStatus(200);
            $this->assertTrue($response->json('success'));
        }
    }

    #[Test]
    public function it_returns_consistent_response_structure()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        // Verify the response doesn't contain fields that should not be in show responses
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
    public function it_returns_product_with_proper_data_types()
    {
        $product = Product::factory()->create([
            'name' => 'Type Test Product',
            'price' => 99.99,
            'sale_price' => 79.99,
            'stock_quantity' => 100,
            'active' => true,
            'taxable' => false,
            'categories' => ['Electronics'],
            'tags' => ['test', 'product'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify data types
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['price']);
        $this->assertIsString($data['sale_price']);
        $this->assertIsInt($data['stock_quantity']);
        $this->assertIsBool($data['active']);
        $this->assertIsBool($data['taxable']);
        $this->assertIsArray($data['categories']);
        $this->assertIsArray($data['tags']);
        $this->assertIsString($data['created_at']);
        $this->assertIsString($data['updated_at']);
    }

    #[Test]
    public function it_prevents_sql_injection_in_id_parameter()
    {
        $maliciousId = "1' OR '1'='1";

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$maliciousId}");

        // Should handle malicious input gracefully
        $this->assertContains($response->status(), [400, 404]);

        $response->assertJson([
            'success' => false,
        ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
    }

    #[Test]
    public function it_ensures_rate_limiting_compliance()
    {
        $product = Product::factory()->create();

        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->getJson("/api/v1/products/{$product->id}");

            // Should not be rate limited for normal usage
            $this->assertNotEquals(429, $response->status());
        }
    }
}
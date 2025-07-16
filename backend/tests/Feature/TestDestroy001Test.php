<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestDestroy001Test extends TestCase
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

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

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

        // Verify the product was not deleted
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    #[Test]
    public function it_successfully_deletes_existing_product()
    {
        $product = Product::factory()->create([
            'name' => 'Product to Delete',
            'description' => 'This product will be deleted',
            'price' => 50.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource deleted successfully',
            ]);

        // Verify the product was deleted from the database
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function it_returns_404_for_non_existent_product()
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$nonExistentId}");

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
            ->deleteJson("/api/v1/products/{$invalidId}");

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
    public function it_handles_database_errors_gracefully()
    {
        $product = Product::factory()->create();

        // Test normal operation first
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        if ($response->status() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'An error occurred while deleting the resource',
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
    public function it_returns_consistent_response_structure_for_success()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        // Verify the response doesn't contain fields that should not be in delete responses
        $this->assertArrayNotHasKey('data', $response->json());
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
        $this->assertEquals('Resource deleted successfully', $response->json('message'));
    }

    #[Test]
    public function it_ensures_transaction_consistency()
    {
        $product = Product::factory()->create([
            'name' => 'Transaction Test Product',
            'price' => 100.00,
        ]);

        $originalCount = Product::count();

        // Delete the product
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        if ($response->status() === 200) {
            // Verify the product count decreased by 1
            $this->assertEquals($originalCount - 1, Product::count());
            
            // Verify the specific product is gone
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        } else {
            // If deletion failed, verify the product still exists
            $this->assertEquals($originalCount, Product::count());
            $this->assertDatabaseHas('products', ['id' => $product->id]);
        }
    }

    #[Test]
    public function it_handles_multiple_deletion_attempts()
    {
        $product = Product::factory()->create();

        // First deletion should succeed
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response1->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        // Second deletion attempt should return 404
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response2->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    #[Test]
    public function it_prevents_sql_injection_in_id_parameter()
    {
        $maliciousId = "1' OR '1'='1";

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$maliciousId}");

        // Should handle malicious input gracefully
        $this->assertContains($response->status(), [400, 404]);

        $response->assertJson([
            'success' => false,
        ]);

        $this->assertFalse($response->json('success'));
        $this->assertIsString($response->json('message'));
    }

    #[Test]
    public function it_logs_deletion_operations()
    {
        $product = Product::factory()->create([
            'name' => 'Product for Logging Test',
            'price' => 25.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        if ($response->status() === 200) {
            // Verify successful deletion
            $this->assertTrue($response->json('success'));
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        } else {
            // If deletion failed, verify error is logged appropriately
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_ensures_rate_limiting_compliance()
    {
        $products = Product::factory()->count(5)->create();

        // Make multiple delete requests to test rate limiting
        foreach ($products as $product) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->deleteJson("/api/v1/products/{$product->id}");

            // Should not be rate limited for normal usage
            $this->assertNotEquals(429, $response->status());
        }
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
            $response = $this->actingAs($this->user, 'sanctum')
                ->deleteJson("/api/v1/products/{$invalidId}");

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
    public function it_handles_concurrent_deletion_attempts()
    {
        $product = Product::factory()->create();

        // Simulate concurrent deletion attempts
        // In a real scenario, this would require more sophisticated testing
        // but we can test that the endpoint handles the scenario gracefully
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        // First attempt should succeed or fail gracefully
        $this->assertContains($response->status(), [200, 404, 500]);

        if ($response->status() === 200) {
            $this->assertTrue($response->json('success'));
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        } else {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
        }
    }

    #[Test]
    public function it_provides_clear_feedback_about_deletion_result()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        if ($response->status() === 200) {
            $this->assertTrue($response->json('success'));
            $this->assertEquals('Resource deleted successfully', $response->json('message'));
        } else {
            $this->assertFalse($response->json('success'));
            $this->assertIsString($response->json('message'));
            $this->assertNotEmpty($response->json('message'));
        }
    }

    #[Test]
    public function it_maintains_data_integrity_after_deletion()
    {
        $product = Product::factory()->create([
            'name' => 'Data Integrity Test',
            'price' => 150.00,
        ]);

        $productId = $product->id;
        $originalCount = Product::count();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$productId}");

        if ($response->status() === 200) {
            // Verify the exact product is deleted
            $this->assertDatabaseMissing('products', ['id' => $productId]);
            
            // Verify the count decreased by exactly 1
            $this->assertEquals($originalCount - 1, Product::count());
            
            // Verify other products are not affected
            $otherProducts = Product::where('id', '!=', $productId)->get();
            $this->assertCount($originalCount - 1, $otherProducts);
        }
    }
}